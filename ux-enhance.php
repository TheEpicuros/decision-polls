<?php
/**
 * UX Enhancement and Shortcode Fixes
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixes for Decision Polls UX and Shortcode Issues
 */
class Decision_Polls_UX_Enhancements {

	/**
	 * Initialize the enhancements.
	 */
	public static function init() {
		// Register UX improvements stylesheet.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_ux_styles' ) );
		
		// Fix shortcode output on the polls page.
		add_filter( 'the_content', array( __CLASS__, 'fix_shortcode_content' ), 99 );
		
		// Add custom wrapper around poll lists for better spacing.
		add_filter( 'decision_polls_before_list', array( __CLASS__, 'add_polls_wrapper_start' ) );
		add_filter( 'decision_polls_after_list', array( __CLASS__, 'add_polls_wrapper_end' ) );
	}

	/**
	 * Register and enqueue UX improvement styles.
	 */
	public static function register_ux_styles() {
		$css_file = 'assets/css/ux-improvements.css';
		$css_path = plugin_dir_path( __FILE__ ) . $css_file;
		
		wp_register_style(
			'decision-polls-ux-improvements',
			plugins_url( $css_file, __FILE__ ),
			array( 'decision-polls' ),
			file_exists( $css_path ) ? filemtime( $css_path ) : DECISION_POLLS_VERSION
		);
		
		wp_enqueue_style( 'decision-polls-ux-improvements' );
	}

	/**
	 * Fix shortcode content on polls page.
	 *
	 * @param string $content The post content.
	 * @return string Modified post content.
	 */
	public static function fix_shortcode_content( $content ) {
		// Only apply on pages with our shortcode.
		if ( ! is_page() || ! has_shortcode( $content, 'decision_polls' ) ) {
			return $content;
		}
		
		// If we just see the raw shortcode, process it.
		if ( trim( $content ) === '[decision_polls]' ) {
			// Remove the shortcode to prevent double processing.
			remove_shortcode( 'decision_polls' );
			
			// Get the list of polls from our shortcode function.
			$polls_list_output = Decision_Polls_Shortcodes::polls_list_shortcode( array() );
			
			// Re-add the shortcode for other uses.
			add_shortcode( 'decision_polls', array( 'Decision_Polls_Shortcodes', 'polls_list_shortcode' ) );
			
			// Add our wrapper.
			$output = apply_filters( 'decision_polls_before_list', '' );
			$output .= $polls_list_output;
			$output .= apply_filters( 'decision_polls_after_list', '' );
			
			return $output;
		}
		
		return $content;
	}

	/**
	 * Add wrapper start for polls list.
	 *
	 * @return string HTML wrapper start.
	 */
	public static function add_polls_wrapper_start() {
		return '<div class="decision-polls-enhanced-container">';
	}

	/**
	 * Add wrapper end for polls list.
	 *
	 * @return string HTML wrapper end.
	 */
	public static function add_polls_wrapper_end() {
		return '</div>';
	}
}

// Initialize the enhancements.
Decision_Polls_UX_Enhancements::init();

/**
 * Add a custom admin notice about the UX improvements.
 */
function decision_polls_ux_admin_notice() {
	// Only show to administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Get current screen.
	$screen = get_current_screen();
	
	// Only show on plugins page or our plugin pages.
	$valid_screens = array( 'plugins', 'toplevel_page_decision-polls', 'decision-polls_page_decision-polls-create' );
	if ( ! $screen || ! in_array( $screen->id, $valid_screens, true ) ) {
		return;
	}
	
	// Check if notice has been dismissed.
	$dismissed = get_option( 'decision_polls_ux_notice_dismissed', false );
	if ( $dismissed ) {
		return;
	}
	
	?>
	<div class="notice notice-info is-dismissible" id="decision-polls-ux-notice">
		<h3 style="margin-top: 0.5em; margin-bottom: 0.5em;">Decision Polls UX Improvements</h3>
		<p>
			<?php esc_html_e( 'Your Decision Polls plugin has been enhanced with improved user experience features!', 'decision-polls' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'The enhanced version includes:', 'decision-polls' ); ?>
		</p>
		<ul style="list-style: disc; padding-left: 2em;">
			<li><?php esc_html_e( 'Better visual design for polls and results', 'decision-polls' ); ?></li>
			<li><?php esc_html_e( 'Improved mobile responsiveness', 'decision-polls' ); ?></li>
			<li><?php esc_html_e( 'Enhanced accessibility features', 'decision-polls' ); ?></li>
			<li><?php esc_html_e( 'Fixed poll display and redirection issues', 'decision-polls' ); ?></li>
		</ul>
		<p>
			<?php esc_html_e( 'Visit your polls page to see the improvements!', 'decision-polls' ); ?>
		</p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'decision-polls' ); ?></span>
		</button>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#decision-polls-ux-notice .notice-dismiss').on('click', function() {
			$.ajax({
				url: ajaxurl,
				data: {
					action: 'dismiss_decision_polls_ux_notice'
				}
			});
		});
	});
	</script>
	<?php
}
add_action( 'admin_notices', 'decision_polls_ux_admin_notice' );

/**
 * AJAX handler for dismissing the admin notice.
 */
function dismiss_decision_polls_ux_notice_handler() {
	// Only administrators can dismiss the notice.
	if ( current_user_can( 'manage_options' ) ) {
		update_option( 'decision_polls_ux_notice_dismissed', true );
	}
	wp_die();
}
add_action( 'wp_ajax_dismiss_decision_polls_ux_notice', 'dismiss_decision_polls_ux_notice_handler' );
