# Task Management System

A full-stack task management application with real-time updates, file attachments, virus scanning, and video streaming.

## Architecture

The project is a monorepo with two applications:

| Directory   | Stack                                                          | Role                     |
|-------------|----------------------------------------------------------------|--------------------------|
| `backend/`  | Laravel 13, PHP 8.3+, MySQL, JWT, Laravel Reverb (WebSockets)  | REST API + realtime bus  |
| `frontend/` | Next.js 16, React 19, Tailwind CSS, TypeScript, pusher-js     | Responsive web dashboard |

The frontend talks to the backend over a JWT-authenticated REST API. Task CRUD operations broadcast events over a private `tasks` channel via Reverb, and the dashboard refreshes instantly when tasks change.

## Features

- JWT authentication (tymon/jwt-auth), session stored in an httpOnly cookie
- Task CRUD with filtering, sorting, and pagination
- Role-based access control (user / manager / admin)
- **Real-time updates** via WebSockets (task created/updated/deleted)
- **Real-time task comments** — per-task live comments with instant create/delete across clients
- **Drag & drop file attachments** (images, documents, videos up to 50MB) with per-file status and live upload progress bars
- **Video upload & streaming** — seekable playback through HTTP Range requests (206 partial content)
- Image thumbnails and background virus scanning (queue-based)
- Quarantine system for infected files
- Responsive UI: the task table collapses into cards, modals become bottom-sheets on mobile

## Repo Structure

```
task-management/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Events/          # Reverb broadcast events
│   │   ├── Http/Controllers/
│   │   ├── Jobs/            # Thumbnail generation, virus scanning
│   │   ├── Models/
│   │   └── Services/
│   ├── routes/api.php       # API routes (attachments, broadcasting auth)
│   ├── routes/channels.php  # Reverb channels
│   └── docs/                # API, database, architecture, deployment
└── frontend/                # Next.js app
    ├── app/
    │   ├── actions/         # Server actions (tasks, attachments, auth)
    │   ├── api/             # Route handlers (download/thumbnail/stream proxies, WS auth)
    │   ├── lib/             # Types, session management
    │   └── ui/              # Dashboard, task form/detail, dropzone, realtime badge
    └── proxy.ts             # Next.js middleware (route protection)
```

Detailed docs live in each app's README:

- [Backend README](backend/README.md) — installation, API docs, env vars, security
- [Frontend README](frontend/README.md) — setup, env vars, realtime & uploads guide

## Prerequisites

- PHP 8.3+
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 20+ and pnpm (or npm)

## Quick Start

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Set your database credentials in `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=
```

Create the database, then migrate and seed:

```bash
mysql -u root -p -e "CREATE DATABASE task_management;"
php artisan migrate --seed
php artisan storage:link
```

### 2. Frontend

```bash
cd frontend
pnpm install
```

Create `frontend/.env` (or copy the values below):

```env
NEXT_PUBLIC_BACKEND_URL=http://localhost:8000/api
NEXT_PUBLIC_REVERB_APP_KEY=task-management-key
NEXT_PUBLIC_REVERB_HOST=127.0.0.1
NEXT_PUBLIC_REVERB_PORT=8080
SESSION_SECRET=change-me-in-production-to-a-random-32-char-string
```

`SESSION_SECRET` signs the frontend session cookie. Use a long random value in production.

### 3. Run it

From `backend/` (each in its own terminal):

```bash
php artisan serve          # API on http://localhost:8000
php artisan reverb:start   # WebSocket server on port 8080
php artisan queue:work     # thumbnail generation & virus scanning
```

From `frontend/`:

```bash
pnpm dev                   # app on http://localhost:3000
```

Open [http://localhost:3000](http://localhost:3000) and log in with a seeded user:

- Email: `admin@example.com`
- Password: `password`

## Environment Variables

### Backend (`backend/.env`)

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Backend base URL | http://localhost:8000 |
| `DB_*` | Database connection | local MySQL |
| `QUEUE_CONNECTION` | Queue driver | database |
| `BROADCAST_CONNECTION` | Broadcast driver | reverb |
| `REVERB_APP_ID` | Reverb application ID | task-management |
| `REVERB_APP_KEY` | Reverb public app key | task-management-key |
| `REVERB_APP_SECRET` | Reverb secret | generated |
| `REVERB_HOST` / `REVERB_PORT` | Reverb client host/port | 127.0.0.1 / 8080 |
| `JWT_SECRET` | JWT signing key | auto-generated |

### Frontend (`frontend/.env`)

| Variable | Description |
|----------|-------------|
| `NEXT_PUBLIC_BACKEND_URL` | Backend API URL used by client & proxies, e.g. `http://localhost:8000/api` |
| `NEXT_PUBLIC_REVERB_APP_KEY` | Reverb public app key |
| `NEXT_PUBLIC_REVERB_HOST` | Reverb WebSocket host |
| `NEXT_PUBLIC_REVERB_PORT` | Reverb WebSocket port |
| `SESSION_SECRET` | Secret signing the frontend session cookie |

## Real-time Updates

Task CRUD broadcasts `task.created`, `task.updated`, and `task.deleted` over a private `tasks` channel. The frontend dashboard subscribes with pusher-js and refreshes automatically. Channel authorization is proxied through the frontend (`/api/broadcasting/auth`) using the session JWT.

Reverb must be running (`php artisan reverb:start`) for real-time updates to work.

## File Uploads & Video Streaming

- Upload via drag & drop in the task detail view. Supported types: jpg/png/gif/webp, pdf/doc/docx/xls/xlsx/txt, mp4/mov/avi — up to 50MB per file.
- Files are stored privately; every byte flows through the backend (a streaming upload proxy / route handlers), so the JWT never reaches the browser.
- Uploads show a live progress bar driven by `XMLHttpRequest` upload events.
- Videos stream inline with an HTTP Range–aware endpoint (`GET /api/attachments/{attachment}/stream`), enabling seekable playback without downloading the whole file.
- Image thumbnails and virus scans run on the queue worker; infected files are quarantined.

## API Documentation

Interactive API docs (Scalar) are available at [http://localhost:8000/scalar](http://localhost:8000/scalar). See [backend/docs/api.md](backend/docs/api.md) for the endpoint reference.

## Tests

```bash
cd backend
php artisan test
```

## License

MIT
