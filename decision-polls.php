<?php
/**
 * Plugin Name: Decision Polls
 * Plugin URI: https://example.com/decision-polls
 * Description: A modern polling plugin with simple, multiple choice, and ranked choice polls. Allows frontend poll creation.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: decision-polls
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * License: GPL v2 or later
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'DECISION_POLLS_VERSION', '1.0.0' );
define( 'DECISION_POLLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DECISION_POLLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DECISION_POLLS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DECISION_POLLS_PATH', DECISION_POLLS_PLUGIN_DIR );

// Autoloader.
require_once DECISION_POLLS_PLUGIN_DIR . 'includes/class-decision-polls-autoloader.php';

/**
 * Main plugin class
 */
final class Decision_Polls {
	/**
	 * Singleton instance
	 *
	 * @var Decision_Polls
	 */
	private static $instance = null;

	/**
	 * API instance
	 *
	 * @var Decision_Polls_API
	 */
	public $api;

	/**
	 * Get singleton instance
	 *
	 * @return Decision_Polls
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Activation/deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Initialize the plugin if WordPress is fully loaded.
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		require_once DECISION_POLLS_PLUGIN_DIR . 'includes/core/class-install.php';
		Decision_Polls_Install::activate();

		// Register API capabilities.
		require_once DECISION_POLLS_PLUGIN_DIR . 'includes/core/class-api.php';
		Decision_Polls_API::register_capabilities();

		// Force flush rewrite rules to enable custom endpoints.
		delete_option( 'decision_polls_rewrite_rules_flushed' );
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Remove API capabilities (optional).
		// Decision_Polls_API::remove_capabilities();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'decision-polls', false, dirname( DECISION_POLLS_PLUGIN_BASENAME ) . '/languages' );

		// Initialize components.
		$this->init_components();
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components() {
		// Initialize API.
		$this->init_api();

		// Admin.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Frontend.
		$this->init_frontend();

		// Shortcodes.
		$this->init_shortcodes();

		// AJAX handlers.
		$this->init_ajax();

		// Assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
	}

	/**
	 * Initialize API.
	 */
	private function init_api() {
		$this->api = new Decision_Polls_API();
	}

	/**
	 * Initialize admin.
	 */
	private function init_admin() {
		$this->load_class( 'admin/class-admin' );
	}

	/**
	 * Initialize frontend.
	 */
	private function init_frontend() {
		$this->load_class( 'frontend/class-frontend' );

		// Load custom endpoints for clean URLs.
		require_once DECISION_POLLS_PLUGIN_DIR . 'includes/frontend/class-custom-endpoints.php';
		
		// Load UX enhancements & shortcode fixes.
		require_once DECISION_POLLS_PLUGIN_DIR . 'ux-enhance.php';
	}

	/**
	 * Initialize shortcodes.
	 */
	private function init_shortcodes() {
		require_once DECISION_POLLS_PLUGIN_DIR . 'includes/class-shortcodes.php';
	}

	/**
	 * Initialize AJAX handlers.
	 */
	private function init_ajax() {
		require_once DECISION_POLLS_PLUGIN_DIR . 'includes/ajax.php';
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class_path Relative path to the class file.
	 * @return object|null Class instance or null.
	 */
	private function load_class( $class_path ) {
		$file = DECISION_POLLS_PLUGIN_DIR . 'includes/' . $class_path . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
			$class_name = $this->get_class_name_from_path( $class_path );
			if ( class_exists( $class_name ) ) {
				return new $class_name();
			}
		}
		return null;
	}

	/**
	 * Convert file path to class name.
	 *
	 * @param string $path File path.
	 * @return string Class name.
	 */
	private function get_class_name_from_path( $path ) {
		$parts      = explode( '/', $path );
		$class_file = end( $parts );
		$class_name = str_replace( array( 'class-', '.php' ), '', $class_file );
		$class_name = 'Decision_Polls_' . implode( '_', array_map( 'ucfirst', explode( '-', $class_name ) ) );
		return $class_name;
	}

	/**
	 * Register frontend assets.
	 */
	public function register_assets() {
		// CSS.
		wp_register_style( 'decision-polls', DECISION_POLLS_PLUGIN_URL . 'assets/css/frontend.css', array(), DECISION_POLLS_VERSION );

		// JavaScript (traditional).
		wp_register_script( 'decision-polls', DECISION_POLLS_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery', 'jquery-ui-sortable' ), DECISION_POLLS_VERSION, true );

		// React components.
		wp_register_script( 'decision-polls-ranked', DECISION_POLLS_PLUGIN_URL . 'assets/dist/ranked-poll.js', array( 'wp-element' ), DECISION_POLLS_VERSION, true );
		wp_register_script( 'decision-polls-creator', DECISION_POLLS_PLUGIN_URL . 'assets/dist/poll-creator.js', array( 'wp-element' ), DECISION_POLLS_VERSION, true );
		wp_register_script( 'decision-polls-results', DECISION_POLLS_PLUGIN_URL . 'assets/dist/results.js', array( 'wp-element' ), DECISION_POLLS_VERSION, true );

		// Localize script with API data.
		wp_localize_script(
			'decision-polls',
			'decisionPollsAPI',
			array(
				'url'      => esc_url_raw( rest_url( $this->api->get_endpoint( 'polls' )->get_namespace() ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'adminUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			)
		);

		// Localize script with translations.
		wp_localize_script(
			'decision-polls',
			'decisionPollsL10n',
			array(
				'maxChoicesError'   => esc_html__( 'You can select a maximum of {max} options.', 'decision-polls' ),
				'selectOptionError' => esc_html__( 'Please select at least one option.', 'decision-polls' ),
				'voteSuccess'       => esc_html__( 'Your vote has been recorded. Thank you!', 'decision-polls' ),
				'voteError'         => esc_html__( 'There was an error submitting your vote. Please try again.', 'decision-polls' ),
				'totalVotes'        => esc_html__( 'Total votes: {total}', 'decision-polls' ),
				'votes'             => esc_html__( '{votes} votes', 'decision-polls' ),
				'lastUpdated'       => esc_html__( 'Last updated: {time}', 'decision-polls' ),
				'pollCreated'       => esc_html__( 'Poll created successfully!', 'decision-polls' ),
				'pollCreateError'   => esc_html__( 'An error occurred while creating the poll. Please try again.', 'decision-polls' ),
				'option'            => esc_html__( 'Option', 'decision-polls' ),
				'remove'            => esc_html__( 'Remove', 'decision-polls' ),
				'pollLink'          => esc_url( add_query_arg( 'poll_id', 'POLL_ID', get_permalink() ) ),
				'viewPoll'          => esc_html__( 'View your poll', 'decision-polls' ),
			)
		);
	}

	/**
	 * Register admin assets
	 */
	public function register_admin_assets() {
		// Admin CSS.
		wp_register_style( 'decision-polls-admin', DECISION_POLLS_PLUGIN_URL . 'assets/css/admin.css', array(), DECISION_POLLS_VERSION );

		// Admin JS.
		wp_register_script( 'decision-polls-admin', DECISION_POLLS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), DECISION_POLLS_VERSION, true );

		// Localize script with API data.
		wp_localize_script(
			'decision-polls-admin',
			'decisionPollsAPI',
			array(
				'url'   => esc_url_raw( rest_url( $this->api->get_endpoint( 'polls' )->get_namespace() ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

/**
 * Returns the main instance of the plugin.
 *
 * @return Decision_Polls
 */
function decision_polls() {
	return Decision_Polls::instance();
}

// Get the ball rolling.
decision_polls();
