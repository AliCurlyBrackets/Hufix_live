<?php

namespace GFML\TranslationEditor;

trait TopLevelGroupTrait {

	/**
	 * @param array $groups
	 *
	 * @return array
	 */
	public function addTopLevelGroup( $groups ) {
		return array_merge(
			[ GroupsAndLabels::TOP_LEVEL_GROUP_SLUG => GroupsAndLabels::TOP_LEVEL_GROUP ],
			$groups
		);
	}

}
