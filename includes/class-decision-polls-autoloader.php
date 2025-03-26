<?php
/**
 * Autoloader Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle autoloading plugin classes.
 */
class Decision_Polls_Autoloader {
	/**
	 * Directories to search for classes.
	 *
	 * @var array
	 */
	private $dirs = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register base directories where classes can be found.
		$this->add_directory( '' );
		$this->add_directory( 'admin' );
		$this->add_directory( 'frontend' );
		$this->add_directory( 'core' );
		$this->add_directory( 'core/api' );
		$this->add_directory( 'core/models' );
		$this->add_directory( 'core/permissions' );
		
		// Register the autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Add a directory to the autoloader.
	 *
	 * @param string $dir Directory to add (relative to includes/).
	 */
	public function add_directory( $dir ) {
		$dir = trim( $dir, '/' );
		$this->dirs[] = trailingslashit( DECISION_POLLS_PLUGIN_DIR . 'includes/' . $dir );
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class_name Class name to autoload.
	 */
	public function autoload( $class_name ) {
		// Only autoload classes with our prefix.
		if ( strpos( $class_name, 'Decision_Polls_' ) !== 0 ) {
			return;
		}
		
		// Convert class name to file name.
		$file_name = $this->get_file_name_from_class( $class_name );
		
		// Search in all registered directories.
		foreach ( $this->dirs as $dir ) {
			$file = $dir . $file_name;
			
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}
	}

	/**
	 * Convert class name to file name.
	 *
	 * @param string $class_name Class name.
	 * @return string File name.
	 */
	private function get_file_name_from_class( $class_name ) {
		// Strip the prefix.
		$class_name = str_replace( 'Decision_Polls_', '', $class_name );
		
		// Convert to lowercase and replace underscores with hyphens.
		$file_name = strtolower( str_replace( '_', '-', $class_name ) );
		
		// Add the class prefix and extension.
		return 'class-' . $file_name . '.php';
	}
}

// Initialize the autoloader.
new Decision_Polls_Autoloader();
