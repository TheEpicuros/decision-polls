<?php
/**
 * Installer Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle plugin installation and updates
 */
class Decision_Polls_Install {

    /**
     * Database version
     */
    private static $db_version = '1.0';

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create or update database tables
        self::create_tables();
        
        // Add initial options
        self::add_options();
        
        // Set current database version
        update_option('decision_polls_db_version', self::$db_version);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        // Get WordPress database collation
        $charset_collate = $wpdb->get_charset_collate();
        
        // Define table names
        $polls_table = $wpdb->prefix . 'decision_polls';
        $answers_table = $wpdb->prefix . 'decision_poll_answers';
        $votes_table = $wpdb->prefix . 'decision_poll_votes';
        $results_table = $wpdb->prefix . 'decision_poll_results';
        
        // SQL for polls table
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
        
        // Include WordPress database upgrade functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create the tables
        dbDelta($polls_sql);
        dbDelta($answers_sql);
        dbDelta($votes_sql);
        dbDelta($results_sql);
    }
    
    /**
     * Add default plugin options
     */
    private static function add_options() {
        // General settings
        $options = array(
            'decision_polls_version' => DECISION_POLLS_VERSION,
            'decision_polls_allow_guests' => 1,
            'decision_polls_results_view' => 'after_vote',  // 'after_vote', 'after_end', 'always'
            'decision_polls_default_poll_type' => 'standard', // 'standard', 'multiple', 'ranked'
            'decision_polls_require_login_to_create' => 1,
            'decision_polls_poll_archive_page' => 0,
            'decision_polls_enable_poll_comments' => 1,
            'decision_polls_enable_social_sharing' => 1,
            'decision_polls_allow_frontend_creation' => 1,
        );
        
        // Add options if they don't exist
        foreach ($options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
        
        // Create default capabilities for users
        self::add_capabilities();
    }
    
    /**
     * Add custom capabilities to roles
     */
    private static function add_capabilities() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Administrator capabilities
        $admin_caps = array(
            'create_decision_polls',
            'edit_decision_polls',
            'edit_others_decision_polls',
            'publish_decision_polls',
            'read_private_decision_polls',
            'delete_decision_polls',
            'delete_private_decision_polls',
            'delete_others_decision_polls',
            'manage_decision_polls',
        );
        
        // Editor capabilities
        $editor_caps = array(
            'create_decision_polls',
            'edit_decision_polls',
            'edit_others_decision_polls',
            'publish_decision_polls',
            'read_private_decision_polls',
            'delete_decision_polls',
        );
        
        // Author capabilities
        $author_caps = array(
            'create_decision_polls',
            'edit_decision_polls',
            'publish_decision_polls',
            'delete_decision_polls',
        );
        
        // Add administrator capabilities
        foreach ($admin_caps as $cap) {
            $wp_roles->add_cap('administrator', $cap);
        }
        
        // Add editor capabilities
        foreach ($editor_caps as $cap) {
            $wp_roles->add_cap('editor', $cap);
        }
        
        // Add author capabilities
        foreach ($author_caps as $cap) {
            $wp_roles->add_cap('author', $cap);
        }
    }
}
