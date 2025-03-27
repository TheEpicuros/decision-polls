<?php
/**
 * AJAX Handlers
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle AJAX voting.
 */
function decision_polls_ajax_vote() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'decision_polls_vote' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'decision-polls' ) ) );
	}
	
	// Get poll ID.
	$poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;
	if ( ! $poll_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid poll ID.', 'decision-polls' ) ) );
	}
	
	// Get poll type.
	$poll_type = isset( $_POST['poll_type'] ) ? sanitize_text_field( wp_unslash( $_POST['poll_type'] ) ) : 'standard';
	
	// Get vote model.
	$vote_model = new Decision_Polls_Vote();
	
	// Check if user has already voted.
	if ( $vote_model->has_voted( $poll_id ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You have already voted in this poll.', 'decision-polls' ) ) );
	}
	
	$result = false;
	
	// Process vote based on poll type.
	switch ( $poll_type ) {
		case 'standard':
			// Get answer ID.
			$answer_id = isset( $_POST['answer_id'] ) ? absint( $_POST['answer_id'] ) : 0;
			if ( ! $answer_id ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please select an option.', 'decision-polls' ) ) );
			}
			
			// Record vote.
			$result = $vote_model->record_vote( $poll_id, $answer_id );
			break;
		
		case 'multiple':
			// Get answer IDs.
			$answer_ids = isset( $_POST['answer_ids'] ) ? array_map( 'absint', (array) $_POST['answer_ids'] ) : array();
			if ( empty( $answer_ids ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please select at least one option.', 'decision-polls' ) ) );
			}
			
			// Record multiple votes.
			$result = $vote_model->record_multiple_votes( $poll_id, $answer_ids );
			break;
		
		case 'ranked':
			// Get ranked answers.
			$ranked_answers = isset( $_POST['ranked_answers'] ) ? array_map( 'absint', (array) $_POST['ranked_answers'] ) : array();
			if ( empty( $ranked_answers ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please rank all options.', 'decision-polls' ) ) );
			}
			
			// Record ranked votes.
			$result = $vote_model->record_ranked_votes( $poll_id, $ranked_answers );
			break;
		
		default:
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid poll type.', 'decision-polls' ) ) );
	}
	
	// Check result.
	if ( ! $result ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to record your vote. Please try again.', 'decision-polls' ) ) );
	}
	
	// Get poll results.
	$results = $vote_model->get_results( $poll_id );
	
	// Send success response with results.
	wp_send_json_success(
		array(
			'message' => esc_html__( 'Your vote has been recorded!', 'decision-polls' ),
			'results' => $results,
		)
	);
}
add_action( 'wp_ajax_decision_polls_vote', 'decision_polls_ajax_vote' );
add_action( 'wp_ajax_nopriv_decision_polls_vote', 'decision_polls_ajax_vote' );

/**
 * Handle AJAX poll creation.
 */
function decision_polls_ajax_create_poll() {
	// Verify nonce - allow for both regular nonce and creator nonce field.
	$nonce_verified = false;
	
	if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'decision_polls_create' ) ) {
		$nonce_verified = true;
	}
	
	if ( ! $nonce_verified && isset( $_POST['decision_polls_creator_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['decision_polls_creator_nonce'] ) ), 'decision_polls_create' ) ) {
		$nonce_verified = true;
	}
	
	if ( ! $nonce_verified ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'decision-polls' ) ) );
	}
	
	// Check if frontend creation is allowed.
	$allow_frontend = get_option( 'decision_polls_allow_frontend_creation', 1 );
	if ( ! $allow_frontend ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Frontend poll creation is disabled.', 'decision-polls' ) ) );
	}
	
	// Check if login is required.
	$require_login = get_option( 'decision_polls_require_login_to_create', 1 );
	if ( $require_login && ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You must be logged in to create polls.', 'decision-polls' ) ) );
	}
	
	// Get poll data.
	$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
	$poll_type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'standard';
	$multiple_choices = isset( $_POST['multiple_choices'] ) ? absint( $_POST['multiple_choices'] ) : 0;
	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'published';
	$is_private = isset( $_POST['is_private'] ) ? (bool) $_POST['is_private'] : false;
	
	// Validate required fields.
	if ( empty( $title ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Poll title is required.', 'decision-polls' ) ) );
	}
	
	// Get answers.
	$answers = isset( $_POST['answers'] ) ? $_POST['answers'] : array();
	if ( ! is_array( $answers ) ) {
		$answers = array( $answers );
	}
	
	// Sanitize answers.
	$sanitized_answers = array();
	foreach ( $answers as $answer ) {
		$sanitized_answer = sanitize_text_field( wp_unslash( $answer ) );
		if ( ! empty( $sanitized_answer ) ) {
			$sanitized_answers[] = $sanitized_answer;
		}
	}
	
	// Validate answers.
	if ( count( $sanitized_answers ) < 2 ) {
		wp_send_json_error( array( 'message' => esc_html__( 'At least two poll options are required.', 'decision-polls' ) ) );
	}
	
	// Create poll data.
	$poll_data = array(
		'title'            => $title,
		'description'      => $description,
		'type'             => $poll_type,
		'multiple_choices' => $multiple_choices,
		'status'           => $status,
		'is_private'       => $is_private,
		'answers'          => $sanitized_answers,
	);
	
	// Create poll.
	$poll_model = new Decision_Polls_Poll();
	$result = $poll_model->create( $poll_data );
	
	// Check result.
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}
	
	// Success response.
	wp_send_json_success(
		array(
			'message' => esc_html__( 'Poll created successfully!', 'decision-polls' ),
			'poll'    => $result,
		)
	);
}
add_action( 'wp_ajax_decision_polls_create_poll', 'decision_polls_ajax_create_poll' );
add_action( 'wp_ajax_nopriv_decision_polls_create_poll', 'decision_polls_ajax_create_poll' );

/**
 * Handle poll deletion from admin area.
 */
function decision_polls_ajax_delete_poll() {
	// Check if user has capability to manage polls.
	if ( ! current_user_can( 'manage_decision_polls' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete polls.', 'decision-polls' ) );
	}
	
	// Check if we're processing a delete action.
	if ( ! isset( $_GET['action'] ) || 'delete' !== $_GET['action'] ) {
		return;
	}
	
	// Get poll ID.
	$poll_id = isset( $_GET['poll_id'] ) ? absint( $_GET['poll_id'] ) : 0;
	if ( ! $poll_id ) {
		wp_die( esc_html__( 'Invalid poll ID.', 'decision-polls' ) );
	}
	
	// Verify nonce.
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'delete-poll_' . $poll_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'decision-polls' ) );
	}
	
	// Delete poll.
	$poll_model = new Decision_Polls_Poll();
	$result = $poll_model->delete( $poll_id );
	
	// Redirect back to polls list.
	$redirect_url = admin_url( 'admin.php?page=decision-polls' );
	
	// Add message parameter based on result.
	if ( $result ) {
		$redirect_url = add_query_arg( 'message', 'deleted', $redirect_url );
	} else {
		$redirect_url = add_query_arg( 'error', 'delete_failed', $redirect_url );
	}
	
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_init', 'decision_polls_ajax_delete_poll' );
