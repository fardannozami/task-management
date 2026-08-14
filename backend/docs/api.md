# API Documentation

## Base URL

```
http://localhost:8000/api
```

## Authentication

All API endpoints except `/api/auth/login` require JWT Bearer token authentication.

```
Authorization: Bearer <token>
```

## Interactive Documentation

Visit `/scalar` on your local server for interactive API documentation powered by [Scalar](https://github.com/scalar/scalar).

## Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Login user |
| POST | `/auth/logout` | Logout user |
| GET | `/auth/me` | Get current user info |

### Tasks

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tasks` | List tasks (paginated, filterable, sortable) |
| POST | `/tasks` | Create new task |
| GET | `/tasks/{id}` | Show task details |
| PUT | `/tasks/{id}` | Update task |
| DELETE | `/tasks/{id}` | Delete task |

**Task Filters:**
- `status` - pending, in_progress, completed, cancelled
- `priority` - low, medium, high, urgent
- `assigned_user_id` - integer
- `created_by` - integer
- `due_date_from` - datetime
- `due_date_to` - datetime
- `search` - search in title and description
- `sort_by` - field to sort by (default: created_at)
- `sort_dir` - asc or desc (default: desc)
- `per_page` - items per page (default: 10)

### Attachments

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/tasks/{task}/attachments` | Upload file attachment |
| GET | `/attachments/{id}/download` | Download attachment |
| GET | `/attachments/{id}/thumbnail` | Download thumbnail |
| POST | `/attachments/{id}/scan` | Scan for viruses |
| GET | `/attachments/{id}/versions` | List versions |
| POST | `/attachments/{id}/versions/{version}/restore` | Restore version |
| DELETE | `/attachments/{id}` | Delete attachment |

**Upload Constraints:**
- Max file size: 50MB
- Allowed types: images (jpg, png, gif, webp), documents (pdf, doc, docx, xls, xlsx, txt), videos (mp4, mov, avi)
- Thumbnails auto-generated for images (300x300 JPEG)

### Chunked Uploads

For files >50MB:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/chunked-uploads/tasks/{task}/init` | Initialize upload session |
| POST | `/chunked-uploads/{id}/chunk` | Upload chunk |
| POST | `/chunked-uploads/{id}/merge` | Merge chunks |
| DELETE | `/chunked-uploads/{id}` | Cancel upload |

**Chunk size:** 5MB per chunk

### Bulk Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/bulk/tasks/status` | Bulk update task status |

**Constraints:** max 100 tasks per request

### Exports

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/exports/tasks/csv` | Queue CSV export |
| POST | `/exports/tasks/pdf` | Queue PDF export |
| GET | `/exports/tasks/csv/download` | Download CSV |
| GET | `/exports/tasks/pdf/download` | Download PDF |

**Supported filters:** same as task list

## Response Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 202 | Accepted (queued job) |
| 204 | No content (deleted) |
| 401 | Unauthorized |
| 403 | Forbidden (quarantined file) |
| 404 | Not found |
| 409 | Conflict (upload already completed) |
| 422 | Validation error |
| 429 | Too many requests |
| 500 | Server error |

## Error Response Format

```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Pagination

List endpoints return paginated responses:

```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 5,
  "per_page": 10,
  "total": 48
}
```

## Rate Limiting

- Login: 5 requests per minute
- File upload: 10 requests per minute
