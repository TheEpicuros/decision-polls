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
		// Register the endpoint.
		add_action( 'init', array( __CLASS__, 'add_endpoints' ) );

		// Add query vars.
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );

		// Handle template redirect.
		add_action( 'template_redirect', array( __CLASS__, 'handle_template_redirect' ) );

		// Filter permalinks for polls.
		add_filter( 'page_link', array( __CLASS__, 'filter_poll_permalink' ), 10, 2 );
	}

	/**
	 * Add our custom endpoints.
	 */
	public static function add_endpoints() {
		// Add endpoint for poll create.
		add_rewrite_rule(
			'^poll/create/?$',
			'index.php?poll_action=create',
			'top'
		);

		// Add endpoint for single poll view.
		add_rewrite_rule(
			'^poll/([0-9]+)/?$',
			'index.php?poll_id=$matches[1]',
			'top'
		);

		// Flush rewrite rules only once.
		if ( ! get_option( 'decision_polls_rewrite_rules_flushed' ) ) {
			flush_rewrite_rules();
			update_option( 'decision_polls_rewrite_rules_flushed', true );
		}
	}

	/**
	 * Add custom query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'poll_action';
		$vars[] = 'poll_id';
		$vars[] = 'show_results';
		return $vars;
	}

	/**
	 * Handle template redirection based on our custom endpoints.
	 */
	public static function handle_template_redirect() {
		global $wp_query;

		// First, handle GET parameters for backward compatibility and direct access.
		if ( isset( $_GET['poll_id'] ) && ! isset( $wp_query->query_vars['poll_id'] ) ) {
			// If poll_id is directly in GET params, set in query vars.
			$wp_query->set( 'poll_id', absint( $_GET['poll_id'] ) );
			$wp_query->query_vars['poll_id'] = absint( $_GET['poll_id'] );
			
			// Also set show_results if present.
			if ( isset( $_GET['show_results'] ) && '1' === $_GET['show_results'] ) {
				$wp_query->set( 'show_results', '1' );
				$wp_query->query_vars['show_results'] = '1';
			}
		}

		// Check if we're on a poll create page.
		if ( isset( $wp_query->query_vars['poll_action'] ) && 'create' === $wp_query->query_vars['poll_action'] ) {
			// Clear any 404 status.
			status_header( 200 );
			$wp_query->is_404 = false;
            
			// Set a flag that this is a poll creation page.
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_home     = false;
			$wp_query->is_archive  = false;
			$wp_query->is_category = false;

			// Set the page title.
			add_filter( 'the_title', array( __CLASS__, 'set_create_poll_title' ), 10, 2 );

			// Load the template.
			add_filter( 'template_include', array( __CLASS__, 'load_poll_create_template' ) );
		}

		// Check if we're on a single poll view page.
		if ( isset( $wp_query->query_vars['poll_id'] ) ) {
			$poll_id = absint( $wp_query->query_vars['poll_id'] );

			if ( $poll_id > 0 ) {
				// Clear any 404 status.
				status_header( 200 );
				$wp_query->is_404 = false;
                
				// Set a flag that this is a single poll view page.
				$wp_query->is_page     = true;
				$wp_query->is_singular = true;
				$wp_query->is_home     = false;
				$wp_query->is_archive  = false;
				$wp_query->is_category = false;

				// Check if we need to display results.
				$show_results = isset( $wp_query->query_vars['show_results'] ) &&
					'1' === $wp_query->query_vars['show_results'];
				
				// Also check cookies for redirection after vote.
				if ( ! $show_results && isset( $_COOKIE['decision_polls_show_results_' . $poll_id] ) ) {
					$show_results = true;
				}
				
				// Store the show_results flag in the query for later use.
				$wp_query->set( 'decision_polls_show_results', $show_results );

				// Set the page title.
				add_filter( 'the_title', array( __CLASS__, 'set_poll_title' ), 10, 2 );

				// Load the template.
				add_filter( 'template_include', array( __CLASS__, 'load_single_poll_template' ) );
			}
		}

		// Handle direct 'create_poll' parameter.
		if ( isset( $_GET['create_poll'] ) && ! isset( $wp_query->query_vars['poll_action'] ) ) {
			// Clear any 404 status.
			status_header( 200 );
			$wp_query->is_404 = false;
            
			// Set a flag that this is a poll creation page.
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_home     = false;
			$wp_query->is_archive  = false;
			$wp_query->is_category = false;

			// Set the page title.
			add_filter( 'the_title', array( __CLASS__, 'set_create_poll_title' ), 10, 2 );

			// Load the template.
			add_filter( 'template_include', array( __CLASS__, 'load_poll_create_template' ) );
		}
	}

	/**
	 * Set the title for the poll creation page.
	 *
	 * @param string $title The page title.
	 * @param int    $id The post ID.
	 * @return string Modified title.
	 */
	public static function set_create_poll_title( $title, $id = null ) {
		if ( is_admin() || ! in_the_loop() ) {
			return $title;
		}

		return __( 'Create a New Poll', 'decision-polls' );
	}

	/**
	 * Set the title for the single poll page.
	 *
	 * @param string $title The page title.
	 * @param int    $id The post ID.
	 * @return string Modified title.
	 */
	public static function set_poll_title( $title, $id = null ) {
		if ( is_admin() || ! in_the_loop() ) {
			return $title;
		}

		global $wp_query;
		$poll_id = isset( $wp_query->query_vars['poll_id'] ) ? absint( $wp_query->query_vars['poll_id'] ) : 0;

		if ( $poll_id > 0 ) {
			// Get poll title from database.
			$poll_model = new Decision_Polls_Poll();
			$poll       = $poll_model->get( $poll_id );

			if ( $poll && isset( $poll['title'] ) ) {
				return esc_html( $poll['title'] );
			}
		}

		return __( 'View Poll', 'decision-polls' );
	}

	/**
	 * Load the template for poll creation.
	 *
	 * @param string $template The current template path.
	 * @return string The modified template path.
	 */
	public static function load_poll_create_template( $template ) {
		// Render header.
		get_header();

		echo '<div class="decision-polls-page-container">';
		echo '<h1 class="entry-title">' . esc_html__( 'Create a New Poll', 'decision-polls' ) . '</h1>';

		// Display the poll creator form.
		echo do_shortcode( '[decision_poll_creator]' );

		echo '</div>';

		// Render footer.
		get_footer();

		// Return a blank template to prevent further output.
		return DECISION_POLLS_PLUGIN_DIR . 'templates/blank-template.php';
	}

	/**
	 * Load the template for a single poll view.
	 *
	 * @param string $template The current template path.
	 * @return string The modified template path.
	 */
	public static function load_single_poll_template( $template ) {
		global $wp_query;
		$poll_id = isset( $wp_query->query_vars['poll_id'] ) ? absint( $wp_query->query_vars['poll_id'] ) : 0;

		if ( $poll_id > 0 ) {
			// Get poll from database.
			$poll_model = new Decision_Polls_Poll();
			$poll       = $poll_model->get( $poll_id );

			if ( ! $poll ) {
				// If poll doesn't exist, try to load the 404 template
				if ( file_exists( get_theme_file_path( '404.php' ) ) ) {
					return get_theme_file_path( '404.php' );
				}
                
				// Don't modify the template if poll not found
				return $template;
			}

			// Make sure we don't have a 404 status if the poll exists
			if ( is_404() ) {
				status_header( 200 );
				$wp_query->is_404 = false;
			}

			// Render header.
			get_header();

			echo '<div class="decision-polls-page-container">';
			echo '<h1 class="entry-title">' . esc_html( $poll['title'] ) . '</h1>';

			// Check if we need to show results.
			$show_results = null !== $wp_query->get( 'decision_polls_show_results' ) &&
				$wp_query->get( 'decision_polls_show_results' );

			// Display the poll shortcode with the appropriate poll ID and results parameter if needed.
			$shortcode = '[decision_poll id="' . esc_attr( $poll_id ) . '"';
			if ( $show_results ) {
				$shortcode .= ' show_results="1"';
			}
			$shortcode .= ']';
			
			echo do_shortcode( $shortcode );

			echo '</div>';

			// Render footer.
			get_footer();

			// Return a blank template to prevent further output.
			return DECISION_POLLS_PLUGIN_DIR . 'templates/blank-template.php';
		}

		return $template;
	}

	/**
	 * Filter the poll permalink to use our clean URL structure.
	 *
	 * @param string $permalink The current permalink.
	 * @param int    $post_id The post ID.
	 * @return string The modified permalink.
	 */
	public static function filter_poll_permalink( $permalink, $post_id ) {
		// Only modify permalinks for our custom query vars.
		if ( is_admin() ) {
			return $permalink;
		}

		// Replace poll creation links.
		if ( strpos( $permalink, 'create_poll=1' ) !== false ) {
			return home_url( 'poll/create/' );
		}

		// Replace single poll view links.
		if ( preg_match( '/poll_id=(\d+)/', $permalink, $matches ) ) {
			$poll_id = $matches[1];
			return home_url( "poll/{$poll_id}/" );
		}

		return $permalink;
	}

	/**
	 * Get the URL for creating a new poll.
	 *
	 * @return string The URL for poll creation.
	 */
	public static function get_create_poll_url() {
		return home_url( 'poll/create/' );
	}

	/**
	 * Get the URL for viewing a poll.
	 *
	 * @param int $poll_id The poll ID.
	 * @return string The URL for viewing the poll.
	 */
	public static function get_poll_url( $poll_id ) {
		return home_url( "poll/{$poll_id}/" );
	}
}

// Initialize the class.
Decision_Polls_Custom_Endpoints::init();
