<?php

/**
 * This class currently only does actions related to RFG groups.
 * Any future or legacy actions for "delete_post" should go here aswell.
 *
 * This class is currently only loaded if m2m is active (by Types_M2M controller).
 *
 * @since m2m
 */
class Types_Post_Deletion {
	const RFG_CLEANUP_OPTION = 'types_rfg_cleanup_queue';
	const RFG_CLEANUP_LOCK = 'types_rfg_cleanup_lock';
	const RFG_CLEANUP_CRON_HOOK = 'types_rfg_cleanup_cron';

	/**
	 * State of process
	 *
	 * @var bool
	 */
	private static $is_running;

	/**
	 * Cache for post_type_has_rfg() results, keyed by post type slug.
	 *
	 * @var array<string, bool>
	 */
	private static $post_type_rfg_cache = array();

	/**
	 * Whether bulk RFG cleanup has already run in this request.
	 *
	 * @var bool
	 */
	private static $bulk_rfg_cleanup_done = false;

	/**
	 * Skip flag to prevent recursive cleanup when deleting queued RFG items.
	 *
	 * @var bool
	 */
	private static $skip_rfg_cleanup = false;


	/**
	 * @action before_delete_post
	 *
	 * @param int $postid
	 */
	public function before_delete_post( $postid ) {
		if ( self::$is_running || self::$skip_rfg_cleanup ) {
			// prevent self calls on our item deletion
			return;
		}

		self::$is_running = true;

		try {
			$post = get_post( $postid );
			if ( ! $post instanceof WP_Post ) {
				return;
			}

			$is_bulk = $this->is_bulk_trash_request( $post );

			if ( $is_bulk && $this->maybe_queue_bulk_rfg_cleanup( $post ) ) {
				return;
			}

			if ( ! $is_bulk && ! $this->post_type_has_rfg( $post->post_type ) ) {
				return;
			}

			$this->delete_rfg_items_for_post( $post );
		} finally {
			self::$is_running = false;
		}
	}


	/**
	 * Delete RFG items for a single post using legacy field-group traversal.
	 *
	 * @param WP_Post $post
	 */
	private function delete_rfg_items_for_post( WP_Post $post ) {
		$types_post_builder = new Types_Post_Builder();
		$types_post_builder->set_wp_post( $post );
		$types_post_builder->load_assigned_field_groups( 9999 );
		$types_post = $types_post_builder->get_types_post();

		if ( $field_groups = $types_post->get_field_groups() ) {
			$this->delete_field_groups_items( $field_groups );
		}
	}


	/**
	 * Queue RFG cleanup for bulk empty trash and skip expensive per-post processing.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool True when queued or nothing to do, false to continue sync deletion.
	 */
	private function maybe_queue_bulk_rfg_cleanup( WP_Post $post ) {
		if ( self::$bulk_rfg_cleanup_done ) {
			return true;
		}

		if ( ! $this->post_type_has_rfg( $post->post_type ) ) {
			self::$bulk_rfg_cleanup_done = true;
			return true;
		}

		$tables = $this->get_toolset_table_names();
		if ( ! $tables ) {
			// Fallback to synchronous deletion if Toolset tables are unavailable.
			return false;
		}

		$parent_ids = $this->get_bulk_trash_parent_ids( $post->post_type );
		if ( empty( $parent_ids ) ) {
			self::$bulk_rfg_cleanup_done = true;
			return true;
		}

		$parent_ids = $this->normalize_parent_ids_for_wpml( $parent_ids, $post->post_type );
		if ( empty( $parent_ids ) ) {
			self::$bulk_rfg_cleanup_done = true;
			return true;
		}

		$child_ids = $this->collect_rfg_child_ids_bulk( $parent_ids, $tables );
		if ( empty( $child_ids ) ) {
			self::$bulk_rfg_cleanup_done = true;
			return true;
		}

		$this->queue_rfg_cleanup_ids( $child_ids );
		$this->schedule_rfg_cleanup();

		self::$bulk_rfg_cleanup_done = true;
		return true;
	}


	/**
	 * Get trash post IDs for the given post type.
	 *
	 * @param string $post_type
	 *
	 * @return int[]
	 */
	private function get_bulk_trash_parent_ids( $post_type ) {
		$parent_ids = get_posts( array(
			'post_type' => $post_type,
			'post_status' => 'trash',
			'fields' => 'ids',
			'numberposts' => -1,
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		if ( empty( $parent_ids ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'intval', $parent_ids ) ) );
	}


	/**
	 * Normalize parent IDs to the default language when WPML is active.
	 *
	 * @param int[] $parent_ids
	 * @param string $post_type
	 *
	 * @return int[]
	 */
	private function normalize_parent_ids_for_wpml( array $parent_ids, $post_type ) {
		if ( ! isset( $GLOBALS['sitepress'] ) ) {
			return $parent_ids;
		}

		$sitepress = $GLOBALS['sitepress'];
		if ( ! is_object( $sitepress ) || ! method_exists( $sitepress, 'get_default_language' ) ) {
			return $parent_ids;
		}

		$is_translated = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
		if ( false === $is_translated ) {
			return $parent_ids;
		}

		$default_language = $sitepress->get_default_language();
		if ( ! is_string( $default_language ) || '' === $default_language ) {
			return $parent_ids;
		}

		$normalized = array();
		foreach ( $parent_ids as $parent_id ) {
			$default_id = apply_filters( 'wpml_object_id', $parent_id, $post_type, false, $default_language );
			if ( empty( $default_id ) ) {
				$default_id = $parent_id;
			}
			$normalized[] = (int) $default_id;
		}

		$normalized = array_values( array_filter( array_unique( $normalized ) ) );
		return $normalized;
	}


	/**
	 * Collect all RFG child IDs for a list of parents (including nested groups).
	 *
	 * @param int[] $parent_ids
	 * @param array<string, string> $tables
	 *
	 * @return int[]
	 */
	private function collect_rfg_child_ids_bulk( array $parent_ids, array $tables ) {
		$all_child_ids = array();
		$queue = array_values( array_unique( $parent_ids ) );
		$processed = array();

		while ( ! empty( $queue ) ) {
			$chunk = array_splice( $queue, 0, 200 );
			$chunk = array_values( array_diff( $chunk, array_keys( $processed ) ) );

			if ( empty( $chunk ) ) {
				continue;
			}

			foreach ( $chunk as $parent_id ) {
				$processed[ (int) $parent_id ] = true;
			}

			$child_ids = $this->query_rfg_child_ids_bulk( $chunk, $tables );
			if ( empty( $child_ids ) ) {
				continue;
			}

			foreach ( $child_ids as $child_id ) {
				$child_id = (int) $child_id;
				if ( $child_id <= 0 ) {
					continue;
				}

				$all_child_ids[ $child_id ] = true;
				if ( ! isset( $processed[ $child_id ] ) ) {
					$queue[] = $child_id;
				}
			}
		}

		return array_keys( $all_child_ids );
	}


	/**
	 * Query direct child RFG IDs for a batch of parent IDs using Toolset tables.
	 *
	 * @param int[] $parent_ids
	 * @param array<string, string> $tables
	 *
	 * @return int[]
	 */
	private function query_rfg_child_ids_bulk( array $parent_ids, array $tables ) {
		global $wpdb;

		$parent_ids = array_values( array_filter( array_map( 'intval', $parent_ids ) ) );
		if ( empty( $parent_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $parent_ids ), '%d' ) );

		$sql = "
			SELECT DISTINCT child.element_id
			FROM {$tables['associations']} a
			INNER JOIN {$tables['relationships']} r ON r.id = a.relationship_id
			INNER JOIN {$tables['connected']} parent ON parent.id = a.parent_id
			INNER JOIN {$tables['connected']} child ON child.id = a.child_id
			WHERE parent.element_id IN ({$placeholders})
				AND parent.domain = %s
				AND child.domain = %s
				AND r.origin = %s";

		$params = $parent_ids;
		$params[] = Toolset_Element_Domain::POSTS;
		$params[] = Toolset_Element_Domain::POSTS;
		$params[] = Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD;

		$sql = $wpdb->prepare( $sql, $params );
		$ids = $wpdb->get_col( $sql );

		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_map( 'intval', $ids );
	}


	/**
	 * Add child IDs to the cleanup queue and persist it.
	 *
	 * @param int[] $child_ids
	 */
	private function queue_rfg_cleanup_ids( array $child_ids ) {
		$child_ids = array_values( array_filter( array_map( 'intval', $child_ids ) ) );
		if ( empty( $child_ids ) ) {
			return;
		}

		$queue = get_option( self::RFG_CLEANUP_OPTION, array() );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}

		$queue = array_values( array_unique( array_merge( $queue, $child_ids ) ) );
		update_option( self::RFG_CLEANUP_OPTION, $queue, false );
	}


	/**
	 * Schedule cleanup cron if not already scheduled.
	 */
	private function schedule_rfg_cleanup() {
		if ( ! wp_next_scheduled( self::RFG_CLEANUP_CRON_HOOK ) ) {
			$delay = (int) apply_filters( 'types_rfg_cleanup_cron_delay', 10 );
			$delay = $delay < 1 ? 1 : $delay;
			wp_schedule_single_event( time() + $delay, self::RFG_CLEANUP_CRON_HOOK );
		}
	}


	/**
	 * Cron handler wrapper.
	 */
	public static function cron_rfg_cleanup_handler() {
		$instance = new self();
		$instance->process_rfg_cleanup_queue();
	}


	/**
	 * Process the queued RFG cleanup in batches.
	 */
	public function process_rfg_cleanup_queue() {
		if ( ! $this->acquire_rfg_cleanup_lock() ) {
			return;
		}

		try {
			$queue = get_option( self::RFG_CLEANUP_OPTION, array() );
			if ( ! is_array( $queue ) || empty( $queue ) ) {
				delete_option( self::RFG_CLEANUP_OPTION );
				return;
			}

			$batch_size = $this->get_rfg_cleanup_batch_size();
			$batch = array_splice( $queue, 0, $batch_size );

			if ( empty( $batch ) ) {
				delete_option( self::RFG_CLEANUP_OPTION );
				return;
			}

			self::$skip_rfg_cleanup = true;
			foreach ( $batch as $post_id ) {
				$post_id = (int) $post_id;
				if ( $post_id > 0 ) {
					wp_delete_post( $post_id, true );
				}
			}
			self::$skip_rfg_cleanup = false;

			if ( ! empty( $queue ) ) {
				update_option( self::RFG_CLEANUP_OPTION, array_values( $queue ), false );
				$this->schedule_rfg_cleanup();
			} else {
				delete_option( self::RFG_CLEANUP_OPTION );
			}
		} finally {
			self::$skip_rfg_cleanup = false;
			$this->release_rfg_cleanup_lock();
		}
	}


	/**
	 * Get cleanup batch size.
	 *
	 * @return int
	 */
	private function get_rfg_cleanup_batch_size() {
		$batch_size = (int) apply_filters( 'types_rfg_cleanup_batch_size', 200 );
		return $batch_size > 0 ? $batch_size : 200;
	}


	/**
	 * Acquire a lock to prevent concurrent cleanup runs.
	 *
	 * @return bool
	 */
	private function acquire_rfg_cleanup_lock() {
		if ( get_transient( self::RFG_CLEANUP_LOCK ) ) {
			return false;
		}

		set_transient( self::RFG_CLEANUP_LOCK, 1, 300 );
		return true;
	}


	/**
	 * Release the cleanup lock.
	 */
	private function release_rfg_cleanup_lock() {
		delete_transient( self::RFG_CLEANUP_LOCK );
	}


	/**
	 * Detect whether the current request is "Empty Trash" for this post type.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	private function is_bulk_trash_request( WP_Post $post ) {
		if ( ! is_admin() ) {
			return false;
		}

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$action2 = isset( $_REQUEST['action2'] ) ? $_REQUEST['action2'] : '';

		if ( empty( $_REQUEST['delete_all'] ) && 'delete_all' !== $action && 'delete_all' !== $action2 ) {
			return false;
		}

		if ( isset( $_REQUEST['post_status'] ) && 'trash' !== $_REQUEST['post_status'] ) {
			return false;
		}

		if ( isset( $_REQUEST['post_type'] ) && $post->post_type !== $_REQUEST['post_type'] ) {
			return false;
		}

		return true;
	}


	/**
	 * Resolve Toolset relationship table names, or false if unavailable.
	 *
	 * @return array<string, string>|false
	 */
	private function get_toolset_table_names() {
		/** @var wpdb $wpdb */
		global $wpdb;
		if ( ! isset( $wpdb->prefix ) ) {
			return false;
		}

		$tables = array(
			'associations' => $wpdb->prefix . 'toolset_associations',
			'connected' => $wpdb->prefix . 'toolset_connected_elements',
			'relationships' => $wpdb->prefix . 'toolset_relationships',
		);

		foreach ( $tables as $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( $exists !== $table ) {
				return false;
			}
		}

		return $tables;
	}

	/**
	 * Lightweight check for whether a post type has any RFGs assigned.
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function post_type_has_rfg( $post_type ) {
		if ( isset( self::$post_type_rfg_cache[ $post_type ] ) ) {
			return self::$post_type_rfg_cache[ $post_type ];
		}

		$group_ids = apply_filters( 'types_filter_get_field_group_ids_by_post_type', array(), $post_type );
		if ( ! is_array( $group_ids ) || empty( $group_ids ) ) {
			self::$post_type_rfg_cache[ $post_type ] = false;
			return false;
		}

		$group_ids = array_values( array_filter( array_map( 'intval', $group_ids ) ) );
		if ( empty( $group_ids ) ) {
			self::$post_type_rfg_cache[ $post_type ] = false;
			return false;
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $group_ids ), '%d' ) );
		$like = '%' . $wpdb->esc_like( '_repeatable_group_' ) . '%';

		$params = $group_ids;
		$params[] = '_wp_types_group_fields';
		$params[] = $like;

		$sql = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
				WHERE post_id IN ({$placeholders})
					AND meta_key = %s
					AND meta_value LIKE %s
				LIMIT 1",
			$params
		);

		$found = $wpdb->get_var( $sql );
		$has_rfg = ! empty( $found );

		self::$post_type_rfg_cache[ $post_type ] = $has_rfg;
		return $has_rfg;
	}


	/**
	 * @param Types_Field_Group_Post[] $field_groups
	 */
	private function delete_field_groups_items( array $field_groups ) {
		if ( empty( $field_groups ) ) {
			// no field groups to delete
			return;
		}

		foreach ( $field_groups as $field_group ) {
			if ( ! $field_group instanceof Types_Field_Group_Repeatable ) {
				// NO RFG
				// search for rfg and delete if exists
				if ( $rfgs = $field_group->get_repeatable_groups() ) {
					$this->delete_field_groups_items( $rfgs );
				}

				continue;
			}

			// repeatable field group
			if ( $items = $field_group->get_posts() ) {
				foreach ( $items as $item ) {
					/**@var $item Types_Field_Group_Repeatable_Item */
					if ( $nested_rfgs = $item->get_field_groups() ) {
						$this->delete_field_groups_items( $nested_rfgs );
					}

					// delete item (second parameter to true will bypass the trash and really delete the item)
					wp_delete_post( $item->get_wp_post()->ID, true );
				}
			}
		}
	}
}
