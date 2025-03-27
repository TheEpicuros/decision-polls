<?php
/**
 * Debug Database Script
 * 
 * This script checks database tables and creates a test poll.
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '../../../../wp-load.php';

// Only allow admins to run this script
if (!current_user_can('manage_options')) {
    die('You do not have permission to run this script.');
}

echo '<pre>';
echo "Decision Polls Database Debug\n";
echo "============================\n\n";

// Check if tables exist
global $wpdb;
$tables = [
    $wpdb->prefix . 'decision_polls',
    $wpdb->prefix . 'decision_poll_answers',
    $wpdb->prefix . 'decision_poll_votes',
    $wpdb->prefix . 'decision_poll_results'
];

$tables_exist = true;

echo "Checking tables:\n";
echo "----------------\n";
foreach ($tables as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "Table $table " . ($table_exists ? "exists" : "does not exist") . "\n";
    
    if (!$table_exists) {
        $tables_exist = false;
    }
}

if (!$tables_exist) {
    echo "\nSome tables are missing. Creating tables...\n";
    
    // Include the install class
    require_once 'includes/core/class-install.php';
    Decision_Polls_Install::create_tables();
    
    echo "\nChecking tables again:\n";
    echo "----------------\n";
    foreach ($tables as $table) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        echo "Table $table " . ($table_exists ? "now exists" : "still does not exist") . "\n";
    }
}

// Create a test poll
echo "\nCreating a test poll...\n";
echo "----------------\n";

// Include poll model
require_once 'includes/core/models/class-model.php';
require_once 'includes/core/models/class-poll.php';

$poll_model = new Decision_Polls_Poll();

$poll_data = [
    'title' => 'Test Poll ' . date('Y-m-d H:i:s'),
    'description' => 'This is a test poll created by the debug script',
    'type' => 'standard',
    'status' => 'published',
    'answers' => [
        'Option One',
        'Option Two',
        'Option Three'
    ],
    'is_private' => false
];

$result = $poll_model->create($poll_data);

if (is_wp_error($result)) {
    echo "Error creating poll: " . $result->get_error_message() . "\n";
} else {
    echo "Test poll created with ID: " . $result['id'] . "\n";
    echo "Title: " . $result['title'] . "\n";
    echo "Type: " . $result['type'] . "\n";
    
    echo "\nPoll answers:\n";
    foreach ($result['answers'] as $answer) {
        echo "- " . $answer['text'] . " (ID: " . $answer['id'] . ")\n";
    }
    
    echo "\nYou can view this poll using the shortcode:\n";
    echo '[decision_poll id="' . $result['id'] . '"]' . "\n";
}

// Check plugin options
echo "\nChecking plugin options:\n";
echo "----------------\n";
$options = [
    'decision_polls_version',
    'decision_polls_allow_guests',
    'decision_polls_results_view',
    'decision_polls_default_poll_type',
    'decision_polls_require_login_to_create',
    'decision_polls_allow_frontend_creation'
];

foreach ($options as $option) {
    $value = get_option($option);
    echo "$option: " . ($value !== false ? var_export($value, true) : "not set") . "\n";
}

// Create options if missing
if (get_option('decision_polls_version') === false) {
    echo "\nCreating default options...\n";
    require_once 'includes/core/class-install.php';
    Decision_Polls_Install::add_options();
    
    echo "\nOptions after creation:\n";
    foreach ($options as $option) {
        $value = get_option($option);
        echo "$option: " . ($value !== false ? var_export($value, true) : "still not set") . "\n";
    }
}

echo "\nDebug completed!\n";
echo '</pre>';
