# Decision Polls

A modern WordPress polling plugin with simple, multiple choice, and ranked choice polls. Allows frontend users to create and participate in polls.

## Features

- **Multiple Poll Types**
  - Standard (single choice)
  - Multiple choice
  - Ranked choice

- **Frontend Poll Creation**
  - Users can create their own polls from the frontend
  - Clean, responsive design
  - Easy-to-use intuitive interface

- **Modern Architecture**
  - Well-organized, maintainable code
  - REST API for all operations
  - Follows WordPress coding standards
  - Responsive design that works on all devices

## Installation

1. Download the plugin zip file
2. Upload to your WordPress site via Plugins > Add New > Upload Plugin
3. Activate the plugin

## Usage

### Shortcodes

The plugin provides the following shortcodes:

1. Display a poll:
```
[decision_poll id="123"]
```

2. Display poll results:
```
[decision_poll_results id="123" type="bar"]
```

3. Display poll creation form (frontend):
```
[decision_poll_form type="standard"]
```

### Creating a Poll in Admin

1. Go to the Decision Polls menu in your WordPress admin
2. Click "Add New"
3. Fill in the poll details
4. Publish the poll
5. Use the provided shortcode to display it on your site

### Creating a Poll on Frontend

1. Place the `[decision_poll_form]` shortcode on any page
2. Users can fill in the form to create a new poll
3. On submission, users will receive a shortcode they can use

## Testing

To test the plugin:

1. Create a new page in WordPress
2. Add the following shortcodes to test different features:

```
<h2>Create a Poll</h2>
[decision_poll_form]

<h2>Example Poll</h2>
[decision_poll id="1"]

<h2>Poll Results</h2>
[decision_poll_results id="1"]
```

3. View the page to see the poll creation form, an example poll, and poll results

## File Structure

```
decision-polls/
├── assets/
│   ├── css/
│   │   ├── admin.css      # Admin styles
│   │   └── frontend.css   # Frontend styles
│   ├── js/
│   │   ├── admin.js       # Admin scripts (placeholder)
│   │   └── frontend.js    # Frontend scripts
│   └── dist/              # For compiled React components
├── includes/
│   ├── admin/
│   │   └── class-admin.php
│   ├── core/
│   │   ├── api/           # REST API endpoints
│   │   ├── models/        # Data models
│   │   ├── permissions/   # API permissions
│   │   ├── class-api.php  # Main API coordinator
│   │   └── class-install.php
│   ├── frontend/
│   │   └── class-frontend.php
│   └── class-decision-polls-autoloader.php
├── languages/             # For translations
├── decision-polls.php     # Main plugin file
├── API-STRUCTURE.md       # API documentation
└── README.md
```

## Development

The plugin is organized into several components:

1. Core functionality in `includes/core/`
2. Admin interface in `includes/admin/`
3. Frontend in `includes/frontend/`
4. API with endpoints in `includes/core/api/`

The API follows a modular structure as documented in API-STRUCTURE.md.
