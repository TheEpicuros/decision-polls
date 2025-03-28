<?php
/**
 * Endpoint Test Script
 *
 * This script tests the custom endpoints by generating test URLs
 * and providing diagnostic information.
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set content type to plain text if run from CLI.
if ( PHP_SAPI === 'cli' ) {
	header( 'Content-Type: text/plain' );
}

/**
 * Test poll endpoints and report issues.
 */
function dp_test_endpoints() {
	// Ensure only admins can run tests.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	echo '<div class="wrap">';
	echo '<h1>Decision Polls - Endpoint Test</h1>';
	echo '<p>This utility tests your poll URLs to help diagnose any issues.</p>';

	// Test permalink configuration.
	$using_permalinks = '' !== get_option( 'permalink_structure' );
	if ( ! $using_permalinks ) {
		echo '<div class="notice notice-error"><p><strong>Error:</strong> Custom permalinks are not enabled. This plugin requires permalinks to be enabled for clean URLs to work.</p>';
		echo '<p>Please go to <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Settings → Permalinks</a> and choose any option other than "Plain".</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>✓ Permalinks are enabled with structure: <code>' . esc_html( get_option( 'permalink_structure' ) ) . '</code></p></div>';
	}

	// Get all poll IDs for testing.
	$poll_model = new Decision_Polls_Poll();
	$polls = $poll_model->get_all(
		array(
			'per_page' => 5,
			'order'    => 'DESC',
			'orderby'  => 'created_at',
		)
	);

	// Test create page URL.
	echo '<h2>Poll Creation URL</h2>';
	$create_url = home_url( 'poll/create/' );
	echo '<p>Testing URL: <a href="' . esc_url( $create_url ) . '" target="_blank">' . esc_html( $create_url ) . '</a></p>';

	// Test accessible poll URLs.
	echo '<h2>Poll View URLs</h2>';
	if ( empty( $polls['data'] ) ) {
		echo '<p>No polls found to test. Please create a poll first.</p>';
	} else {
		echo '<ul>';
		foreach ( $polls['data'] as $poll ) {
			$poll_url = home_url( 'poll/' . absint( $poll['id'] ) . '/' );
			$poll_status = dp_check_url( $poll_url );
			
			echo '<li>';
			echo 'Poll #' . absint( $poll['id'] ) . ' - ' . esc_html( $poll['title'] ) . ':<br>';
			echo 'URL: <a href="' . esc_url( $poll_url ) . '" target="_blank">' . esc_html( $poll_url ) . '</a><br>';
			
			if ( 'ok' === $poll_status['status'] ) {
				echo '<span style="color: green;">✓ Working correctly</span>';
			} else {
				echo '<span style="color: red;">✗ Error: ' . esc_html( $poll_status['message'] ) . '</span>';
			}
			
			echo '</li>';
		}
		echo '</ul>';
	}

	// Test fallback URLs.
	echo '<h2>Fallback URL Format</h2>';
	if ( empty( $polls['data'] ) ) {
		echo '<p>No polls found to test fallback URLs.</p>';
	} else {
		echo '<ul>';
		foreach ( $polls['data'] as $poll ) {
			$poll_url = add_query_arg( array( 'poll_id' => absint( $poll['id'] ) ), home_url() );
			$poll_status = dp_check_url( $poll_url );
			
			echo '<li>';
			echo 'Poll #' . absint( $poll['id'] ) . ' - Query Parameter URL:<br>';
			echo 'URL: <a href="' . esc_url( $poll_url ) . '" target="_blank">' . esc_html( $poll_url ) . '</a><br>';
			
			if ( 'ok' === $poll_status['status'] ) {
				echo '<span style="color: green;">✓ Working correctly</span>';
			} else {
				echo '<span style="color: red;">✗ Error: ' . esc_html( $poll_status['message'] ) . '</span>';
			}
			
			echo '</li>';
		}
		echo '</ul>';
	}

	// Test results display.
	echo '<h2>Results URLs</h2>';
	if ( empty( $polls['data'] ) ) {
		echo '<p>No polls found to test results URLs.</p>';
	} else {
		echo '<ul>';
		foreach ( $polls['data'] as $poll ) {
			$results_url = add_query_arg( array( 'show_results' => '1' ), home_url( 'poll/' . absint( $poll['id'] ) . '/' ) );
			$poll_status = dp_check_url( $results_url );
			
			echo '<li>';
			echo 'Poll #' . absint( $poll['id'] ) . ' - Results URL:<br>';
			echo 'URL: <a href="' . esc_url( $results_url ) . '" target="_blank">' . esc_html( $results_url ) . '</a><br>';
			
			if ( 'ok' === $poll_status['status'] ) {
				echo '<span style="color: green;">✓ Working correctly</span>';
			} else {
				echo '<span style="color: red;">✗ Error: ' . esc_html( $poll_status['message'] ) . '</span>';
			}
			
			echo '</li>';
		}
		echo '</ul>';
	}

	// Display rewrite rules registered.
	$rules = get_option( 'rewrite_rules' );
	echo '<h2>Active Rewrite Rules</h2>';
	echo '<p>These are the poll-related rewrite rules currently active:</p>';
	
	echo '<ul>';
	$found_rules = false;
	if ( is_array( $rules ) ) {
		foreach ( $rules as $pattern => $redirect ) {
			if ( strpos( $pattern, 'poll/' ) === 0 || strpos( $redirect, 'poll_id=' ) !== false || strpos( $redirect, 'poll_action=' ) !== false ) {
				echo '<li><code>' . esc_html( $pattern ) . '</code> → <code>' . esc_html( $redirect ) . '</code></li>';
				$found_rules = true;
			}
		}
	}
	
	if ( ! $found_rules ) {
		echo '<li><strong>No poll rules found!</strong> Try refreshing your rewrite rules.</li>';
	}
	echo '</ul>';

	// Display troubleshooting information.
	echo '<h2>Troubleshooting</h2>';
	
	echo '<p>If you\'re experiencing problems with poll URLs, try these steps:</p>';
	
	echo '<ol>';
	echo '<li>Visit <a href="' . esc_url( admin_url( 'admin.php?page=decision-polls-refresh-rules' ) ) . '">Refresh Rules</a> to update rewrite rules.</li>';
	echo '<li>Go to <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Settings → Permalinks</a> and click "Save Changes" without changing anything.</li>';
	echo '<li>Check if you have any permalink-related plugins that might be conflicting.</li>';
	echo '<li>Try temporarily switching to a default WordPress theme to rule out theme conflicts.</li>';
	echo '<li>Look at <code>wp-content/dp-debug.log</code> for detailed error information.</li>';
	echo '</ol>';
	
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=decision-polls' ) ) . '" class="button button-primary">Return to Decision Polls</a></p>';
	
	echo '</div>';
}

/**
 * Check if a URL is accessible.
 *
 * @param string $url The URL to check.
 * @return array Status information about the URL.
 */
function dp_check_url( $url ) {
	$args = array(
		'timeout'     => 5,
		'redirection' => 0,
		'sslverify'   => false,
	);
	
	$response = wp_remote_get( $url, $args );
	
	if ( is_wp_error( $response ) ) {
		return array(
			'status'  => 'error',
			'message' => $response->get_error_message(),
		);
	}
	
	$status_code = wp_remote_retrieve_response_code( $response );
	
	if ( 404 === $status_code ) {
		return array(
			'status'  => 'error',
			'message' => '404 Not Found - URL is not accessible',
		);
	}
	
	return array(
		'status'  => 'ok',
		'message' => 'URL is accessible',
	);
}

// Run tests if we're in the admin interface.
if ( is_admin() ) {
	dp_test_endpoints();
}
