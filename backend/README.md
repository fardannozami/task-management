# Task Management System - Backend API

A Laravel 13 backend API for task management with advanced file handling, queue-based processing, and security features.

## Features

- JWT Authentication
- Task CRUD with filtering, sorting, and pagination
- Secure file uploads with validation, thumbnails, and virus scanning
- HTTP Range video streaming (`GET /api/attachments/{attachment}/stream` serves 206 Partial Content with `Content-Range`, enabling seekable playback)
- Drag-and-drop file attachments (frontend interface, multiple files, per-file status)
- File versioning system
- Chunked uploads for large files (>50MB)
- Queue-based background processing:
  - Email notifications on task assignment
  - Bulk task status updates
  - Async thumbnail generation and virus scanning
  - CSV/PDF data exports
- Role-based access control
- Real-time task updates via WebSockets (Laravel Reverb)

## Documentation

- **[Setup Guide](README.md#installation)** - Installation and configuration
- **[API Documentation](docs/api.md)** - Endpoint reference and examples
- **[Interactive API Docs](http://localhost:8000/scalar)** - Swagger-like interface
- **[Database Schema](docs/database.md)** - Tables, columns, and relationships
- **[Architecture Decisions](docs/architecture.md)** - ADRs and design rationale
- **[Deployment Guide](docs/deployment.md)** - Production deployment steps

## Tech Stack

- **Framework**: Laravel 13
- **PHP**: 8.3+
- **Database**: MySQL
- **Authentication**: JWT (tymon/jwt-auth)
- **Queue**: Database driver
- **Real-time**: Laravel Reverb + Pusher protocol
- **PDF Generation**: barryvdh/laravel-dompdf
- **CSV**: league/csv
- **API Docs**: scalar/laravel

## Prerequisites

- PHP 8.3 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & npm (for frontend, optional)

## Installation

1. Clone the repository:
```bash
git clone <repository-url> task-management
cd task-management/backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Generate JWT secret:
```bash
php artisan jwt:secret
```

6. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=
```

7. Create database:
```bash
mysql -u root -p -e "CREATE DATABASE task_management;"
```

8. Run migrations and seeders:
```bash
php artisan migrate --seed
```

9. Create storage link for public files:
```bash
php artisan storage:link
```

10. Start queue worker (in separate terminal):
```bash
php artisan queue:work --tries=3
```

11. Start Reverb WebSocket server (in separate terminal):
```bash
php artisan reverb:start
```

12. Start development server:
```bash
php artisan serve
```

## Real-time Updates (WebSockets)

Task CRUD operations broadcast events over a private `tasks` channel using Laravel Reverb (Pusher protocol):

- `task.created` - a task was created
- `task.updated` - a task was updated
- `task.deleted` - a task was deleted

Clients (the frontend dashboard) subscribe to `private-tasks`; channel authorization is handled by `POST /broadcasting/auth`, protected by the JWT `api` guard.

Configure Reverb credentials in `.env` (see `REVERB_*` variables below). Reverb must be running (`php artisan reverb:start`) for real-time updates to work.

## Default Seeded Data

- **Users**: 5 users with roles (user, admin, manager)
- **Password**: `password` for all seeded users
- **Tasks**: 15 sample tasks
- **Attachments**: 10 sample attachments
- **Comments**: 10 sample comments

## API Documentation

Interactive API documentation is available at:

```
http://localhost:8000/scalar
```

This provides a Swagger/OpenAPI-like interface for exploring all endpoints.

## Running Tests

```bash
php artisan test
```

## Queue Worker

For production, run queue worker as a daemon:

```bash
php artisan queue:work --daemon --sleep=3 --tries=3 --max-time=3600
```

Or use Supervisor for automatic restarts.

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | Laravel |
| `APP_ENV` | Environment | local |
| `APP_DEBUG` | Debug mode | true |
| `APP_URL` | Application URL | http://localhost:8000 |
| `DB_CONNECTION` | Database driver | mysql |
| `DB_HOST` | Database host | 127.0.0.1 |
| `DB_PORT` | Database port | 3306 |
| `DB_DATABASE` | Database name | backend |
| `DB_USERNAME` | Database user | root |
| `DB_PASSWORD` | Database password | null |
| `QUEUE_CONNECTION` | Queue driver | database |
| `BROADCAST_CONNECTION` | Broadcast driver | reverb |
| `REVERB_APP_ID` | Reverb application ID | task-management |
| `REVERB_APP_KEY` | Reverb public app key | task-management-key |
| `REVERB_APP_SECRET` | Reverb app secret | (generated) |
| `REVERB_HOST` | Reverb client host | 127.0.0.1 |
| `REVERB_PORT` | Reverb client port | 8080 |
| `REVERB_SCHEME` | Reverb scheme | http |
| `REVERB_SERVER_HOST` | Reverb server bind host | 127.0.0.1 |
| `REVERB_SERVER_PORT` | Reverb server bind port | 8080 |
| `MAIL_MAILER` | Mail driver | log |
| `JWT_SECRET` | JWT signing key | auto-generated |

## Security

- JWT-based authentication
- File type validation via MIME sniffing
- Virus scanning simulation
- Quarantine system for infected files
- Rate limiting on sensitive endpoints
- Private file storage with restricted permissions

## License

MIT License
