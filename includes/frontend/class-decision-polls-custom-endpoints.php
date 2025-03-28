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
		global $wp_query, $post;
		
		// Create a dummy post to prevent errors in WordPress core functions.
		$dummy_post_id = -999; // Use a negative ID to ensure it doesn't conflict with any real post.
		$dummy_post = self::create_dummy_post(
			$dummy_post_id, 
			__( 'Create a New Poll', 'decision-polls' ),
			'',
			'page'
		);
		
		// Set the global post object and setup post data.
		$post = $dummy_post;
		$wp_query->post = $dummy_post;
		$wp_query->posts = array( $dummy_post );
		$wp_query->queried_object = $dummy_post;
		$wp_query->queried_object_id = $dummy_post_id;
		$wp_query->found_posts = 1;
		$wp_query->post_count = 1;
		
		// Setup post data.
		setup_postdata( $dummy_post );
		
		// Override template include filter with lower priority.
		add_filter( 'template_include', function( $orig_template ) {
			remove_all_filters( 'the_content' );
			add_filter( 'the_content', function( $content ) {
				// Our custom content.
				return '<div class="decision-polls-page-container">' .
					'<h1 class="entry-title">' . esc_html__( 'Create a New Poll', 'decision-polls' ) . '</h1>' .
					do_shortcode( '[decision_poll_creator]' ) .
					'</div>';
			});
			
			// Try to use the page template from the theme.
			$possible_templates = array(
				'page.php',
				'single.php',
				'index.php'
			);
			
			foreach ( $possible_templates as $possible_template ) {
				$path = get_template_directory() . '/' . $possible_template;
				if ( file_exists( $path ) ) {
					return $path;
				}
			}
			
			// Fallback to the original template.
			return $orig_template;
		}, 99999 );
		
		return $template;
	}

	/**
	 * Load the template for a single poll view.
	 *
	 * @param string $template The current template path.
	 * @return string The modified template path.
	 */
	public static function load_single_poll_template( $template ) {
		global $wp_query, $post;
		$poll_id = isset( $wp_query->query_vars['poll_id'] ) ? absint( $wp_query->query_vars['poll_id'] ) : 0;

		if ( $poll_id > 0 ) {
			// Get poll from database.
			$poll_model = new Decision_Polls_Poll();
			$poll       = $poll_model->get( $poll_id );

			if ( ! $poll ) {
				// If poll doesn't exist, set 404 and use 404 template
				$wp_query->set_404();
				status_header( 404 );
				return get_404_template();
			}

			// Make sure we don't have a 404 status if the poll exists
			if ( is_404() ) {
				status_header( 200 );
				$wp_query->is_404 = false;
			}
			
			// Create a dummy post to prevent errors in WordPress core functions.
			$dummy_post_id = -$poll_id; // Use a negative ID to ensure it doesn't conflict with any real post.
			$dummy_post = self::create_dummy_post(
				$dummy_post_id, 
				$poll['title'],
				isset($poll['description']) ? $poll['description'] : '',
				'page'
			);
			
			// Set the global post object and setup post data.
			$post = $dummy_post;
			$wp_query->post = $dummy_post;
			$wp_query->posts = array( $dummy_post );
			$wp_query->queried_object = $dummy_post;
			$wp_query->queried_object_id = $dummy_post_id;
			$wp_query->found_posts = 1;
			$wp_query->post_count = 1;
			
			// Setup post data.
			setup_postdata( $dummy_post );

			// Check if we need to show results.
			$show_results = null !== $wp_query->get( 'decision_polls_show_results' ) &&
				$wp_query->get( 'decision_polls_show_results' );
			
			// Override template include filter with lower priority.
			add_filter( 'template_include', function( $orig_template ) use ( $poll_id, $poll, $show_results ) {
				remove_all_filters( 'the_content' );
				add_filter( 'the_content', function( $content ) use ( $poll_id, $poll, $show_results ) {
					// Build the shortcode.
					$shortcode = '[decision_poll id="' . esc_attr( $poll_id ) . '"';
					if ( $show_results ) {
						$shortcode .= ' show_results="1"';
					}
					$shortcode .= ']';
					
					// Our custom content.
					return '<div class="decision-polls-page-container">' .
						'<h1 class="entry-title">' . esc_html( $poll['title'] ) . '</h1>' .
						do_shortcode( $shortcode ) .
						'</div>';
				});
				
				// Try to use the page template from the theme.
				$possible_templates = array(
					'page.php',
					'single.php',
					'index.php'
				);
				
				foreach ( $possible_templates as $possible_template ) {
					$path = get_template_directory() . '/' . $possible_template;
					if ( file_exists( $path ) ) {
						return $path;
					}
				}
				
				// Fallback to the original template.
				return $orig_template;
			}, 99999 );
			
			return $template;
		}

		return $template;
	}
	
	/**
	 * Create a dummy post object for use with custom endpoints.
	 *
	 * @param int    $id          The post ID to use.
	 * @param string $title       The post title.
	 * @param string $content     The post content.
	 * @param string $post_type   The post type.
	 * @return object The dummy post object.
	 */
	private static function create_dummy_post( $id, $title, $content = '', $post_type = 'page' ) {
		// Create a stdClass object to mimic a WP_Post object.
		$post = new \stdClass();
		
		// Set up the minimum required fields.
		$post->ID = $id;
		$post->post_author = 1;
		$post->post_date = current_time( 'mysql' );
		$post->post_date_gmt = current_time( 'mysql', 1 );
		$post->post_title = $title;
		$post->post_content = $content;
		$post->post_status = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->post_name = sanitize_title( $title );
		$post->post_type = $post_type;
		$post->filter = 'raw';
		$post->post_parent = 0;
		$post->comment_count = 0;
		$post->guid = home_url( '/' . $post->post_name );
		$post->post_mime_type = '';
		$post->ancestors = array();
		
		// Add other fields that might be needed.
		$post->post_excerpt = '';
		$post->post_modified = $post->post_date;
		$post->post_modified_gmt = $post->post_date_gmt;
		$post->post_password = '';
		$post->post_content_filtered = '';
		$post->menu_order = 0;
		$post->page_template = 'default';
		
		// Convert to a proper WP_Post object if the class exists.
		if ( class_exists( 'WP_Post' ) ) {
			$post = new \WP_Post( $post );
		}
		
		return $post;
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
