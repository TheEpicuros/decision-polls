<?php
/**
 * Decision Polls Custom Endpoints Class - Forward compatibility wrapper
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the original custom endpoints class if not already included.
require_once plugin_dir_path( __FILE__ ) . 'class-custom-endpoints.php';

// We're using class_alias in the original file, but if for some reason that fails,
// create a wrapper class for complete compatibility.
if ( ! class_exists( 'Decision_Polls_Custom_Endpoints' ) ) {
	/**
	 * Wrapper class for the Custom_Endpoints class.
	 * This ensures backward compatibility after restoring the original endpoints implementation.
	 */
	class Decision_Polls_Custom_Endpoints extends Custom_Endpoints {
		// The original class handles everything - this just provides a compatible class name.
	}
}
