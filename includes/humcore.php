<?php
/**
 * Customizations to Humanities Core
 *
 * @package Hc_Custom
 */

/**
 * Array of member groups that can author deposits in Core.
 *
 * @return array Group ids.
 */
function hcommons_core_member_groups_with_authorship() {

	$committee_group_ids = array();
	//$society_id      = get_network_option( '', 'society_id' );

	$args = array(
		'type'       => 'alphabetical',
		//'group_type' => $society_id,
		'meta_query' => array(
			array(
				'key'     => 'mla_oid',
				'value'   => 'M',
				'compare' => 'LIKE',
			),
		),
		'per_page'   => '500',
	);

	/* Special case for now - remove committees.
	$m_groups = groups_get_groups( $args );

	foreach ( $m_groups['groups'] as $group ) {
		$committee_group_ids[] = $group->id;
	}
	*/

	return array_merge( $committee_group_ids, array( 296, 378, 444 ) );

}
add_filter( 'humcore_member_groups_with_authorship', 'hcommons_core_member_groups_with_authorship' );

/**
 * Remove groups that are marked as committees from Core group list.
 *
 * @param array Groups.
 * @return array Groups with committees removed.
 */
function hcommons_filter_humcore_groups_list( $groups ) {

	$filtered_groups = array();
	foreach ( $groups as $group_id => $group_name ) {
		if ( ! mla_is_group_committee( $group_id ) ) {
			$filtered_groups[ $group_id ] = $group_name;
		}
	}

	return $filtered_groups;

}
add_filter( 'humcore_deposits_group_list', 'hcommons_filter_humcore_groups_list' );

