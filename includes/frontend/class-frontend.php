<?php
/**
 * Frontend Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle frontend functionality.
 */
class Decision_Polls_Frontend {
	/**
	 * Flag to determine if assets should be loaded.
	 *
	 * @var bool
	 */
	private $needs_assets = false;

	/**
	 * Flag for standard poll shortcode detection.
	 *
	 * @var bool
	 */
	private $has_poll = false;

	/**
	 * Flag for ranked poll shortcode detection.
	 *
	 * @var bool
	 */
	private $has_ranked_poll = false;

	/**
	 * Flag for polls list shortcode detection.
	 *
	 * @var bool
	 */
	private $has_poll_list = false;

	/**
	 * Flag for poll creator shortcode detection.
	 *
	 * @var bool
	 */
	private $has_poll_creator = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		// Enqueue assets when shortcodes are used.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Add shortcode listener to content.
		add_filter( 'the_content', array( $this, 'detect_shortcodes' ) );

		// Add query parameter handler.
		add_action( 'wp', array( $this, 'handle_query_parameters' ) );
	}

	/**
	 * Register scripts and styles.
	 */
	public function register_assets() {
		// Register main styles.
		wp_register_style(
			'decision-polls',
			plugins_url( 'assets/css/frontend.css', DECISION_POLLS_PLUGIN_FILE ),
			array(),
			DECISION_POLLS_VERSION
		);

		// Register ranked polls styles.
		wp_register_style(
			'decision-polls-ranked',
			plugins_url( 'assets/css/ranked-polls.css', DECISION_POLLS_PLUGIN_FILE ),
			array( 'decision-polls' ),
			DECISION_POLLS_VERSION
		);

		// Register script.
		wp_register_script(
			'decision-polls',
			plugins_url( 'assets/js/frontend.js', DECISION_POLLS_PLUGIN_FILE ),
			array( 'jquery' ),
			DECISION_POLLS_VERSION,
			true
		);
	}

	/**
	 * Detect shortcodes in content.
	 *
	 * @param string $content Post content.
	 * @return string Original content.
	 */
	public function detect_shortcodes( $content ) {
		// Single poll detection.
		if ( has_shortcode( $content, 'decision_poll' ) ) {
			$this->needs_assets = true;
			$this->has_poll     = true;

			// Check for ranked choice polls.
			if ( preg_match( '/decision_poll([^]]*?)type=["\']ranked["\']/', $content ) ) {
				$this->has_ranked_poll = true;
			}
		}

		// Polls list detection.
		if ( has_shortcode( $content, 'decision_polls' ) ) {
			$this->needs_assets  = true;
			$this->has_poll_list = true;
		}

		// Poll creator detection.
		if ( has_shortcode( $content, 'decision_poll_creator' ) ) {
			$this->needs_assets     = true;
			$this->has_poll_creator = true;
		}

		return $content;
	}

	/**
	 * Handle query parameters like poll_id.
	 */
	public function handle_query_parameters() {
		// Check for poll_id in query string.
		$poll_id = isset( $_GET['poll_id'] ) ? absint( $_GET['poll_id'] ) : 0;
		if ( $poll_id > 0 ) {
			$this->needs_assets = true;

			// Get poll to determine type.
			$poll_model = new Decision_Polls_Poll();
			$poll       = $poll_model->get( $poll_id );

			if ( $poll && isset( $poll['type'] ) && 'ranked' === $poll['type'] ) {
				$this->has_ranked_poll = true;
			}

			// Add filter to inject poll into the content.
			add_filter( 'the_content', array( $this, 'inject_poll' ) );
		}

		// Check for create_poll parameter.
		if ( isset( $_GET['create_poll'] ) ) {
			$this->needs_assets     = true;
			$this->has_poll_creator = true;

			// Add filter to inject poll creator form into the content.
			add_filter( 'the_content', array( $this, 'inject_poll_creator' ) );
		}
	}

	/**
	 * Inject the poll into the content when poll_id is in the URL.
	 *
	 * @param string $content The original post content.
	 * @return string Modified content with poll injected.
	 */
	public function inject_poll( $content ) {
		// Check for poll_id in the URL.
		$poll_id = isset( $_GET['poll_id'] ) ? absint( $_GET['poll_id'] ) : 0;

		if ( $poll_id <= 0 ) {
			return $content;
		}

		// Check if this content already has a poll shortcode with this ID.
		if ( has_shortcode( $content, 'decision_poll' ) &&
			preg_match( '/\[decision_poll([^]]*?)id=["\']' . $poll_id . '["\']/', $content ) ) {
			return $content;
		}

		// Get the poll to verify it exists.
		$poll_model = new Decision_Polls_Poll();
		$poll       = $poll_model->get( $poll_id );

		if ( $poll ) {
			// Create shortcode output using the shortcode class.
			$shortcode_output = Decision_Polls_Shortcodes::poll_shortcode( array( 'id' => $poll_id ) );

			// Replace any existing poll list with our specific poll.
			if ( has_shortcode( $content, 'decision_polls' ) ) {
				$pattern = '/\[decision_polls(.*?)\]/';
				$content = preg_replace( $pattern, '', $content );
			}

			// Add the poll at the top of the content.
			return $shortcode_output . $content;
		}

		return $content;
	}

	/**
	 * Inject the poll creator form into the content.
	 *
	 * @param string $content The original post content.
	 * @return string Modified content with poll creator form.
	 */
	public function inject_poll_creator( $content ) {
		// Only add poll creator if it's not already in the content.
		if ( ! has_shortcode( $content, 'decision_poll_creator' ) ) {
			// We need to show the actual form when ?create_poll=1 is in the URL.
			if ( isset( $_GET['create_poll'] ) ) {
				// Use the actual poll creator shortcode.
				$shortcode = Decision_Polls_Shortcodes::poll_creator_shortcode( array() );
				$content   = $shortcode . $content;
			} else {
				// Just show a link to the creation page.
				$create_url    = add_query_arg( 'create_poll', '1', get_permalink() );
				$creator_link  = '<div class="decision-polls-create-notice">';
				$creator_link .= '<p>' . esc_html__( 'You can create a new poll here:', 'decision-polls' ) . '</p>';
				$creator_link .= '<p><a href="' . esc_url( $create_url ) . '" class="button decision-polls-create-button">';
				$creator_link .= esc_html__( 'Create New Poll', 'decision-polls' ) . '</a></p>';
				$creator_link .= '</div>';

				$content = $creator_link . $content;
			}
		}

		return $content;
	}

	/**
	 * Enqueue necessary assets when shortcodes are used.
	 */
	public function enqueue_assets() {
		global $post;

		// If we're in the loop and we have a post.
		if ( is_singular() && is_a( $post, 'WP_Post' ) ) {
			// Check if post content has our shortcodes.
			$this->detect_shortcodes( $post->post_content );
		}

		// Enqueue poll assets if needed.
		if ( $this->needs_assets ) {
			// Common assets.
			wp_enqueue_style( 'decision-polls' );
			wp_enqueue_script( 'decision-polls' );

			// Additional scripts and styles for specific poll types.
			if ( $this->has_ranked_poll ) {
				// For ranked choice polls, use jQuery UI Sortable.
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_style( 'decision-polls-ranked' );

				// jQuery UI Touch Punch for mobile drag and drop support.
				wp_enqueue_script(
					'jquery-ui-touch-punch',
					plugins_url( 'assets/js/jquery.ui.touch-punch.min.js', DECISION_POLLS_PLUGIN_FILE ),
					array( 'jquery-ui-sortable' ),
					'0.2.3',
					true
				);
			}

			// Localize script with translations and settings.
			wp_localize_script(
				'decision-polls',
				'decisionPollsL10n',
				array(
					'maxChoicesError'   => esc_html__( 'You can select a maximum of {max} options.', 'decision-polls' ),
					'selectOptionError' => esc_html__( 'Please select at least one option.', 'decision-polls' ),
					'voteSuccess'       => esc_html__( 'Your vote has been recorded. Thank you!', 'decision-polls' ),
					'voteError'         => esc_html__( 'There was an error submitting your vote. Please try again.', 'decision-polls' ),
					'totalVotes'        => esc_html__( 'Total votes: {total}', 'decision-polls' ),
					'votes'             => esc_html__( '{votes} votes', 'decision-polls' ),
					'lastUpdated'       => esc_html__( 'Last updated: {time}', 'decision-polls' ),
					'pollCreated'       => esc_html__( 'Poll created successfully!', 'decision-polls' ),
					'pollCreateError'   => esc_html__( 'An error occurred while creating the poll. Please try again.', 'decision-polls' ),
					'option'            => esc_html__( 'Option', 'decision-polls' ),
					'remove'            => esc_html__( 'Remove', 'decision-polls' ),
					'pollLink'          => esc_url( add_query_arg( 'poll_id', 'POLL_ID', get_permalink() ) ),
					'viewPoll'          => esc_html__( 'View your poll', 'decision-polls' ),
				)
			);

			// Localize script with API data.
			$api_url = rest_url( 'decision-polls/v1' );
			$nonce   = wp_create_nonce( 'wp_rest' );

			wp_localize_script(
				'decision-polls',
				'decisionPollsAPI',
				array(
					'url'      => esc_url_raw( $api_url ),
					'nonce'    => $nonce,
					'adminUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				)
			);
		}
	}
}
