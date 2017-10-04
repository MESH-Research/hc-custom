<?php
/**
 * Customizations to elasticpress-buddypress.
 *
 * @package Hc_Custom
 */

/**
 * Add HC taxonomies to elasticsearch index.
 */
function hcommons_filter_ep_sync_taxonomies( $taxonomies ) {
	return array_merge( $taxonomies, [
		get_taxonomy( 'mla_academic_interests' ),
		get_taxonomy( 'humcore_deposit_subject' ),
		get_taxonomy( 'humcore_deposit_tag' ),
	] );
}
add_filter( 'ep_sync_taxonomies', 'hcommons_filter_ep_sync_taxonomies' );

// add custom taxonomies to elasticsearch queries
function hcommons_add_terms_to_search_query( $query ) {
	if (
		is_search() &&
		! ( defined( 'WP_CLI' ) && WP_CLI ) &&
		! apply_filters( 'ep_skip_query_integration', false, $query )
	) {
		$query->set( 'search_fields', array_unique( array_merge_recursive(
			(array) $query->get( 'search_fields' ),
			[ 'taxonomies' => [
				'mla_academic_interests',
				'humcore_deposit_subject',
				'humcore_deposit_tag'
			] ]
		), SORT_REGULAR ) );
	}
}
add_action( 'pre_get_posts', 'hcommons_add_terms_to_search_query', 20 ); // after elasticpress ep_improve_default_search()

/**
 * Overwrite search result post excerpt with the relevant matching text from the query so it's obvious what content matched.
 * Since ElasticSearch is fuzzy, there may not be exact matches - in which case just defer to elasticpress defaults.
 */
function hcommons_filter_ep_search_results_array( $results, $response, $args, $scope ) {
	$abbreviate_match = function( $str, $pos ) {
		$strlen = strlen( get_search_query() );
		$padding = 20 * $strlen; // max characters to include on either side of the matched text
		return substr( strip_tags( $str ), ( $pos - $padding > 0 ) ? $pos - $padding : 0, 2 * $padding );
	};

	$search_query = strtolower( get_search_query() );

	foreach ( $results['posts'] as &$post ) {
		$matched_text = [];

		foreach ( $post['terms'] as $tax ) {
			foreach ( $tax as $term ) {
				$strpos = strpos( strtolower( strip_tags( $term['name'] ) ), $search_query );
				if ( $strpos !== false ) {
					$matched_text[ $term['slug'] ] = $abbreviate_match( $term['name'], $strpos );
				}
			}
		}

		foreach ( [ 'post_excerpt', 'post_content' ] as $property ) {
			if ( ! empty( $matched_text[ $property ] ) ) {
				$strpos = strpos( strtolower( strip_tags( $property ) ), $search_query );
				if ( $strpos !== false ) {
					$matched_text[ $property ] = $abbreviate_match( $property, $strpos );
				}
			}
		}

		// ensure we're not duplicating content that's already in the excerpt
		// (excerpt can include terms depending on type e.g. member "about" xprofile field)
		foreach ( $matched_text as $i => $match ) {
			// adjust comparison for different filtering
			$clean_match = preg_replace('/\s+/', ' ', strip_tags( $match ) );
			$clean_excerpt = preg_replace('/\s+/', ' ', strip_tags( $post['post_excerpt'] ) );

			if ( false !== strpos( $clean_excerpt, $clean_match ) ) {
				unset( $matched_text[ $i ] );
			}
		}

		if ( count( $matched_text ) ) {
			$post['post_excerpt'] = implode( '', [
				'...',
				implode( '...<br>...', array_unique( $matched_text ) ),
				'...<br><br>',
				$post['post_excerpt'],
			] );
		}
	}

	return $results;
}
add_filter( 'ep_search_results_array', 'hcommons_filter_ep_search_results_array', 10, 4 );

/**
 * filter out humcore child posts from indexing
 */
function hcommons_filter_ep_post_sync_kill( $kill, $post_args, $post_id ) {
	if ( $post_args['post_type'] === 'humcore_deposit' && $post_args['post_parent'] !== 0 ) {
		$kill = true;
	}
	return $kill;
}
add_filter( 'ep_post_sync_kill', 'hcommons_filter_ep_post_sync_kill', 10, 3 );

// do not index legacy xprofile group
add_filter( 'ep_bp_index_xprofile_group_profile', '__return_false' );

// hide some networks & post types from search facets
add_filter( 'ep_bp_show_network_facet_6', '__return_false' ); // UP
add_filter( 'ep_bp_show_post_type_facet_post', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_page', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_attachment', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_forum', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_doc', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_event', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_docs_folder', '__return_false' );

// if query contains quotes, no fuzziness
function hcommons_filter_ep_fuzziness_arg( $fuzziness ) {
	global $wp_query;
	if ( strpos( $wp_query->get( 's' ), '"' ) !== false ) {
		$fuzziness = 0;
	}
	return $fuzziness;
}
add_filter( 'ep_fuzziness_arg', 'hcommons_filter_ep_fuzziness_arg', 2 );

function hcommons_filter_ep_bp_fallback_post_type_facet_selection( $post_types ) {
	return array_merge( $post_types, [
		'humcore_deposit',
	] );
}
add_filter( 'ep_bp_fallback_post_type_facet_selection', 'hcommons_filter_ep_bp_fallback_post_type_facet_selection' );

