<?php
/**
 * Simple Utility script to refresh rewrite rules
 *
 * This script forces WordPress to flush and regenerate rewrite rules
 * without requiring a nonce for the first run.
 *
 * @package Decision_Polls
 */

// Display page header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Decision Polls - Rewrite Rules Refresher</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #3498db; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .instructions { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Decision Polls - Rewrite Rules Refresher</h1>';

// Try to bootstrap WordPress
$wp_load_paths = array(
    __DIR__ . '/../../../wp-load.php', // Standard path
    __DIR__ . '/../../../../wp-load.php', // Another common path
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php' // Using dirname for compatibility
);

$wordpress_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wordpress_loaded = true;
        break;
    }
}

if (!$wordpress_loaded) {
    echo '<p class="error">⚠️ Could not find WordPress installation. This script should be placed in the decision-polls plugin directory.</p>';
    echo '<p>Attempted to load from:</p><ul>';
    foreach ($wp_load_paths as $path) {
        echo '<li>' . htmlspecialchars($path) . '</li>';
    }
    echo '</ul>';
    echo '</body></html>';
    exit;
}

// Display current path information
echo '<p>Script located at: ' . htmlspecialchars(__FILE__) . '</p>';
echo '<p>Current working directory: ' . htmlspecialchars(getcwd()) . '</p>';

// Only proceed if WordPress is loaded successfully
if (function_exists('flush_rewrite_rules') && function_exists('delete_option')) {
    // Flush rewrite rules
    flush_rewrite_rules(true);
    
    // Clear the rewrite rules flushed flag
    delete_option('decision_polls_rewrite_rules_flushed');
    
    echo '<p class="success">✅ Rewrite rules have been successfully flushed.</p>';
    
    echo '<div class="instructions">
        <h2>Next Steps:</h2>
        <ol>
            <li>Go to <a href="' . esc_url(admin_url('options-permalink.php')) . '">Permalinks Settings</a> in your WordPress admin.</li>
            <li>Simply click "Save Changes" to ensure WordPress updates its internal rules.</li>
            <li>Test that polls are working correctly with redirection after voting.</li>
            <li>Remove this script from your server for security.</li>
        </ol>
    </div>';
} else {
    echo '<p class="error">⚠️ WordPress core functions not available. Make sure this script is placed in the correct plugin directory.</p>';
}

echo '<p>Done. <a href="' . esc_url(home_url('/polls/')) . '">Go back to polls</a></p>';
echo '</body></html>';
