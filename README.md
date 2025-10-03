# V4 Evaluation APIs

## Rejection Reasons

### GET /v4/evaluation/get-rejection-reasons

**Active rejection reasons only**

### GET /v4/evaluation/get-rejection-reasons/all

**All rejection reasons**

### GET /v4/evaluation/get-rejection-reason/{id}

**Get specific rejection reason by ID**

**Path Parameters:**

-   `id`: integer (required)

### POST /v4/evaluation/create-rejection-reason

```json
{
    "title": "string (required, max 255)",
    "description": "string (required, unique)",
    "meta": {
        "key": "string value (optional)"
    }
}
```

### PUT /v4/evaluation/update-rejection-reason

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

### DELETE /v4/evaluation/delete-rejection-reason

```json
{
    "id": "integer (required)"
}
```

## Categories

### GET /v4/evaluation/get-categories

**Active categories only**

### GET /v4/evaluation/get-categories/all

**All categories**

### GET /v4/evaluation/get-category/{id}

**Get specific category by ID**

**Path Parameters:**

-   `id`: integer (required)

### POST /v4/evaluation/create-category

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

### PUT /v4/evaluation/update-category

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

### DELETE /v4/evaluation/delete-category

```json
{
    "id": "integer (required)"
}
```

## Questions & Categories

### GET /v4/evaluation/get-questions-categories-options

**All questions with categories and options**

### GET /v4/evaluation/category/{categoryId}/get-questions-options

**Questions for specific category**

**Path Parameters:**

-   `categoryId`: integer (required)
