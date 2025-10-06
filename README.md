# V4 API Documentation

## Authentication

### POST /v4/send-login-otp

Send OTP for login

### POST /v4/verify-login-otp

Verify login OTP

### POST /v4/child-login

Child user login

## Profile Management

### GET /v4/profile

Get user profile data

### POST /v4/profile-batch

Get profile batch data

### POST /v4/update-profile

Update user profile

**Request Body:**

```json
{
    "first_name": "string (optional, max 255)",
    "last_name": "string (optional, max 255)",
    "email": "string (optional, email)",
    "phone": "string (optional, max 20)",
    "country": "string (optional, max 100)",
    "state": "string (optional, max 100)",
    "city": "string (optional, max 100)",
    "date_of_birth": "date (optional, before today)",
    "zip": "string (optional, max 20)",
    "is_onboarded": "boolean (optional)",
    "enable_private_account": "boolean (optional)",
    "receive_news_offers": "boolean (optional)",
    "terms_accepted": "boolean (required for first-time onboarding if role is not coach/parent)",
    "profile_photo": "file (optional, image, max 5MB)",

    // Player-specific fields
    "teams": "array (optional)",
    "leagues": "array (optional)",
    "handedness": "string (optional, in: left, right, ambidextrous)",
    "weight": "numeric (optional)",
    "height": "numeric (optional)",
    "position": "string (optional, max 100)",
    "gender": "string (optional, in: male, female, other)",

    // Coach-specific fields
    "leagues": "array (optional)",
    "teams": "array (optional)",

    // Team-specific fields
    "team_name": "string (optional, max 255)",
    "team_type": "string (optional, max 100)",
    "founded_year": "integer (optional)",
    "website": "string (optional, max 255)",
    "description": "string (optional)",
    "leagues": "array (optional)",

    // Scout-specific fields
    "leagues": "array (optional)",
    "teams": "array (optional)",
    "scouting_experience": "integer (optional)",
    "specialties": "array (optional)",

    // Academy-specific fields
    "academy_name": "string (optional, max 255)",
    "academy_type": "string (optional, max 100)",
    "founded_year": "integer (optional)",
    "website": "string (optional, max 255)",
    "description": "string (optional)",
    "leagues": "array (optional)",

    // Organizer-specific fields
    "organization_name": "string (optional, max 255)",
    "organization_type": "string (optional, max 100)",
    "website": "string (optional, max 255)",
    "description": "string (optional)",
    "leagues": "array (optional)",

    // Adviser-specific fields
    "leagues": "array (optional)",
    "teams": "array (optional)",
    "business_name": "string (optional, max 255)",
    "business_phone": "string (optional, max 20)",
    "website": "string (optional, max 255)",
    "address": "string (optional, max 255)",
    "level_hockey_played": "string (optional, max 255)",
    "current_involvement_level": "string (optional, max 255)",
    "current_sport_role": "string (optional, max 255)",
    "number_of_years_experience": "integer (optional)",
    "resume": "file (optional, pdf, max 10MB)",
    "references": "array (optional)",
    "references[].name": "string (required with references, max 255)",
    "references[].email": "string (required with references, email, max 255)",
    "references[].phone": "string (required with references, max 20)"
}
```

### POST /v4/add-child

Add child to user account

**Request Body:**

```json
{
    "first_name": "string (required, max 50)",
    "last_name": "string (required, max 50)",
    "date_of_birth": "date (required, before today)",
    "gender": "string (required, in: male, female, other)",
    "username": "string (required, unique)",
    "password": "string (required, min 6)",
    "position": "string (optional, max 100)",
    "email": "string (optional, email)",
    "teams": "array (optional)",
    "leagues": "array (optional)",
    "permissions": {
        "can_chat": "boolean (optional)",
        "can_view_events": "boolean (optional)",
        "can_view_feed": "boolean (optional)",
        "can_view_messages": "boolean (optional)",
        "can_accept_invites": "boolean (optional)",
        "can_send_friend_requests": "boolean (optional)",
        "can_use_marketplace": "boolean (optional)"
    }
}
```

### POST /v4/update-child-permissions/{childId}

Update child permissions

**Path Parameters:**

-   `childId`: integer (required)

### POST /v4/update-child-credentials/{childId}

Update child credentials

**Path Parameters:**

-   `childId`: integer (required)

### GET /v4/search-users

Search users

## Evaluation APIs

### Rejection Reasons

#### GET /v4/evaluation/get-rejection-reasons

Get active rejection reasons only

#### GET /v4/evaluation/get-rejection-reasons/all

Get all rejection reasons

#### GET /v4/evaluation/get-rejection-reason/{id}

Get specific rejection reason by ID

**Path Parameters:**

-   `id`: integer (required)

#### POST /v4/evaluation/create-rejection-reason

Create new rejection reason

**Request Body:**

```json
{
    "title": "string (required, max 255)",
    "description": "string (required, unique)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### PUT /v4/evaluation/update-rejection-reason

Update rejection reason

**Request Body:**

```json
{
    "id": "integer (required)",
    "title": "string (optional, max 255)",
    "description": "string (optional, unique)",
    "active": "boolean (optional)",
    "sort_order": "integer (optional, min 1, unique among active)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### DELETE /v4/evaluation/delete-rejection-reason

Delete rejection reason

**Request Body:**

```json
{
    "id": "integer (required)"
}
```

### Categories

#### GET /v4/evaluation/get-categories

Get active categories only

#### GET /v4/evaluation/get-categories/all

Get all categories

#### GET /v4/evaluation/get-category/{id}

Get specific category by ID

**Path Parameters:**

-   `id`: integer (required)

#### POST /v4/evaluation/create-category

Create new category

**Request Body:**

```json
{
    "name": "string (required, max 255, unique among active)",
    "slug": "string (optional, max 255, unique among active, auto-generated from name if not provided)",
    "description": "string (required)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### PUT /v4/evaluation/update-category

Update category

**Request Body:**

```json
{
    "id": "integer (required)",
    "name": "string (optional, max 255, unique among active)",
    "slug": "string (optional, max 255, unique among active, auto-generated from name if name updated)",
    "description": "string (optional)",
    "active": "boolean (optional)",
    "sort_order": "integer (optional, min 1, unique among active)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### DELETE /v4/evaluation/delete-category

Delete category

**Request Body:**

```json
{
    "id": "integer (required)"
}
```

### Questions

#### GET /v4/evaluation/get-questions

Get active questions only

#### GET /v4/evaluation/get-questions/all

Get all questions

#### GET /v4/evaluation/get-question/{id}

Get specific question by ID

**Path Parameters:**

-   `id`: integer (required)

#### POST /v4/evaluation/create-question

Create new question

**Request Body:**

```json
{
    "category_id": "integer (required, exists in evaluation_categories)",
    "title": "string (required, max 255)",
    "question": "string (required)",
    "required": "boolean (optional, default false)",
    "sort_order": "integer (optional, min 1)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### PUT /v4/evaluation/update-question

Update question

**Request Body:**

```json
{
    "id": "integer (required, exists in evaluation_questions)",
    "category_id": "integer (optional, exists in evaluation_categories)",
    "title": "string (optional, max 255)",
    "question": "string (optional)",
    "required": "boolean (optional)",
    "sort_order": "integer (optional, min 1)",
    "active": "boolean (optional)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### DELETE /v4/evaluation/delete-question

Delete question

**Request Body:**

```json
{
    "id": "integer (required)"
}
```

### Question Options

#### GET /v4/evaluation/get-question-options

Get question options

#### GET /v4/evaluation/get-question-option/{id}

Get specific question option by ID

**Path Parameters:**

-   `id`: integer (required)

#### POST /v4/evaluation/create-question-option

Create new question option

**Request Body:**

```json
{
    "question_id": "integer (required, exists in evaluation_questions)",
    "title": "string (optional, max 255)",
    "option": "string (required)",
    "rating": "numeric (required, min 0, max 5, must be in multiples of 0.5)",
    "sort_order": "integer (optional, min 1)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### PUT /v4/evaluation/update-question-option

Update question option

**Request Body:**

```json
{
    "id": "integer (required, exists in evaluation_question_options)",
    "question_id": "integer (optional, exists in evaluation_questions)",
    "title": "string (optional, max 255)",
    "option": "string (optional)",
    "rating": "numeric (optional, min 0, max 5)",
    "sort_order": "integer (optional, min 1)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

#### DELETE /v4/evaluation/delete-question-option

Delete question option

**Request Body:**

```json
{
    "id": "integer (required)"
}
```

### Questions & Categories

#### GET /v4/evaluation/get-categories-questions-options

Get all questions with categories and options

#### GET /v4/evaluation/category/{categoryId}/get-questions-options

Get questions for specific category

**Path Parameters:**

-   `categoryId`: integer (required)

## Media Management

### POST /v4/upload-media

Upload media file

### GET /v4/all-media

Get all user media

### PUT /v4/edit-media/{id}

Edit media

**Path Parameters:**

-   `id`: integer (required)

### DELETE /v4/delete-media/{id}

Delete media

**Path Parameters:**

-   `id`: integer (required)

## User Blocking

### POST /v4/block-user

Block a user

**Request Body:**

```json
{
    "blocked_id": "integer (required, exists in v4_users)",
    "reason": "string (optional, max 500)"
}
```

### POST /v4/unblock-user/{userId}

Unblock a user

**Path Parameters:**

-   `userId`: integer (required)

### GET /v4/blocked-users

Get list of blocked users

### GET /v4/block-history

Get block history

### GET /v4/check-block-status/{userId}

Check if user is blocked

**Path Parameters:**

-   `userId`: integer (required)

## Chat

### Chat Media

#### POST /v4/chat/upload-media

Upload chat media

#### GET /v4/chat/get-media

Get chat media

### Direct Chat (Commented - Not Active)

-   `GET /v4/chat/get-chat-id`
-   `GET /v4/chat/recent-chats`
-   `POST /v4/chat/send-message`
-   `POST /v4/chat/send-media-message`
-   `GET /v4/chat/get-messages`
-   `PUT /v4/chat/mark-as-read`

### Group Chat (Commented - Not Active)

-   `POST /v4/chat/create-group-chat`
-   `GET /v4/chat/group-chats`
-   `POST /v4/chat/add-participants`
-   `POST /v4/chat/remove-participants`

## Authentication Requirements

All routes except authentication endpoints require `auth:v4api` middleware.

## Base URL

All endpoints are prefixed with `/v4/`

## Response Format

All API responses follow this format:

**Success Response:**

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { ... }
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... } // For validation errors
}
```

## Status Codes

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `404` - Not Found
-   `422` - Validation Error
-   `500` - Internal Server Error
