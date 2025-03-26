<?php
/**
 * Frontend Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling frontend functionality
 */
class Decision_Polls_Frontend {
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('decision_poll', array($this, 'poll_shortcode'));
        add_shortcode('decision_poll_results', array($this, 'poll_results_shortcode'));
        add_shortcode('decision_poll_form', array($this, 'poll_form_shortcode'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('decision-polls');
        wp_enqueue_script('decision-polls');
    }

    /**
     * Poll shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function poll_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'show_results' => 'after', // after, always, never
            ),
            $atts,
            'decision_poll'
        );
        
        $poll_id = absint($atts['id']);
        
        if (!$poll_id) {
            return '<div class="decision-polls-error">' . esc_html__('Error: Poll ID is required.', 'decision-polls') . '</div>';
        }
        
        // For now, return a placeholder with the poll ID that includes JS initialization
        $output = '<div class="decision-polls-container" data-poll-id="' . esc_attr($poll_id) . '" data-show-results="' . esc_attr($atts['show_results']) . '">';
        $output .= '<div class="decision-polls-loading">' . esc_html__('Loading poll...', 'decision-polls') . '</div>';
        $output .= '</div>';
        
        // Load required scripts
        wp_enqueue_style('decision-polls');
        wp_enqueue_script('decision-polls');
        
        return $output;
    }
    
    /**
     * Poll results shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function poll_results_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'type' => 'bar', // bar, pie, text
            ),
            $atts,
            'decision_poll_results'
        );
        
        $poll_id = absint($atts['id']);
        
        if (!$poll_id) {
            return '<div class="decision-polls-error">' . esc_html__('Error: Poll ID is required.', 'decision-polls') . '</div>';
        }
        
        // For now, return a placeholder with the poll ID that includes JS initialization
        $output = '<div class="decision-polls-results-container" data-poll-id="' . esc_attr($poll_id) . '" data-chart-type="' . esc_attr($atts['type']) . '">';
        $output .= '<div class="decision-polls-loading">' . esc_html__('Loading poll results...', 'decision-polls') . '</div>';
        $output .= '</div>';
        
        // Load required scripts
        wp_enqueue_style('decision-polls');
        wp_enqueue_script('decision-polls');
        wp_enqueue_script('decision-polls-results');
        
        return $output;
    }
    
    /**
     * Poll creation form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function poll_form_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'type' => 'standard', // standard, multiple, ranked
                'redirect' => '', // URL to redirect after submission
            ),
            $atts,
            'decision_poll_form'
        );
        
        // Check if user can create polls
        if (!current_user_can('create_decision_polls')) {
            return '<div class="decision-polls-error">' . esc_html__('Error: You do not have permission to create polls.', 'decision-polls') . '</div>';
        }
        
        // For now, return a placeholder with the poll type that includes JS initialization
        $output = '<div class="decision-polls-form-container" data-poll-type="' . esc_attr($atts['type']) . '" data-redirect="' . esc_url($atts['redirect']) . '">';
        $output .= '<div class="decision-polls-form-placeholder">' . esc_html__('Poll creation form will be loaded here...', 'decision-polls') . '</div>';
        $output .= '</div>';
        
        // Load required scripts
        wp_enqueue_style('decision-polls');
        wp_enqueue_script('decision-polls');
        wp_enqueue_script('decision-polls-creator');
        
        return $output;
    }
}
