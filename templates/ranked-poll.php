<?php
/**
 * Ranked Choice Poll Template
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

<div id="<?php echo esc_attr( $poll_container_id ); ?>" class="decision-poll decision-poll-ranked" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
	<div class="decision-poll-header">
		<h3 class="decision-poll-title"><?php echo esc_html( $poll['title'] ); ?></h3>
		<?php if ( ! empty( $poll['description'] ) ) : ?>
			<div class="decision-poll-description"><?php echo wp_kses_post( $poll['description'] ); ?></div>
		<?php endif; ?>
		<div class="decision-poll-instructions">
			<?php esc_html_e( 'Drag and drop options to rank them in your preferred order (top = most preferred).', 'decision-polls' ); ?>
		</div>
	</div>

	<form class="decision-poll-form" action="" method="post">
		<?php wp_nonce_field( 'decision_polls_vote', 'decision_polls_nonce' ); ?>
		<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll_id ); ?>">
		<input type="hidden" name="poll_type" value="ranked">
		
		<div class="decision-poll-options decision-poll-sortable">
			<?php if ( ! empty( $poll['answers'] ) && is_array( $poll['answers'] ) ) : ?>
				<?php foreach ( $poll['answers'] as $index => $answer ) : ?>
					<div class="decision-poll-option" data-answer-id="<?php echo esc_attr( $answer['id'] ); ?>">
						<div class="decision-poll-drag-handle">
							<span class="dashicons dashicons-menu"></span>
						</div>
						<div class="decision-poll-option-rank"><?php echo esc_html( $index + 1 ); ?></div>
						<div class="decision-poll-option-text"><?php echo esc_html( $answer['text'] ); ?></div>
						<input type="hidden" name="ranked_answers[]" value="<?php echo esc_attr( $answer['id'] ); ?>">
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
	var $container = $('#<?php echo esc_js( $poll_container_id ); ?>');
	var $form = $container.find('.decision-poll-form');
	var $sortable = $container.find('.decision-poll-sortable');
	
	// Initialize sortable for drag and drop ranking.
	$sortable.sortable({
		handle: '.decision-poll-drag-handle',
		axis: 'y',
		containment: 'parent',
		update: function(event, ui) {
			// Update rank numbers and hidden inputs.
			updateRanks();
		}
	});
	
	// Function to update rank numbers and hidden inputs after sorting.
	function updateRanks() {
		$sortable.find('.decision-poll-option').each(function(index) {
			var $option = $(this);
			var answerId = $option.data('answer-id');
			
			// Update visible rank number.
			$option.find('.decision-poll-option-rank').text(index + 1);
			
			// Update hidden input value.
			$option.find('input[name="ranked_answers[]"]').val(answerId);
		});
	}
	
	// Handle form submission.
	$form.on('submit', function(e) {
		e.preventDefault();
		
		var $message = $form.find('.decision-poll-message');
		var $submit = $form.find('.decision-poll-submit');
		var pollId = $form.find('input[name="poll_id"]').val();
		var rankedAnswers = $form.find('input[name="ranked_answers[]"]').map(function() {
			return $(this).val();
		}).get();
		
		// Clear previous messages.
		$message.empty().hide();
		
		// Disable submit button.
		$submit.prop('disabled', true);
		
		// Submit vote via AJAX.
		$.ajax({
			url: decisionPollsAPI.url + '/polls/' + pollId + '/vote',
			method: 'POST',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', decisionPollsAPI.nonce );
			},
			data: {
				answers: rankedAnswers,
				type: 'ranked',
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
