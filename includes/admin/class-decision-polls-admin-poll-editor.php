<?php
/**
 * Admin Poll Editor Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling the admin poll editor page.
 */
class Decision_Polls_Admin_Poll_Editor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX handler for poll saving.
		add_action( 'wp_ajax_decision_polls_save_poll', array( $this, 'handle_save_poll_ajax' ) );
	}

	/**
	 * Handle poll save via AJAX.
	 */
	public function handle_save_poll_ajax() {
		check_ajax_referer( 'decision_polls_admin', 'nonce' );

		if ( ! current_user_can( 'create_decision_polls' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'decision-polls' ) ) );
		}

		parse_str( $_POST['data'], $form_data );

		// Validate required fields.
		if ( empty( $form_data['poll_title'] ) || empty( $form_data['poll_option'] ) || count( $form_data['poll_option'] ) < 2 ) {
			wp_send_json_error( array( 'message' => __( 'Please provide a title and at least two options.', 'decision-polls' ) ) );
		}

		// Sanitize poll data.
		$poll_data = array(
			'title'       => sanitize_text_field( $form_data['poll_title'] ),
			'description' => sanitize_textarea_field( $form_data['poll_description'] ),
			'type'        => sanitize_text_field( $form_data['poll_type'] ),
			'status'      => sanitize_text_field( $form_data['poll_status'] ),
			'is_private'  => isset( $form_data['poll_private'] ) ? 1 : 0,
		);

		// Handle multiple choice settings.
		if ( 'multiple' === $poll_data['type'] && isset( $form_data['poll_max_choices'] ) ) {
			$poll_data['multiple_choices'] = absint( $form_data['poll_max_choices'] );
		}

		// Process poll options.
		$poll_data['answers'] = array();
		foreach ( $form_data['poll_option'] as $option ) {
			if ( ! empty( $option ) ) {
				$poll_data['answers'][] = array(
					'text' => sanitize_text_field( $option ),
				);
			}
		}

		// Save poll.
		$poll_model = new Decision_Polls_Poll();
		$poll_id    = isset( $form_data['poll_id'] ) ? absint( $form_data['poll_id'] ) : 0;
		$result     = 0;

		if ( $poll_id > 0 ) {
			// Update existing poll.
			$poll_data['id'] = $poll_id;
			$result          = $poll_model->update( $poll_data );
		} else {
			// Create new poll.
			$result = $poll_model->create( $poll_data );
		}

		if ( $result ) {
			wp_send_json_success( array( 'poll_id' => $result ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save poll.', 'decision-polls' ) ) );
		}
	}

	/**
	 * Render the poll editor page.
	 */
	public static function render_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'create_decision_polls' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'decision-polls' ) );
		}

		// No need to check nonce when simply viewing the editor
		// The actual editing is protected via AJAX nonce checks

		$poll_id = isset( $_GET['poll_id'] ) ? absint( $_GET['poll_id'] ) : 0;
		$editing = ( $poll_id > 0 );
		$poll    = array();

		if ( $editing ) {
			$poll_model = new Decision_Polls_Poll();
			$poll       = $poll_model->get( $poll_id );

			if ( ! $poll ) {
				wp_die( esc_html__( 'Poll not found.', 'decision-polls' ) );
			}
		}

		$title = $editing ? esc_html__( 'Edit Poll', 'decision-polls' ) : esc_html__( 'Add New Poll', 'decision-polls' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $title ) . '</h1>';

		?>
		<form method="post" action="" id="decision-polls-admin-form">
			<?php wp_nonce_field( 'decision_polls_admin', 'decision_polls_nonce' ); ?>
			<input type="hidden" name="poll_id" value="<?php echo $editing ? esc_attr( $poll_id ) : ''; ?>">
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="poll_title"><?php esc_html_e( 'Poll Title', 'decision-polls' ); ?> <span class="required">*</span></label></th>
						<td>
							<input name="poll_title" type="text" id="poll_title" value="<?php echo $editing ? esc_attr( $poll['title'] ) : ''; ?>" class="regular-text" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="poll_description"><?php esc_html_e( 'Description', 'decision-polls' ); ?></label></th>
						<td>
							<textarea name="poll_description" id="poll_description" class="large-text" rows="3"><?php echo $editing ? esc_textarea( $poll['description'] ) : ''; ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional. Provide more context about your poll.', 'decision-polls' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="poll_type"><?php esc_html_e( 'Poll Type', 'decision-polls' ); ?></label></th>
						<td>
							<select name="poll_type" id="poll_type">
								<option value="standard" <?php selected( $editing && 'standard' === $poll['type'] ); ?>><?php esc_html_e( 'Standard (Single Choice)', 'decision-polls' ); ?></option>
								<option value="multiple" <?php selected( $editing && 'multiple' === $poll['type'] ); ?>><?php esc_html_e( 'Multiple Choice', 'decision-polls' ); ?></option>
								<option value="ranked" <?php selected( $editing && 'ranked' === $poll['type'] ); ?>><?php esc_html_e( 'Ranked Choice', 'decision-polls' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="poll_status"><?php esc_html_e( 'Status', 'decision-polls' ); ?></label></th>
						<td>
							<select name="poll_status" id="poll_status">
								<option value="draft" <?php selected( $editing && 'draft' === $poll['status'] ); ?>><?php esc_html_e( 'Draft', 'decision-polls' ); ?></option>
								<option value="published" <?php selected( ! $editing || ( $editing && 'published' === $poll['status'] ) ); ?>><?php esc_html_e( 'Published', 'decision-polls' ); ?></option>
								<option value="closed" <?php selected( $editing && 'closed' === $poll['status'] ); ?>><?php esc_html_e( 'Closed', 'decision-polls' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Poll Options', 'decision-polls' ); ?> <span class="required">*</span></th>
						<td>
							<div id="poll-options-container">
								<?php
								if ( $editing && ! empty( $poll['answers'] ) ) {
									foreach ( $poll['answers'] as $index => $answer ) {
										?>
										<div class="poll-option">
											<input type="text" name="poll_option[]" value="<?php echo esc_attr( $answer['text'] ); ?>" placeholder="
																										<?php
																										/* translators: %d: option number */
																										echo esc_attr( sprintf( __( 'Option %d', 'decision-polls' ), $index + 1 ) );
																										?>
											" required>
											<button type="button" class="button remove-option" <?php echo ( count( $poll['answers'] ) <= 2 ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Remove', 'decision-polls' ); ?></button>
										</div>
										<?php
									}
								} else {
									?>
									<div class="poll-option">
										<input type="text" name="poll_option[]" placeholder="<?php esc_attr_e( 'Option 1', 'decision-polls' ); ?>" required>
										<button type="button" class="button remove-option" disabled><?php esc_html_e( 'Remove', 'decision-polls' ); ?></button>
									</div>
									<div class="poll-option">
										<input type="text" name="poll_option[]" placeholder="<?php esc_attr_e( 'Option 2', 'decision-polls' ); ?>" required>
										<button type="button" class="button remove-option" disabled><?php esc_html_e( 'Remove', 'decision-polls' ); ?></button>
									</div>
									<?php
								}
								?>
							</div>
							<button type="button" class="button button-secondary add-option"><?php esc_html_e( 'Add Option', 'decision-polls' ); ?></button>
							<p class="description"><?php esc_html_e( 'Add at least two options for your poll.', 'decision-polls' ); ?></p>
						</td>
					</tr>
					<tr class="decision-polls-multiple-options" style="<?php echo ( $editing && 'multiple' === $poll['type'] ) ? '' : 'display: none;'; ?>">
						<th scope="row"><label for="poll_max_choices"><?php esc_html_e( 'Maximum Choices', 'decision-polls' ); ?></label></th>
						<td>
							<input type="number" name="poll_max_choices" id="poll_max_choices" min="0" value="<?php echo $editing && isset( $poll['multiple_choices'] ) ? esc_attr( $poll['multiple_choices'] ) : '0'; ?>">
							<p class="description"><?php esc_html_e( 'Maximum number of options a voter can select. Enter 0 for unlimited.', 'decision-polls' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="poll_private"><?php esc_html_e( 'Privacy', 'decision-polls' ); ?></label></th>
						<td>
							<label for="poll_private">
								<input type="checkbox" name="poll_private" id="poll_private" <?php checked( $editing && ! empty( $poll['is_private'] ) ); ?>>
								<?php esc_html_e( 'Make poll private', 'decision-polls' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Private polls are only accessible via direct link.', 'decision-polls' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $editing ? esc_attr__( 'Update Poll', 'decision-polls' ) : esc_attr__( 'Create Poll', 'decision-polls' ); ?>">
			</p>
		</form>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var $typeSelect = $('#poll_type');
			var $multipleOptions = $('.decision-polls-multiple-options');
			var $optionsContainer = $('#poll-options-container');
			
			// Toggle multiple choice options when type changes.
			$typeSelect.on('change', function() {
				if ( 'multiple' === $(this).val() ) {
					$multipleOptions.show();
				} else {
					$multipleOptions.hide();
				}
			});
			
			// Add a new option field.
			$('.add-option').on('click', function(e) {
				e.preventDefault();
				
				var optionCount = $optionsContainer.find('.poll-option').length + 1;
				var optionHtml = '<div class="poll-option">' +
					'<input type="text" name="poll_option[]" placeholder="Option ' + optionCount + '" required>' +
					'<button type="button" class="button remove-option">Remove</button>' +
					'</div>';
				
				$optionsContainer.append(optionHtml);
				
				// Enable all remove buttons if we have more than 2 options.
				if ( optionCount > 2 ) {
					$optionsContainer.find('.remove-option').prop('disabled', false);
				}
			});
			
			// Remove an option field.
			$optionsContainer.on('click', '.remove-option', function() {
				if ( $(this).prop('disabled') ) {
					return;
				}
				
				$(this).closest('.poll-option').remove();
				
				// Update placeholders for remaining options.
				$optionsContainer.find('.poll-option').each(function(index) {
					$(this).find('input').attr('placeholder', 'Option ' + (index + 1));
				});
				
				// Disable remove buttons if we have 2 or fewer options.
				if ( $optionsContainer.find('.poll-option').length <= 2 ) {
					$optionsContainer.find('.remove-option').prop('disabled', true);
				}
			});
			
			// Process form submission.
			$('#decision-polls-admin-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $submit = $form.find('#submit');
				var data = $form.serialize();
				
				// Disable submit button.
				$submit.prop('disabled', true);
				
				// Show a loading indicator.
				$submit.val($submit.val() + '...');
				
				// Submit form data using AJAX.
				$.ajax({
					url: ajaxurl, // WordPress global AJAX URL.
					type: 'POST',
					data: {
						action: 'decision_polls_save_poll',
						data: data,
						nonce: $('#decision_polls_nonce').val()
					},
					success: function(response) {
						if ( response.success ) {
							// Redirect to polls list.
							window.location.href = 'admin.php?page=decision-polls&message=created';
						} else {
							// Show error.
							alert(response.data.message || '<?php echo esc_js( __( 'Failed to save poll. Please try again.', 'decision-polls' ) ); ?>');
							$submit.prop('disabled', false);
							$submit.val($submit.val().replace('...', ''));
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'decision-polls' ) ); ?>');
						$submit.prop('disabled', false);
						$submit.val($submit.val().replace('...', ''));
					}
				});
			});
		});
		</script>
		<?php

		echo '</div>';
	}
}
