# API Routes

Base URL: `http://localhost:8000/api`

All authenticated routes require `Authorization: Bearer {token}` header.

## Auth

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | `/login` | Public | Login → `{ user, token, dashboard_route }` |
| POST | `/logout` | Sanctum | Revoke current token |
| GET | `/user` | Sanctum | Current user profile |

## Admin (`role:admin`)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/dashboard` | Stats + chart data |
| GET | `/admin/students` | List students |
| POST | `/admin/students` | Create student |
| GET | `/admin/students/{student}` | Show student |
| PUT | `/admin/students/{student}` | Update student |
| DELETE | `/admin/students/{student}` | Delete student |
| GET/POST/PUT/DELETE | `/admin/teachers` | Teacher CRUD |
| GET/POST/PUT/DELETE | `/admin/sections` | Program block CRUD |
| GET/POST/PUT/DELETE | `/admin/subjects` | Subject CRUD |
| GET/POST/PUT/DELETE | `/admin/teaching-assignments` | Class assignment CRUD |
| POST | `/admin/teaching-assignments/bulk` | Bulk create assignments |
| PUT | `/admin/teachers/{teacher}/teaching-assignments` | Sync teacher assignments |
| GET | `/admin/enrollment-options` | Form options for enrollments |
| POST | `/admin/enrollments/bulk` | Bulk enroll students |
| POST | `/admin/enrollments/import` | CSV import enrollments |
| GET | `/admin/students/{student}/enrollments` | List student enrollments |
| POST | `/admin/students/{student}/enrollments` | Add enrollment |
| DELETE | `/admin/students/{student}/enrollments/{enrollment}` | Remove enrollment |
| GET | `/admin/reports/attendance` | Filtered attendance report |
| GET | `/admin/reports/attendance/pdf` | PDF export |
| GET | `/admin/reports/weekly-summary` | Weekly summary data |

## Teacher (`role:teacher`)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/teacher/dashboard` | Stats + chart |
| GET | `/teacher/profile` | Teacher profile |
| GET | `/teacher/session-options` | Classes for session form |
| GET | `/teacher/sessions` | List sessions |
| POST | `/teacher/sessions` | Create session |
| GET | `/teacher/sessions/{classSession}` | Session detail + roster + QR SVG |
| GET | `/teacher/sessions/{classSession}/qr` | Session QR SVG only |
| POST | `/teacher/sessions/{classSession}/close` | Close session |
| DELETE | `/teacher/sessions/{classSession}` | Delete session |

## Student (`role:student`)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/student/dashboard` | Personal attendance |
| GET | `/student/profile` | Student profile |
| POST | `/student/scan` | Body: `{ token }` — scan session QR |

## Parent (`role:parent`)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/parent/dashboard` | Children summary + recent attendance |

## Error responses

| Code | When |
|------|------|
| 401 | Missing/invalid token |
| 403 | Wrong role |
| 404 | Resource not found |
| 422 | Validation error or business rule (e.g. invalid QR) |

## Scan endpoint detail

```
POST /api/student/scan
{ "token": "session-qr-uuid" }

Success 200: { "message": "Attendance recorded successfully.", "data": { ... } }
Failure 422: { "message": "Invalid or expired QR code." }
Failure 422: { "message": "You are not enrolled in this class." }
```
