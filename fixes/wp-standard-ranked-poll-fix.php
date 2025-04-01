<?php
/**
 * Ranked Poll Display Fix
 *
 * @package Decision_Polls
 */

/**
 * Class to apply the ranked poll fixes
 */
class Decision_Polls_Ranked_Fix {
	/**
	 * Initialize the fixes
	 */
	public static function init() {
		add_filter( 'decision_polls_api_response', array( __CLASS__, 'modify_results_for_ranked_polls' ), 10, 2 );
		add_filter( 'decision_polls_get_results', array( __CLASS__, 'modify_results_query' ), 10, 2 );
	}

	/**
	 * Modify the results query to handle ranked polls properly
	 *
	 * @param string $query   The SQL query.
	 * @param array  $poll    The poll data.
	 * @return string Modified query.
	 */
	public static function modify_results_query( $query, $poll ) {
		if ( isset( $poll['type'] ) && 'ranked' === $poll['type'] ) {
			// For ranked polls, order by votes_count DESC (higher points = higher rank).
			$query = str_replace( 
				'ORDER BY r.votes_count DESC, a.sort_order ASC', 
				'ORDER BY r.votes_count DESC', 
				$query 
			);
		}
		return $query;
	}

	/**
	 * Add explicit rank field to results for ranked polls
	 *
	 * @param array $response The API response data.
	 * @param array $poll     The poll data.
	 * @return array Modified response.
	 */
	public static function modify_results_for_ranked_polls( $response, $poll ) {
		if ( isset( $poll['type'] ) && 'ranked' === $poll['type'] && isset( $response['results'] ) ) {
			// Add poll_type to response.
			$response['poll_type'] = $poll['type'];
			
			// Add explicit rank to each result.
			$rank = 0;
			foreach ( $response['results'] as $key => $result ) {
				$rank++;
				$response['results'][ $key ]['rank'] = $rank;
			}
		}
		return $response;
	}
}

/**
 * Function to be added to templates/results.php to fix rank display
 *
 * @param array $result      The result item.
 * @param array $results_data All results data.
 * @return int The rank index.
 */
function decision_polls_get_rank_index( $result, $results_data ) {
	// If we have an explicit rank field, use it.
	if ( isset( $result['rank'] ) ) {
		return $result['rank'] - 1;
	}
	
	// Fallback to array_search.
	return array_search( $result, $results_data, true );
}

// Don't initialize the class here - this is a reference implementation for the developer to add to their theme's functions.php or in a custom plugin.
// Decision_Polls_Ranked_Fix::init();
