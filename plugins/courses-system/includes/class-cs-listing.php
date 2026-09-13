<?php
/**
 * Feature: Listing (عرض الكاتيجوري + محرك العرض)
 * وضعين:
 *   - Summary  (مفيش فلاتر): كارت واحد لكل ماستر، من غير مدينة/تاريخ.
 *   - Detailed (فيه فلتر):   كارت لكل ماستر×دولة (أقرب سيشن)، بالمدينة والتاريخ.
 * وبيرندر الكروت من مكان واحد عشان نفس الماركب في اللود الأول وفي الأجاكس.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Listing {

	/**
	 * @param array $args term_id(0=all) / search / mode / language / country / month
	 * @return array[]
	 */
	public static function get_cards( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'term_id'   => 0,
			'master_id' => 0,
			'search'    => '',
			'mode'      => '',
			'language'  => '',
			'country'   => '',
			'month'     => 0,
		) );

		// الوضع التفصيلي بيتفعّل مع أي فلتر (مش الكاتيجوري لوحدها).
		$detailed = ( $args['master_id'] || $args['search'] || $args['mode'] || $args['language'] || $args['country'] || $args['month'] );

		$today = current_time( 'Y-m-d' );

		$meta_query = array(
			'relation' => 'AND',
			array( 'key' => CS_META_MASTER, 'compare' => 'EXISTS' ),
			array( 'key' => CS_META_DATE, 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ),
		);
		if ( $args['country'] ) {
			// المكان بقى نص حر (مش كود دولة ثابت زي "EG")، فبنقارنه زي ما هو
			// من غير ما نفرض عليه حروف كبيرة -- المقارنة أصلاً case-insensitive
			// على مستوى الداتابيز.
			$meta_query[] = array( 'key' => CS_META_COUNTRY, 'value' => trim( $args['country'] ) );
		}
		if ( (int) $args['master_id'] > 0 ) {
			$meta_query[] = array( 'key' => CS_META_MASTER, 'value' => (int) $args['master_id'] );
		}

		$q = array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'meta_key'       => CS_META_DATE,
			'order'          => 'ASC',
			// مهم: بنوقف فلترة WPML التلقائية هنا لأن السيشن ممكن تكون
			// مرتبطة بكاتيجوري ترجمة/بلغة مختلفة عن سياق الكويري رغم أن
			// الماستر نفسه هو الترجمة الصحيحة للصفحة الحالية. فلترة اللغة
			// بتتم يدويًا أسفل الكويري على لغة الماستر، وده يمنع خلط
			// العربي والإنجليزي بدون ما WPML يسقط السيشن الصحيح قبل ما
			// نقدر نتحقق من الماستر.
			'suppress_filters' => true,
		);
		if ( (int) $args['term_id'] > 0 ) {
			$q['tax_query'] = array(
				array( 'taxonomy' => CS_TAX, 'field' => 'term_id', 'terms' => (int) $args['term_id'] ),
			);
		}
		if ( $args['search'] ) {
			$q['s'] = sanitize_text_field( $args['search'] );
		}

		// فيكس: 'suppress_filters' => true فوق كان المفروض يمنع فلترة WPML
		// عن الكويري دي، بس مبيكفيش لوحده -- بيوقف بس فلاتر الـ SQL (زي
		// posts_where/posts_join)، أما فلترة WPML التلقائية للغة فبتحصل
		// عن طريق hook اسمه pre_get_posts، وده بيتنفذ *قبل* ما suppress_filters
		// يبقى ليه أي تأثير خالص (نفس بالظبط مشكلة term_exists() جوه
		// wp_set_object_terms() في class-cs-wpml.php -- إجراء "قراءة مؤمّنة"
		// مش كفاية لوحده، لازم نوقف فلترة WPML تمامًا). لو سيشن اتعمل في
		// سياق مالوش لغة واضحة (كرون/دفعة خلفية) وأخد لغة الموقع الافتراضية
		// بدل لغة الماستر بتاعه الفعلية، WPML كان بيستبعده بصمت من هنا قبل
		// حتى ما فلترة اللغة اليدوية تحت (على لغة الماستر) تاخد فرصتها.
		// without_language_filter() هي الحل الرسمي (switch_lang('all',true))
		// اللي بيوقف كل فلترة WPML فعليًا، بما فيها pre_get_posts.
		$ids = ( class_exists( 'CS_WPML' ) && CS_WPML::active() )
			? CS_WPML::without_language_filter( function () use ( $q ) {
				return get_posts( $q );
			} )
			: get_posts( $q );
		$seen  = array();
		$cards = array();

		// فلترة اللغة اليدوية: لا نعتمد على فلتر WPML داخل WP_Query، لأن
		// السيشنز قد تكون مرتبطة بكاتيجوري ترجمة أو تحمل لغة WPML مختلفة
		// مؤقتًا عن سياق الكويري. نحدد اللغة من الماستر نفسه، وهو المصدر
		// الحقيقي لهوية الكورس، ثم نستبعد الماسترز من اللغة الأخرى.
		$current_lang = '';
		if ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
			$current_lang = apply_filters( 'wpml_current_language', null );
		}

		// وضع التجميع:
		//  - master محدّد أو فيه search => بلاش تجميع (اعرض كل السيشنز/الكوبيهات الأصلية).
		//  - فيه فلتر تاني                => كارت لكل ماستر×دولة (أقرب سيشن).
		//  - مفيش فلاتر                  => كارت لكل ماستر (Summary).
		$dedup = ( $args['master_id'] || $args['search'] ) ? 'none' : ( $detailed ? 'master_country' : 'master' );

		foreach ( $ids as $sid ) {
			$master_id = (int) get_post_meta( $sid, CS_META_MASTER, true );
			if ( ! $master_id ) {
				continue;
			}
			// The listing may intentionally bypass WPML SQL filtering, so resolve
			// the master from the CURRENT FRONTEND LANGUAGE explicitly. This is
			// stronger than trusting a stale _cs_master pointer on old sessions.
			if ( $current_lang && class_exists( 'CS_WPML' ) ) {
				$master_id = CS_WPML::localized_post_id( $master_id, $current_lang );
				$master_lang = CS_WPML::post_language( $master_id );
				if ( $master_lang && $master_lang !== $current_lang ) {
					continue;
				}
			}
			// The card must point to the SESSION of the current language, not merely
			// a WPML translation of whatever stale session ID the query returned.
			// This is what prevents an Arabic card from opening an English session.
			if ( $current_lang && class_exists( 'CS_WPML' ) ) {
				$localized_sid = CS_WPML::localized_session_id( $sid, $current_lang );
				if ( $localized_sid && get_post_type( $localized_sid ) === CS_CPT ) {
					$sid = $localized_sid;
				}
			}

			$country   = get_post_meta( $sid, CS_META_COUNTRY, true );
			$date      = get_post_meta( $sid, CS_META_DATE, true );

			if ( $args['month'] && (int) gmdate( 'n', strtotime( $date ) ) !== (int) $args['month'] ) {
				continue;
			}

			$mode = get_field( 'cs_delivery_mode', $master_id );
			$lang = get_field( 'cs_language', $master_id );
			// المود (زي اللغة) بقى ممكن يحمل أكتر من قيمة مفصولة بـ "/"
			// (Hybrid / Online / In-Person)، فالفلترة بقت "contains" مش تطابق تام.
			if ( $args['mode'] && ! self::has_multi_value( $mode, $args['mode'] ) ) {
				continue;
			}
			// اللغة بقت ممكن تبقى أكتر من لغة مفصولة بـ "/" (English / Arabic)،
			// فالفلترة بقت "هل اللغة المختارة موجودة جوه القائمة دي؟" مش تطابق تام.
			if ( $args['language'] && ! self::has_multi_value( $lang, $args['language'] ) ) {
				continue;
			}

			if ( 'none' !== $dedup ) {
				$key = ( 'master_country' === $dedup ) ? ( $master_id . '|' . $country ) : (string) $master_id;
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = true;
			}

			// اعرض المدينة/التاريخ في كل الأوضاع ماعدا Summary.
			$show_meta = ( 'master' !== $dedup );
			$cards[]   = self::build_card( $sid, $master_id, $country, $date, $show_meta );
		}

		return $cards;
	}

	/**
	 * Return the URL for the post in the current WPML language.
	 *
	 * IMPORTANT: the caller (build_card()) already passes a $post_id that was
	 * carefully resolved to the correct-language SESSION via
	 * CS_WPML::localized_session_id() -- that function verifies the candidate
	 * actually carries the session's own _cs_master meta before trusting it.
	 *
	 * This function used to run its OWN, second, unguarded WPML lookup
	 * (wpml_object_id + wpml_element_link) on top of that. Both master and
	 * session posts share the exact same CPT, so "get_post_type() === CS_CPT"
	 * cannot tell them apart. When a stale/incorrect WPML translation-group
	 * link exists for a session (a leftover from older buggy imports -- see
	 * the extensive history in class-cs-wpml.php), wpml_object_id() can
	 * return the MASTER post instead of the sibling session, and this
	 * function would silently accept it because a master IS a CS_CPT post
	 * too. The card then linked to the bare Master URL (no numeric
	 * duplicate-slug suffix, since the Master always owns the "clean" slug)
	 * and the single-course template rendered with blank price/location/date
	 * because a Master post carries none of that session postmeta.
	 *
	 * This was invisible in English because those sessions happened not to
	 * carry a stale translation-group link, but it reliably broke Arabic
	 * cards. The fix: never swap $post_id away from the already-resolved
	 * session unless the candidate is verifiably a session itself (i.e. it
	 * carries CS_META_MASTER). Otherwise, just use the ID we were given.
	 */
	protected static function localized_permalink( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return home_url( '/' );
		}
		if ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
			$lang = apply_filters( 'wpml_current_language', null );
			if ( $lang ) {
				$localized = (int) apply_filters( 'wpml_object_id', $post_id, CS_CPT, false, $lang );
				// Only trust $localized when it's a different post AND it is
				// genuinely a session (has its own _cs_master pointer). A
				// Master post never has CS_META_MASTER, so this rejects the
				// exact failure mode described above.
				if ( $localized && $localized !== $post_id && get_post_type( $localized ) === CS_CPT
					&& get_post_meta( $localized, CS_META_MASTER, true ) ) {
					$post_id = $localized;
				}
			}
		}
		return get_permalink( $post_id );
	}

	protected static function build_card( $session_id, $master_id, $country, $date, $show_meta ) {
		$end = get_post_meta( $session_id, '_cs_session_end', true );
		$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );
		$pc  = self::country_price( $master_id, $country, $loc_index ); // سعر + عملة مكان السيشن

		// في وضع الكارت العادي (Summary، لسه مفيش فلتر متحدد)، الكارت
		// مبيوريش دولة/تاريخ خالص ($show_meta = false)، فمينفعش اللينك
		// يوديك على سيشن معين (بدولة وتاريخ معينين) وكأنك اخترتهم -- ده
		// بيدّي انطباع غلط إن فيه دولة متحددة من غير ما المستخدم يختارها.
		// بدل كده بنوديه على صفحة الماستر نفسه مباشرة (بالظبط زي لينك
		// "View" اللي بيظهر جنب الكورس في لوحة التحكم)، فتفتح الصفحة من
		// غير دولة محددة. في وضع التفصيل (فيه فلتر) اللينك يفضل للسيشن
		// المعروض فعلاً، لإن الدولة/التاريخ ظاهرين على الكارت أصلاً.
		// Always open the Session page. The Session carries the selected
		// location/date and resolves its Master in the current WPML language.
		// Linking to the master here was the reason Arabic cards opened the
		// English single-course URL and then lost Arabic price/location data.
		$permalink = self::localized_permalink( $session_id );

		// في وضع الكارت العادي (Summary، لسه مفيش فلتر متحدد) الكارت نفسه
		// مبيوريش دولة/تاريخ ($show_meta = false)، فصفحة الكورس اللي هيفتحها
		// المستخدم لازم برضو ما تعرضش سعر/لوكيشن/تاريخ سيشن معين وكأنه هو
		// الوحيد المتاح -- المستخدم لسه ما اختارش سيشن بعينه. بنعلّم اللينك
		// بـ cs_general=1 عشان single-course.php يخفي السعر/اللوكيشن/التاريخ.
		// في وضع التفصيل (فيه فلتر) مفيش علامة، فالصفحة تعرض كل حاجة عادي.
		if ( ! $show_meta ) {
			$permalink = add_query_arg( 'cs_general', '1', $permalink );
		}

		return array(
			'permalink'  => $permalink,
			'title'      => get_the_title( $master_id ),
			'image'      => get_the_post_thumbnail_url( $master_id, 'medium' ),
			'mode'       => self::label_mode( get_field( 'cs_delivery_mode', $master_id ) ),
			'days'       => cs_duration_days( $master_id ),
			'language'   => self::label_lang( get_field( 'cs_language', $master_id ) ),
			'country'    => $show_meta ? CS_Countries::name( $country ) : '',
			'date_range' => $show_meta ? self::date_range( $date, $end ) : '',
			'price'      => $pc['price'] ? number_format( (float) $pc['price'] ) : '',
			'currency'   => $pc['currency'],
			'show_meta'  => (bool) $show_meta,
		);
	}

	/**
	 * سعر + عملة دولة معيّنة من repeater الأسعار (fallback للسعر القديم).
	 * @return array{price:mixed,currency:string}
	 */
	public static function country_price( $master_id, $country, $loc_index = '' ) {
		$rows = get_field( 'cs_country_prices', $master_id );
		if ( ! empty( $rows ) && is_array( $rows ) ) {
			// المطابقة بالـ loc_index هي الأوثق: أسماء الأماكن قد تختلف بين
			// العربي والإنجليزي (Cairo/القاهرة)، لكن ترتيب الصف في الشيتين واحد.
			if ( '' !== (string) $loc_index && isset( $rows[ (int) $loc_index ] ) && is_array( $rows[ (int) $loc_index ] ) ) {
				$row = $rows[ (int) $loc_index ];
				return array(
					'price'    => $row['price'] ?? '',
					'currency' => ! empty( $row['currency'] ) ? $row['currency'] : 'USD',
				);
			}

			foreach ( $rows as $row ) {
				if ( ! empty( $row['country'] ) && strtoupper( $row['country'] ) === strtoupper( (string) $country ) ) {
					return array(
						'price'    => $row['price'] ?? '',
						'currency' => ! empty( $row['currency'] ) ? $row['currency'] : 'USD',
					);
				}
			}
		}
		// fallback: السعر العام القديم لو موجود.
		return array(
			'price'    => get_field( 'cs_price', $master_id ),
			'currency' => get_field( 'cs_currency', $master_id ) ?: '$',
		);
	}

	/**
	 * كورسات (ماسترز) جوه كاتيجوري — لملء دروب داون "All Courses".
	 *
	 * ملحوظة مهمة: إحنا هنا بنستنتج الماسترز من الـ SESSIONS المرتبطة
	 * بالكاتيجوري دي (زي get_cards() بالظبط)، مش من تاج الكاتيجوري على
	 * الماستر نفسه مباشرة. السبب: مع WPML، الماستر المترجم (مثلاً النسخة
	 * العربية) ممكن ميبقاش متعلّم بنفس term_id بتاع نسخة اللغة دي من
	 * الكاتيجوري (مشكلة مزامنة تاج معروفة)، فلو اعتمدنا على تاج الماستر
	 * مباشرة كان الدروب داون بيرجع فاضي في اللغة التانية حتى لو الكروت
	 * نفسها ظاهرة صح (لأن الكروت بتعتمد على تاج السيشنز مش الماستر). أما
	 * السيشنز فبتتعلّم بالكاتيجوري الصح عن طريق مزامنة منفصلة (شوف
	 * expected_terms_for_post/same_language_terms)، فالاعتماد عليها هنا
	 * أوثق مصدر لهوية الكورس الحقيقية.
	 *
	 * @return array id => title
	 */
	public static function category_masters( $term_id ) {
		$q_args = array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => CS_META_MASTER, 'compare' => 'EXISTS' ) ),
			'tax_query'      => array(
				array( 'taxonomy' => CS_TAX, 'field' => 'term_id', 'terms' => (int) $term_id ),
			),
			'suppress_filters' => true,
		);
		// فيكس: نفس شرح without_language_filter() في get_cards() فوق --
		// pre_get_posts بيفلتر بغض النظر عن suppress_filters.
		$session_ids = ( class_exists( 'CS_WPML' ) && CS_WPML::active() )
			? CS_WPML::without_language_filter( function () use ( $q_args ) {
				return get_posts( $q_args );
			} )
			: get_posts( $q_args );

		$current_lang = '';
		if ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
			$current_lang = apply_filters( 'wpml_current_language', null );
		}

		$out = array();
		foreach ( $session_ids as $sid ) {
			$master_id = (int) get_post_meta( $sid, CS_META_MASTER, true );
			if ( ! $master_id ) {
				continue;
			}
			// اترجم الماستر للغة الصفحة الحالية بنفس المنطق المستخدم في
			// get_cards()، عشان السيشن الإنجليزي القديم (مثلًا) ميرجّعش
			// اسم الكورس الإنجليزي في دروب داون عربي.
			if ( $current_lang && class_exists( 'CS_WPML' ) ) {
				$master_id   = CS_WPML::localized_post_id( $master_id, $current_lang );
				$master_lang = CS_WPML::post_language( $master_id );
				if ( $master_lang && $master_lang !== $current_lang ) {
					continue;
				}
			}
			if ( ! isset( $out[ $master_id ] ) ) {
				$out[ $master_id ] = get_the_title( $master_id );
			}
		}

		asort( $out, SORT_STRING | SORT_FLAG_CASE );
		return $out;
	}

	/**
	 * رندر الكروت (نفس الماركب في اللود الأول والأجاكس).
	 * @return string HTML لكل الكروت.
	 */
	public static function cards_html( $cards, $theme_img ) {
		if ( empty( $cards ) ) {
			return '<p class="cs-empty">' . esc_html( cs__( 'No courses found.', 'listing_no_courses' ) ) . '</p>';
		}

		ob_start();
		foreach ( $cards as $card ) :
			// لو مفيش صورة مرفوعة للكورس (الماستر)، الكارت بيتعرض عادي من غيرها
			// (من غير صورة بديلة/بلايسهولدر) -- زي ما اتفقنا.
			$img = $card['image'];
		?>
    <div class="cs-card<?php echo $img ? '' : ' cs-card-no-img'; ?>">
      <?php if ( $img ) : ?>
      <div class="cs-card-img">
        <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>">
        <?php if ( $card['show_meta'] ) : ?>
        <div class="cs-mode-badge"><img src="<?php echo esc_url( $theme_img . 'cat_icon.png' ); ?>" alt=""><?php echo esc_html( $card['mode'] ); ?></div>
        <?php endif; ?>
      </div>
      <?php elseif ( $card['show_meta'] ) : ?>
      <div class="cs-mode-badge cs-mode-badge-noimg"><img src="<?php echo esc_url( $theme_img . 'cat_icon.png' ); ?>" alt=""><?php echo esc_html( $card['mode'] ); ?></div>
      <?php endif; ?>
      <div class="cs-card-body">
        <div class="cs-card-title"><?php echo esc_html( $card['title'] ); ?></div>
        <div class="cs-meta">
          <?php if ( $card['show_meta'] && $card['country'] ) : ?>
          <div class="cs-meta-item"><img src="<?php echo esc_url( $theme_img . 'PIN_ICON.png' ); ?>" alt=""><?php echo esc_html( $card['country'] ); ?></div>
          <?php endif; ?>
          <?php if ( $card['show_meta'] ) : ?>
          <div class="cs-meta-item"><img src="<?php echo esc_url( $theme_img . 'CLOCK_ICON.png' ); ?>" alt=""><?php echo esc_html( $card['days'] ); ?> <?php cs_e( 'days', 'unit_days' ); ?></div>
          <?php endif; ?>
          <?php if ( $card['show_meta'] ) : ?>
          <div class="cs-meta-item"><img src="<?php echo esc_url( $theme_img . 'GLOBE_ICON.png' ); ?>" alt=""><?php echo esc_html( $card['language'] ); ?></div>
          <?php endif; ?>
          <?php if ( $card['show_meta'] && $card['date_range'] ) : ?>
          <div class="cs-meta-item"><img src="<?php echo esc_url( $theme_img . 'CAL_ICON.png' ); ?>" alt=""><?php echo esc_html( $card['date_range'] ); ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="cs-card-footer">
        <?php if ( $card['show_meta'] ) : ?>
        <div class="cs-price"><?php echo esc_html( $card['currency'] ); ?> <strong><?php echo esc_html( $card['price'] ); ?></strong></div>
        <?php else : ?>
        <div class="cs-price cs-price-empty"></div>
        <?php endif; ?>
        <a href="<?php echo esc_url( $card['permalink'] ); ?>" class="cs-learn-btn"><?php
					$current = ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) ? apply_filters( 'wpml_current_language', null ) : '';
					if ( 'ar' === $current ) {
						echo esc_html( 'عرض التفاصيل' );
					} else {
						echo esc_html( 'View Details' );
					}
				?></a>
      </div>
    </div>
		<?php
		endforeach;
		return ob_get_clean();
	}

	/* ============ Labels / format ============ */

	/**
	 * "Hybrid / Online / In-Person" -> "Hybrid - Online - In-Person"
	 * (نفس معاملة اللغة بالظبط -- المود بقى ممكن يحمل أكتر من قيمة مفصولة
	 * بـ "/" جوه الحقل، وبيتعرض بـ " - " بين كل قيمة والتانية.)
	 */
	public static function label_mode( $v ) {
		return self::format_multi_values( $v );
	}

	/**
	 * حقل اللغة بقى نص حر ممكن يحمل أكتر من لغة مفصولة بـ "/" (زي
	 * "English / Arabic / French" جوه الحقل نفسه). للعرض، بنحوّل الفاصل
	 * من "/" لـ " - " بين كل لغة والتانية (زي ما طُلب)، وبنسيب كل لغة
	 * زي ما اتكتبت بالظبط -- مفيش ترجمة أو قايمة لغات محددة.
	 */
	public static function label_lang( $v ) {
		return self::format_multi_values( $v );
	}

	/**
	 * "English / Arabic / French" -> "English - Arabic - French"
	 * (نفس الدالة مستخدمة للغة والمود، الاتنين نفس شكل التخزين بالظبط.)
	 */
	public static function format_multi_values( $raw ) {
		if ( ! $raw ) {
			return '';
		}
		$parts = array_map( 'trim', explode( '/', (string) $raw ) );
		$parts = array_filter( $parts, function ( $p ) {
			return '' !== $p;
		} );
		return implode( ' - ', $parts );
	}

	/**
	 * نفس format_multi_values() -- سايبينها لأي كود قديم بينادي عليها بالاسم
	 * القديم (خاص باللغة بس).
	 */
	public static function format_languages( $raw ) {
		return self::format_multi_values( $raw );
	}

	/**
	 * هل القيمة $needle موجودة جوه قائمة القيم المخزّنة في $raw
	 * ("English / Arabic / French" أو "Hybrid / Online / In-Person")؟
	 * مقارنة case-insensitive. مستخدمة لفلتر اللغة والمود.
	 */
	public static function has_multi_value( $raw, $needle ) {
		if ( ! $raw || '' === trim( (string) $needle ) ) {
			return false;
		}
		$needle = mb_strtolower( trim( (string) $needle ) );
		foreach ( explode( '/', (string) $raw ) as $part ) {
			if ( mb_strtolower( trim( $part ) ) === $needle ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * نفس has_multi_value() -- سايبينها لأي كود قديم بينادي عليها بالاسم
	 * القديم (خاص باللغة بس).
	 */
	public static function has_language( $raw, $needle ) {
		return self::has_multi_value( $raw, $needle );
	}

	/**
	 * كل اللغات المستخدمة فعليًا (لملء فلتر الـ Language) -- بتتلمّ من
	 * حقل cs_language لكل الكورسات (الماسترز)، وبتتقسّم على "/" عشان
	 * كل لغة تظهر كخيار لوحدها في الفلتر، حتى لو الكورس مكتوب فيه أكتر
	 * من لغة سوا.
	 *
	 * @return array key(lowercase, بيتستخدم كـ value في الـ select) => label للعرض
	 */
	public static function used_languages( $term_id = 0 ) {
		return self::used_multi_values( 'cs_language', $term_id );
	}

	/**
	 * زي used_languages() بالظبط بس لحقل المود (cs_delivery_mode) -- بتلمّ
	 * كل الأوضاع المستخدمة فعليًا (Hybrid/Online/In-Person أو أي حاجة
	 * تانية اتكتبت) لملء فلتر "All Modes" أوتوماتيك بدل قايمة ثابتة.
	 *
	 * @return array key(lowercase) => label للعرض
	 */
	public static function used_modes( $term_id = 0 ) {
		return self::used_multi_values( 'cs_delivery_mode', $term_id );
	}

	/**
	 * جامعة عامة: كل القيم المستخدمة فعليًا في حقل نص حر متعدد القيم
	 * (مفصول بـ "/") عبر كل الكورسات (الماسترز)، مقسّمة لقيم مفردة.
	 *
	 * @param string $field_name اسم حقل ACF (cs_language أو cs_delivery_mode).
	 * @param int    $term_id    فلترة على كاتيجوري معيّن (0 = كل الكاتيجوريز).
	 * @return array key(lowercase) => label للعرض (أول شكل اتكتب بيه القيمة).
	 */
	protected static function used_multi_values( $field_name, $term_id = 0 ) {
		$q = array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => CS_META_IS_MASTER, 'value' => 1 ) ),
			// شوف نفس الملحوظة في get_cards() فوق.
			'suppress_filters' => false,
		);
		if ( $term_id ) {
			$q['tax_query'] = array( array( 'taxonomy' => CS_TAX, 'field' => 'term_id', 'terms' => (int) $term_id ) );
		}
		$ids = get_posts( $q );
		$out = array();
		foreach ( $ids as $id ) {
			$raw = get_field( $field_name, $id );
			if ( ! $raw ) {
				continue;
			}
			foreach ( explode( '/', $raw ) as $val ) {
				$val = trim( $val );
				if ( '' === $val ) {
					continue;
				}
				$key = mb_strtolower( $val );
				if ( ! isset( $out[ $key ] ) ) {
					$out[ $key ] = $val;
				}
			}
		}
		asort( $out );
		return $out;
	}

	/**
	 * نطاق التاريخ (Oct 5 - Oct 9, 2026) — عربي/إنجليزي مباشرة حسب لغة الصفحة.
	 */
	public static function date_range( $start, $end ) {
		if ( ! $start ) {
			return '';
		}
		$s = strtotime( $start );
		$e = $end ? strtotime( $end ) : $s;

		if ( strpos( get_locale(), 'ar' ) === 0 ) {
			return self::date_range_ar( $s, $e );
		}

		if ( gmdate( 'Y', $s ) === gmdate( 'Y', $e ) ) {
			return gmdate( 'M j', $s ) . ' - ' . gmdate( 'M j, Y', $e );
		}
		return gmdate( 'M j, Y', $s ) . ' - ' . gmdate( 'M j, Y', $e );
	}

	/** أسماء الشهور العربية (بدون اختصار، عشان العربي مالوش صيغة مختصرة زي الإنجليزي). */
	protected static function ar_months() {
		return array(
			1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',   4 => 'أبريل',
			5 => 'مايو',   6 => 'يونيو',  7 => 'يوليو',   8 => 'أغسطس',
			9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
		);
	}

	/** نسخة عربية من تنسيق التاريخ: يوم + شهر (بدل شهر + يوم زي الإنجليزي). */
	protected static function date_range_ar( $s, $e ) {
		$months = self::ar_months();

		$s_day   = gmdate( 'j', $s );
		$s_month = $months[ (int) gmdate( 'n', $s ) ];
		$s_year  = gmdate( 'Y', $s );

		$e_day   = gmdate( 'j', $e );
		$e_month = $months[ (int) gmdate( 'n', $e ) ];
		$e_year  = gmdate( 'Y', $e );

		if ( $s_year === $e_year ) {
			return $s_day . ' ' . $s_month . ' - ' . $e_day . ' ' . $e_month . ' ' . $e_year;
		}
		return $s_day . ' ' . $s_month . ' ' . $s_year . ' - ' . $e_day . ' ' . $e_month . ' ' . $e_year;
	}

	/** الدول المستخدمة فعليًا (لملء فلتر الـ Location). */
	public static function used_countries( $term_id = 0 ) {
		$q = array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => CS_META_COUNTRY, 'compare' => 'EXISTS' ) ),
			// شوف نفس الملحوظة في get_cards() فوق.
			'suppress_filters' => false,
		);
		if ( $term_id ) {
			$q['tax_query'] = array( array( 'taxonomy' => CS_TAX, 'field' => 'term_id', 'terms' => (int) $term_id ) );
		}
		$ids   = get_posts( $q );
		$codes = array();
		foreach ( $ids as $id ) {
			$c = get_post_meta( $id, CS_META_COUNTRY, true );
			if ( $c ) {
				$codes[ $c ] = CS_Countries::name( $c );
			}
		}
		asort( $codes );
		return $codes;
	}


	/**
	 * الدول المتاح فيها الكورس (الماستر) ده فعليًا (من كل السيشنز التابعة له).
	 * @return array code => name
	 */
	public static function master_countries( $master_id ) {
		$ids = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => (int) $master_id ),
				array( 'key' => CS_META_COUNTRY, 'compare' => 'EXISTS' ),
			),
			// شوف نفس الملحوظة في get_cards() فوق (هنا أساسًا مقيدة بـ
			// master_id محدد، فمش هتفرق كتير، لكن سيبناها لنفس الاتساق).
			'suppress_filters' => false,
		) );
		$codes = array();
		foreach ( $ids as $id ) {
			$c = get_post_meta( $id, CS_META_COUNTRY, true );
			if ( $c ) {
				$codes[ $c ] = CS_Countries::name( $c );
			}
		}
		asort( $codes );
		return $codes;
	}

	public static function search_cards( $search ) {
		return self::get_cards( array(
			'search' => $search,
		) );
	}

	/**
	 * تشخيص مؤقت: بيوري ليه كاتيجوري معيّنة بترجع "0 كورسات" في صفحة
	 * العرض، بمراحل منفصلة (كل مرحلة بتلغي فلتر واحد) عشان نعرف المرحلة
	 * اللي بتصفّر النتيجة بالظبط:
	 *  1) الماسترز المتحطة على الـ term ده (تاكسونومي بس، من غير أي فلتر تاني).
	 *  2) لكل ماستر: لغته في WPML، وعدد السيشنز المتحطة عليه، وهل فيه أي
	 *     سيشن بتاريخ فاضل (>= النهارده) ولا كلها فاتت.
	 * استخدامها: ?cs_debug=1 على صفحة الكاتيجوري (أدمن بس -- شوف الشرط في
	 * القالب). تتشال بعد ما تتحل المشكلة.
	 *
	 * @return array
	 */
	public static function debug_term_diagnostics( $term_id ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		// كل الماسترز المتحطة على الـ term ده، من غير أي فلتر لغة أو تاريخ
		// أو حتى post_status -- suppress_filters=true عشان فلترة WPML
		// ماتخفيش حاجة من التشخيص نفسه.
		$master_ids = get_posts( array(
			'post_type'         => CS_CPT,
			'post_status'       => 'any',
			'posts_per_page'    => -1,
			'fields'            => 'ids',
			'meta_query'        => array( array( 'key' => CS_META_IS_MASTER, 'value' => 1 ) ),
			'tax_query'         => array(
				array( 'taxonomy' => CS_TAX, 'field' => 'term_id', 'terms' => (int) $term_id ),
			),
			'suppress_filters'  => true,
		) );

		$current_lang = ( class_exists( 'CS_WPML' ) && CS_WPML::active() )
			? apply_filters( 'wpml_current_language', null )
			: null;

		$term    = get_term( (int) $term_id, CS_TAX );
		$masters = array();

		foreach ( $master_ids as $mid ) {
			$sessions = get_posts( array(
				'post_type'        => CS_CPT,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => CS_META_MASTER,
				'meta_value'       => $mid,
				'suppress_filters' => true,
			) );

			$future_sessions = 0;
			$session_dates    = array();
			foreach ( $sessions as $sid ) {
				$d = get_post_meta( $sid, CS_META_DATE, true );
				$session_dates[] = $d;
				if ( $d && $d >= $today ) {
					$future_sessions++;
				}
			}

			$has_term_directly = false;
			$session_has_term   = false;
			foreach ( $sessions as $sid ) {
				$t = wp_get_object_terms( $sid, CS_TAX, array( 'fields' => 'ids', 'suppress_filters' => true ) );
				if ( $t && ! is_wp_error( $t ) && in_array( (int) $term_id, array_map( 'intval', $t ), true ) ) {
					$session_has_term = true;
					break;
				}
			}

			$masters[] = array(
				'master_id'          => $mid,
				'title'               => get_the_title( $mid ),
				'post_status'         => get_post_status( $mid ),
				'master_lang'         => ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) ? CS_WPML::post_language( $mid ) : 'n/a',
				'sessions_total'      => count( $sessions ),
				'sessions_future'     => $future_sessions,
				'session_dates'       => $session_dates,
				'a_session_has_term'  => $session_has_term,
			);
		}

		return array(
			'term_id'            => (int) $term_id,
			'term_name'          => $term && ! is_wp_error( $term ) ? $term->name : '(term مش موجودة)',
			'today'              => $today,
			'current_wpml_lang'  => $current_lang,
			'masters_on_term'    => $masters,
		);
	}
}