<?php
/**
 * Poll Results Template
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add last-resort meta refresh if this was reached by a direct vote action and there's no JavaScript
if ( isset( $_COOKIE['decision_polls_refresh_results'] ) ) {
	$poll_id = isset( $poll['id'] ) ? absint( $poll['id'] ) : 0;
	if ( $poll_id > 0 ) {
		// Clear the cookie
		setcookie( 'decision_polls_refresh_results', '', time() - 3600, '/' );
		// Add meta refresh as absolute last resort (5 second delay)
		echo '<meta http-equiv="refresh" content="5;url=' . esc_url( add_query_arg( array( 'poll_id' => $poll_id, 'show_results' => '1', 'ts' => time() ), get_permalink() ) ) . '">';
	}
}

// Ensure variables are available.
if ( ! isset( $poll ) || empty( $poll ) || ! isset( $results ) || empty( $results ) ) {
	return;
}

// Set unique ID for this poll instance.
$poll_id           = absint( $poll['id'] );
$poll_container_id = 'decision-poll-results-' . $poll_id;

// Sanitize and prepare results data.
$total_votes  = isset( $results['total_votes'] ) ? absint( $results['total_votes'] ) : 0;
$results_data = isset( $results['results'] ) ? $results['results'] : array();
$last_updated = isset( $results['last_updated'] ) ? $results['last_updated'] : '';
?>

<div id="<?php echo esc_attr( $poll_container_id ); ?>" class="decision-poll decision-poll-results" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
	<div class="decision-poll-header">
		<h3 class="decision-poll-title"><?php echo esc_html( $poll['title'] ); ?></h3>
		<?php if ( ! empty( $poll['description'] ) ) : ?>
			<div class="decision-poll-description"><?php echo wp_kses_post( $poll['description'] ); ?></div>
		<?php endif; ?>
		<div class="decision-poll-total-votes">
			<?php
			printf(
				/* translators: %d: total number of votes */
				esc_html( _n( '%d vote', '%d votes', $total_votes, 'decision-polls' ) ),
				esc_html( $total_votes )
			);
			?>
		</div>
	</div>

	<div class="decision-poll-results-list">
		<?php if ( ! empty( $results_data ) && is_array( $results_data ) ) : ?>
			<?php foreach ( $results_data as $result ) : ?>
				<?php
				$answer_id   = isset( $result['id'] ) ? absint( $result['id'] ) : 0;
				$answer_text = isset( $result['text'] ) ? esc_html( $result['text'] ) : '';
				$votes       = isset( $result['votes'] ) ? absint( $result['votes'] ) : 0;
				$percentage  = isset( $result['percentage'] ) ? floatval( $result['percentage'] ) : 0;

				// Format percentage with 1 decimal place.
				$formatted_percentage = number_format( $percentage, 1 );

				// Determine the bar color based on rank.
				$poll_type    = isset( $poll['type'] ) ? $poll['type'] : 'standard';
				$bar_class    = 'decision-poll-result-bar';
				$rank_display = '';

				// Special handling for ranked choice polls.
				if ( 'ranked' === $poll_type ) {
					$bar_class .= ' decision-poll-ranked-bar';

					// Calculate the rank index (assuming results are already sorted by rank).
					$rank_index = array_search( $result, $results_data, true );
					$rank_text  = '';

					// Create rank label (1st, 2nd, 3rd, etc.).
					switch ( $rank_index + 1 ) {
						case 1:
							$rank_text  = esc_html__( '1st choice', 'decision-polls' );
							$bar_class .= ' rank-first';
							break;
						case 2:
							$rank_text  = esc_html__( '2nd choice', 'decision-polls' );
							$bar_class .= ' rank-second';
							break;
						case 3:
							$rank_text  = esc_html__( '3rd choice', 'decision-polls' );
							$bar_class .= ' rank-third';
							break;
						default:
							/* translators: %d: the rank number (4, 5, etc.) */
							$rank_text  = sprintf( esc_html__( '%dth choice', 'decision-polls' ), $rank_index + 1 );
							$bar_class .= ' rank-other';
							break;
					}

					$rank_display = '<span class="decision-poll-rank-indicator">' . $rank_text . '</span>';
				}
				?>
				<div class="decision-poll-result" data-answer-id="<?php echo esc_attr( $answer_id ); ?>">
					<div class="decision-poll-result-text">
						<?php echo esc_html( $answer_text ); ?>
						<?php echo $rank_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above ?>
					</div>
					<div class="decision-poll-result-bar-container">
						<div class="<?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo esc_attr( $percentage . '%' ); ?>;">
							<span class="decision-poll-result-percentage"><?php echo esc_html( $formatted_percentage . '%' ); ?></span>
						</div>
					</div>
					<?php if ( 'ranked' !== $poll_type ) : ?>
						<div class="decision-poll-result-votes">
							<?php
							printf(
								/* translators: %d: number of votes for this option */
								esc_html( _n( '%d vote', '%d votes', $votes, 'decision-polls' ) ),
								esc_html( $votes )
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="decision-poll-error">
				<?php esc_html_e( 'No results available for this poll.', 'decision-polls' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $last_updated ) ) : ?>
		<div class="decision-poll-footer">
			<div class="decision-poll-last-updated">
				<?php
				/* translators: %s: date and time of last update */
				printf( esc_html__( 'Last updated: %s', 'decision-polls' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_updated ) ) ) );
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( isset( $poll['show_return_link'] ) && $poll['show_return_link'] ) : ?>
		<div class="decision-poll-return-link">
			<a href="<?php echo esc_url( remove_query_arg( 'show_results' ) ); ?>"><?php esc_html_e( '← Back to polls', 'decision-polls' ); ?></a>
		</div>
	<?php endif; ?>
	
	<?php if ( get_option( 'decision_polls_allow_frontend_creation', 1 ) ) : ?>
		<div class="decision-polls-create-link">
			<a href="<?php echo esc_url( home_url( 'poll/create/' ) ); ?>" class="button decision-polls-create-button">
				<?php esc_html_e( 'Create New Poll', 'decision-polls' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
