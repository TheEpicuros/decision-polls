<?php
/**
 * Polls API Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling polls API endpoints
 */
class Decision_Polls_API_Polls extends Decision_Polls_API_Base {
    /**
     * Poll model instance
     *
     * @var Decision_Polls_Poll
     */
    private $poll_model;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->poll_model = new Decision_Polls_Poll();
    }

    /**
     * Register API routes
     */
    public function register_routes() {
        // Get all polls
        $this->register_get_route(
            '/polls',
            array($this, 'get_polls'),
            array($this->permissions, 'can_view_polls')
        );

        // Get single poll
        $this->register_get_route(
            '/polls/(?P<id>\d+)',
            array($this, 'get_poll'),
            array($this, 'can_view_poll_callback'),
            $this->get_id_param_args()
        );

        // Create poll
        $this->register_post_route(
            '/polls',
            array($this, 'create_poll'),
            array($this->permissions, 'can_create_polls')
        );

        // Update poll
        $this->register_put_route(
            '/polls/(?P<id>\d+)',
            array($this, 'update_poll'),
            array($this, 'can_update_poll_callback'),
            $this->get_id_param_args()
        );

        // Delete poll
        $this->register_delete_route(
            '/polls/(?P<id>\d+)',
            array($this, 'delete_poll'),
            array($this, 'can_delete_poll_callback'),
            $this->get_id_param_args()
        );
        
        // Get user polls
        $this->register_get_route(
            '/user/polls',
            array($this, 'get_user_polls'),
            array($this->permissions, 'can_view_user_polls')
        );
    }

    /**
     * Get polls callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_polls($request) {
        $params = $this->parse_params($request, array(
            'per_page' => 10,
            'page' => 1,
            'status' => 'published',
            'type' => '',
        ));
        
        $result = $this->poll_model->get_all($params);
        
        $response = $this->success($result['polls']);
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['total_pages']);
        
        return $response;
    }

    /**
     * Get poll callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_poll($request) {
        $poll_id = $request['id'];
        $poll = $this->poll_model->get($poll_id);
        
        if (!$poll) {
            return $this->error('not_found', 'Poll not found', 404);
        }
        
        return $this->success($poll);
    }

    /**
     * Create poll callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function create_poll($request) {
        $result = $this->poll_model->create($request->get_params());
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success($result, 201);
    }

    /**
     * Update poll callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_poll($request) {
        $poll_id = $request['id'];
        $result = $this->poll_model->update($poll_id, $request->get_params());
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success($result);
    }

    /**
     * Delete poll callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function delete_poll($request) {
        $poll_id = $request['id'];
        $result = $this->poll_model->delete($poll_id);
        
        if (!$result) {
            return $this->error('delete_failed', 'Failed to delete poll', 500);
        }
        
        return $this->success(null, 204);
    }

    /**
     * Get user polls callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_user_polls($request) {
        $user_id = get_current_user_id();
        
        $params = $this->parse_params($request, array(
            'per_page' => 10,
            'page' => 1,
        ));
        
        $result = $this->poll_model->get_user_polls($user_id, $params);
        
        $response = $this->success($result['polls']);
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['total_pages']);
        
        return $response;
    }

    /**
     * Permission callback for viewing single poll
     *
     * @param WP_REST_Request $request Request object.
     * @return bool Whether the user can view the poll.
     */
    public function can_view_poll_callback($request) {
        return $this->permissions->can_view_poll($request['id']);
    }

    /**
     * Permission callback for updating poll
     *
     * @param WP_REST_Request $request Request object.
     * @return bool Whether the user can update the poll.
     */
    public function can_update_poll_callback($request) {
        return $this->permissions->can_update_poll($request['id']);
    }

    /**
     * Permission callback for deleting poll
     *
     * @param WP_REST_Request $request Request object.
     * @return bool Whether the user can delete the poll.
     */
    public function can_delete_poll_callback($request) {
        return $this->permissions->can_delete_poll($request['id']);
    }
}
