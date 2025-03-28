<?php
/**
 * Poll Creator Template
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Unique ID for this form instance.
$form_id = 'decision-poll-creator-' . uniqid();

// Get redirect URL from shortcode attributes.
$redirect_url = isset( $atts['redirect'] ) && ! empty( $atts['redirect'] ) ? $atts['redirect'] : '';
?>

<div id="<?php echo esc_attr( $form_id ); ?>" class="decision-poll-creator">
	<h2><?php esc_html_e( 'Create a New Poll', 'decision-polls' ); ?></h2>
	
	<form class="decision-poll-creator-form" method="post">
		<?php wp_nonce_field( 'decision_polls_create', 'decision_polls_creator_nonce' ); ?>
		<input type="hidden" id="decision_polls_creator_nonce" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'decision_polls_create' ) ); ?>" />
		
		<div class="decision-poll-creator-field">
			<label for="poll_title"><?php esc_html_e( 'Poll Title', 'decision-polls' ); ?> <span class="required">*</span></label>
			<input type="text" name="poll_title" id="poll_title" required>
		</div>
		
		<div class="decision-poll-creator-field">
			<label for="poll_description"><?php esc_html_e( 'Description', 'decision-polls' ); ?></label>
			<textarea name="poll_description" id="poll_description"></textarea>
			<p class="description"><?php esc_html_e( 'Optional. Provide more context about your poll.', 'decision-polls' ); ?></p>
		</div>
		
		<div class="decision-poll-creator-field">
			<label for="poll_type"><?php esc_html_e( 'Poll Type', 'decision-polls' ); ?></label>
			<select name="poll_type" id="poll_type">
				<option value="standard"><?php esc_html_e( 'Standard (Single Choice)', 'decision-polls' ); ?></option>
				<option value="multiple"><?php esc_html_e( 'Multiple Choice', 'decision-polls' ); ?></option>
				<option value="ranked"><?php esc_html_e( 'Ranked Choice', 'decision-polls' ); ?></option>
			</select>
		</div>
		
		<div class="decision-poll-multiple-options" style="display: none;">
			<div class="decision-poll-creator-field">
				<label for="poll_max_choices"><?php esc_html_e( 'Maximum Choices', 'decision-polls' ); ?></label>
				<input type="number" name="poll_max_choices" id="poll_max_choices" min="0" value="0">
				<p class="description"><?php esc_html_e( 'Maximum number of options a voter can select. Enter 0 for unlimited.', 'decision-polls' ); ?></p>
			</div>
		</div>
		
		<div class="decision-poll-creator-field">
			<label><?php esc_html_e( 'Poll Options', 'decision-polls' ); ?> <span class="required">*</span></label>
			<p class="description"><?php esc_html_e( 'Add at least two options for your poll.', 'decision-polls' ); ?></p>
			
			<div class="decision-poll-creator-options">
				<div class="decision-poll-creator-option">
					<input type="text" name="poll_option[]" placeholder="<?php esc_attr_e( 'Option 1', 'decision-polls' ); ?>" required>
					<button type="button" class="decision-poll-creator-remove-option" disabled><?php esc_html_e( 'Remove', 'decision-polls' ); ?></button>
				</div>
				<div class="decision-poll-creator-option">
					<input type="text" name="poll_option[]" placeholder="<?php esc_attr_e( 'Option 2', 'decision-polls' ); ?>" required>
					<button type="button" class="decision-poll-creator-remove-option" disabled><?php esc_html_e( 'Remove', 'decision-polls' ); ?></button>
				</div>
			</div>
			
			<button type="button" class="decision-poll-creator-add-option button-secondary">
				<?php esc_html_e( 'Add Option', 'decision-polls' ); ?>
			</button>
		</div>
		
		<div class="decision-poll-creator-field">
			<label for="poll_private">
				<input type="checkbox" name="poll_private" id="poll_private">
				<?php esc_html_e( 'Make poll private', 'decision-polls' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Private polls are only accessible via direct link.', 'decision-polls' ); ?></p>
		</div>
		
		<?php if ( ! empty( $redirect_url ) ) : ?>
			<input type="hidden" name="redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>">
		<?php endif; ?>
		
		<div class="decision-poll-creator-actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Create Poll', 'decision-polls' ); ?></button>
		</div>
		
		<div class="decision-poll-message" style="display: none;"></div>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var $form = $('#<?php echo esc_js( $form_id ); ?> .decision-poll-creator-form');
	var $optionsContainer = $form.find('.decision-poll-creator-options');
	var $addButton = $form.find('.decision-poll-creator-add-option');
	var $typeSelect = $form.find('select[name="poll_type"]');
	var $multipleOptions = $form.find('.decision-poll-multiple-options');
	var $message = $form.find('.decision-poll-message');
	
	// Initially hide or show multiple choice options based on selected type.
	toggleMultipleOptions();
	
	// Add a new option field.
	$addButton.on('click', function(e) {
		e.preventDefault();
		
		var optionCount = $optionsContainer.find('.decision-poll-creator-option').length + 1;
		var optionHtml = '<div class="decision-poll-creator-option">' +
			'<input type="text" name="poll_option[]" placeholder="' + decisionPollsL10n.option + ' ' + optionCount + '" required>' +
			'<button type="button" class="decision-poll-creator-remove-option">' + decisionPollsL10n.remove + '</button>' +
			'</div>';
		
		$optionsContainer.append(optionHtml);
		
		// Enable all remove buttons if we have more than 2 options.
		if (optionCount > 2) {
			$optionsContainer.find('.decision-poll-creator-remove-option').prop('disabled', false);
		}
	});
	
	// Remove an option field.
	$optionsContainer.on('click', '.decision-poll-creator-remove-option', function() {
		if ($(this).prop('disabled')) {
			return;
		}
		
		$(this).parent('.decision-poll-creator-option').remove();
		
		// Update placeholders for remaining options.
		$optionsContainer.find('.decision-poll-creator-option').each(function(index) {
			$(this).find('input').attr('placeholder', decisionPollsL10n.option + ' ' + (index + 1));
		});
		
		// Disable remove buttons if we have 2 or fewer options.
		if ($optionsContainer.find('.decision-poll-creator-option').length <= 2) {
			$optionsContainer.find('.decision-poll-creator-remove-option').prop('disabled', true);
		}
	});
	
	// Toggle multiple choice options when type changes.
	$typeSelect.on('change', toggleMultipleOptions);
	
	// Function to toggle multiple choices options visibility.
	function toggleMultipleOptions() {
		if ($typeSelect.val() === 'multiple') {
			$multipleOptions.show();
		} else {
			$multipleOptions.hide();
		}
	}
	
	// Handle form submission.
	$form.on('submit', function(e) {
		e.preventDefault();
		
		var $submit = $form.find('button[type="submit"]');
		
		// Check if form is already being submitted to prevent duplicates
		if ($submit.prop('disabled')) {
			return false;
		}
		
		// Clear previous messages.
		$message.empty().hide();
		
		// Disable submit button to prevent multiple submissions
		$submit.prop('disabled', true);
		
		// Show loading indicator
		$message.html('<p class="info">Creating your poll...</p>').fadeIn();
		
		// Get form data.
		var formData = new FormData(this);
		var data = {
			title: formData.get('poll_title'),
			description: formData.get('poll_description') || '',
			type: formData.get('poll_type'),
			status: 'published',
			is_private: formData.get('poll_private') === 'on',
			nonce: $('#decision_polls_creator_nonce').val()
		};
		
		// Get options.
		var options = [];
		$optionsContainer.find('input[name="poll_option[]"]').each(function() {
			var value = $(this).val().trim();
			if (value) {
				options.push(value);
			}
		});
		
		// Validate options.
		if (options.length < 2) {
			$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + ': ' + 
				 '<?php echo esc_js( __( 'At least two poll options are required.', 'decision-polls' ) ); ?></p>').fadeIn();
			$submit.prop('disabled', false);
			return;
		}
		
		data.answers = options;
		
		// Add multiple choices limit if applicable.
		if (data.type === 'multiple') {
			data.multiple_choices = parseInt(formData.get('poll_max_choices'), 10) || 0;
		}
		
		$.ajax({
			url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
			method: 'POST',
			data: {
				action: 'decision_polls_create_poll',
				...data
			},
			success: function(response) {
				if (response.success) {
					// Show success message.
					$message.html('<p class="success">' + decisionPollsL10n.pollCreated + '</p>').fadeIn();
					
					// Check if redirect URL is provided.
					var redirectUrl = $form.find('input[name="redirect_url"]').val();
					if (redirectUrl) {
						setTimeout(function() {
							window.location.href = redirectUrl;
						}, 1500);
						return;
					}
					
					// Clear form.
					$form[0].reset();
					$optionsContainer.find('.decision-poll-creator-option').not(':first, :nth-child(2)').remove();
					$optionsContainer.find('.decision-poll-creator-option input').val('');
					
					// Show link to view poll and automatically redirect after a delay
					// Use clean URL structure
					var pollUrl = window.location.protocol + '//' + window.location.host + '/poll/' + response.data.poll.id + '/';
					
					$message.append('<p><a href="' + pollUrl + '" class="button">' + 
						decisionPollsL10n.viewPoll + '</a></p>');
						
					// Redirect to the poll after 1.5 seconds
					setTimeout(function() {
						window.location.href = pollUrl;
					}, 1500);
				} else {
					$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + 
						(response.data && response.data.message ? ': ' + response.data.message : '') + 
						'</p>').fadeIn();
					$submit.prop('disabled', false);
				}
			},
			error: function() {
				$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + '</p>').fadeIn();
				$submit.prop('disabled', false);
			}
		});
	});
});
</script>
