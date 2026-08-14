# Database Schema Documentation

## Overview

The Task Management System uses MySQL with the following tables:

## Entity Relationship Diagram

```
users
  ├── tasks (created_by)
  ├── tasks (assigned_user_id)
  └── task_comments (user_id)

tasks
  ├── task_attachments
  ├── task_comments
  └── chunked_uploads

task_attachments
  ├── task_versions
  └── virus_scan_results
```

## Tables

### users

Stores user account information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | User full name |
| email | varchar(255) | NOT NULL, UNIQUE | Email address |
| email_verified_at | timestamp | NULL | Email verification timestamp |
| password | varchar(255) | NOT NULL | Hashed password |
| remember_token | varchar(100) | NULL | Remember me token |
| role | varchar(255) | DEFAULT 'user' | User role (user, admin, manager) |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `users_email_unique` (email)

### tasks

Stores task information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| title | varchar(255) | NOT NULL | Task title |
| description | text | NULL | Task description |
| status | varchar(255) | DEFAULT 'pending' | Task status |
| priority | varchar(255) | DEFAULT 'medium' | Task priority |
| assigned_user_id | bigint | FK (users.id), NULL | Assigned user |
| created_by | bigint | FK (users.id) | Task creator |
| due_date | datetime | NULL | Task due date |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `tasks_status_priority_index` (status, priority)
- `tasks_assigned_user_id_index` (assigned_user_id)
- `tasks_created_by_index` (created_by)
- `tasks_due_date_index` (due_date)

**Foreign Keys:**
- `assigned_user_id` → `users.id` (ON DELETE SET NULL)
- `created_by` → `users.id` (ON DELETE CASCADE)

### task_attachments

Stores file attachments for tasks.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_id | bigint | FK (tasks.id) | Parent task |
| file_name | varchar(255) | NOT NULL | Original file name |
| file_path | varchar(255) | NOT NULL | Storage path |
| file_size | bigint | UNSIGNED | File size in bytes |
| mime_type | varchar(255) | NOT NULL | File MIME type |
| thumbnail_path | varchar(255) | NULL | Thumbnail storage path |
| thumbnail_size | bigint | UNSIGNED, NULL | Thumbnail size in bytes |
| uploaded_at | timestamp | NOT NULL | Upload timestamp |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `task_attachments_task_id_index` (task_id)

**Foreign Keys:**
- `task_id` → `tasks.id` (ON DELETE CASCADE)

### task_comments

Stores comments on tasks.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_id | bigint | FK (tasks.id) | Parent task |
| user_id | bigint | FK (users.id) | Comment author |
| comment | text | NOT NULL | Comment content |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `task_comments_task_id_index` (task_id)
- `task_comments_user_id_index` (user_id)

**Foreign Keys:**
- `task_id` → `tasks.id` (ON DELETE CASCADE)
- `user_id` → `users.id` (ON DELETE CASCADE)

### task_versions

Stores version history for task attachments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_attachment_id | bigint | FK (task_attachments.id) | Parent attachment |
| user_id | bigint | FK (users.id) | User who created version |
| file_path | varchar(255) | NOT NULL | Version file path |
| file_name | varchar(255) | NOT NULL | Version file name |
| file_size | bigint | NOT NULL | File size in bytes |
| mime_type | varchar(255) | NOT NULL | File MIME type |
| version | integer | NOT NULL | Version number |
| change_description | varchar(255) | NULL | Description of changes |
| uploaded_at | timestamp | NOT NULL | Upload timestamp |

**Indexes:**
- `task_versions_attachment_id_index` (task_attachment_id)
- `task_versions_user_id_index` (user_id)
- `task_versions_version_index` (version)
- `UNIQUE (task_attachment_id, version)`

**Foreign Keys:**
- `task_attachment_id` → `task_attachments.id` (ON DELETE CASCADE)
- `user_id` → `users.id` (ON DELETE SET NULL)

### virus_scan_results

Stores virus scan results for attachments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_attachment_id | bigint | FK (task_attachments.id) | Scanned attachment |
| user_id | bigint | FK (users.id), NULL | User who triggered scan |
| scan_engine | varchar(255) | DEFAULT 'simulated-clamav' | Scanner engine name |
| status | varchar(255) | DEFAULT 'pending' | Scan status |
| threats_found | text | NULL | JSON array of threats |
| action_taken | varchar(255) | DEFAULT 'none' | Action taken |
| scanned_at | timestamp | NOT NULL | Scan timestamp |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `virus_scan_results_attachment_id_index` (task_attachment_id)
- `virus_scan_results_status_index` (status)
- `virus_scan_results_scanned_at_index` (scanned_at)

**Foreign Keys:**
- `task_attachment_id` → `task_attachments.id` (ON DELETE CASCADE)
- `user_id` → `users.id` (ON DELETE SET NULL)

### chunked_uploads

Tracks chunked upload sessions for large files.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_id | bigint | FK (tasks.id) | Parent task |
| user_id | bigint | FK (users.id) | Upload initiator |
| original_file_name | varchar(255) | NOT NULL | Original file name |
| temp_path | varchar(255) | NOT NULL | Temporary chunk storage path |
| final_path | varchar(255) | NULL | Final merged file path |
| mime_type | varchar(255) | NULL | File MIME type |
| total_size | bigint | UNSIGNED | Total file size in bytes |
| chunk_size | integer | UNSIGNED | Size per chunk in bytes |
| total_chunks | integer | UNSIGNED | Total number of chunks |
| uploaded_chunks | integer | UNSIGNED, DEFAULT 0 | Chunks uploaded so far |
| status | varchar(255) | DEFAULT 'initiated' | Upload status |
| completed_at | timestamp | NULL | Completion timestamp |
| expires_at | timestamp | NULL | Expiration timestamp |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Update timestamp |

**Indexes:**
- `chunked_uploads_task_id_index` (task_id)
- `chunked_uploads_user_id_index` (user_id)
- `chunked_uploads_status_index` (status)
- `chunked_uploads_expires_at_index` (expires_at)

**Foreign Keys:**
- `task_id` → `tasks.id` (ON DELETE CASCADE)
- `user_id` → `users.id` (ON DELETE CASCADE)

### attachment_versions

Stores version history for task attachments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| task_attachment_id | bigint | FK (task_attachments.id) | Parent attachment |
| user_id | bigint | FK (users.id) | User who created version |
| file_path | varchar(255) | NOT NULL | Version file path |
| file_name | varchar(255) | NOT NULL | Version file name |
| file_size | bigint | NOT NULL | File size in bytes |
| mime_type | varchar(255) | NOT NULL | File MIME type |
| version | integer | NOT NULL | Version number |
| change_description | varchar(255) | NULL | Description of changes |
| uploaded_at | timestamp | NOT NULL | Upload timestamp |

**Indexes:**
- `attachment_versions_attachment_id_index` (task_attachment_id)
- `attachment_versions_user_id_index` (user_id)
- `attachment_versions_version_index` (version)
- `UNIQUE (task_attachment_id, version)`

**Foreign Keys:**
- `task_attachment_id` → `task_attachments.id` (ON DELETE CASCADE)
- `user_id` → `users.id` (ON DELETE SET NULL)

## Migrations

All migrations are located in `database/migrations/`:

- `0001_01_01_000000_create_users_table.php`
- `2026_08_13_223950_create_tasks_table.php`
- `2026_08_13_223951_create_task_attachments_table.php`
- `2026_08_13_223952_create_task_comments_table.php`
- `2026_08_13_231551_add_thumbnail_fields_to_task_attachments_table.php`
- `2026_08_13_232147_create_chunked_uploads_table.php`
- `2026_08_13_232928_create_virus_scan_results_table.php`
- `2026_08_13_233948_create_attachment_versions_table.php`

## Seeders

Sample data is available in `database/seeders/`:

- `DatabaseSeeder.php` - Main seeder
- `UserSeeder.php` - 5 users with roles
- `TaskSeeder.php` - 15 tasks
- `TaskAttachmentSeeder.php` - 10 attachments
- `TaskCommentSeeder.php` - 10 comments
