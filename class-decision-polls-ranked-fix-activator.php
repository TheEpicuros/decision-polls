<?php
/**
 * Decision Polls - Ranked Poll Fix Activator
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to activate the ranked poll fixes.
 */
class Decision_Polls_Ranked_Fix_Activator {
	/**
	 * Initialize the fixes.
	 */
	public static function init() {
		// Register necessary hooks.
		add_action( 'plugins_loaded', array( __CLASS__, 'register_hooks' ) );
	}

	/**
	 * Register hooks to fix ranked poll display.
	 */
	public static function register_hooks() {
		// Only register if Decision Polls plugin is active.
		if ( class_exists( 'Decision_Polls_Poll' ) ) {
			// These hooks are not necessary since we updated the code directly,
			// but they're included here for completeness in case the direct changes
			// are reverted in the future.
			add_filter( 'decision_polls_api_response', array( __CLASS__, 'modify_results_for_ranked_polls' ), 10, 2 );
		}
	}

	/**
	 * Add explicit rank field to results for ranked polls.
	 *
	 * @param array $response The API response data.
	 * @param array $poll     The poll data.
	 * @return array Modified response.
	 */
	public static function modify_results_for_ranked_polls( $response, $poll ) {
		if ( isset( $poll['type'] ) && 'ranked' === $poll['type'] && isset( $response['results'] ) ) {
			// Add poll_type to response if not already present.
			if ( ! isset( $response['poll_type'] ) ) {
				$response['poll_type'] = $poll['type'];
			}
			
			// Add explicit rank to each result if not already present.
			$rank = 0;
			foreach ( $response['results'] as $key => $result ) {
				if ( ! isset( $result['rank'] ) ) {
					$rank++;
					$response['results'][ $key ]['rank'] = $rank;
				}
			}
		}
		return $response;
	}
}

// Initialize the fixes.
add_action( 'init', array( 'Decision_Polls_Ranked_Fix_Activator', 'init' ) );
