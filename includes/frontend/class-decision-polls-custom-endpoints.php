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
	 * Static property to store current poll ID for permalink functions.
	 *
	 * @var int
	 */
	private static $current_poll_id = 0;

	/**
	 * Initialize the class.
	 */
	public static function init() {
		// Register the endpoint.
		add_action( 'init', array( __CLASS__, 'add_endpoints' ), 10 );

		// Add query vars.
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );

		// Pre-handle query before template_redirect for earlier setup
		add_action( 'parse_request', array( __CLASS__, 'pre_handle_request' ), 1 );

		// Handle template redirect.
		add_action( 'template_redirect', array( __CLASS__, 'handle_template_redirect' ) );

		// Filter permalinks for polls.
		add_filter( 'page_link', array( __CLASS__, 'filter_poll_permalink' ), 10, 2 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_poll_permalink' ), 10, 2 );

		// Filter for permalinks with no post object
		add_filter( 'post_type_archive_link', array( __CLASS__, 'filter_post_type_archive' ), 10, 2 );
		add_filter( 'permalink_manager_detect_post_id', array( __CLASS__, 'provide_poll_id_for_permalink' ), 10, 2 );
		add_filter( 'get_post_metadata', array( __CLASS__, 'filter_post_metadata' ), 10, 4 );
	}

	/**
	 * Add our custom endpoints.
	 */
	public static function add_endpoints() {
		// Simulate a post type for better integration - don't actually register it
		global $wp_post_types;
		if ( ! isset( $wp_post_types['poll'] ) ) {
			$args = array(
				'label'               => __( 'Polls', 'decision-polls' ),
				'description'         => __( 'Decision Polls', 'decision-polls' ),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor' ),
				'_builtin'            => false,
			);
			
			// Create a stdClass to mimic a post type object without registering it
			$poll_type = (object) $args;
			$poll_type->name = 'poll';
			$poll_type->labels = (object) array(
				'name'               => __( 'Polls', 'decision-polls' ),
				'singular_name'      => __( 'Poll', 'decision-polls' ),
				'menu_name'          => __( 'Polls', 'decision-polls' ),
				'all_items'          => __( 'All Polls', 'decision-polls' ),
				'view_item'          => __( 'View Poll', 'decision-polls' ),
				'add_new_item'       => __( 'Add New Poll', 'decision-polls' ),
				'add_new'            => __( 'Add New', 'decision-polls' ),
				'edit_item'          => __( 'Edit Poll', 'decision-polls' ),
				'update_item'        => __( 'Update Poll', 'decision-polls' ),
				'search_items'       => __( 'Search Polls', 'decision-polls' ),
				'not_found'          => __( 'Not found', 'decision-polls' ),
				'not_found_in_trash' => __( 'Not found in Trash', 'decision-polls' ),
			);
			
			// Add to global post types
			$wp_post_types['poll'] = $poll_type;
		}
		
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
	 * Pre-handle request to set up post data early
	 * 
	 * @param WP $wp Current WordPress environment instance
	 */
	public static function pre_handle_request( $wp ) {
		// Check for poll_id in query vars
		if ( isset( $wp->query_vars['poll_id'] ) ) {
			$poll_id = absint( $wp->query_vars['poll_id'] );
			self::$current_poll_id = $poll_id;
			
			// Set up early post data
			self::setup_early_poll_data( $poll_id );
		}
		
		// Check for poll_action query var
		if ( isset( $wp->query_vars['poll_action'] ) && 'create' === $wp->query_vars['poll_action'] ) {
			// Set up early post data for create page
			self::setup_early_create_page_data();
		}
		
		// Handle GET parameters for backward compatibility
		if ( isset( $_GET['poll_id'] ) && ! isset( $wp->query_vars['poll_id'] ) ) {
			$poll_id = absint( $_GET['poll_id'] );
			self::$current_poll_id = $poll_id;
			
			// Set up early post data
			self::setup_early_poll_data( $poll_id );
		}
		
		// Handle direct 'create_poll' parameter
		if ( isset( $_GET['create_poll'] ) && ! isset( $wp->query_vars['poll_action'] ) ) {
			// Set up early post data for create page
			self::setup_early_create_page_data();
		}
	}
	
	/**
	 * Set up early post data for poll page
	 * 
	 * @param int $poll_id The poll ID
	 */
	private static function setup_early_poll_data( $poll_id ) {
		global $wp_query, $post;
		
		if ( $poll_id <= 0 ) {
			return;
		}
		
		// Get poll from database
		$poll_model = new Decision_Polls_Poll();
		$poll = $poll_model->get( $poll_id );
		
		if ( ! $poll ) {
			return;
		}
		
		// Create a dummy post object
		$dummy_post_id = -$poll_id;
		$dummy_post = self::create_dummy_post(
			$dummy_post_id,
			$poll['title'],
			isset( $poll['description'] ) ? $poll['description'] : '',
			'poll'  // Use our virtual post type
		);
		
		// Set up globals early
		$post = $dummy_post;
	}
	
	/**
	 * Set up early post data for create page
	 */
	private static function setup_early_create_page_data() {
		global $wp_query, $post;
		
		// Create a dummy post
		$dummy_post_id = -999;
		$dummy_post = self::create_dummy_post(
			$dummy_post_id,
			__( 'Create a New Poll', 'decision-polls' ),
			'',
			'poll'  // Use our virtual post type
		);
		
		// Set up globals early
		$post = $dummy_post;
	}
	
	/**
	 * Handle template redirection based on our custom endpoints.
	 */
	public static function handle_template_redirect() {
		global $wp_query, $wp;

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
		
		// Generate slug from title
		$slug = sanitize_title( $title );
		
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
		$post->post_name = $slug;
		$post->post_type = $post_type;
		$post->filter = 'raw';
		$post->post_parent = 0;
		$post->comment_count = 0;
		
		// Create proper guid based on post type
		if ( $post_type === 'poll' && $id < 0 ) {
			// For polls, use our custom permalink structure
			$poll_id = absint( $id * -1 ); // Convert negative ID to positive
			if ( $poll_id === 999 ) {
				// Create poll URL
				$post->guid = home_url( '/poll/create/' );
			} else {
				// Single poll URL
				$post->guid = home_url( '/poll/' . $poll_id . '/' );
			}
		} else {
			// Default guid
			$post->guid = home_url( '/' . $slug . '/' );
		}
		
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
		$post->post_category = array(); // Required by some link functions
		$post->tags_input = array();    // Required by some link functions
		
		// Convert to a proper WP_Post object if the class exists.
		if ( class_exists( 'WP_Post' ) ) {
			$post = new \WP_Post( $post );
		}
		
		return $post;
	}

	/**
	 * Provide poll ID for permalink generation.
	 * 
	 * @param int|null $post_id The post ID, or null if not found.
	 * @param string   $url     The current URL.
	 * @return int|null The poll ID if available, or the original post ID.
	 */
	public static function provide_poll_id_for_permalink( $post_id, $url ) {
		// Check if this is a poll URL
		if ( strpos( $url, '/poll/' ) !== false ) {
			if ( strpos( $url, '/poll/create/' ) !== false ) {
				return -999; // Special ID for create page
			}
			
			// Extract poll ID from URL
			if ( preg_match( '/\/poll\/(\d+)\//', $url, $matches ) ) {
				$poll_id = absint( $matches[1] );
				return -$poll_id; // Return negative ID to match our dummy posts
			}
		}
		
		// Check if we have a current poll ID
		if ( self::$current_poll_id > 0 ) {
			return -self::$current_poll_id; // Return negative ID to match our dummy posts
		}
		
		return $post_id;
	}
	
	/**
	 * Filter post metadata for our dummy posts.
	 * 
	 * @param mixed  $value     The value to return.
	 * @param int    $object_id The object ID.
	 * @param string $meta_key  The meta key.
	 * @param bool   $single    Whether to return a single value.
	 * @return mixed The filtered value.
	 */
	public static function filter_post_metadata( $value, $object_id, $meta_key, $single ) {
		// Check if this is one of our dummy posts (negative ID)
		if ( $object_id < 0 ) {
			// Special case for post_type which is often used in template functions
			if ( '_wp_page_template' === $meta_key ) {
				return $single ? 'default' : array( 'default' );
			}
		}
		
		return $value;
	}
	
	/**
	 * Filter post type archive link.
	 * 
	 * @param string $link      The archive link.
	 * @param string $post_type The post type.
	 * @return string The filtered link.
	 */
	public static function filter_post_type_archive( $link, $post_type ) {
		// Check if this is our poll post type
		if ( 'poll' === $post_type ) {
			return home_url( '/polls/' );
		}
		
		return $link;
	}
	
	/**
	 * Filter the poll permalink to use our clean URL structure.
	 *
	 * @param string      $permalink The current permalink.
	 * @param int|WP_Post $post_id   The post ID or object.
	 * @return string The modified permalink.
	 */
	public static function filter_poll_permalink( $permalink, $post_id ) {
		// Only modify permalinks for our custom query vars.
		if ( is_admin() ) {
			return $permalink;
		}
		
		// Handle post object
		if ( is_object( $post_id ) ) {
			$post = $post_id;
			$post_id = $post->ID;
			
			// If this is our dummy poll post type
			if ( isset( $post->post_type ) && 'poll' === $post->post_type ) {
				// For create page
				if ( $post_id === -999 ) {
					return home_url( 'poll/create/' );
				}
				
				// For single poll view, convert negative ID to positive
				if ( $post_id < 0 ) {
					$poll_id = absint( $post_id * -1 );
					return home_url( "poll/{$poll_id}/" );
				}
			}
		}

		// Handle string permalinks with query parameters
		// Replace poll creation links.
		if ( strpos( $permalink, 'create_poll=1' ) !== false ) {
			return home_url( 'poll/create/' );
		}

		// Replace single poll view links.
		if ( preg_match( '/poll_id=(\d+)/', $permalink, $matches ) ) {
			$poll_id = $matches[1];
			return home_url( "poll/{$poll_id}/" );
		}
		
		// Check if this is one of our dummy posts
		if ( $post_id < 0 ) {
			// Special case for create page
			if ( $post_id === -999 ) {
				return home_url( 'poll/create/' );
			}
			
			// For regular polls, convert negative ID to positive
			$poll_id = absint( $post_id * -1 );
			return home_url( "poll/{$poll_id}/" );
		}
		
		// If we have a current poll context and no post ID
		if ( self::$current_poll_id > 0 && empty( $post_id ) ) {
			return home_url( "poll/" . self::$current_poll_id . "/" );
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
