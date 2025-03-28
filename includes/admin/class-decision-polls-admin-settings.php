<?php
/**
 * Admin Settings Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling the admin settings page.
 */
class Decision_Polls_Admin_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'decision_polls_settings',
			'decision_polls_allow_guests',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);

		register_setting(
			'decision_polls_settings',
			'decision_polls_results_view',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'after_vote',
			)
		);

		register_setting(
			'decision_polls_settings',
			'decision_polls_default_poll_type',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'standard',
			)
		);

		register_setting(
			'decision_polls_settings',
			'decision_polls_require_login_to_create',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);

		register_setting(
			'decision_polls_settings',
			'decision_polls_allow_frontend_creation',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_decision_polls' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'decision-polls' ) );
		}

		// Get current settings.
		$allow_guests            = get_option( 'decision_polls_allow_guests', 1 );
		$results_view            = get_option( 'decision_polls_results_view', 'after_vote' );
		$default_poll_type       = get_option( 'decision_polls_default_poll_type', 'standard' );
		$require_login_to_create = get_option( 'decision_polls_require_login_to_create', 1 );
		$allow_frontend_creation = get_option( 'decision_polls_allow_frontend_creation', 0 );

		// Check if form was submitted.
		if ( isset( $_POST['decision_polls_settings_nonce'] ) ) {
			check_admin_referer( 'decision_polls_settings', 'decision_polls_settings_nonce' );

			// Update settings.
			update_option( 'decision_polls_allow_guests', isset( $_POST['allow_guests'] ) ? 1 : 0 );
			update_option( 'decision_polls_results_view', sanitize_text_field( wp_unslash( $_POST['results_view'] ) ) );
			update_option( 'decision_polls_default_poll_type', sanitize_text_field( wp_unslash( $_POST['default_poll_type'] ) ) );
			update_option( 'decision_polls_require_login_to_create', isset( $_POST['require_login_to_create'] ) ? 1 : 0 );
			update_option( 'decision_polls_allow_frontend_creation', isset( $_POST['allow_frontend_creation'] ) ? 1 : 0 );

			// Add settings saved message.
			add_settings_error(
				'decision_polls_settings',
				'settings_updated',
				__( 'Settings saved.', 'decision-polls' ),
				'updated'
			);
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings', 'decision-polls' ); ?></h1>

			<?php settings_errors( 'decision_polls_settings' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'decision_polls_settings', 'decision_polls_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Voting Settings', 'decision-polls' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Voting Settings', 'decision-polls' ); ?></legend>
									<label for="allow_guests">
										<input name="allow_guests" type="checkbox" id="allow_guests" value="1" <?php checked( $allow_guests ); ?>>
										<?php esc_html_e( 'Allow guest voting (users who are not logged in)', 'decision-polls' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="results_view"><?php esc_html_e( 'Results Display', 'decision-polls' ); ?></label></th>
							<td>
								<select name="results_view" id="results_view">
									<option value="after_vote" <?php selected( 'after_vote' === $results_view ); ?>><?php esc_html_e( 'After voting', 'decision-polls' ); ?></option>
									<option value="always" <?php selected( 'always' === $results_view ); ?>><?php esc_html_e( 'Always visible', 'decision-polls' ); ?></option>
									<option value="after_closed" <?php selected( 'after_closed' === $results_view ); ?>><?php esc_html_e( 'Only after poll is closed', 'decision-polls' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'When to display poll results to voters.', 'decision-polls' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="default_poll_type"><?php esc_html_e( 'Default Poll Type', 'decision-polls' ); ?></label></th>
							<td>
								<select name="default_poll_type" id="default_poll_type">
									<option value="standard" <?php selected( 'standard' === $default_poll_type ); ?>><?php esc_html_e( 'Standard (Single Choice)', 'decision-polls' ); ?></option>
									<option value="multiple" <?php selected( 'multiple' === $default_poll_type ); ?>><?php esc_html_e( 'Multiple Choice', 'decision-polls' ); ?></option>
									<option value="ranked" <?php selected( 'ranked' === $default_poll_type ); ?>><?php esc_html_e( 'Ranked Choice', 'decision-polls' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'The default poll type for new polls.', 'decision-polls' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Frontend Poll Creation', 'decision-polls' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Frontend Poll Creation', 'decision-polls' ); ?></legend>
									<label for="allow_frontend_creation">
										<input name="allow_frontend_creation" type="checkbox" id="allow_frontend_creation" value="1" <?php checked( $allow_frontend_creation ); ?>>
										<?php esc_html_e( 'Allow poll creation from frontend', 'decision-polls' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'If enabled, polls can be created using shortcodes on the frontend.', 'decision-polls' ); ?></p>
									<br>
									<label for="require_login_to_create">
										<input name="require_login_to_create" type="checkbox" id="require_login_to_create" value="1" <?php checked( $require_login_to_create ); ?>>
										<?php esc_html_e( 'Require users to be logged in to create polls', 'decision-polls' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'decision-polls' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}
}
