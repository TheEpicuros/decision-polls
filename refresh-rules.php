<?php
/**
 * Utility script to refresh rewrite rules
 *
 * This script forces WordPress to flush and regenerate rewrite rules,
 * which is especially helpful after modifying custom URL handling.
 *
 * Can be run in browser or via WP-CLI.
 *
 * @package Decision_Polls
 */

// Security check if run from browser.
if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'decision_polls_refresh_rules' ) ) {
		die( 'Security check failed. Please use a valid nonce parameter.' );
	}
}

// Bootstrap WordPress.
$wp_load_file = __DIR__ . '/../../../wp-load.php';
if ( ! file_exists( $wp_load_file ) ) {
	die( 'Could not find WordPress installation. Make sure this script is in the correct location.' );
}
require_once $wp_load_file;

// Only allow administrators to run this script.
if ( ! current_user_can( 'manage_options' ) ) {
	die( 'Sorry, you do not have permission to run this script.' );
}

// Flush rewrite rules.
flush_rewrite_rules( true );

// Clear the rewrite rules flushed flag to force regeneration on next request.
delete_option( 'decision_polls_rewrite_rules_flushed' );

// Generate a new nonce for future use.
$new_nonce = wp_create_nonce( 'decision_polls_refresh_rules' );

// Output messages.
if ( php_sapi_name() === 'cli' ) {
	// Command line output.
	echo "Rewrite rules have been flushed successfully.\n";
	echo "Please visit the Permalinks settings page in WordPress admin to complete the process.\n";
} else {
	// Browser output.
	echo '<!DOCTYPE html>
<html>
<head>
    <title>Decision Polls - Rewrite Rules Refreshed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #3498db; }
        .success { color: #27ae60; }
        .instructions { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Decision Polls - Rewrite Rules Refreshed</h1>
    <p class="success">✅ Rewrite rules have been successfully flushed.</p>
    
    <div class="instructions">
        <h2>Next Steps:</h2>
        <ol>
            <li>Go to <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Permalinks Settings</a> in your WordPress admin.</li>
            <li>Simply click "Save Changes" to ensure WordPress updates its internal rules.</li>
            <li>Test that polls are working correctly with the "show results" feature.</li>
        </ol>
        
        <h2>How to Run This on Your Live Site:</h2>
        <ol>
            <li>Upload this file to your WordPress plugin directory: <code>/wp-content/plugins/decision-polls/</code></li>
            <li>Run it by visiting in your browser: <code>https://your-domain.com/wp-content/plugins/decision-polls/refresh-rules.php?nonce=' . $new_nonce . '</code></li>
            <li>Or via SSH/command line: <code>php /path/to/wp-content/plugins/decision-polls/refresh-rules.php</code></li>
            <li>Remember to <strong>delete this file</strong> after you\'re done for security reasons.</li>
        </ol>
    </div>
    
    <p><a href="' . esc_url( admin_url() ) . '">Return to WordPress Dashboard</a></p>
</body>
</html>';
}
