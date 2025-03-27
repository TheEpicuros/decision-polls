<?php
/**
 * Polls List Template
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure $polls_data variable is available.
if ( ! isset( $polls_data ) || empty( $polls_data ) ) {
	return;
}

$polls = isset( $polls_data['polls'] ) ? $polls_data['polls'] : array();
$total_polls = isset( $polls_data['total'] ) ? absint( $polls_data['total'] ) : 0;
$current_page = isset( $polls_data['page'] ) ? absint( $polls_data['page'] ) : 1;
$per_page = isset( $polls_data['per_page'] ) ? absint( $polls_data['per_page'] ) : 10;
$total_pages = ceil( $total_polls / $per_page );

// Get current URL for pagination links.
$current_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$base_url = remove_query_arg( 'poll_page', $current_url );
?>

<div class="decision-polls-list-container">
	<?php if ( ! empty( $polls ) && is_array( $polls ) ) : ?>
		<div class="decision-polls-list">
			<?php foreach ( $polls as $poll ) : ?>
				<?php
				$poll_id = isset( $poll['id'] ) ? absint( $poll['id'] ) : 0;
				$poll_title = isset( $poll['title'] ) ? esc_html( $poll['title'] ) : '';
				$poll_description = isset( $poll['description'] ) ? esc_html( $poll['description'] ) : '';
				$poll_type = isset( $poll['type'] ) ? esc_html( $poll['type'] ) : 'standard';
				$created_at = isset( $poll['created_at'] ) ? $poll['created_at'] : '';
				$total_votes = isset( $poll['total_votes'] ) ? absint( $poll['total_votes'] ) : 0;

				// Format poll type for display.
				$poll_type_display = '';
				switch ( $poll_type ) {
					case 'standard':
						$poll_type_display = esc_html__( 'Standard', 'decision-polls' );
						break;
					case 'multiple':
						$poll_type_display = esc_html__( 'Multiple Choice', 'decision-polls' );
						break;
					case 'ranked':
						$poll_type_display = esc_html__( 'Ranked Choice', 'decision-polls' );
						break;
					default:
						$poll_type_display = esc_html( ucfirst( $poll_type ) );
						break;
				}

				// Format date.
				$formatted_date = '';
				if ( $created_at ) {
					$formatted_date = date_i18n( get_option( 'date_format' ), strtotime( $created_at ) );
				}

				// Build poll URL.
				$poll_url = add_query_arg( 'poll_id', $poll_id, get_permalink() );
				?>
				<div class="decision-polls-list-item">
					<h3 class="decision-polls-list-title">
						<a href="<?php echo esc_url( $poll_url ); ?>"><?php echo esc_html( $poll_title ); ?></a>
					</h3>
					
					<?php if ( ! empty( $poll_description ) ) : ?>
						<div class="decision-polls-list-description">
							<?php echo wp_kses_post( wp_trim_words( $poll_description, 20, '...' ) ); ?>
						</div>
					<?php endif; ?>
					
					<!-- Display actual poll results -->
					<?php
					// Get poll results
					$vote_model = new Decision_Polls_Vote();
					$results = $vote_model->get_results( $poll_id );
					
					// If we have results, show them
					if ( isset( $results['results'] ) && ! empty( $results['results'] ) ) :
						// Get total votes
						$poll_total_votes = isset( $results['total_votes'] ) ? absint( $results['total_votes'] ) : 0;
					?>
						<div class="decision-polls-preview-results">
							<?php foreach ( array_slice( $results['results'], 0, 3 ) as $result ) : ?>
								<?php
								$answer_text = isset( $result['text'] ) ? esc_html( $result['text'] ) : '';
								$percentage = isset( $result['percentage'] ) ? floatval( $result['percentage'] ) : 0;
								$formatted_percentage = number_format( $percentage, 1 );
								
								// For ranked polls, add rank indicators
								$rank_label = '';
								if ( 'ranked' === $poll_type ) {
									$index = array_search( $result, $results['results'], true );
									switch ( $index ) {
										case 0:
											$rank_label = '<span class="rank-indicator rank-first">1st</span> ';
											break;
										case 1:
											$rank_label = '<span class="rank-indicator rank-second">2nd</span> ';
											break;
										case 2:
											$rank_label = '<span class="rank-indicator rank-third">3rd</span> ';
											break;
									}
								}
								?>
								<div class="decision-polls-preview-result">
									<div class="decision-polls-preview-text">
										<?php echo $rank_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above ?>
										<?php echo esc_html( $answer_text ); ?>
										<span class="decision-polls-preview-percentage">(<?php echo esc_html( $formatted_percentage ); ?>%)</span>
									</div>
									<div class="decision-polls-preview-bar-container">
										<div class="decision-polls-preview-bar <?php echo 'ranked' === $poll_type ? 'rank-' . ( $index + 1 ) : ''; ?>" style="width: <?php echo esc_attr( $percentage . '%' ); ?>;"></div>
									</div>
								</div>
							<?php endforeach; ?>
							
							<?php if ( count( $results['results'] ) > 3 ) : ?>
								<div class="decision-polls-more-results">
									<a href="<?php echo esc_url( $poll_url ); ?>"><?php esc_html_e( 'View all results', 'decision-polls' ); ?></a>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					
					<div class="decision-polls-meta decision-polls-clearfix">
						<div class="decision-polls-type">
							<span><?php echo esc_html( $poll_type_display ); ?></span>
						</div>
						
						<?php if ( $formatted_date ) : ?>
							<div class="decision-polls-date">
								<?php echo esc_html( $formatted_date ); ?>
							</div>
						<?php endif; ?>
						
						<div class="decision-polls-votes">
							<?php
							printf(
								/* translators: %d: total number of votes */
								esc_html( _n( '%d vote', '%d votes', $total_votes, 'decision-polls' ) ),
								esc_html( $total_votes )
							);
							?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="decision-polls-pagination">
				<?php
				// Previous page link.
				if ( $current_page > 1 ) {
					$prev_url = add_query_arg( 'poll_page', $current_page - 1, $base_url );
					echo '<a href="' . esc_url( $prev_url ) . '" class="prev">&laquo; ' . esc_html__( 'Previous', 'decision-polls' ) . '</a>';
				}

				// Page numbers.
				$start_page = max( 1, $current_page - 2 );
				$end_page = min( $total_pages, $current_page + 2 );

				if ( $start_page > 1 ) {
					$first_url = add_query_arg( 'poll_page', 1, $base_url );
					echo '<a href="' . esc_url( $first_url ) . '">1</a>';
					if ( $start_page > 2 ) {
						echo '<span class="dots">...</span>';
					}
				}

				for ( $i = $start_page; $i <= $end_page; $i++ ) {
					if ( $i === $current_page ) {
						echo '<span class="current">' . esc_html( $i ) . '</span>';
					} else {
						$page_url = add_query_arg( 'poll_page', $i, $base_url );
						echo '<a href="' . esc_url( $page_url ) . '">' . esc_html( $i ) . '</a>';
					}
				}

				if ( $end_page < $total_pages ) {
					if ( $end_page < $total_pages - 1 ) {
						echo '<span class="dots">...</span>';
					}
					$last_url = add_query_arg( 'poll_page', $total_pages, $base_url );
					echo '<a href="' . esc_url( $last_url ) . '">' . esc_html( $total_pages ) . '</a>';
				}

				// Next page link.
				if ( $current_page < $total_pages ) {
					$next_url = add_query_arg( 'poll_page', $current_page + 1, $base_url );
					echo '<a href="' . esc_url( $next_url ) . '" class="next">' . esc_html__( 'Next', 'decision-polls' ) . ' &raquo;</a>';
				}
				?>
			</div>
		<?php endif; ?>
		
	<?php else : ?>
		<div class="decision-polls-empty">
			<p><?php esc_html_e( 'No polls found.', 'decision-polls' ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php if ( get_option( 'decision_polls_allow_frontend_creation', 1 ) ) : ?>
		<div class="decision-polls-create-link">
			<a href="<?php echo esc_url( add_query_arg( 'create_poll', '1', get_permalink() ) ); ?>" class="button decision-polls-create-button">
				<?php esc_html_e( 'Create New Poll', 'decision-polls' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
