<?php
/**
 * Base Model Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Model class for all Decision Polls models.
 */
abstract class Decision_Polls_Model {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Gets table name with prefix.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return string Table name with prefix.
	 */
	protected function get_table_name( $table_name ) {
		return $this->wpdb->prefix . $table_name;
	}

	/**
	 * Sanitizes data based on type.
	 *
	 * @param mixed  $data Data to sanitize.
	 * @param string $type Type of data.
	 * @return mixed Sanitized data.
	 */
	protected function sanitize( $data, $type = 'text' ) {
		switch ( $type ) {
			case 'text':
				return sanitize_text_field( $data );
			case 'textarea':
				return sanitize_textarea_field( $data );
			case 'int':
				return intval( $data );
			case 'float':
				return floatval( $data );
			case 'bool':
				return (bool) $data;
			case 'array':
				return array_map( array( $this, 'sanitize' ), $data );
			default:
				return sanitize_text_field( $data );
		}
	}

	/**
	 * Format data for database insertion.
	 *
	 * @param array $data Data to format.
	 * @return array Formatted data.
	 */
	abstract protected function format_for_db( $data );

	/**
	 * Format data from database for API response.
	 *
	 * @param object $data Data to format.
	 * @return array Formatted data.
	 */
	abstract protected function format_for_api( $data );
}
