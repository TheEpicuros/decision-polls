<?php
/**
 * Utility script to refresh rewrite rules
 * 
 * This script forces WordPress to flush and regenerate rewrite rules,
 * which is especially helpful after modifying custom URL handling.
 *
 * @package Decision_Polls
 */

// Bootstrap WordPress.
require_once dirname( __FILE__ ) . '/../../../wp-load.php';

// Flush rewrite rules.
flush_rewrite_rules( true );

// Clear the rewrite rules flushed flag to force regeneration.
delete_option( 'decision_polls_rewrite_rules_flushed' );

// Output message.
echo "Rewrite rules have been flushed successfully.\n";
echo "Please visit the Permalinks settings page in WordPress admin to complete the process.\n";
