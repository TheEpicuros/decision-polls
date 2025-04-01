# Ranked Poll Issues and Fixes

After examining the code, I've identified the issue with ranked polls not correctly showing the voted ranking. Here are the problems and solutions:

## Issue 1: Results Ordering in Class-Vote.php

The `get_results` method in `includes/core/models/class-vote.php` is always ordering results by vote count, even for ranked polls. This isn't optimal for ranked polls where we want to show results in rank order.

### Fix:

Modify the SQL query in the `get_results` method to sort differently for ranked polls:

```php
// Around line 171 in includes/core/models/class-vote.php
// Change this:
"SELECT r.*, a.answer_text FROM $results_table r
 JOIN $answers_table a ON r.answer_id = a.id
 WHERE r.poll_id = %d
 ORDER BY r.votes_count DESC, a.sort_order ASC"

// To this (handling ranked polls explicitly):
$order_by = ($poll['type'] === 'ranked') ? 'r.votes_count DESC' : 'r.votes_count DESC, a.sort_order ASC';
"SELECT r.*, a.answer_text FROM $results_table r
 JOIN $answers_table a ON r.answer_id = a.id
 WHERE r.poll_id = %d
 ORDER BY $order_by"
```

Also, modify the return value to include the poll type:

```php
// Add 'poll_type' to the return array (around line 223)
return array(
    'poll_id'      => (int) $poll_id,
    'poll_type'    => $poll['type'],  // Add this line
    'total_votes'  => (int) $total_votes,
    'results'      => $formatted_results,
    'last_updated' => !empty($results) ? $results[0]->last_calculated : current_time('mysql'),
);
```

## Issue 2: Results Template Display

The `templates/results.php` file has code for displaying ranked choice indicators, but it's using `array_search()` which may not work correctly for associative arrays.

### Fix:

Modify the ranked display in the results.php file around line 99:

```php
// Change this:
$rank_index = array_search($result, $results_data, true);

// To this (using the explicit rank field we added in get_results):
$rank_index = isset($result['rank']) ? $result['rank'] - 1 : array_search($result, $results_data, true);
```

## Implementation Steps

1. Make these changes to the respective files
2. Test with a ranked poll to ensure rankings display properly
3. Verify that standard and multiple-choice polls still function normally
