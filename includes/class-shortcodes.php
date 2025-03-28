<?php
/**
 * Shortcodes Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle plugin shortcodes.
 */
class Decision_Polls_Shortcodes {

	/**
	 * Initialize shortcodes.
	 */
	public static function init() {
		// Single poll shortcode.
		add_shortcode( 'decision_poll', array( __CLASS__, 'poll_shortcode' ) );

		// Poll list shortcode.
		add_shortcode( 'decision_polls', array( __CLASS__, 'polls_list_shortcode' ) );

		// Poll creation form shortcode.
		add_shortcode( 'decision_poll_creator', array( __CLASS__, 'poll_creator_shortcode' ) );
	}

	/**
	 * Single poll shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function poll_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'show_results' => false,
			),
			$atts,
			'decision_poll'
		);

		$poll_id      = absint( $atts['id'] );
		$show_results = filter_var( $atts['show_results'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $poll_id ) {
			return '<div class="decision-polls-error">' . esc_html__( 'Poll ID is required.', 'decision-polls' ) . '</div>';
		}

		// Get poll data.
		$poll_model = new Decision_Polls_Poll();
		$poll       = $poll_model->get( $poll_id );

		if ( ! $poll ) {
			return '<div class="decision-polls-error">' . esc_html__( 'Poll not found.', 'decision-polls' ) . '</div>';
		}

		// Check if user has already voted.
		$vote_model = new Decision_Polls_Vote();
		$has_voted  = $vote_model->has_voted( $poll_id );

		// Get poll results.
		$results = array();
		if ( $has_voted || $show_results ) {
			$results = $vote_model->get_results( $poll_id );
		}

		// Enqueue necessary assets.
		wp_enqueue_style( 'decision-polls' );
		wp_enqueue_script( 'decision-polls' );

		if ( 'ranked' === $poll['type'] ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		// Start output buffer.
		ob_start();

		// Include template based on poll type and whether to show results.
		if ( $has_voted || $show_results ) {
			// Show results.
			include DECISION_POLLS_PLUGIN_DIR . 'templates/results.php';
		} else {
			// Show voting form.
			switch ( $poll['type'] ) {
				case 'ranked':
					include DECISION_POLLS_PLUGIN_DIR . 'templates/ranked-poll.php';
					break;
				case 'multiple':
					include DECISION_POLLS_PLUGIN_DIR . 'templates/multiple-poll.php';
					break;
				default:
					include DECISION_POLLS_PLUGIN_DIR . 'templates/standard-poll.php';
					break;
			}
		}

		return ob_get_clean();
	}

	/**
	 * Polls list shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function polls_list_shortcode( $atts ) {
		// IMPORTANT: Handle special cases first to prevent duplicates

		// Case 1: Display single poll if poll_id is present in the URL
		if ( isset( $_GET['poll_id'] ) && absint( $_GET['poll_id'] ) > 0 ) {
			return self::poll_shortcode( array( 'id' => absint( $_GET['poll_id'] ) ) );
		}

		// Case 2: Display poll creator if create_poll is in the URL and we're not already in the creator shortcode
		if ( isset( $_GET['create_poll'] ) && ! did_action( 'decision_polls_creator_shortcode_rendered' ) ) {
			// Return the poll creator shortcode output
			return self::poll_creator_shortcode( array() );
		}

		// If we're here, we're displaying the poll list
		$atts = shortcode_atts(
			array(
				'per_page'  => 10,
				'status'    => 'published',
				'type'      => '',
				'author_id' => 0,
			),
			$atts,
			'decision_polls'
		);

		$args = array(
			'per_page' => absint( $atts['per_page'] ),
			'page'     => isset( $_GET['poll_page'] ) ? absint( $_GET['poll_page'] ) : 1,
			'status'   => sanitize_text_field( $atts['status'] ),
			'type'     => sanitize_text_field( $atts['type'] ),
		);

		// Get appropriate polls
		$poll_model = new Decision_Polls_Poll();

		if ( absint( $atts['author_id'] ) > 0 ) {
			// Get polls for specific author
			$author_id  = absint( $atts['author_id'] );
			$polls_data = $poll_model->get_user_polls( $author_id, $args );
		} else {
			// Get all polls
			$polls_data = $poll_model->get_all( $args );
		}

		// Enqueue required assets.
		wp_enqueue_style( 'decision-polls' );
		wp_enqueue_script( 'decision-polls' );

		// Start output buffer.
		ob_start();

		// Include template.
		include DECISION_POLLS_PLUGIN_DIR . 'templates/polls-list.php';

		return ob_get_clean();
	}

	/**
	 * Poll creation form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function poll_creator_shortcode( $atts ) {
		// Set a flag that this shortcode has been rendered to prevent duplicates
		do_action( 'decision_polls_creator_shortcode_rendered' );

		$atts = shortcode_atts(
			array(
				'redirect' => '',
			),
			$atts,
			'decision_poll_creator'
		);

		// Check if frontend creation is allowed.
		$allow_frontend = get_option( 'decision_polls_allow_frontend_creation', 1 );
		if ( ! $allow_frontend ) {
			return '<div class="decision-polls-error">' . esc_html__( 'Frontend poll creation is disabled.', 'decision-polls' ) . '</div>';
		}

		// Check if login is required.
		$require_login = get_option( 'decision_polls_require_login_to_create', 1 );
		if ( $require_login && ! is_user_logged_in() ) {
			return '<div class="decision-polls-error">' .
				sprintf(
					/* translators: %s: login URL */
					esc_html__( 'You must be logged in to create polls. %s', 'decision-polls' ),
					'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'decision-polls' ) . '</a>'
				) .
				'</div>';
		}

		// Enqueue required assets.
		wp_enqueue_style( 'decision-polls' );
		wp_enqueue_script( 'decision-polls' );

		// Start output buffer.
		ob_start();

		// Include poll creator template.
		include DECISION_POLLS_PLUGIN_DIR . 'templates/poll-creator.php';

		return ob_get_clean();
	}
}

// Initialize shortcodes.
Decision_Polls_Shortcodes::init();
