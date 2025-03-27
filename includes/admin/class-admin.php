
<?php
/**
 * Admin Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
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
        add_action('admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Display admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        // Only on our plugin pages
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'decision-polls' ) === false ) {
            return;
        }
        
        // Poll deleted notice
        if ( isset( $_GET['message'] ) && 'deleted' === $_GET['message'] ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Poll deleted successfully.', 'decision-polls' ); ?></p>
            </div>
            <?php
        }
        
        // Poll deletion error
        if ( isset( $_GET['error'] ) && 'delete_failed' === $_GET['error'] ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'Failed to delete poll. Please try again.', 'decision-polls' ); ?></p>
            </div>
            <?php
        }
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
        echo '<h1 class="wp-heading-inline">' . esc_html__('All Polls', 'decision-polls') . '</h1>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=decision-polls-add-new')) . '" class="page-title-action">' . esc_html__('Add New', 'decision-polls') . '</a>';
        echo '<hr class="wp-header-end">';
        
        // Get all polls
        $poll_model = new Decision_Polls_Poll();
        $args = array(
            'per_page' => 20,
            'page' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
        );
        
        $polls_data = $poll_model->get_all($args);
        $polls = isset($polls_data['polls']) ? $polls_data['polls'] : array();
        $total_polls = isset($polls_data['total']) ? absint($polls_data['total']) : 0;
        
        if (empty($polls)) {
            echo '<div class="notice notice-info">';
            echo '<p>' . esc_html__('No polls found. Create your first poll!', 'decision-polls') . '</p>';
            echo '</div>';
        } else {
            // Display polls in a WP list table style
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Title', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Type', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Votes', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Status', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Date', 'decision-polls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($polls as $poll) : ?>
                        <?php
                        $poll_id = isset($poll['id']) ? absint($poll['id']) : 0;
                        $edit_link = admin_url('admin.php?page=decision-polls-add-new&poll_id=' . $poll_id);
                        $delete_link = wp_nonce_url(admin_url('admin.php?page=decision-polls&action=delete&poll_id=' . $poll_id), 'delete-poll_' . $poll_id);
                        $view_link = add_query_arg('poll_id', $poll_id, get_home_url());
                        
                        $poll_type = isset($poll['type']) ? $poll['type'] : 'standard';
                        $poll_type_display = '';
                        switch ($poll_type) {
                            case 'standard':
                                $poll_type_display = esc_html__('Standard', 'decision-polls');
                                break;
                            case 'multiple':
                                $poll_type_display = esc_html__('Multiple Choice', 'decision-polls');
                                break;
                            case 'ranked':
                                $poll_type_display = esc_html__('Ranked Choice', 'decision-polls');
                                break;
                            default:
                                $poll_type_display = esc_html(ucfirst($poll_type));
                                break;
                        }
                        
                        $status = isset($poll['status']) ? $poll['status'] : 'draft';
                        $date = isset($poll['created_at']) ? date_i18n(get_option('date_format'), strtotime($poll['created_at'])) : '';
                        $total_votes = isset($poll['total_votes']) ? absint($poll['total_votes']) : 0;
                        ?>
                        <tr>
                            <td class="title column-title has-row-actions column-primary">
                                <strong><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html($poll['title']); ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'decision-polls'); ?></a> | </span>
                                    <span class="view"><a href="<?php echo esc_url($view_link); ?>" target="_blank"><?php esc_html_e('View', 'decision-polls'); ?></a> | </span>
                                    <span class="trash"><a href="<?php echo esc_url($delete_link); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this poll?', 'decision-polls'); ?>');"><?php esc_html_e('Delete', 'decision-polls'); ?></a></span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'decision-polls'); ?></span></button>
                            </td>
                            <td><?php echo esc_html($poll_type_display); ?></td>
                            <td><?php echo esc_html($total_votes); ?></td>
                            <td><?php echo esc_html(ucfirst($status)); ?></td>
                            <td><?php echo esc_html($date); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Title', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Type', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Votes', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Status', 'decision-polls'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Date', 'decision-polls'); ?></th>
                    </tr>
                </tfoot>
            </table>
            <?php
            
            // Pagination
            $total_pages = ceil($total_polls / $args['per_page']);
            if ($total_pages > 1) {
                echo '<div class="tablenav-pages">';
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $args['page']
                ));
                echo '</div>';
            }
        }
        
        echo '</div>';
    }

    /**
     * Render add new poll page
     */
    public function render_add_new_page() {
        $poll_id = isset($_GET['poll_id']) ? absint($_GET['poll_id']) : 0;
        $editing = ($poll_id > 0);
        $poll = array();
        
        if ($editing) {
            $poll_model = new Decision_Polls_Poll();
            $poll = $poll_model->get($poll_id);
            
            if (!$poll) {
                wp_die(__('Poll not found.', 'decision-polls'));
            }
        }
        
        $title = $editing ? __('Edit Poll', 'decision-polls') : __('Add New Poll', 'decision-polls');
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($title) . '</h1>';
        
        ?>
        <form method="post" action="" id="decision-polls-admin-form">
            <?php wp_nonce_field('decision_polls_admin', 'decision_polls_nonce'); ?>
            <input type="hidden" name="poll_id" value="<?php echo $editing ? esc_attr($poll_id) : ''; ?>">
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="poll_title"><?php esc_html_e('Poll Title', 'decision-polls'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input name="poll_title" type="text" id="poll_title" value="<?php echo $editing ? esc_attr($poll['title']) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_description"><?php esc_html_e('Description', 'decision-polls'); ?></label></th>
                        <td>
                            <textarea name="poll_description" id="poll_description" class="large-text" rows="3"><?php echo $editing ? esc_textarea($poll['description']) : ''; ?></textarea>
                            <p class="description"><?php esc_html_e('Optional. Provide more context about your poll.', 'decision-polls'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_type"><?php esc_html_e('Poll Type', 'decision-polls'); ?></label></th>
                        <td>
                            <select name="poll_type" id="poll_type">
                                <option value="standard" <?php selected($editing && $poll['type'] === 'standard'); ?>><?php esc_html_e('Standard (Single Choice)', 'decision-polls'); ?></option>
                                <option value="multiple" <?php selected($editing && $poll['type'] === 'multiple'); ?>><?php esc_html_e('Multiple Choice', 'decision-polls'); ?></option>
                                <option value="ranked" <?php selected($editing && $poll['type'] === 'ranked'); ?>><?php esc_html_e('Ranked Choice', 'decision-polls'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="decision-polls-multiple-options" style="<?php echo ($editing && $poll['type'] === 'multiple') ? '' : 'display: none;'; ?>">
                        <th scope="row"><label for="poll_max_choices"><?php esc_html_e('Maximum Choices', 'decision-polls'); ?></label></th>
                        <td>
                            <input type="number" name="poll_max_choices" id="poll_max_choices" min="0" value="<?php echo $editing && isset($poll['multiple_choices']) ? esc_attr($poll['multiple_choices']) : '0'; ?>">
                            <p class="description"><?php esc_html_e('Maximum number of options a voter can select. Enter 0 for unlimited.', 'decision-polls'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_status"><?php esc_html_e('Status', 'decision-polls'); ?></label></th>
                        <td>
                            <select name="poll_status" id="poll_status">
                                <option value="draft" <?php selected($editing && $poll['status'] === 'draft'); ?>><?php esc_html_e('Draft', 'decision-polls'); ?></option>
                                <option value="published" <?php selected(!$editing || ($editing && $poll['status'] === 'published')); ?>><?php esc_html_e('Published', 'decision-polls'); ?></option>
                                <option value="closed" <?php selected($editing && $poll['status'] === 'closed'); ?>><?php esc_html_e('Closed', 'decision-polls'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Poll Options', 'decision-polls'); ?> <span class="required">*</span></th>
                        <td>
                            <div id="poll-options-container">
                                <?php 
                                if ($editing && !empty($poll['answers'])) {
                                    foreach ($poll['answers'] as $index => $answer) {
                                        ?>
                                        <div class="poll-option">
                                            <input type="text" name="poll_option[]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php echo esc_attr(sprintf(__('Option %d', 'decision-polls'), $index + 1)); ?>" required>
                                            <button type="button" class="button remove-option" <?php echo (count($poll['answers']) <= 2) ? 'disabled' : ''; ?>><?php esc_html_e('Remove', 'decision-polls'); ?></button>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <div class="poll-option">
                                        <input type="text" name="poll_option[]" placeholder="<?php esc_attr_e('Option 1', 'decision-polls'); ?>" required>
                                        <button type="button" class="button remove-option" disabled><?php esc_html_e('Remove', 'decision-polls'); ?></button>
                                    </div>
                                    <div class="poll-option">
                                        <input type="text" name="poll_option[]" placeholder="<?php esc_attr_e('Option 2', 'decision-polls'); ?>" required>
                                        <button type="button" class="button remove-option" disabled><?php esc_html_e('Remove', 'decision-polls'); ?></button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <button type="button" class="button button-secondary add-option"><?php esc_html_e('Add Option', 'decision-polls'); ?></button>
                            <p class="description"><?php esc_html_e('Add at least two options for your poll.', 'decision-polls'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_private"><?php esc_html_e('Privacy', 'decision-polls'); ?></label></th>
                        <td>
                            <label for="poll_private">
                                <input type="checkbox" name="poll_private" id="poll_private" <?php checked($editing && !empty($poll['is_private'])); ?>>
                                <?php esc_html_e('Make poll private', 'decision-polls'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Private polls are only accessible via direct link.', 'decision-polls'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $editing ? esc_attr__('Update Poll', 'decision-polls') : esc_attr__('Create Poll', 'decision-polls'); ?>">
            </p>
        </form>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $typeSelect = $('#poll_type');
            var $multipleOptions = $('.decision-polls-multiple-options');
            var $optionsContainer = $('#poll-options-container');
            
            // Toggle multiple choice options when type changes
            $typeSelect.on('change', function() {
                if ($(this).val() === 'multiple') {
                    $multipleOptions.show();
                } else {
                    $multipleOptions.hide();
                }
            });
            
            // Add a new option field
            $('.add-option').on('click', function(e) {
                e.preventDefault();
                
                var optionCount = $optionsContainer.find('.poll-option').length + 1;
                var optionHtml = '<div class="poll-option">' +
                    '<input type="text" name="poll_option[]" placeholder="Option ' + optionCount + '" required>' +
                    '<button type="button" class="button remove-option">Remove</button>' +
                    '</div>';
                
                $optionsContainer.append(optionHtml);
                
                // Enable all remove buttons if we have more than 2 options
                if (optionCount > 2) {
                    $optionsContainer.find('.remove-option').prop('disabled', false);
                }
            });
            
            // Remove an option field
            $optionsContainer.on('click', '.remove-option', function() {
                if ($(this).prop('disabled')) {
                    return;
                }
                
                $(this).closest('.poll-option').remove();
                
                // Update placeholders for remaining options
                $optionsContainer.find('.poll-option').each(function(index) {
                    $(this).find('input').attr('placeholder', 'Option ' + (index + 1));
                });
                
                // Disable remove buttons if we have 2 or fewer options
                if ($optionsContainer.find('.poll-option').length <= 2) {
                    $optionsContainer.find('.remove-option').prop('disabled', true);
                }
            });
            
            // Process form submission (you would need to add AJAX handling here in a full implementation)
            $('#decision-polls-admin-form').on('submit', function(e) {
                // For now, just show a message that this would be implemented in the full version
                e.preventDefault();
                alert('Form submission would be implemented in the full version. You can create polls from the frontend.');
            });
        });
        </script>
        <?php
        
        echo '</div>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Settings', 'decision-polls' ) . '</h1>';
        
        // Check if form was submitted
        $saved = false;
        if ( isset( $_POST['decision_polls_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['decision_polls_settings_nonce'] ) ), 'decision_polls_settings' ) ) {
            // Save settings
            $allow_guests = isset( $_POST['allow_guests'] ) ? 1 : 0;
            $results_view = isset( $_POST['results_view'] ) ? sanitize_text_field( wp_unslash( $_POST['results_view'] ) ) : 'after_vote';
            $default_poll_type = isset( $_POST['default_poll_type'] ) ? sanitize_text_field( wp_unslash( $_POST['default_poll_type'] ) ) : 'standard';
            $require_login_to_create = isset( $_POST['require_login_to_create'] ) ? 1 : 0;
            $allow_frontend_creation = isset( $_POST['allow_frontend_creation'] ) ? 1 : 0;
            
            update_option( 'decision_polls_allow_guests', $allow_guests );
            update_option( 'decision_polls_results_view', $results_view );
            update_option( 'decision_polls_default_poll_type', $default_poll_type );
            update_option( 'decision_polls_require_login_to_create', $require_login_to_create );
            update_option( 'decision_polls_allow_frontend_creation', $allow_frontend_creation );
            
            $saved = true;
        }
        
        // Get current settings
        $allow_guests = get_option( 'decision_polls_allow_guests', 1 );
        $results_view = get_option( 'decision_polls_results_view', 'after_vote' );
        $default_poll_type = get_option( 'decision_polls_default_poll_type', 'standard' );
        $require_login_to_create = get_option( 'decision_polls_require_login_to_create', 1 );
        $allow_frontend_creation = get_option( 'decision_polls_allow_frontend_creation', 1 );
        
        // Display settings saved message
        if ( $saved ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'decision-polls' ) . '</p></div>';
        }
        
        // Display settings form
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'decision_polls_settings', 'decision_polls_settings_nonce' ); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Voting Settings', 'decision-polls' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( 'Voting Settings', 'decision-polls' ); ?></span></legend>
                                <label for="allow_guests">
                                    <input name="allow_guests" type="checkbox" id="allow_guests" value="1" <?php checked( $allow_guests ); ?>>
                                    <?php esc_html_e( 'Allow guest voting (users without an account)', 'decision-polls' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="results_view"><?php esc_html_e( 'Results Display', 'decision-polls' ); ?></label></th>
                        <td>
                            <select name="results_view" id="results_view">
                                <option value="after_vote" <?php selected( $results_view, 'after_vote' ); ?>><?php esc_html_e( 'Show results after voting', 'decision-polls' ); ?></option>
                                <option value="always" <?php selected( $results_view, 'always' ); ?>><?php esc_html_e( 'Always show results', 'decision-polls' ); ?></option>
                                <option value="never" <?php selected( $results_view, 'never' ); ?>><?php esc_html_e( 'Never show results (admin only)', 'decision-polls' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="default_poll_type"><?php esc_html_e( 'Default Poll Type', 'decision-polls' ); ?></label></th>
                        <td>
                            <select name="default_poll_type" id="default_poll_type">
                                <option value="standard" <?php selected( $default_poll_type, 'standard' ); ?>><?php esc_html_e( 'Standard (Single Choice)', 'decision-polls' ); ?></option>
                                <option value="multiple" <?php selected( $default_poll_type, 'multiple' ); ?>><?php esc_html_e( 'Multiple Choice', 'decision-polls' ); ?></option>
                                <option value="ranked" <?php selected( $default_poll_type, 'ranked' ); ?>><?php esc_html_e( 'Ranked Choice', 'decision-polls' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Frontend Creation', 'decision-polls' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( 'Frontend Creation', 'decision-polls' ); ?></span></legend>
                                <label for="allow_frontend_creation">
                                    <input name="allow_frontend_creation" type="checkbox" id="allow_frontend_creation" value="1" <?php checked( $allow_frontend_creation ); ?>>
                                    <?php esc_html_e( 'Allow polls to be created from the frontend', 'decision-polls' ); ?>
                                </label>
                                <br>
                                <label for="require_login_to_create">
                                    <input name="require_login_to_create" type="checkbox" id="require_login_to_create" value="1" <?php checked( $require_login_to_create ); ?>>
                                    <?php esc_html_e( 'Require login to create polls', 'decision-polls' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'If both options are enabled, only logged-in users can create polls from the frontend.', 'decision-polls' ); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'decision-polls' ); ?>">
            </p>
        </form>
        <?php
        
        echo '</div>';
    }
}
