<?php
/**
 * Customizations to elasticpress-buddypress.
 *
 * @package Hc_Custom
 */

/**
 * Add HC taxonomies to elasticsearch index.
 *
 * @param array $taxonomies Taxonomies to be synced.
 */
function hcommons_filter_ep_sync_taxonomies( array $taxonomies ) {
	return array_merge(
		$taxonomies, [
			get_taxonomy( 'mla_academic_interests' ),
			get_taxonomy( 'humcore_deposit_subject' ),
			get_taxonomy( 'humcore_deposit_tag' ),
		]
	);
}
add_filter( 'ep_sync_taxonomies', 'hcommons_filter_ep_sync_taxonomies' );

/**
 * Add custom taxonomies to elasticsearch queries.
 *
 * @param WP_Query $query Search query.
 */
function hcommons_add_terms_to_search_query( WP_Query $query ) {
	if (
		is_search() &&
		! ( defined( 'WP_CLI' ) && WP_CLI ) &&
		! apply_filters( 'ep_skip_query_integration', false, $query )
	) {
		$query->set(
			'search_fields', array_unique(
				array_merge_recursive(
					(array) $query->get( 'search_fields' ),
					[
						'taxonomies' => [
							'mla_academic_interests',
							'humcore_deposit_subject',
							'humcore_deposit_tag',
						],
					]
				), SORT_REGULAR
			)
		);
	}
}
// After elasticpress ep_improve_default_search().
add_action( 'pre_get_posts', 'hcommons_add_terms_to_search_query', 20 );

/**
 * Overwrite search result post excerpt with the relevant matching text from the query so it's obvious what content matched.
 * Since ElasticSearch is fuzzy, there may not be exact matches - in which case just defer to elasticpress defaults.
 *
 * @param array  $results The unfiltered search results.
 * @param array  $response The response body retrieved from Elasticsearch.
 * @param array  $args See EP_API->query().
 * @param string $scope See EP_API->query().
 * @return array $results Filtered results.
 */
function hcommons_filter_ep_search_results_array( array $results, array $response, array $args, string $scope ) {
	$abbreviate_match = function( $str, $pos ) {
		$strlen = strlen( get_search_query() );
		$padding = 20 * $strlen; // Max characters to include on either side of the matched text.
		return substr( strip_tags( $str ), ( $pos - $padding > 0 ) ? $pos - $padding : 0, 2 * $padding );
	};

	$search_query = strtolower( get_search_query() );

	foreach ( $results['posts'] as &$post ) {
		$matched_text = [];

		foreach ( $post['terms'] as $tax ) {
			foreach ( $tax as $term ) {
				$strpos = strpos( strtolower( strip_tags( $term['name'] ) ), $search_query );
				if ( false !== $strpos ) {
					$matched_text[ $term['slug'] ] = $abbreviate_match( $term['name'], $strpos );
				}
			}
		}

		foreach ( [ 'post_excerpt', 'post_content' ] as $property ) {
			if ( ! empty( $matched_text[ $property ] ) ) {
				$strpos = strpos( strtolower( strip_tags( $property ) ), $search_query );
				if ( false !== $strpos ) {
					$matched_text[ $property ] = $abbreviate_match( $property, $strpos );
				}
			}
		}

		/**
		 * Ensure we're not duplicating content that's already in the excerpt.
		 * (excerpt can include terms depending on type e.g. member "about" xprofile field)
		 */
		foreach ( $matched_text as $i => $match ) {
			// Adjust comparison for different filtering.
			$clean_match = preg_replace( '/\s+/', ' ', strip_tags( $match ) );
			$clean_excerpt = preg_replace( '/\s+/', ' ', strip_tags( $post['post_excerpt'] ) );

			if ( false !== strpos( $clean_excerpt, $clean_match ) ) {
				unset( $matched_text[ $i ] );
			}
		}

		if ( count( $matched_text ) ) {
			$post['post_excerpt'] = implode(
				'', [
					'...',
					implode( '...<br>...', array_unique( $matched_text ) ),
					'...<br><br>',
					$post['post_excerpt'],
				]
			);
		}
	}

	return $results;
}
add_filter( 'ep_search_results_array', 'hcommons_filter_ep_search_results_array', 10, 4 );

/**
 * Filter out humcore child posts from indexing.
 *
 * @param bool  $kill Prevent this post from being indexed (or not).
 * @param array $post_args Return value of ep_prepare_post( $post_id ).
 * @param int   $post_id Post ID.
 */
function hcommons_filter_ep_post_sync_kill( bool $kill, array $post_args, int $post_id ) {
	if ( 'humcore_deposit' === $post_args['post_type'] && 0 !== $post_args['post_parent'] ) {
		$kill = true;
	}
	return $kill;
}
add_filter( 'ep_post_sync_kill', 'hcommons_filter_ep_post_sync_kill', 10, 3 );

// Do not index legacy xprofile group.
add_filter( 'ep_bp_index_xprofile_group_profile', '__return_false' );

// Hide some networks & post types from search facets.
add_filter( 'ep_bp_show_network_facet_6', '__return_false' ); // UP.
add_filter( 'ep_bp_show_post_type_facet_post', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_page', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_attachment', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_forum', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_doc', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_event', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_docs_folder', '__return_false' );

/**
 * If query contains quotes, no fuzziness.
 *
 * @param float $fuzziness Fuzziness argument for ElasticSearch.
 * @return float
 */
function hcommons_filter_ep_fuzziness_arg( $fuzziness ) {
	global $wp_query;
	if ( strpos( $wp_query->get( 's' ), '"' ) !== false ) {
		$fuzziness = 0;
	}
	return $fuzziness;
}
add_filter( 'ep_fuzziness_arg', 'hcommons_filter_ep_fuzziness_arg', 2 );

/**
 * Add custom post types to the fallback selections for search facets.
 *
 * @param array $post_types List of post types to index.
 * @return array
 */
function hcommons_filter_ep_bp_fallback_post_type_facet_selection( $post_types ) {
	return array_merge(
		$post_types, [
			'humcore_deposit',
		]
	);
}
add_filter( 'ep_bp_fallback_post_type_facet_selection', 'hcommons_filter_ep_bp_fallback_post_type_facet_selection' );
