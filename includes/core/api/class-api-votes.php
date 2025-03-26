<?php
/**
 * Votes API Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling votes API endpoints
 */
class Decision_Polls_API_Votes extends Decision_Polls_API_Base {
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
        parent::__construct();
        $this->vote_model = new Decision_Polls_Vote();
    }

    /**
     * Register API routes
     */
    public function register_routes() {
        // Submit vote
        $this->register_post_route(
            '/polls/(?P<id>\d+)/vote',
            array($this, 'submit_vote'),
            array($this, 'can_vote_callback'),
            $this->get_id_param_args()
        );

        // Get poll results
        $this->register_get_route(
            '/polls/(?P<id>\d+)/results',
            array($this, 'get_results'),
            array($this, 'can_view_results_callback'),
            $this->get_id_param_args()
        );
    }

    /**
     * Submit vote callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function submit_vote($request) {
        $poll_id = $request['id'];
        $result = $this->vote_model->submit_vote($poll_id, $request->get_params());
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success($result);
    }

    /**
     * Get results callback
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_results($request) {
        $poll_id = $request['id'];
        $result = $this->vote_model->get_results($poll_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $this->success($result);
    }

    /**
     * Permission callback for voting
     *
     * @param WP_REST_Request $request Request object.
     * @return bool Whether the user can vote on the poll.
     */
    public function can_vote_callback($request) {
        return $this->permissions->can_vote($request['id']);
    }

    /**
     * Permission callback for viewing results
     *
     * @param WP_REST_Request $request Request object.
     * @return bool Whether the user can view results of the poll.
     */
    public function can_view_results_callback($request) {
        return $this->permissions->can_view_results($request['id']);
    }
}
