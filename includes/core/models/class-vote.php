<?php
/**
 * Vote Model Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vote Model class for handling vote data
 */
class Decision_Polls_Vote extends Decision_Polls_Model {
    /**
     * Votes table name
     */
    const TABLE_NAME = 'decision_poll_votes';

    /**
     * Results table name
     */
    const RESULTS_TABLE_NAME = 'decision_poll_results';

    /**
     * Submit a vote
     *
     * @param int   $poll_id Poll ID.
     * @param array $data Vote data.
     * @return array|WP_Error Vote result or error.
     */
    public function submit_vote($poll_id, $data) {
        // Validate required fields
        if (!isset($data['answers'])) {
            return new WP_Error('missing_field', 'Missing required field: answers', ['status' => 400]);
        }
        
        $answers = $data['answers'];
        
        // Get poll data to check vote type
        $poll_model = new Decision_Polls_Poll();
        $poll = $poll_model->get($poll_id);
        
        if (!$poll) {
            return new WP_Error('not_found', 'Poll not found', ['status' => 404]);
        }
        
        // Validate vote based on poll type
        if ($poll['type'] === 'standard' && count($answers) !== 1) {
            return new WP_Error('invalid_vote', 'Standard poll requires exactly one answer', ['status' => 400]);
        }
        
        if ($poll['type'] === 'multiple' && $poll['multiple_choices'] > 0 && count($answers) > $poll['multiple_choices']) {
            return new WP_Error('invalid_vote', 'Too many choices selected', ['status' => 400]);
        }
        
        // Begin transaction
        $this->wpdb->query('START TRANSACTION');
        
        try {
            $success = $this->record_vote($poll_id, $answers);
            
            if (!$success) {
                $this->wpdb->query('ROLLBACK');
                return new WP_Error('vote_failed', 'Failed to record vote', ['status' => 500]);
            }
            
            // Update results cache
            $this->update_results_cache($poll_id);
            
            $this->wpdb->query('COMMIT');
            
            // Get results after vote
            return $this->get_results($poll_id);
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('vote_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Check if a user has voted in a poll
     *
     * @param int $poll_id Poll ID.
     * @param int $user_id User ID (optional, defaults to current user).
     * @return bool Whether the user has voted.
     */
    public function has_voted($poll_id, $user_id = null) {
        $votes_table = $this->get_table_name(self::TABLE_NAME);
        
        if (is_user_logged_in()) {
            $user_id = $user_id ?: get_current_user_id();
            
            $has_voted = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $votes_table WHERE poll_id = %d AND user_id = %d",
                    $poll_id, $user_id
                )
            );
            
            return $has_voted > 0;
        } else {
            // For guests, check by IP
            $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
            
            $has_voted = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $votes_table WHERE poll_id = %d AND user_ip = %s",
                    $poll_id, $user_ip
                )
            );
            
            return $has_voted > 0;
        }
    }

    /**
     * Get poll results
     *
     * @param int $poll_id Poll ID.
     * @return array|WP_Error Poll results or error.
     */
    public function get_results($poll_id) {
        $poll_model = new Decision_Polls_Poll();
        $poll = $poll_model->get($poll_id);
        
        if (!$poll) {
            return new WP_Error('not_found', 'Poll not found', ['status' => 404]);
        }
        
        // Get results from cache or calculate if not cached
        $results_table = $this->get_table_name(self::RESULTS_TABLE_NAME);
        $answers_table = $this->get_table_name('decision_poll_answers');
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT r.*, a.answer_text FROM $results_table r 
                 JOIN $answers_table a ON r.answer_id = a.id
                 WHERE r.poll_id = %d
                 ORDER BY r.votes_count DESC, a.sort_order ASC",
                $poll_id
            )
        );
        
        // If no results cached, calculate them
        if (empty($results)) {
            $this->update_results_cache($poll_id);
            
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT r.*, a.answer_text FROM $results_table r 
                     JOIN $answers_table a ON r.answer_id = a.id
                     WHERE r.poll_id = %d
                     ORDER BY r.votes_count DESC, a.sort_order ASC",
                    $poll_id
                )
            );
        }
        
        // Get total votes
        $votes_table = $this->get_table_name(self::TABLE_NAME);
        $total_votes = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id, user_ip) FROM $votes_table WHERE poll_id = %d",
                $poll_id
            )
        );
        
        // Format results for API
        $formatted_results = [];
        foreach ($results as $result) {
            $formatted_results[] = [
                'id' => (int) $result->answer_id,
                'text' => $result->answer_text,
                'votes' => (int) $result->votes_count,
                'percentage' => (float) $result->percentage
            ];
        }
        
        return [
            'poll_id' => (int) $poll_id,
            'total_votes' => (int) $total_votes,
            'results' => $formatted_results,
            'last_updated' => !empty($results) ? $results[0]->last_calculated : current_time('mysql')
        ];
    }

    /**
     * Record a vote
     *
     * @param int   $poll_id Poll ID.
     * @param array $answers Answer IDs to vote for.
     * @return bool Whether the vote was recorded.
     */
    private function record_vote($poll_id, $answers) {
        $votes_table = $this->get_table_name(self::TABLE_NAME);
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $now = current_time('mysql');
        
        // For ranked choice, the vote value corresponds to the preference order (1 = first choice)
        $is_ranked = false;
        
        // Get poll type
        $poll_model = new Decision_Polls_Poll();
        $poll = $poll_model->get($poll_id);
        $is_ranked = ($poll['type'] === 'ranked');
        
        // Process each answer
        foreach ($answers as $index => $answer_data) {
            $answer_id = null;
            $vote_value = 1;
            
            // Handle different formats:
            // 1. Array of simple answer IDs: [1, 2, 3]
            // 2. Array of objects with id and optionally value: [{id: 1, value: 5}, {id: 2}]
            
            if (is_array($answer_data) && isset($answer_data['id'])) {
                $answer_id = (int) $answer_data['id'];
                $vote_value = isset($answer_data['value']) ? (int) $answer_data['value'] : ($is_ranked ? $index + 1 : 1);
            } else {
                $answer_id = (int) $answer_data;
                $vote_value = $is_ranked ? $index + 1 : 1;
            }
            
            // Insert vote
            $inserted = $this->wpdb->insert(
                $votes_table,
                [
                    'poll_id' => $poll_id,
                    'answer_id' => $answer_id,
                    'user_id' => $user_id,
                    'user_ip' => $user_ip,
                    'vote_value' => $vote_value,
                    'voted_at' => $now,
                ]
            );
            
            if (!$inserted) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Update results cache for a poll
     *
     * @param int $poll_id Poll ID.
     * @return bool Whether the cache was updated.
     */
    private function update_results_cache($poll_id) {
        $votes_table = $this->get_table_name(self::TABLE_NAME);
        $results_table = $this->get_table_name(self::RESULTS_TABLE_NAME);
        $answers_table = $this->get_table_name('decision_poll_answers');
        
        // Clear existing results
        $this->wpdb->delete($results_table, ['poll_id' => $poll_id]);
        
        // Get total voters (unique combination of user_id and user_ip)
        $total_voters = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id ELSE CONCAT('ip:', user_ip) END) 
                 FROM $votes_table WHERE poll_id = %d",
                $poll_id
            )
        );
        
        if ($total_voters <= 0) {
            // No votes yet, nothing to cache
            return false;
        }
        
        // Get all answers for the poll
        $answers = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id FROM $answers_table WHERE poll_id = %d",
                $poll_id
            ),
            ARRAY_A
        );
        
        // Calculate votes for each answer
        foreach ($answers as $answer) {
            $answer_id = $answer['id'];
            
            $votes_count = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $votes_table WHERE poll_id = %d AND answer_id = %d",
                    $poll_id, $answer_id
                )
            );
            
            $percentage = ($votes_count / $total_voters) * 100;
            
            // Insert result into cache
            $this->wpdb->insert(
                $results_table,
                [
                    'poll_id' => $poll_id,
                    'answer_id' => $answer_id,
                    'votes_count' => $votes_count,
                    'percentage' => round($percentage, 2),
                    'last_calculated' => current_time('mysql'),
                ]
            );
        }
        
        return true;
    }

    /**
     * Format data for database insertion
     *
     * @param array $data Data to format.
     * @return array Formatted data.
     */
    protected function format_for_db($data) {
        // Base implementation for the abstract method
        // This method is primarily used for Vote submission which is handled by record_vote()
        return $data;
    }

    /**
     * Format data from database for API response
     *
     * @param object $data Data to format.
     * @return array Formatted data.
     */
    protected function format_for_api($data) {
        // Base implementation for the abstract method
        // This method is primarily used for Vote results which is handled by get_results()
        return (array) $data;
    }
}
