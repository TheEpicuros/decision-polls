<?php
/**
 * Direct Vote Handler - Fallback for JavaScript Failure
 *
 * This file provides a non-JavaScript direct form submission fallback
 * for voting when JavaScript is disabled or fails to work properly.
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Direct Vote Handler class
 */
class Decision_Polls_Direct_Vote {

	/**
	 * Initialize the handler.
	 */
	public static function init() {
		// Add the action to handle direct form submissions.
		add_action( 'init', array( __CLASS__, 'handle_direct_vote' ) );

		// Add form action URL to templates.
		add_filter( 'decision_polls_form_action', array( __CLASS__, 'get_form_action_url' ) );
	}

	/**
	 * Handle direct vote form submissions.
	 */
	public static function handle_direct_vote() {
		// Only process if this is a direct vote form submission.
		if ( ! isset( $_POST['decision_polls_direct_vote'] ) || '1' !== $_POST['decision_polls_direct_vote'] ) {
			return;
		}

		// Check nonce for security.
		if ( ! isset( $_POST['decision_polls_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['decision_polls_nonce'] ) ), 'decision_polls_vote' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'decision-polls' ) );
		}

		// Get poll details.
		$poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;
		if ( ! $poll_id ) {
			wp_die( esc_html__( 'Invalid poll ID.', 'decision-polls' ) );
		}

		// Verify the poll exists before processing the vote.
		$poll_model = new Decision_Polls_Poll();
		$poll = $poll_model->get( $poll_id );
		if ( ! $poll ) {
			wp_die( esc_html__( 'Poll not found. Please try again.', 'decision-polls' ) );
		}

		// Handle different poll types.
		$poll_type = isset( $_POST['poll_type'] ) ? sanitize_text_field( wp_unslash( $_POST['poll_type'] ) ) : 'standard';

		// Process vote depending on poll type.
		$answers = array();
		switch ( $poll_type ) {
			case 'standard':
				if ( isset( $_POST['poll_answer'] ) ) {
					$answers = array( absint( $_POST['poll_answer'] ) );
				}
				break;

			case 'multiple':
				if ( isset( $_POST['poll_answers'] ) && is_array( $_POST['poll_answers'] ) ) {
					$poll_answers = array_map( 'absint', wp_unslash( $_POST['poll_answers'] ) );
					foreach ( $poll_answers as $answer_id ) {
						$answers[] = $answer_id;
					}
				}
				break;

			case 'ranked':
				if ( isset( $_POST['ranked_answers'] ) && is_array( $_POST['ranked_answers'] ) ) {
					$ranked_answers = array_map( 'absint', wp_unslash( $_POST['ranked_answers'] ) );
					foreach ( $ranked_answers as $answer_id ) {
						$answers[] = $answer_id;
					}
				}
				break;
		}

		// Check if we have answers.
		if ( empty( $answers ) ) {
			wp_die( esc_html__( 'No answers selected.', 'decision-polls' ) );
		}

		// Submit the vote directly.
		$vote_model = new Decision_Polls_Vote();
		$result = $vote_model->add(
			array(
				'poll_id' => $poll_id,
				'answers' => $answers,
			)
		);

		if ( ! $result ) {
			wp_die( esc_html__( 'Failed to submit vote. Please try again.', 'decision-polls' ) );
		}

		// Set cookies for results display and refresh fallback.
		setcookie( 'decision_polls_show_results_' . $poll_id, '1', time() + 3600, '/' );
		setcookie( 'decision_polls_refresh_results', '1', time() + 3600, '/' );

		// Build the redirect URL using our custom endpoint helper.
		if ( class_exists( 'Decision_Polls_Custom_Endpoints' ) && method_exists( 'Decision_Polls_Custom_Endpoints', 'get_poll_results_url' ) ) {
			// Use our custom endpoint method
			$redirect_url = Decision_Polls_Custom_Endpoints::get_poll_results_url( $poll_id );
		} else {
			// Fallback to basic URL construction
			if ( get_option( 'permalink_structure' ) ) {
				// Pretty permalinks enabled
				$redirect_url = home_url( "poll/{$poll_id}/results/" );
			} else {
				// No permalinks - use query parameters
				$redirect_url = add_query_arg(
					array(
						'poll_id' => $poll_id,
						'show_results' => '1',
					),
					home_url()
				);
			}
		}

		// Prevent caching
		$redirect_url = add_query_arg( 'nocache', time(), $redirect_url );

		// Redirect to results.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get form action URL for direct vote processing.
	 *
	 * @return string Form action URL.
	 */
	public static function get_form_action_url() {
		// Use the current page URL to submit the form back to itself.
		$current_url = remove_query_arg( array( 'poll_id', 'show_results' ) );
		return esc_url( $current_url );
	}

	/**
	 * Check if a page exists with the specified shortcode.
	 *
	 * @param string $shortcode Shortcode to check for.
	 * @return bool Whether a page exists with the shortcode.
	 */
	private static function page_exists_with_shortcode( $shortcode ) {
		// Get pages with the shortcode.
		$args  = array(
			'post_type'      => 'page',
			'posts_per_page' => 1,
			's'              => '[' . $shortcode,
		);
		$query = new WP_Query( $args );

		return $query->have_posts();
	}
}

// Initialize the direct vote handler.
Decision_Polls_Direct_Vote::init();
