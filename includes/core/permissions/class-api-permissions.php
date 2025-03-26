<?php
/**
 * API Permissions Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling API permissions
 */
class Decision_Polls_API_Permissions {
    /**
     * Vote model instance
     *
     * @var Decision_Polls_Vote
     */
    private $vote_model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->vote_model = new Decision_Polls_Vote();
    }

    /**
     * Check if user can view polls
     *
     * @return bool True if user can view polls.
     */
    public function can_view_polls() {
        // Public polls are readable by anyone
        return true;
    }

    /**
     * Check if user can view a specific poll
     *
     * @param int $poll_id Poll ID.
     * @return bool True if user can view the poll.
     */
    public function can_view_poll($poll_id) {
        global $wpdb;
        
        $poll_table = $wpdb->prefix . 'decision_polls';
        
        // Check if poll exists
        $poll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, author_id, is_private FROM $poll_table WHERE id = %d",
                $poll_id
            )
        );
        
        if (!$poll) {
            return false;
        }
        
        // If the poll is not private, anyone can view it
        if (!$poll->is_private) {
            return true;
        }
        
        // Only the poll author can view private polls
        $current_user_id = get_current_user_id();
        return $current_user_id && $current_user_id === (int) $poll->author_id;
    }

    /**
     * Check if user can create polls
     *
     * @return bool True if user can create polls.
     */
    public function can_create_polls() {
        // Check if user is logged in
        if (!is_user_logged_in() && get_option('decision_polls_require_login_to_create', 1)) {
            return false;
        }
        
        // Check if user has capability
        return current_user_can('create_decision_polls');
    }

    /**
     * Check if user can update a specific poll
     *
     * @param int $poll_id Poll ID.
     * @return bool True if user can update the poll.
     */
    public function can_update_poll($poll_id) {
        global $wpdb;
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        $poll_table = $wpdb->prefix . 'decision_polls';
        $current_user_id = get_current_user_id();
        
        // Get poll author
        $poll_author_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT author_id FROM $poll_table WHERE id = %d",
                $poll_id
            )
        );
        
        // Check if user can edit all polls or is the author
        if (current_user_can('edit_others_decision_polls') || 
            ($current_user_id === (int) $poll_author_id && current_user_can('edit_decision_polls'))) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can delete a specific poll
     *
     * @param int $poll_id Poll ID.
     * @return bool True if user can delete the poll.
     */
    public function can_delete_poll($poll_id) {
        global $wpdb;
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        $poll_table = $wpdb->prefix . 'decision_polls';
        $current_user_id = get_current_user_id();
        
        // Get poll author
        $poll_author_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT author_id FROM $poll_table WHERE id = %d",
                $poll_id
            )
        );
        
        // Check if user can delete all polls or is the author
        if (current_user_can('delete_others_decision_polls') || 
            ($current_user_id === (int) $poll_author_id && current_user_can('delete_decision_polls'))) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can vote in a specific poll
     *
     * @param int $poll_id Poll ID.
     * @return bool True if user can vote in the poll.
     */
    public function can_vote($poll_id) {
        global $wpdb;
        
        $poll_table = $wpdb->prefix . 'decision_polls';
        
        // Check if poll exists and is active
        $poll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status, starts_at, expires_at FROM $poll_table WHERE id = %d",
                $poll_id
            )
        );
        
        if (!$poll || $poll->status !== 'published') {
            return false;
        }
        
        // Check if poll has started
        if ($poll->starts_at && strtotime($poll->starts_at) > time()) {
            return false;
        }
        
        // Check if poll has expired
        if ($poll->expires_at && strtotime($poll->expires_at) < time()) {
            return false;
        }
        
        // Check if guest voting is allowed
        if (!is_user_logged_in() && !get_option('decision_polls_allow_guests', 1)) {
            return false;
        }
        
        // Check if user has already voted
        if ($this->vote_model->has_voted($poll_id)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if user can view results of a specific poll
     *
     * @param int $poll_id Poll ID.
     * @return bool True if user can view results.
     */
    public function can_view_results($poll_id) {
        global $wpdb;
        
        $poll_table = $wpdb->prefix . 'decision_polls';
        
        // Check if poll exists
        $poll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status, expires_at FROM $poll_table WHERE id = %d",
                $poll_id
            )
        );
        
        if (!$poll) {
            return false;
        }
        
        // Check results view option
        $results_view = get_option('decision_polls_results_view', 'after_vote');
        
        if ($results_view === 'always') {
            return true;
        }
        
        if ($results_view === 'after_end' && $poll->expires_at && strtotime($poll->expires_at) < time()) {
            return true;
        }
        
        if ($results_view === 'after_vote' && $this->vote_model->has_voted($poll_id)) {
            return true;
        }
        
        // Poll administrators can always see results
        return current_user_can('manage_decision_polls');
    }

    /**
     * Check if user can view their polls
     *
     * @return bool True if user can view their polls.
     */
    public function can_view_user_polls() {
        // User must be logged in to see their polls
        return is_user_logged_in();
    }
}
