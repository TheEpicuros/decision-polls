<?php
/**
 * Admin Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling admin functionality
 */
class Decision_Polls_Admin {
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Decision Polls', 'decision-polls'),
            __('Decision Polls', 'decision-polls'),
            'manage_decision_polls',
            'decision-polls',
            array($this, 'render_polls_page'),
            'dashicons-chart-pie',
            25
        );
        
        add_submenu_page(
            'decision-polls',
            __('All Polls', 'decision-polls'),
            __('All Polls', 'decision-polls'),
            'manage_decision_polls',
            'decision-polls',
            array($this, 'render_polls_page')
        );
        
        add_submenu_page(
            'decision-polls',
            __('Add New Poll', 'decision-polls'),
            __('Add New', 'decision-polls'),
            'create_decision_polls',
            'decision-polls-add-new',
            array($this, 'render_add_new_page')
        );
        
        add_submenu_page(
            'decision-polls',
            __('Settings', 'decision-polls'),
            __('Settings', 'decision-polls'),
            'manage_decision_polls',
            'decision-polls-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'decision-polls') === false) {
            return;
        }
        
        wp_enqueue_style('decision-polls-admin');
        wp_enqueue_script('decision-polls-admin');
    }

    /**
     * Render polls page
     */
    public function render_polls_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('All Polls', 'decision-polls') . '</h1>';
        
        // Display polls list or placeholder for now
        echo '<div class="notice notice-info">';
        echo '<p>' . esc_html__('This is a placeholder for the polls list. The actual functionality will be implemented in the full version.', 'decision-polls') . '</p>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Render add new poll page
     */
    public function render_add_new_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Add New Poll', 'decision-polls') . '</h1>';
        
        // Display add new poll form or placeholder for now
        echo '<div class="notice notice-info">';
        echo '<p>' . esc_html__('This is a placeholder for the add new poll form. The actual functionality will be implemented in the full version.', 'decision-polls') . '</p>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Settings', 'decision-polls') . '</h1>';
        
        // Display settings form or placeholder for now
        echo '<div class="notice notice-info">';
        echo '<p>' . esc_html__('This is a placeholder for the settings form. The actual functionality will be implemented in the full version.', 'decision-polls') . '</p>';
        echo '</div>';
        
        echo '</div>';
    }
}
