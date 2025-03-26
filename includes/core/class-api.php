<?php
/**
 * API Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main API class for Decision Polls
 */
class Decision_Polls_API {
    /**
     * API endpoints instances
     *
     * @var array
     */
    private $endpoints = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_endpoints();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load models
        require_once DECISION_POLLS_PATH . 'includes/core/models/class-model.php';
        require_once DECISION_POLLS_PATH . 'includes/core/models/class-poll.php';
        require_once DECISION_POLLS_PATH . 'includes/core/models/class-vote.php';
        
        // Load permissions
        require_once DECISION_POLLS_PATH . 'includes/core/permissions/class-api-permissions.php';
        
        // Load API base class
        require_once DECISION_POLLS_PATH . 'includes/core/api/class-api-base.php';
        
        // Load API endpoint classes
        require_once DECISION_POLLS_PATH . 'includes/core/api/class-api-polls.php';
        require_once DECISION_POLLS_PATH . 'includes/core/api/class-api-votes.php';
    }

    /**
     * Initialize API endpoints
     */
    private function init_endpoints() {
        $this->endpoints['polls'] = new Decision_Polls_API_Polls();
        $this->endpoints['votes'] = new Decision_Polls_API_Votes();
    }

    /**
     * Get endpoint instance
     *
     * @param string $endpoint Endpoint name.
     * @return Decision_Polls_API_Base|null Endpoint instance or null.
     */
    public function get_endpoint($endpoint) {
        return isset($this->endpoints[$endpoint]) ? $this->endpoints[$endpoint] : null;
    }

    /**
     * Register custom capabilities for polls
     */
    public static function register_capabilities() {
        // Custom capabilities for polls
        $capabilities = array(
            'create_decision_polls',
            'edit_decision_polls',
            'edit_others_decision_polls',
            'delete_decision_polls',
            'delete_others_decision_polls',
            'manage_decision_polls',
        );
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add basic poll capabilities to editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('create_decision_polls');
            $editor_role->add_cap('edit_decision_polls');
            $editor_role->add_cap('delete_decision_polls');
        }
        
        // Add basic poll capabilities to author
        $author_role = get_role('author');
        if ($author_role) {
            $author_role->add_cap('create_decision_polls');
            $author_role->add_cap('edit_decision_polls');
            $author_role->add_cap('delete_decision_polls');
        }
    }

    /**
     * Remove custom capabilities for polls
     */
    public static function remove_capabilities() {
        // Custom capabilities for polls
        $capabilities = array(
            'create_decision_polls',
            'edit_decision_polls',
            'edit_others_decision_polls',
            'delete_decision_polls',
            'delete_others_decision_polls',
            'manage_decision_polls',
        );
        
        // Get all roles
        $roles = get_editable_roles();
        
        // Remove capabilities from all roles
        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
