<?php
/**
 * Poll Model Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( !defined('ABSPATH')) {
    exit;
}

/**
 * Poll Model class for handling poll data
 */
class Decision_Polls_Poll extends Decision_Polls_Model {
    /**
     * Poll table name
     */
    const TABLE_NAME = 'decision_polls';

    /**
     * Answers table name
     */
    const ANSWERS_TABLE_NAME = 'decision_poll_answers';

    /**
     * Valid poll types
     */
    const VALID_POLL_TYPES = ['standard', 'multiple', 'ranked'];

    /**
     * Get a poll by ID
     *
     * @param int $id Poll ID.
     * @return array|false Poll data or false if not found.
     */
    public function get($id) {
        $poll_table = $this->get_table_name(self::TABLE_NAME);
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        
        // Get poll data
        $poll = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $poll_table WHERE id = %d",
                $id
            )
        );
        
        if (!$poll) {
            return false;
        }
        
        // Get poll answers
        $answers = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $answers_table WHERE poll_id = %d ORDER BY sort_order ASC",
                $id
            )
        );
        
        // Format poll data for API
        $formatted_poll = $this->format_for_api($poll);
        
        // Add answers to poll data
        $formatted_poll['answers'] = array_map(function($answer) {
            return [
                'id' => (int) $answer->id,
                'text' => $answer->answer_text,
                'sort_order' => (int) $answer->sort_order
            ];
        }, $answers);
        
        return $formatted_poll;
    }

    /**
     * Get all polls
     *
     * @param array $args Query arguments.
     * @return array Polls data.
     */
    public function get_all($args = []) {
        $defaults = [
            'per_page' => 10,
            'page' => 1,
            'status' => 'published',
            'type' => '',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitize args
        $per_page = min(50, (int) $args['per_page']);
        $page = (int) $args['page'];
        $offset = ($page - 1) * $per_page;
        $status = $this->sanitize($args['status']);
        $type = $this->sanitize($args['type']);
        
        $poll_table = $this->get_table_name(self::TABLE_NAME);
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        
        // Build query
        $where = "WHERE status = %s";
        $where_args = [$status];
        
        // Filter by type if specified
        if (!empty($type)) {
            $where .= " AND poll_type = %s";
            $where_args[] = $type;
        }
        
        // Get total count for pagination
        $total_polls = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $poll_table $where",
                $where_args
            )
        );
        
        // Get polls
        $polls = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $poll_table $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
                array_merge($where_args, [$per_page, $offset])
            )
        );
        
        // Get poll answers
        $poll_ids = wp_list_pluck($polls, 'id');
        $answers = [];
        
        if (!empty($poll_ids)) {
            $placeholders = implode(', ', array_fill(0, count($poll_ids), '%d'));
            $answers_query = $this->wpdb->prepare(
                "SELECT * FROM $answers_table WHERE poll_id IN ($placeholders) ORDER BY sort_order ASC",
                $poll_ids
            );
            
            $all_answers = $this->wpdb->get_results($answers_query);
            
            // Group answers by poll ID
            foreach ($all_answers as $answer) {
                if (!isset($answers[$answer->poll_id])) {
                    $answers[$answer->poll_id] = [];
                }
                $answers[$answer->poll_id][] = $answer;
            }
        }
        
        // Format response
        $formatted_polls = [];
        foreach ($polls as $poll) {
            $poll_answers = isset($answers[$poll->id]) ? $answers[$poll->id] : [];
            
            $formatted_poll = $this->format_for_api($poll);
            
            // Add answers to poll data
            $formatted_poll['answers'] = array_map(function($answer) {
                return [
                    'id' => (int) $answer->id,
                    'text' => $answer->answer_text,
                    'sort_order' => (int) $answer->sort_order
                ];
            }, $poll_answers);
            
            $formatted_polls[] = $formatted_poll;
        }
        
        return [
            'polls' => $formatted_polls,
            'total' => (int) $total_polls,
            'total_pages' => ceil($total_polls / $per_page)
        ];
    }

    /**
     * Create a new poll
     *
     * @param array $data Poll data.
     * @return array|WP_Error Created poll data or error.
     */
    public function create($data) {
        // Validate required fields
        $required_fields = ['title', 'answers'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_Error('missing_field', sprintf('Missing required field: %s', $field), ['status' => 400]);
            }
        }
        
        // Validate poll type
        $poll_type = isset($data['type']) ? $this->sanitize($data['type']) : 'standard';
        if (!in_array($poll_type, self::VALID_POLL_TYPES)) {
            return new WP_Error('invalid_type', 'Invalid poll type', ['status' => 400]);
        }
        
        // Validate answers
        $answers = $data['answers'];
        if (!is_array($answers) || count($answers) < 2) {
            return new WP_Error('invalid_answers', 'Poll must have at least 2 answers', ['status' => 400]);
        }
        
        // Format data for database
        $poll_data = $this->format_for_db($data);
        
        // Insert poll
        $poll_table = $this->get_table_name(self::TABLE_NAME);
        $this->wpdb->insert($poll_table, $poll_data);
        $poll_id = $this->wpdb->insert_id;
        
        if (!$poll_id) {
            return new WP_Error('db_error', 'Failed to create poll', ['status' => 500]);
        }
        
        // Insert answers
        $created_answers = $this->insert_answers($poll_id, $answers);
        
        // Prepare response
        $response_data = array_merge($poll_data, [
            'id' => $poll_id,
            'answers' => $created_answers
        ]);
        
        // Convert numeric booleans to actual booleans
        $response_data['is_private'] = (bool) $response_data['is_private'];
        $response_data['allow_comments'] = (bool) $response_data['allow_comments'];
        
        return $response_data;
    }

    /**
     * Update an existing poll
     *
     * @param int   $id Poll ID.
     * @param array $data Poll data.
     * @return array|WP_Error Updated poll data or error.
     */
    public function update($id, $data) {
        $poll_table = $this->get_table_name(self::TABLE_NAME);
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        
        // Check if poll exists
        $poll = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $poll_table WHERE id = %d",
                $id
            )
        );
        
        if (!$poll) {
            return new WP_Error('not_found', 'Poll not found', ['status' => 404]);
        }
        
        // Validate poll type if present
        if (isset($data['type'])) {
            $poll_type = $this->sanitize($data['type']);
            if (!in_array($poll_type, self::VALID_POLL_TYPES)) {
                return new WP_Error('invalid_type', 'Invalid poll type', ['status' => 400]);
            }
        }
        
        // Prepare update data
        $update_data = [];
        
        if (isset($data['title'])) {
            $update_data['title'] = $this->sanitize($data['title']);
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = $this->sanitize($data['description'], 'textarea');
        }
        
        if (isset($data['type'])) {
            $update_data['poll_type'] = $poll_type;
        }
        
        if (isset($data['multiple_choices'])) {
            $update_data['multiple_choices'] = $this->sanitize($data['multiple_choices'], 'int');
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = $this->sanitize($data['status']);
        }
        
        if (isset($data['starts_at'])) {
            $update_data['starts_at'] = $this->sanitize($data['starts_at']);
        }
        
        if (isset($data['expires_at'])) {
            $update_data['expires_at'] = $this->sanitize($data['expires_at']);
        }
        
        if (isset($data['is_private'])) {
            $update_data['is_private'] = $this->sanitize($data['is_private'], 'bool') ? 1 : 0;
        }
        
        if (isset($data['allow_comments'])) {
            $update_data['allow_comments'] = $this->sanitize($data['allow_comments'], 'bool') ? 1 : 0;
        }
        
        if (isset($data['meta'])) {
            $meta = $data['meta'];
            $update_data['meta'] = is_array($meta) || is_object($meta) ? maybe_serialize($meta) : $meta;
        }
        
        // Update poll if there are changes
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            
            $this->wpdb->update(
                $poll_table,
                $update_data,
                ['id' => $id]
            );
        }
        
        // Handle answer updates
        if (isset($data['answers']) && is_array($data['answers'])) {
            $this->update_answers($id, $data['answers']);
        }
        
        // Get updated poll data
        return $this->get($id);
    }

    /**
     * Delete a poll
     *
     * @param int $id Poll ID.
     * @return bool Whether the poll was deleted.
     */
    public function delete($id) {
        $poll_table = $this->get_table_name(self::TABLE_NAME);
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        $votes_table = $this->get_table_name('decision_poll_votes');
        $results_table = $this->get_table_name('decision_poll_results');
        
        // Check if poll exists
        $poll = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM $poll_table WHERE id = %d",
                $id
            )
        );
        
        if (!$poll) {
            return false;
        }
        
        // Begin transaction
        $this->wpdb->query('START TRANSACTION');
        
        // Delete poll results
        $this->wpdb->delete($results_table, ['poll_id' => $id]);
        
        // Delete votes
        $this->wpdb->delete($votes_table, ['poll_id' => $id]);
        
        // Delete answers
        $this->wpdb->delete($answers_table, ['poll_id' => $id]);
        
        // Delete poll
        $deleted = $this->wpdb->delete($poll_table, ['id' => $id]);
        
        // Commit or rollback
        if ($deleted) {
            $this->wpdb->query('COMMIT');
            return true;
        } else {
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Get polls created by a specific user
     *
     * @param int   $user_id User ID.
     * @param array $args Query arguments.
     * @return array User polls.
     */
    public function get_user_polls($user_id, $args = []) {
        $args = wp_parse_args($args, [
            'per_page' => 10,
            'page' => 1,
        ]);
        
        // Add user_id to args
        $args['author_id'] = $user_id;
        
        return $this->get_all($args);
    }

    /**
     * Insert answers for a poll
     *
     * @param int   $poll_id Poll ID.
     * @param array $answers Answers data.
     * @return array Created answers.
     */
    private function insert_answers($poll_id, $answers) {
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        $created_answers = [];
        
        foreach ($answers as $index => $answer) {
            if (is_array($answer) && isset($answer['text'])) {
                $answer_text = $this->sanitize($answer['text']);
                $sort_order = isset($answer['sort_order']) ? $this->sanitize($answer['sort_order'], 'int') : $index;
            } else {
                $answer_text = $this->sanitize($answer);
                $sort_order = $index;
            }
            
            $this->wpdb->insert($answers_table, [
                'poll_id' => $poll_id,
                'answer_text' => $answer_text,
                'sort_order' => $sort_order,
                'created_at' => current_time('mysql'),
            ]);
            
            $created_answers[] = [
                'id' => $this->wpdb->insert_id,
                'text' => $answer_text,
                'sort_order' => $sort_order
            ];
        }
        
        return $created_answers;
    }

    /**
     * Update answers for a poll
     *
     * @param int   $poll_id Poll ID.
     * @param array $answers Answers data.
     * @return void
     */
    private function update_answers($poll_id, $answers) {
        $answers_table = $this->get_table_name(self::ANSWERS_TABLE_NAME);
        
        // Get existing answers
        $existing_answers = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $answers_table WHERE poll_id = %d",
                $poll_id
            ),
            ARRAY_A
        );
        
        $existing_ids = wp_list_pluck($existing_answers, 'id');
        
        foreach ($answers as $index => $answer) {
            if (!isset($answer['id'])) {
                // Create new answer
                $this->wpdb->insert($answers_table, [
                    'poll_id' => $poll_id,
                    'answer_text' => $this->sanitize($answer['text']),
                    'sort_order' => isset($answer['sort_order']) ? $this->sanitize($answer['sort_order'], 'int') : $index,
                    'created_at' => current_time('mysql')
                ]);
            } else {
                // Update existing answer
                $answer_id = (int) $answer['id'];
                
                if (in_array($answer_id, $existing_ids)) {
                    $this->wpdb->update(
                        $answers_table,
                        [
                            'answer_text' => $this->sanitize($answer['text']),
                            'sort_order' => isset($answer['sort_order']) ? $this->sanitize($answer['sort_order'], 'int') : $index
                        ],
                        ['id' => $answer_id]
                    );
                    
                    // Remove from existing IDs
                    $key = array_search($answer_id, $existing_ids);
                    if ($key !== false) {
                        unset($existing_ids[$key]);
                    }
                }
            }
        }
        
        // Delete answers not included in the update
        if (!empty($existing_ids)) {
            $ids_to_delete = implode(',', array_map('intval', $existing_ids));
            $this->wpdb->query("DELETE FROM $answers_table WHERE id IN ($ids_to_delete)");
        }
    }

    /**
     * Format data for database insertion
     *
     * @param array $data Data to format.
     * @return array Formatted data.
     */
    protected function format_for_db($data) {
        $title = $this->sanitize($data['title']);
        $description = isset($data['description']) ? $this->sanitize($data['description'], 'textarea') : '';
        $poll_type = isset($data['type']) ? $this->sanitize($data['type']) : 'standard';
        $multiple_choices = isset($data['multiple_choices']) ? $this->sanitize($data['multiple_choices'], 'int') : 0;
        $status = isset($data['status']) ? $this->sanitize($data['status']) : 'draft';
        $starts_at = isset($data['starts_at']) ? $this->sanitize($data['starts_at']) : null;
        $expires_at = isset($data['expires_at']) ? $this->sanitize($data['expires_at']) : null;
        $is_private = isset($data['is_private']) ? ($this->sanitize($data['is_private'], 'bool') ? 1 : 0) : 0;
        $allow_comments = isset($data['allow_comments']) ? ($this->sanitize($data['allow_comments'], 'bool') ? 1 : 0) : 1;
        $meta = isset($data['meta']) ? $data['meta'] : null;
        
        // Set author ID
        $author_id = get_current_user_id();
        
        return [
            'title' => $title,
            'description' => $description,
            'poll_type' => $poll_type,
            'multiple_choices' => $multiple_choices,
            'status' => $status,
            'author_id' => $author_id,
            'created_at' => current_time('mysql'),
            'starts_at' => $starts_at,
            'expires_at' => $expires_at,
            'is_private' => $is_private,
            'allow_comments' => $allow_comments,
            'meta' => is_array($meta) || is_object($meta) ? maybe_serialize($meta) : $meta
        ];
    }

    /**
     * Format data from database for API response
     *
     * @param object $data Data to format.
     * @return array Formatted data.
     */
    protected function format_for_api($data) {
        return [
            'id' => (int) $data->id,
            'title' => $data->title,
            'description' => $data->description,
            'type' => $data->poll_type,
            'multiple_choices' => (int) $data->multiple_choices,
            'status' => $data->status,
            'author_id' => (int) $data->author_id,
            'created_at' => $data->created_at,
            'starts_at' => $data->starts_at,
            'expires_at' => $data->expires_at,
            'is_private' => (bool) $data->is_private,
            'allow_comments' => (bool) $data->allow_comments,
            'meta' => !empty($data->meta) ? maybe_unserialize($data->meta) : null
        ];
    }
}
