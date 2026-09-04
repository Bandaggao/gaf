# E2E Test Scenarios — GAFS A-Watch

Catalog of automated and MCP-driven end-to-end test scenarios.

**Prerequisites:** `php artisan migrate:fresh --seed`, backend on `:8000`, frontend on `:5173`.

Run automated suite: `npm run test:e2e` from project root.

## Happy path

| ID | Role | Steps | Expected result | Spec |
|----|------|-------|-----------------|------|
| HP-01 | Admin | Login → dashboard | Stats cards visible | `auth.spec.ts`, `admin.spec.ts` |
| HP-02 | Admin | Students page → list loads | Student table with seeded data | `admin.spec.ts` |
| HP-03 | Admin | Navigate sections, subjects, assignments | Pages load without error | `admin.spec.ts` |
| HP-04 | Admin | Enrollments page | Enrollment UI visible | `admin.spec.ts` |
| HP-05 | Admin | Reports page | Filter form and report area visible | `admin.spec.ts` |
| HP-06 | Teacher | Sessions → active session detail | QR SVG displayed | `teacher.spec.ts` |
| HP-07 | Student | API scan active token | 200 + attendance recorded | `student.spec.ts` |
| HP-08 | Parent | Dashboard | Child attendance visible | `parent.spec.ts` |
| HP-09 | Teacher | Close session via API | Session status closed | `teacher.spec.ts` |
| HP-10 | Any | Logout / clear session | Redirect to login | `auth.spec.ts` |

## Edge cases

| ID | Role | Steps | Expected result | Spec |
|----|------|-------|-----------------|------|
| EC-01 | Student | Duplicate scan same token | 200, record updated | `student.spec.ts` |
| EC-02 | Student | Scan after session start | Status `late` or `present` | `student.spec.ts` |
| EC-03 | Student | Irregular student scans cross-section class | 200 success | `student.spec.ts` |
| EC-04 | Student | Unenrolled student scans | 422 not enrolled | `student.spec.ts` |
| EC-05 | Student | Scan expired token | 422 invalid/expired | `student.spec.ts` |
| EC-06 | Student | Scan closed session token | 422 invalid/expired | `student.spec.ts` |
| EC-07 | Student | Navigate to `/admin/dashboard` | Redirect to student home | `auth.spec.ts` |
| EC-08 | Student | Login with redirect query | Lands on `/student/scan` | `auth.spec.ts` |
| EC-09 | Guest | Submit empty login | Validation errors shown | `auth.spec.ts` |

## Negative scenarios

| ID | Role | Steps | Expected result | Spec |
|----|------|-------|-----------------|------|
| NG-01 | Guest | Wrong password | Error message displayed | `auth.spec.ts` |
| NG-02 | Guest | Invalid email format | Validation error | `auth.spec.ts` |
| NG-03 | Student | Invalid QR token via API | 422 | `student.spec.ts` |
| NG-04 | Guest | Unauthenticated API call | 401 | `auth.spec.ts` |
| NG-05 | Teacher | Access admin dashboard URL | Redirect away | `auth.spec.ts` |
| NG-06 | Admin | Create student missing fields via API | 422 validation | `admin.spec.ts` |

## Session QR tokens (from seed data)

| Token | Status |
|-------|--------|
| `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee01` | Active |
| `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee02` | Expired |
| `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee03` | Closed |

See [`test-data/SAMPLE_DATA.md`](test-data/SAMPLE_DATA.md) for full dataset reference.
