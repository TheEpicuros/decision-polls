<?php
/**
 * Refresh Rules Script
 *
 * This standalone script can be run from the wp-admin (under the plugin) to flush rewrite rules
 * and fix any URL-related issues. This is especially helpful for debugging custom endpoint issues.
 *
 * @package Decision_Polls
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set a flag to show that we're on this page.
define( 'DP_REFRESH_RULES', true );

/**
 * Refresh rewrite rules and display debug information.
 */
function dp_refresh_rules() {
	// Only administrators can flush rules.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'decision-polls' ) );
	}

	// Add header.
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Decision Polls - Refresh Rules', 'decision-polls' ) . '</h1>';
	echo '<p>' . esc_html__( 'This tool helps fix URL routing issues with polls.', 'decision-polls' ) . '</p>';

	// Get current rewrite rules.
	$rules_before = get_option( 'rewrite_rules' );
	
	// Display action taken.
	echo '<h2>' . esc_html__( 'Actions:', 'decision-polls' ) . '</h2>';
	
	// Delete the flag that tracks whether we've flushed already.
	delete_option( 'decision_polls_rewrite_rules_flushed' );
	echo '<p>' . esc_html__( '✅ Cleared rewrite rules flag.', 'decision-polls' ) . '</p>';
	
	// Reload the custom endpoints class to re-register rules.
	if ( class_exists( 'Decision_Polls_Custom_Endpoints' ) ) {
		Decision_Polls_Custom_Endpoints::add_rewrite_rules();
		echo '<p>' . esc_html__( '✅ Re-registered poll rewrite rules.', 'decision-polls' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( '❌ Custom endpoints class not found.', 'decision-polls' ) . '</p>';
	}
	
	// Flush rewrite rules.
	flush_rewrite_rules();
	echo '<p>' . esc_html__( '✅ Flushed WordPress rewrite rules.', 'decision-polls' ) . '</p>';
	
	// Get updated rewrite rules.
	$rules_after = get_option( 'rewrite_rules' );
	
	// Show poll-specific rules for debugging.
	echo '<h2>' . esc_html__( 'Poll-Related Rewrite Rules:', 'decision-polls' ) . '</h2>';
	
	$found_rules = false;
	echo '<ul>';
	
	if ( is_array( $rules_after ) ) {
		foreach ( $rules_after as $pattern => $redirect ) {
			if ( strpos( $pattern, 'poll' ) !== false || strpos( $redirect, 'poll_id' ) !== false || strpos( $redirect, 'poll_action' ) !== false ) {
				echo '<li><strong>' . esc_html( $pattern ) . '</strong> → ' . esc_html( $redirect ) . '</li>';
				$found_rules = true;
			}
		}
	}
	
	if ( ! $found_rules ) {
		echo '<li>' . esc_html__( 'No poll-related rules found. This might indicate a problem.', 'decision-polls' ) . '</li>';
	}
	
	echo '</ul>';
	
	// Show current permalink structure.
	echo '<h2>' . esc_html__( 'Current Settings:', 'decision-polls' ) . '</h2>';
	echo '<p><strong>' . esc_html__( 'Permalink Structure:', 'decision-polls' ) . '</strong> ' . esc_html( get_option( 'permalink_structure' ) ) . '</p>';
	
	// Show test links.
	echo '<h2>' . esc_html__( 'Test Links:', 'decision-polls' ) . '</h2>';
	
	// Poll creation.
	$create_url = home_url( 'poll/create/' );
	echo '<p><strong>' . esc_html__( 'Poll Creation URL:', 'decision-polls' ) . '</strong> <a href="' . esc_url( $create_url ) . '" target="_blank">' . esc_html( $create_url ) . '</a></p>';
	
	// Example poll view.
	$poll_url = home_url( 'poll/1/' );
	echo '<p><strong>' . esc_html__( 'Example Poll URL:', 'decision-polls' ) . '</strong> <a href="' . esc_url( $poll_url ) . '" target="_blank">' . esc_html( $poll_url ) . '</a></p>';
	
	// Return to admin.
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=decision-polls' ) ) . '" class="button button-primary">' . 
		esc_html__( 'Return to Decision Polls Admin', 'decision-polls' ) . '</a></p>';
	
	echo '</div>';
}

// Execute if we're in the admin interface.
if ( is_admin() ) {
	dp_refresh_rules();
}
