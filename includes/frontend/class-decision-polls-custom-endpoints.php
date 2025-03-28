<?php
/**
 * Custom Endpoints Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle custom endpoints and rewrites.
 */
class Decision_Polls_Custom_Endpoints {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		// Initialize on init hook.
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		
		// Add query vars.
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		
		// Handle template redirect.
		add_action( 'template_redirect', array( __CLASS__, 'handle_template_redirect' ) );
		
		// Add rewrite flushing on activation.
		register_activation_hook( DECISION_POLLS_PLUGIN_BASENAME, array( __CLASS__, 'flush_rewrite_rules' ) );
	}

	/**
	 * Add custom query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'poll_id';
		$vars[] = 'show_results';
		$vars[] = 'poll_action';
		return $vars;
	}

	/**
	 * Add custom rewrite rules.
	 */
	public static function add_rewrite_rules() {
		// Add rewrite rule for poll viewing.
		add_rewrite_rule(
			'^poll/([0-9]+)/?$',
			'index.php?poll_id=$matches[1]',
			'top'
		);
		
		// Add rewrite rule for poll creation.
		add_rewrite_rule(
			'^poll/create/?$',
			'index.php?poll_action=create',
			'top'
		);
		
		// Flush rewrite rules only once.
		if ( ! get_option( 'decision_polls_rewrite_rules_flushed' ) ) {
			self::flush_rewrite_rules();
		}
	}
	
	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
		update_option( 'decision_polls_rewrite_rules_flushed', true );
	}

	/**
	 * Handle template redirect.
	 */
	public static function handle_template_redirect() {
		global $wp_query;
		
		// Check for poll ID parameter.
		if ( get_query_var( 'poll_id' ) ) {
			$poll_id = absint( get_query_var( 'poll_id' ) );
			
			if ( $poll_id > 0 ) {
				// Get poll from database.
				$poll_model = new Decision_Polls_Poll();
				$poll = $poll_model->get( $poll_id );
				
				if ( ! $poll ) {
					// If poll doesn't exist, return 404.
					$wp_query->set_404();
					status_header( 404 );
					return;
				}
				
				// Set up page variables.
				$wp_query->is_home = false;
				$wp_query->is_singular = true;
				
				// Check if we should show results.
				$show_results = get_query_var( 'show_results' ) == '1';
				
				// Also check cookies for results display after vote.
				if ( ! $show_results && isset( $_COOKIE['decision_polls_show_results_' . $poll_id] ) ) {
					$show_results = true;
				}
				
				// Display poll template.
				self::load_poll_template( $poll, $show_results );
				exit;
			}
		}
		
		// Check for poll creation action.
		if ( get_query_var( 'poll_action' ) === 'create' ) {
			// Set up page variables.
			$wp_query->is_home = false;
			$wp_query->is_singular = true;
			
			// Display poll creation template.
			self::load_poll_create_template();
			exit;
		}
	}
	
	/**
	 * Load the template for viewing a poll.
	 *
	 * @param array $poll        Poll data.
	 * @param bool  $show_results Whether to show results.
	 */
	private static function load_poll_template( $poll, $show_results = false ) {
		// Set up the title.
		$title = isset( $poll['title'] ) ? esc_html( $poll['title'] ) : __( 'View Poll', 'decision-polls' );
		
		get_header();
		
		echo '<div class="decision-polls-container">';
		echo '<h1 class="entry-title">' . $title . '</h1>';
		
		// Build the poll shortcode with parameters.
		$shortcode = '[decision_poll id="' . absint( $poll['id'] ) . '"';
		if ( $show_results ) {
			$shortcode .= ' show_results="1"';
		}
		$shortcode .= ']';
		
		// Output the shortcode.
		echo do_shortcode( $shortcode );
		
		echo '</div>';
		
		get_footer();
	}
	
	/**
	 * Load the template for creating a poll.
	 */
	private static function load_poll_create_template() {
		get_header();
		
		echo '<div class="decision-polls-container">';
		echo '<h1 class="entry-title">' . esc_html__( 'Create a New Poll', 'decision-polls' ) . '</h1>';
		
		// Output the poll creator shortcode.
		echo do_shortcode( '[decision_poll_creator]' );
		
		echo '</div>';
		
		get_footer();
	}
	
	/**
	 * Get the URL for viewing a poll.
	 *
	 * @param int $poll_id The poll ID.
	 * @return string
	 */
	public static function get_poll_url( $poll_id ) {
		return home_url( "poll/{$poll_id}/" );
	}
	
	/**
	 * Get the URL for creating a poll.
	 *
	 * @return string
	 */
	public static function get_create_poll_url() {
		return home_url( 'poll/create/' );
	}
}

// Initialize the class.
Decision_Polls_Custom_Endpoints::init();
