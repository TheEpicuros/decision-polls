<?php
/**
 * Custom Endpoints Class - Backward Compatibility File
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the new properly named file.
require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-custom-endpoints.php';

// For backward compatibility with any code that may be using the original class name directly.
if ( ! class_exists( 'Decision_Polls_Custom_Endpoints_BC' ) ) {
	/**
	 * Alias the class to maintain backward compatibility.
	 */
	class_alias( 'Decision_Polls_Custom_Endpoints', 'Decision_Polls_Custom_Endpoints_BC' );
}
