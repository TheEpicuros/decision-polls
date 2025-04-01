# Ranked Choice Poll Rebuild Plan

## What Was Removed

We have successfully removed the ranked choice poll functionality from the Decision Polls plugin to provide a clean slate for rebuilding this feature. Here's what we modified:

1. Removed ranked poll initialization from `assets/js/frontend.js`
2. Removed ranked poll template handlers from `includes/class-shortcodes.php`
3. Removed ranked poll detection in `includes/frontend/class-frontend.php`
4. Removed ranked poll option from `templates/poll-creator.php`
5. Simplified the poll results handling in `includes/core/models/class-vote.php`
6. Simplified the results display in `templates/results.php`

## Database Considerations

The existing database structure is still intact and has the following elements that will be useful for the rebuild:

1. Vote table with a `vote_value` column that was previously used to store ranking values
2. Results table that caches poll results 
3. Answers table that stores poll options

## Rebuild Planning

### Phase 1: Core Models and Data Handling

1. **Enhance the Vote Model**
   - Implement specific handling for ranked choice votes
   - Ensure vote values correspond to rank positions
   - Add validation specific to ranked choice polls

2. **Results Processing**
   - Implement the ranked choice algorithm
   - Properly weight votes based on rank
   - Cache results with rank information

### Phase 2: Frontend Experience

1. **Create New Ranked Poll Template**
   - Implement drag-and-drop ranking UI
   - Clear instructions for users
   - Visual indicators for rank position

2. **Results Display**
   - Show results with rank indicators (1st choice, 2nd choice, etc.)
   - Use consistent color coding for different ranks
   - Ensure consistency between immediate AJAX display and page refresh

### Phase 3: JavaScript Functionality

1. **Poll Initialization**
   - Add back ranked poll detection
   - Initialize jQuery UI sortable for drag-and-drop
   - Implement rank number updates

2. **Vote Submission**
   - Transform ranked list into properly formatted data
   - Submit with correct ranked vote structure
   - Process server response correctly

3. **Results Display**
   - Ensure results are displayed in rank order
   - Add visual indicators for different ranks
   - Do not re-sort results client-side

### Phase 4: Testing

1. Create comprehensive test cases for ranked polls
2. Test the entire flow from creation to voting to results
3. Test with multiple users/votes to ensure aggregation works
4. Verify consistency across browsers and devices

## Implementation Considerations

- Keep a clear separation between data and presentation
- Document all ranked choice-specific code
- Add extensive comments explaining how ranking works
- Consider adding hooks for future extensibility
- Implement feature detection to avoid JS errors

This clean-slate approach will allow us to build a more robust, maintainable ranked choice polling system that correctly displays votes in the user's preferred order.
