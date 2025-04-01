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
