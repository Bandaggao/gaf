# Backend Documentation — GAFS A-Watch

Laravel 13 REST API with Sanctum authentication, SQLite/MySQL, and background jobs.

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13 (PHP 8.3+) |
| Auth | Laravel Sanctum (API tokens) |
| Database | SQLite (default), MySQL supported |
| QR | simplesoftwareio/simple-qrcode |
| PDF | barryvdh/laravel-dompdf |
| Email | Gmail API (google/apiclient) |
| Queue | Database driver |

## Folder structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── Admin/          # Dashboard, CRUD, Reports, Enrollments
│   │   │   ├── Teacher/        # Dashboard, ClassSession, Profile
│   │   │   ├── Student/        # Dashboard, Scan, Profile
│   │   │   └── Parent/         # Dashboard
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   ├── Models/                 # Eloquent models (11)
│   ├── Services/
│   │   ├── AttendanceService.php   # Core attendance logic
│   │   └── GmailService.php        # Gmail API wrapper
│   ├── Jobs/
│   │   └── SendAttendanceEmail.php
│   └── Console/Commands/
│       ├── MarkAbsentCommand.php
│       └── WeeklySummaryCommand.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── TestDataSeeder.php    # Loads docs/test-data/sample-data.json
└── routes/
    ├── api.php
    └── console.php             # Scheduler definitions
```

## Authentication

### Login flow

1. `POST /api/login` — validates email/password, returns Sanctum token + user profile
2. Client sends `Authorization: Bearer {token}` on subsequent requests
3. `auth:sanctum` middleware validates token
4. `role:{role}` middleware checks `users.role` column

### AuthController

- `login()` — creates token via `$user->createToken()`
- `logout()` — deletes current access token
- `user()` — returns profile with role-specific relations loaded

### RoleMiddleware

Registered as `role:admin`, `role:teacher`, etc. Returns 403 if user's role doesn't match.

## Controllers by namespace

### Admin

| Controller | Responsibility |
|------------|----------------|
| DashboardController | School-wide stats and chart data |
| StudentController | Student CRUD + QR generation |
| TeacherController | Teacher CRUD + section sync |
| SectionController | Program block CRUD |
| SubjectController | Subject CRUD |
| TeachingAssignmentController | Teacher-subject-section links, bulk ops |
| EnrollmentController | Student enrollments, bulk/import |
| ReportController | Attendance reports + PDF export |

### Teacher

| Controller | Responsibility |
|------------|----------------|
| DashboardController | Teacher stats |
| ClassSessionController | Create/list/show/close sessions, QR SVG |
| ProfileController | Teacher profile |

### Student

| Controller | Responsibility |
|------------|----------------|
| DashboardController | Personal attendance history |
| ScanController | QR token scan → AttendanceService |
| ProfileController | Student profile |

### Parent

| Controller | Responsibility |
|------------|----------------|
| DashboardController | Linked children summary + recent attendance |

## AttendanceService (core business logic)

Location: `app/Services/AttendanceService.php`

| Method | Purpose |
|--------|---------|
| `generateSessionQrToken()` | UUID token for new class sessions |
| `recordScan($student, $token)` | Validates session, enrollment, records present/late |
| `markAbsentForExpiredSessions()` | Marks unscanned students absent, queues emails |
| `qrCodeSvg($token)` | Returns QR code SVG string |

### Scan validation rules

1. Session exists, not closed, not expired
2. Session has `teaching_assignment_id`
3. Student enrolled in that teaching assignment
4. Status: `present` if before start time, `late` if after
5. Duplicate scan updates existing record (unique student+session)

## Background jobs

### SendAttendanceEmail

Dispatched when student scans in or is marked absent. Uses `GmailService` to send parent notification. Requires Gmail API credentials — see [`GMAIL_SETUP.md`](GMAIL_SETUP.md).

### Scheduler (`routes/console.php`)

| Command | Schedule | Purpose |
|---------|----------|---------|
| `attendance:mark-absent` | Every minute | Process expired sessions |
| `attendance:weekly-summary` | Sunday 6 PM | Weekly email summary |

Run locally:
```bash
php artisan queue:work
php artisan schedule:work
```

## API routes

Full reference: [`API_ROUTES.md`](API_ROUTES.md)

Base: `http://localhost:8000/api`

## Test data seeding

```bash
php artisan migrate:fresh --seed
```

Loads [`docs/test-data/sample-data.json`](test-data/sample-data.json) via `TestDataSeeder`.

## Running locally

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Adding a new endpoint

1. Add route in `routes/api.php` with appropriate middleware
2. Create controller method in `app/Http/Controllers/Api/`
3. Add validation via `$request->validate()`
4. Use Eloquent models or `AttendanceService` for business logic
5. Return JSON via `response()->json()`
6. Add matching function in `frontend/src/api/`

## Testing

```bash
cd backend
php artisan test          # PHPUnit (boilerplate)
cd .. && npm run test:e2e # Playwright E2E (full stack)
```

See [`E2E_TEST_SCENARIOS.md`](E2E_TEST_SCENARIOS.md).
