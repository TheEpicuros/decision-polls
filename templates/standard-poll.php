<?php
/**
 * Standard Poll Template
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure $poll variable is available.
if ( ! isset( $poll ) || empty( $poll ) ) {
	return;
}

// Set unique ID for this poll instance.
$poll_id = absint( $poll['id'] );
$poll_container_id = 'decision-poll-' . $poll_id;
?>

<div id="<?php echo esc_attr( $poll_container_id ); ?>" class="decision-poll decision-poll-standard" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
	<div class="decision-poll-header">
		<h3 class="decision-poll-title"><?php echo esc_html( $poll['title'] ); ?></h3>
		<?php if ( ! empty( $poll['description'] ) ) : ?>
			<div class="decision-poll-description"><?php echo wp_kses_post( $poll['description'] ); ?></div>
		<?php endif; ?>
	</div>

	<form class="decision-poll-form" action="" method="post">
		<?php wp_nonce_field( 'decision_polls_vote', 'decision_polls_nonce' ); ?>
		<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll_id ); ?>">
		<input type="hidden" name="poll_type" value="standard">
		
		<div class="decision-poll-options">
			<?php if ( ! empty( $poll['answers'] ) && is_array( $poll['answers'] ) ) : ?>
				<?php foreach ( $poll['answers'] as $answer ) : ?>
					<div class="decision-poll-option">
						<label>
							<input type="radio" name="poll_answer" value="<?php echo esc_attr( $answer['id'] ); ?>" required>
							<span class="decision-poll-option-text"><?php echo esc_html( $answer['text'] ); ?></span>
						</label>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="decision-poll-error">
					<?php esc_html_e( 'No options available for this poll.', 'decision-polls' ); ?>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="decision-poll-actions">
			<button type="submit" class="decision-poll-submit button"><?php esc_html_e( 'Vote', 'decision-polls' ); ?></button>
		</div>
		
		<div class="decision-poll-message" style="display: none;"></div>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Handle form submission.
	$('#<?php echo esc_js( $poll_container_id ); ?> .decision-poll-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $message = $form.find('.decision-poll-message');
		var $submit = $form.find('.decision-poll-submit');
		var pollId = $form.find('input[name="poll_id"]').val();
		var answerId = $form.find('input[name="poll_answer"]:checked').val();
		
		// Clear previous messages.
		$message.empty().hide();
		
		// Disable submit button.
		$submit.prop('disabled', true);
		
		// Check if an option is selected.
		if (!answerId) {
			$message.html('<p class="error">' + decisionPollsL10n.selectOptionError + '</p>').fadeIn();
			$submit.prop('disabled', false);
			return;
		}
		
		// Submit vote via AJAX.
		$.ajax({
			url: decisionPollsAPI.url + '/polls/' + pollId + '/vote',
			method: 'POST',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', decisionPollsAPI.nonce );
			},
			data: {
				answers: [answerId],
				type: 'standard',
				nonce: $('#decision_polls_nonce').val()
			},
			success: function(response) {
				// Show success message.
				$message.html('<p class="success">' + decisionPollsL10n.voteSuccess + '</p>').fadeIn();
				
				// If results are available, update the display.
				if (response.data && response.data.results) {
					// Delay slightly to let the user see the success message.
					setTimeout(function() {
						// Replace poll form with results.
						var results = response.data.results;
						var resultsHtml = '<div class="decision-poll-results-list">';
						
						// Add total votes.
						resultsHtml += '<div class="decision-poll-total-votes">' + 
							decisionPollsL10n.totalVotes.replace('{total}', results.total_votes) + 
							'</div>';
						
						// Add results for each option.
						$.each(results.results, function(index, result) {
							resultsHtml += '<div class="decision-poll-result">' +
								'<div class="decision-poll-result-text">' + result.text + '</div>' +
								'<div class="decision-poll-result-bar-container">' +
									'<div class="decision-poll-result-bar" style="width: ' + result.percentage + '%;">' +
										'<span class="decision-poll-result-percentage">' + Math.round(result.percentage * 10) / 10 + '%</span>' +
									'</div>' +
								'</div>' +
								'<div class="decision-poll-result-votes">' + 
									decisionPollsL10n.votes.replace('{votes}', result.votes) + 
								'</div>' +
							'</div>';
						});
						
						resultsHtml += '</div>';
						
						// Add footer with timestamp.
						resultsHtml += '<div class="decision-poll-footer">' +
							'<div class="decision-poll-last-updated">' +
								decisionPollsL10n.lastUpdated.replace('{time}', new Date().toLocaleString()) +
							'</div>' +
						'</div>';
						
						// Replace form with results.
						$form.fadeOut(300, function() {
							$(this).replaceWith(resultsHtml);
						});
					}, 1000);
				} else {
					// Reload page after a delay to show results.
					setTimeout(function() {
						window.location.reload();
					}, 1500);
				}
			},
			error: function(xhr) {
				// Re-enable submit button.
				$submit.prop('disabled', false);
				
				// Show error message.
				var errorMessage = xhr.responseJSON && xhr.responseJSON.message 
					? xhr.responseJSON.message 
					: decisionPollsL10n.voteError;
				
				$message.html('<p class="error">' + errorMessage + '</p>').fadeIn();
			}
		});
	});
});
</script>
