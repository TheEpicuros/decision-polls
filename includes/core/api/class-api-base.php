<?php
/**
 * API Base Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for all API endpoint classes
 */
abstract class Decision_Polls_API_Base {
    /**
     * API namespace
     *
     * @var string
     */
    protected $namespace = 'decision-polls/v1';

    /**
     * Permission handler
     *
     * @var Decision_Polls_API_Permissions
     */
    protected $permissions;

    /**
     * Constructor
     */
    public function __construct() {
        $this->permissions = new Decision_Polls_API_Permissions();
        
        // Register routes on rest_api_init
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register API routes
     */
    abstract public function register_routes();

    /**
     * Create a success response
     *
     * @param mixed $data Response data.
     * @param int   $status HTTP status code.
     * @return WP_REST_Response Response object.
     */
    protected function success($data, $status = 200) {
        return new WP_REST_Response($data, $status);
    }

    /**
     * Create an error response
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param int    $status HTTP status code.
     * @return WP_Error Error object.
     */
    protected function error($code, $message, $status = 400) {
        return new WP_Error($code, $message, array('status' => $status));
    }

    /**
     * Parse query parameters and apply defaults
     *
     * @param WP_REST_Request $request Request object.
     * @param array           $defaults Default parameter values.
     * @return array Parsed parameters.
     */
    protected function parse_params($request, $defaults = array()) {
        $params = array();
        
        foreach ($defaults as $key => $default) {
            if (isset($request[$key])) {
                $params[$key] = $request[$key];
            } else {
                $params[$key] = $default;
            }
        }
        
        return $params;
    }

    /**
     * Register a GET route
     *
     * @param string   $route Route endpoint.
     * @param callable $callback Callback function.
     * @param callable $permission_callback Permission callback function.
     * @param array    $args Route arguments.
     */
    protected function register_get_route($route, $callback, $permission_callback, $args = array()) {
        register_rest_route($this->namespace, $route, array(
            'methods' => 'GET',
            'callback' => $callback,
            'permission_callback' => $permission_callback,
            'args' => $args,
        ));
    }

    /**
     * Register a POST route
     *
     * @param string   $route Route endpoint.
     * @param callable $callback Callback function.
     * @param callable $permission_callback Permission callback function.
     * @param array    $args Route arguments.
     */
    protected function register_post_route($route, $callback, $permission_callback, $args = array()) {
        register_rest_route($this->namespace, $route, array(
            'methods' => 'POST',
            'callback' => $callback,
            'permission_callback' => $permission_callback,
            'args' => $args,
        ));
    }

    /**
     * Register a PUT route
     *
     * @param string   $route Route endpoint.
     * @param callable $callback Callback function.
     * @param callable $permission_callback Permission callback function.
     * @param array    $args Route arguments.
     */
    protected function register_put_route($route, $callback, $permission_callback, $args = array()) {
        register_rest_route($this->namespace, $route, array(
            'methods' => 'PUT',
            'callback' => $callback,
            'permission_callback' => $permission_callback,
            'args' => $args,
        ));
    }

    /**
     * Register a DELETE route
     *
     * @param string   $route Route endpoint.
     * @param callable $callback Callback function.
     * @param callable $permission_callback Permission callback function.
     * @param array    $args Route arguments.
     */
    protected function register_delete_route($route, $callback, $permission_callback, $args = array()) {
        register_rest_route($this->namespace, $route, array(
            'methods' => 'DELETE',
            'callback' => $callback,
            'permission_callback' => $permission_callback,
            'args' => $args,
        ));
    }

    /**
     * Helper to get numeric ID parameter definition
     *
     * @return array Parameter definition.
     */
    protected function get_id_param_args() {
        return array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        );
    }
}
