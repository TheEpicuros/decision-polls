<?php
// Simple table activation script

// Define database connection parameters
// You'll need to update these to match your Local environment
$db_host = 'localhost';
$db_port = '10016'; // Use the port specified in the user's message
$db_name = 'local';
$db_user = 'root';
$db_password = 'root';
$table_prefix = 'wp_';

echo "Connecting to database...\n";

// Connect to the database
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

// Check connection
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

echo "Connected successfully!\n";

// Check for existing tables
$tables = [
    $table_prefix . 'decision_polls',
    $table_prefix . 'decision_poll_answers',
    $table_prefix . 'decision_poll_votes',
    $table_prefix . 'decision_poll_results'
];

$tables_exist = true;
foreach ( $tables as $table ) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    $table_exists = $result && $result->num_rows > 0;
    echo "Table $table " . ($table_exists ? "exists" : "does not exist") . "\n";
    
    if (!$table_exists) {
        $tables_exist = false;
    }
}

if (!$tables_exist) {
    echo "Creating missing tables...\n";
    
    // Get WordPress database collation - usually utf8mb4_unicode_ci
    $charset_collate = "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    // SQL for polls table
    $polls_table = $table_prefix . 'decision_polls';
    $polls_sql = "CREATE TABLE $polls_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        poll_type varchar(20) NOT NULL DEFAULT 'standard',
        multiple_choices int(11) DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'draft',
        author_id bigint(20) unsigned DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        starts_at datetime DEFAULT NULL,
        expires_at datetime DEFAULT NULL,
        is_private tinyint(1) DEFAULT 0,
        allow_comments tinyint(1) DEFAULT 1,
        meta longtext,
        PRIMARY KEY  (id),
        KEY author_id (author_id),
        KEY poll_type (poll_type),
        KEY status (status)
    ) $charset_collate;";
    
    // SQL for answers table
    $answers_table = $table_prefix . 'decision_poll_answers';
    $answers_sql = "CREATE TABLE $answers_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        poll_id bigint(20) unsigned NOT NULL,
        answer_text text NOT NULL,
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        meta longtext,
        PRIMARY KEY  (id),
        KEY poll_id (poll_id)
    ) $charset_collate;";
    
    // SQL for votes table
    $votes_table = $table_prefix . 'decision_poll_votes';
    $votes_sql = "CREATE TABLE $votes_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        poll_id bigint(20) unsigned NOT NULL,
        answer_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        user_ip varchar(100) DEFAULT NULL,
        vote_value int(11) DEFAULT 1,
        voted_at datetime DEFAULT CURRENT_TIMESTAMP,
        meta longtext,
        PRIMARY KEY  (id),
        KEY poll_id (poll_id),
        KEY answer_id (answer_id),
        KEY user_id (user_id),
        KEY poll_user (poll_id, user_id)
    ) $charset_collate;";
    
    // SQL for results table (cached results for performance)
    $results_table = $table_prefix . 'decision_poll_results';
    $results_sql = "CREATE TABLE $results_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        poll_id bigint(20) unsigned NOT NULL,
        answer_id bigint(20) unsigned NOT NULL,
        votes_count int(11) DEFAULT 0,
        percentage decimal(5,2) DEFAULT 0.00,
        last_calculated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY poll_answer (poll_id, answer_id),
        KEY poll_id (poll_id)
    ) $charset_collate;";
    
    // Create the tables
    $mysqli->query($polls_sql);
    $mysqli->query($answers_sql);
    $mysqli->query($votes_sql);
    $mysqli->query($results_sql);
    
    // Check tables again
    echo "Checking tables after creation...\n";
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        $table_exists = $result && $result->num_rows > 0;
        echo "Table $table " . ($table_exists ? "now exists" : "failed to create") . "\n";
    }
}

echo "Database setup complete!\n";

// Create a test poll
echo "Creating a test poll...\n";

$poll_title = "Test Poll " . date('Y-m-d H:i:s');
$poll_description = "This is a test poll created by the activation script";
$poll_type = "standard";
$poll_status = "published";

// Insert the poll
$stmt = $mysqli->prepare("INSERT INTO " . $table_prefix . "decision_polls (title, description, poll_type, status, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("ssss", $poll_title, $poll_description, $poll_type, $poll_status);
$stmt->execute();
$poll_id = $mysqli->insert_id;

if ($poll_id) {
    echo "Successfully created poll with ID: $poll_id\n";
    
    // Add poll options
    $answers = [
        "Option One",
        "Option Two",
        "Option Three"
    ];
    
    foreach ($answers as $index => $answer_text) {
        $stmt = $mysqli->prepare("INSERT INTO " . $table_prefix . "decision_poll_answers (poll_id, answer_text, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $poll_id, $answer_text, $index);
        $stmt->execute();
        $answer_id = $mysqli->insert_id;
        echo "Created poll option: $answer_text with ID: $answer_id\n";
    }
    
    echo "Test poll created successfully! You can now test the frontend functionality.\n";
} else {
    echo "Failed to create test poll: " . $mysqli->error . "\n";
}

$mysqli->close();
