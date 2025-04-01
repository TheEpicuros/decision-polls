<?php
/**
 * Ranked Poll Display Fix
 * 
 * This file contains the necessary changes to fix the ranked poll display issue.
 * The changes should be applied to includes/core/models/class-vote.php
 */

/**
 * Issue: Ranked polls are not showing the proper sorting in results.
 * 
 * Problems identified:
 * 1. Results are sorted by votes_count DESC instead of by rank
 * 2. The ranked order not properly reflected in the results display
 * 
 * The fix modifies the get_results method in class-vote.php to handle ranked polls differently.
 */

// IMPLEMENTATION INSTRUCTIONS:
// Find the get_results method in includes/core/models/class-vote.php (around line 139)
// Replace the method with this improved version

/**
 * Get poll results.
 *
 * @param int $poll_id Poll ID.
 * @return array|WP_Error Poll results or error.
 */
public function get_results( $poll_id ) {
    $poll_model = new Decision_Polls_Poll();
    $poll       = $poll_model->get( $poll_id );

    if ( ! $poll ) {
        return new WP_Error( 'not_found', 'Poll not found', array( 'status' => 404 ) );
    }
    
    // Get results from cache or calculate if not cached.
    $results_table = $this->get_table_name( self::RESULTS_TABLE_NAME );
    $answers_table = $this->get_table_name( 'decision_poll_answers' );
    
    // Different ordering for ranked polls vs standard/multiple polls
    $is_ranked = ( $poll['type'] === 'ranked' );
    
    if ( $is_ranked ) {
        // For ranked polls, order by votes_count DESC (higher points = higher rank)
        $order_by = "r.votes_count DESC";
    } else {
        // For standard/multiple polls, order by votes_count DESC, then sort_order
        $order_by = "r.votes_count DESC, a.sort_order ASC";
    }
    
    $results = $this->wpdb->get_results(
        $this->wpdb->prepare(
            "SELECT r.*, a.answer_text FROM $results_table r
             JOIN $answers_table a ON r.answer_id = a.id
             WHERE r.poll_id = %d
             ORDER BY $order_by",
            $poll_id
        )
    );

    // If no results cached, calculate them.
    if ( empty( $results ) ) {
        $this->update_results_cache( $poll_id );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT r.*, a.answer_text FROM $results_table r
                 JOIN $answers_table a ON r.answer_id = a.id
                 WHERE r.poll_id = %d
                 ORDER BY $order_by",
                $poll_id
            )
        );
    }

    // Get total votes - fixed query to correctly count unique voters.
    $votes_table = $this->get_table_name( self::TABLE_NAME );
    $total_votes = $this->wpdb->get_var(
        $this->wpdb->prepare(
            "SELECT COUNT(DISTINCT voter_id) FROM (
                SELECT CASE
                    WHEN user_id > 0 THEN CONCAT('user:', user_id)
                    ELSE CONCAT('ip:', user_ip)
                END as voter_id
                FROM $votes_table
                WHERE poll_id = %d
            ) AS voters",
            $poll_id
        )
    );

    // Format results for API.
    $formatted_results = array();
    
    // For ranked choice polls, also store the rank
    $rank = 0;
    foreach ( $results as $result ) {
        $rank++;
        $result_data = array(
            'id'         => (int) $result->answer_id,
            'text'       => $result->answer_text,
            'votes'      => (int) $result->votes_count,
            'percentage' => (float) $result->percentage,
        );
        
        // For ranked polls, add an explicit rank field
        if ( $is_ranked ) {
            $result_data['rank'] = $rank;
        }
        
        $formatted_results[] = $result_data;
    }

    return array(
        'poll_id'      => (int) $poll_id,
        'poll_type'    => $poll['type'],
        'total_votes'  => (int) $total_votes,
        'results'      => $formatted_results,
        'last_updated' => ! empty( $results ) ? $results[0]->last_calculated : current_time( 'mysql' ),
    );
}
