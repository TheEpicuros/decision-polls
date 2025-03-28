# Decision Polls

A modern WordPress polling plugin that supports standard (single choice), multiple choice, and ranked choice polls with a clean, responsive design.

## Features

- **Three poll types:**
  - Standard (single choice)
  - Multiple choice (select multiple options)
  - Ranked choice (drag and drop ranking)
  
- **Frontend poll creation:**
  - Allow your users to create their own polls
  - Control permissions with options
  
- **Modern Design:**
  - Clean, responsive interface
  - Mobile-friendly voting and results
  - Animated results display
  
- **Developer-friendly:**
  - Well-organized modular code
  - Follows WordPress coding standards
  - Extensible architecture

## Usage

### Shortcodes

The plugin provides three main shortcodes:

#### 1. Display a Single Poll

```
[decision_poll id="123"]
```

**Parameters:**
- `id` (required): The ID of the poll to display
- `show_results` (optional): Set to "true" to directly show results instead of the voting form

#### 2. Display a List of Polls

```
[decision_polls per_page="10" status="published" type="" author_id="0"]
```

**Parameters:**
- `per_page` (optional): Number of polls to show per page (default: 10)
- `status` (optional): Poll status to show (default: "published")
- `type` (optional): Filter by poll type - "standard", "multiple", or "ranked"
- `author_id` (optional): Show polls by a specific user

#### 3. Display Poll Creation Form

```
[decision_poll_creator redirect="https://example.com/thank-you"]
```

**Parameters:**
- `redirect` (optional): URL to redirect to after poll creation

### Admin Settings

Configure the plugin under **Settings > Decision Polls**:

- Enable/disable frontend poll creation
- Require login for voting
- Require login for poll creation
- Default poll settings

## Styling

The plugin includes a well-structured CSS file with appropriate class names that follow a consistent naming convention. You can easily customize the appearance of polls by adding custom CSS to your theme or using the WordPress Customizer.

## Developer Information

### Custom Templates

You can override the default templates by copying files from the `templates` directory to your theme's `decision-polls` directory.

### Hooks and Filters

The plugin provides various hooks and filters to extend its functionality:

```php
// Modify poll options
add_filter('decision_polls_poll_options', 'my_custom_poll_options', 10, 2);

// Custom validation
add_filter('decision_polls_validate_vote', 'my_custom_validation', 10, 3);

// After vote actions
add_action('decision_polls_after_vote', 'my_after_vote_function', 10, 3);
```

### REST API

The plugin registers custom REST API endpoints for getting poll data and submitting votes:

- `GET /wp-json/decision-polls/v1/polls` - Get all polls
- `GET /wp-json/decision-polls/v1/polls/{id}` - Get a specific poll
- `POST /wp-json/decision-polls/v1/votes` - Submit a vote

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Epicuros, vibe coding.
