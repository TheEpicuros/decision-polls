<?php
/**
 * Decision Polls Debug Helper
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to debug poll-related issues.
 */
class DP_Debug {

	/**
	 * Initialize the debug logger.
	 */
	public static function init() {
		// Log at various WordPress hooks to see exactly where issues happen.
		add_action( 'init', array( __CLASS__, 'log_hook_data' ), 999 );
		add_action( 'parse_request', array( __CLASS__, 'log_parse_request' ), 999 );
		add_action( 'parse_query', array( __CLASS__, 'log_parse_query' ), 999 );
		add_action( 'pre_get_posts', array( __CLASS__, 'log_pre_get_posts' ), 999 );
		add_action( 'template_redirect', array( __CLASS__, 'log_template_redirect' ), 999 );
		
		// Check global post when errors frequently occur.
		add_action( 'wp', array( __CLASS__, 'log_wp_object' ), 999 );
		
		// Monitor link generation which is causing errors.
		add_filter( 'post_type_link', array( __CLASS__, 'log_post_type_link' ), 999, 4 );
		add_filter( 'post_link', array( __CLASS__, 'log_post_link' ), 999, 3 );
		add_filter( 'page_link', array( __CLASS__, 'log_page_link' ), 999, 3 );
		
		// Register custom error handler.
		set_error_handler( array( __CLASS__, 'custom_error_handler' ) );
	}

	/**
	 * Log data at the init hook.
	 */
	public static function log_hook_data() {
		self::log( 'Hook: init', 'Current URL: ' . self::get_current_url() );
	}

	/**
	 * Log data at parse_request.
	 *
	 * @param WP $wp The WP object.
	 */
	public static function log_parse_request( $wp ) {
		self::log( 'Hook: parse_request', array(
			'url' => self::get_current_url(),
			'matched_query' => isset( $wp->matched_query ) ? $wp->matched_query : 'NOT SET',
			'matched_rule' => isset( $wp->matched_rule ) ? $wp->matched_rule : 'NOT SET', 
			'query_vars' => $wp->query_vars,
		));
	}

	/**
	 * Log data at parse_query.
	 *
	 * @param WP_Query $query The query object.
	 */
	public static function log_parse_query( $query ) {
		if ( $query->is_main_query() ) {
			self::log( 'Hook: parse_query (main)', array(
				'query_vars' => $query->query_vars,
				'query' => $query->query,
				'is_404' => $query->is_404(),
				'is_page' => $query->is_page(),
				'post' => isset( $GLOBALS['post'] ) ? self::get_post_info( $GLOBALS['post'] ) : 'NOT SET',
			));
		}
	}

	/**
	 * Log data at pre_get_posts.
	 *
	 * @param WP_Query $query The query object.
	 */
	public static function log_pre_get_posts( $query ) {
		if ( $query->is_main_query() ) {
			self::log( 'Hook: pre_get_posts (main)', array(
				'query_vars' => $query->query_vars,
				'is_404' => $query->is_404(),
				'is_page' => $query->is_page(),
				'post' => isset( $GLOBALS['post'] ) ? self::get_post_info( $GLOBALS['post'] ) : 'NOT SET',
			));
		}
	}

	/**
	 * Log data at template_redirect.
	 */
	public static function log_template_redirect() {
		global $wp_query, $post;
		
		self::log( 'Hook: template_redirect', array(
			'url' => self::get_current_url(),
			'is_404' => is_404(),
			'is_page' => is_page(),
			'post' => isset( $post ) ? self::get_post_info( $post ) : 'NOT SET',
			'wp_query->post' => isset( $wp_query->post ) ? self::get_post_info( $wp_query->post ) : 'NOT SET',
			'queried_object' => isset( $wp_query->queried_object ) ? self::get_post_info( $wp_query->queried_object ) : 'NOT SET',
		));
	}

	/**
	 * Log the WP object at wp hook.
	 *
	 * @param WP $wp The WP object.
	 */
	public static function log_wp_object( $wp ) {
		global $post;
		
		// This is where link-template.php errors often occur.
		self::log( 'Hook: wp (common error location)', array(
			'post' => isset( $post ) ? self::get_post_info( $post ) : 'NOT SET',
			'query_vars' => $wp->query_vars,
			'matched_rule' => isset( $wp->matched_rule ) ? $wp->matched_rule : 'NOT SET',
		));
	}

	/**
	 * Log post_type_link filter.
	 * 
	 * @param string  $post_link The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 * @param bool    $sample    Is it a sample permalink.
	 * @return string The unchanged link.
	 */
	public static function log_post_type_link( $post_link, $post, $leavename, $sample ) {
		self::log( 'Filter: post_type_link', array(
			'link' => $post_link,
			'post' => self::get_post_info( $post ),
			'backtrace' => self::get_backtrace(),
		));
		return $post_link;
	}

	/**
	 * Log post_link filter.
	 * 
	 * @param string  $permalink The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 * @return string The unchanged permalink.
	 */
	public static function log_post_link( $permalink, $post, $leavename ) {
		self::log( 'Filter: post_link', array(
			'link' => $permalink,
			'post' => self::get_post_info( $post ),
		));
		return $permalink;
	}

	/**
	 * Log page_link filter.
	 * 
	 * @param string  $permalink The post's permalink.
	 * @param int     $post_id   The post ID.
	 * @param bool    $sample    Is it a sample permalink.
	 * @return string The unchanged permalink.
	 */
	public static function log_page_link( $permalink, $post_id, $sample ) {
		if ( strpos( $permalink, 'poll_id=' ) !== false || strpos( $permalink, '/poll/' ) !== false ) {
			self::log( 'Filter: page_link (poll related)', array(
				'link' => $permalink,
				'post_id' => $post_id,
				'backtrace' => self::get_backtrace(),
			));
		}
		return $permalink;
	}

	/**
	 * Custom error handler to log errors with backtrace.
	 * 
	 * @param int    $errno   Error number.
	 * @param string $errstr  Error message.
	 * @param string $errfile File where error occurred.
	 * @param int    $errline Line number where error occurred.
	 * @return bool Whether to continue with PHP internal error handler.
	 */
	public static function custom_error_handler( $errno, $errstr, $errfile, $errline ) {
		// Only log errors related to our issues.
		if ( strpos( $errstr, 'post_type' ) !== false || 
			 strpos( $errstr, 'on null' ) !== false || 
			 strpos( $errfile, 'link-template.php' ) !== false ||
			 strpos( $errfile, 'class-wp.php' ) !== false ) {
			
			self::log( 'ERROR in ' . basename( $errfile ) . ':' . $errline, array(
				'message' => $errstr,
				'url' => self::get_current_url(),
				'global_post' => isset( $GLOBALS['post'] ) ? self::get_post_info( $GLOBALS['post'] ) : 'NOT SET',
				'backtrace' => self::get_backtrace(),
			));
		}
		// Return false to allow the standard PHP error handler to log the error as well.
		return false;
	}

	/**
	 * Get information about a post object.
	 * 
	 * @param mixed $post The post object.
	 * @return array|string Information about the post.
	 */
	private static function get_post_info( $post ) {
		if ( ! is_object( $post ) ) {
			return is_array( $post ) ? 'POST IS ARRAY: ' . print_r( $post, true ) : 'NOT AN OBJECT: ' . gettype( $post );
		}
		
		return array(
			'ID' => isset( $post->ID ) ? $post->ID : 'NOT SET',
			'post_type' => isset( $post->post_type ) ? $post->post_type : 'NOT SET',
			'post_status' => isset( $post->post_status ) ? $post->post_status : 'NOT SET',
			'post_name' => isset( $post->post_name ) ? $post->post_name : 'NOT SET',
			'guid' => isset( $post->guid ) ? $post->guid : 'NOT SET',
			'object_type' => get_class( $post ),
		);
	}

	/**
	 * Get a simplified backtrace.
	 * 
	 * @return array Simplified backtrace.
	 */
	private static function get_backtrace() {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 7 );
		$simplified = array();
		
		// Skip the first element as it's this function.
		array_shift( $backtrace );
		// Skip the next element as it's the calling function in this class.
		array_shift( $backtrace );
		
		foreach ( $backtrace as $trace ) {
			$simplified[] = array(
				'file' => isset( $trace['file'] ) ? basename( $trace['file'] ) : 'unknown file',
				'line' => isset( $trace['line'] ) ? $trace['line'] : 'unknown line',
				'function' => isset( $trace['function'] ) ? $trace['function'] : 'unknown function',
				'class' => isset( $trace['class'] ) ? $trace['class'] : '',
			);
		}
		
		return $simplified;
	}

	/**
	 * Get the current URL.
	 * 
	 * @return string The current URL.
	 */
	private static function get_current_url() {
		$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		
		return $protocol . '://' . $host . $uri;
	}

	/**
	 * Log a message to the debug.log file.
	 * 
	 * @param string $title   Title for the log entry.
	 * @param mixed  $data    Data to log.
	 */
	private static function log( $title, $data ) {
		$log_path = WP_CONTENT_DIR . '/dp-debug.log';
		
		$time = date( 'Y-m-d H:i:s' );
		$message = "\n[{$time}] === {$title} ===\n";
		
		if ( is_array( $data ) || is_object( $data ) ) {
			$message .= print_r( $data, true );
		} else {
			$message .= $data . "\n";
		}
		
		error_log( $message, 3, $log_path );
	}
}

// Initialize the debug logger.
add_action( 'plugins_loaded', array( 'DP_Debug', 'init' ) );
