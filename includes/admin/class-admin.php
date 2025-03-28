<?php
/**
 * Admin Class Loader - Compatibility File
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the main admin class.
require_once plugin_dir_path( __FILE__ ) . 'class-decision-polls-admin.php';

/**
 * Backward compatibility class for the Decision_Polls_Admin class.
 *
 * This class extends Decision_Polls_Admin for backward compatibility.
 * New code should use Decision_Polls_Admin and related classes directly.
 */
class Admin extends Decision_Polls_Admin {
	// This class exists for backward compatibility only.
}
