<?php
/**
 * Main Admin Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for initializing admin functionality.
 */
class Decision_Polls_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_components();
	}

	/**
	 * Initialize admin components.
	 */
	private function init_components() {
		// Load admin menu handler.
		require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-admin-menus.php';
		new Decision_Polls_Admin_Menus();

		// Load admin polls list handler.
		require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-admin-polls-list.php';
		new Decision_Polls_Admin_Polls_List();

		// Load admin poll editor handler.
		require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-admin-poll-editor.php';
		new Decision_Polls_Admin_Poll_Editor();

		// Load admin settings handler.
		require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-admin-settings.php';
		new Decision_Polls_Admin_Settings();

		// Register admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register admin notices.
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our plugin pages.
		if ( false === strpos( $hook, 'decision-polls' ) ) {
			return;
		}

		wp_enqueue_style( 'decision-polls-admin' );
		wp_enqueue_script( 'decision-polls-admin' );
	}

	/**
	 * Display admin notices.
	 */
	public function display_admin_notices() {
		// Only on our plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'decision-polls' ) ) {
			return;
		}

		// Verify nonce for GET parameters.
		$nonce_verified = false;
		if ( isset( $_GET['_wpnonce'] ) ) {
			$nonce_verified = wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'decision_polls_admin_notice' );
		}

		// Poll deleted notice.
		if ( isset( $_GET['message'] ) && 'deleted' === $_GET['message'] && $nonce_verified ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Poll deleted successfully.', 'decision-polls' ); ?></p>
			</div>
			<?php
		}

		// Poll deletion error.
		if ( isset( $_GET['error'] ) && 'delete_failed' === $_GET['error'] && $nonce_verified ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Failed to delete poll. Please try again.', 'decision-polls' ); ?></p>
			</div>
			<?php
		}
	}
}
