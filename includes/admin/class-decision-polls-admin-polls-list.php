<?php
/**
 * Admin Polls List Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling the admin polls list page.
 */
class Decision_Polls_Admin_Polls_List {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX handler for bulk actions if needed.
		add_action( 'wp_ajax_decision_polls_bulk_action', array( $this, 'handle_bulk_action_ajax' ) );
	}

	/**
	 * Process bulk actions.
	 *
	 * @return array Results of the bulk operation.
	 */
	private static function process_bulk_actions() {
		$result = array(
			'success_count' => 0,
			'error_count'   => 0,
		);

		// Check if we have an action and selected polls.
		if ( ! isset( $_POST['bulk_action'] ) || '-1' === $_POST['bulk_action'] || empty( $_POST['poll_ids'] ) || ! is_array( $_POST['poll_ids'] ) ) {
			return $result;
		}

		// Verify nonce.
		if ( ! isset( $_POST['decision_polls_bulk_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['decision_polls_bulk_nonce'] ) ), 'decision_polls_bulk_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'decision-polls' ) );
		}

		$action   = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) );
		$poll_ids = array_map( 'absint', $_POST['poll_ids'] );

		// Process delete action.
		if ( 'delete' === $action ) {
			$poll_model = new Decision_Polls_Poll();

			foreach ( $poll_ids as $poll_id ) {
				$delete_result = $poll_model->delete( $poll_id );
				if ( $delete_result ) {
					$result['success_count']++;
				} else {
					$result['error_count']++;
				}
			}

			// Show a message based on the results.
			if ( $result['success_count'] > 0 ) {
				// Translators: %d is the number of polls deleted.
				add_settings_error(
					'decision_polls',
					'polls_deleted',
					sprintf(
						/* translators: %d: number of polls deleted */
						_n(
							'%d poll deleted successfully.',
							'%d polls deleted successfully.',
							$result['success_count'],
							'decision-polls'
						),
						$result['success_count']
					),
					'success'
				);
			}

			if ( $result['error_count'] > 0 ) {
				// Translators: %d is the number of polls that failed to delete.
				add_settings_error(
					'decision_polls',
					'polls_delete_failed',
					sprintf(
						/* translators: %d: number of polls that failed to delete */
						_n(
							'Failed to delete %d poll.',
							'Failed to delete %d polls.',
							$result['error_count'],
							'decision-polls'
						),
						$result['error_count']
					),
					'error'
				);
			}
		}

		return $result;
	}

	/**
	 * Handle bulk action via AJAX.
	 */
	public function handle_bulk_action_ajax() {
		check_ajax_referer( 'decision_polls_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_decision_polls' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'decision-polls' ) ) );
		}

		$result = self::process_bulk_actions();
		wp_send_json_success( $result );
	}

	/**
	 * Render the polls list page.
	 */
	public static function render_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_decision_polls' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'decision-polls' ) );
		}

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'All Polls', 'decision-polls' ) . '</h1>';
		echo ' <a href="' . esc_url( admin_url( 'admin.php?page=decision-polls-add-new' ) ) . '" class="page-title-action">' . esc_html__( 'Add New', 'decision-polls' ) . '</a>';
		echo '<hr class="wp-header-end">';

		// Process bulk actions.
		self::process_bulk_actions();

		// Get all polls.
		$poll_model = new Decision_Polls_Poll();
		$args       = array(
			'per_page' => 20,
			'page'     => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
		);

		$polls_data  = $poll_model->get_all( $args );
		$polls       = isset( $polls_data['polls'] ) ? $polls_data['polls'] : array();
		$total_polls = isset( $polls_data['total'] ) ? absint( $polls_data['total'] ) : 0;

		if ( empty( $polls ) ) {
			echo '<div class="notice notice-info">';
			echo '<p>' . esc_html__( 'No polls found. Create your first poll!', 'decision-polls' ) . '</p>';
			echo '</div>';
		} else {
			// Display polls in a WP list table style.
			?>
			<form method="post">
				<?php wp_nonce_field( 'decision_polls_bulk_action', 'decision_polls_bulk_nonce' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'decision-polls' ); ?></label>
						<select name="bulk_action" id="bulk-action-selector-top">
							<option value="-1"><?php esc_html_e( 'Bulk Actions', 'decision-polls' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'decision-polls' ); ?></option>
						</select>
						<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'decision-polls' ); ?>">
					</div>
					<br class="clear">
				</div>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column">
								<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'decision-polls' ); ?></label>
								<input id="cb-select-all-1" type="checkbox">
							</td>
							<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Title', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Votes', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'decision-polls' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $polls as $poll ) : ?>
						<?php
						$poll_id     = isset( $poll['id'] ) ? absint( $poll['id'] ) : 0;
						$edit_link   = admin_url( 'admin.php?page=decision-polls-add-new&poll_id=' . $poll_id );
						$delete_link = wp_nonce_url( admin_url( 'admin.php?page=decision-polls&action=delete&poll_id=' . $poll_id ), 'delete-poll_' . $poll_id );
						$view_link   = add_query_arg( 'poll_id', $poll_id, get_home_url() );

						$poll_type         = isset( $poll['type'] ) ? $poll['type'] : 'standard';
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

						$status      = isset( $poll['status'] ) ? $poll['status'] : 'draft';
						$date        = isset( $poll['created_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $poll['created_at'] ) ) : '';
						$total_votes = isset( $poll['total_votes'] ) ? absint( $poll['total_votes'] ) : 0;
						?>
						<tr>
							<td class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $poll_id ); ?>">
								<?php
								/* translators: %s: poll title */
								echo esc_html( sprintf( __( 'Select %s', 'decision-polls' ), $poll['title'] ) );
								?>
								</label>
								<input id="cb-select-<?php echo esc_attr( $poll_id ); ?>" type="checkbox" name="poll_ids[]" value="<?php echo esc_attr( $poll_id ); ?>">
							</td>
							<td class="title column-title has-row-actions column-primary">
								<strong><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $poll['title'] ); ?></a></strong>
								<div class="row-actions">
									<span class="edit"><a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit', 'decision-polls' ); ?></a> | </span>
									<span class="view"><a href="<?php echo esc_url( $view_link ); ?>" target="_blank"><?php esc_html_e( 'View', 'decision-polls' ); ?></a> | </span>
									<span class="trash"><a href="<?php echo esc_url( $delete_link ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this poll?', 'decision-polls' ); ?>');"><?php esc_html_e( 'Delete', 'decision-polls' ); ?></a></span>
								</div>
								<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'decision-polls' ); ?></span></button>
							</td>
							<td><?php echo esc_html( $poll_type_display ); ?></td>
							<td><?php echo esc_html( $total_votes ); ?></td>
							<td><?php echo esc_html( ucfirst( $status ) ); ?></td>
							<td><?php echo esc_html( $date ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<td id="cb" class="manage-column column-cb check-column">
								<label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Select All', 'decision-polls' ); ?></label>
								<input id="cb-select-all-2" type="checkbox">
							</td>
							<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Title', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Votes', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'decision-polls' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'decision-polls' ); ?></th>
						</tr>
					</tfoot>
				</table>
				<?php

				// Pagination.
				$total_pages = ceil( $total_polls / $args['per_page'] );
				if ( $total_pages > 1 ) {
					echo '<div class="tablenav-pages">';
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $args['page'],
							)
						)
					);
					echo '</div>';
				}
				?>
			</form>
			<?php
		}

		echo '</div>';
	}
}
