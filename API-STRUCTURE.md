# Decision Polls API Structure

The API system for Decision Polls has been refactored into a modular, maintainable architecture.

## Architecture Overview

```
includes/
├── core/
│   ├── api/
│   │   ├── class-api-base.php        # Base API setup and registration
│   │   ├── class-api-polls.php       # Poll-related endpoints
│   │   └── class-api-votes.php       # Vote-related endpoints
│   ├── permissions/
│   │   └── class-api-permissions.php # Central permission logic
│   ├── models/
│   │   ├── class-model.php           # Base model class
│   │   ├── class-poll.php            # Poll object model
│   │   └── class-vote.php            # Vote object model
│   └── class-api.php                 # Main API coordinator
```

## Key Components

### Models

Models encapsulate database operations and business logic. They're responsible for:
- Sanitizing and validating data
- Performing CRUD operations
- Formatting data for API responses

### Permissions

The permissions class centralizes all permission checks, making it easier to:
- Maintain consistent authorization rules
- Modify permissions in a single location
- Reuse permission logic across endpoints

### API Endpoints

API endpoint classes handle:
- Registering REST routes
- Processing requests
- Calling appropriate model methods
- Formatting responses

## Available Endpoints

### Polls

- `GET /polls` - List all polls
- `GET /polls/{id}` - Get a specific poll
- `POST /polls` - Create a new poll
- `PUT /polls/{id}` - Update a poll
- `DELETE /polls/{id}` - Delete a poll
- `GET /user/polls` - Get current user's polls

### Votes

- `POST /polls/{id}/vote` - Submit a vote for a poll
- `GET /polls/{id}/results` - Get poll results

## Testing the API

You can test the API using WordPress's built-in REST API testing tools or with external tools like Postman.

### Example: Create a Poll

```
POST /wp-json/decision-polls/v1/polls
Content-Type: application/json
X-WP-Nonce: [your_nonce]

{
  "title": "Favorite Programming Language",
  "description": "What's your favorite programming language?",
  "type": "standard",
  "answers": [
    "JavaScript",
    "PHP",
    "Python",
    "Ruby"
  ]
}
```

### Example: Submit a Vote

```
POST /wp-json/decision-polls/v1/polls/1/vote
Content-Type: application/json
X-WP-Nonce: [your_nonce]

{
  "answers": [2]  // ID of the selected answer
}
```

### Example: Get Results

```
GET /wp-json/decision-polls/v1/polls/1/results
X-WP-Nonce: [your_nonce]
```

## Benefits of This Architecture

1. **Separation of Concerns**: Each class has a single responsibility
2. **Reusability**: Models can be used by both API and regular PHP code
3. **Testability**: Smaller, focused classes are easier to test
4. **Maintainability**: Changes to one component won't affect others
5. **Extensibility**: Easy to add new endpoints, models, or features

This architecture follows WordPress coding standards and common REST API practices, making it easy to understand and extend.
