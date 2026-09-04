# Database Documentation — GAFS A-Watch

SQLite (local) or MySQL (production). Schema managed via Laravel migrations.

## Entity relationship overview

```
users
 ├── students (1:1)
 ├── teachers (1:1)
 └── parents (1:1)

sections
 ├── students (1:N via section_id)
 └── teaching_assignments (1:N)

subjects
 └── teaching_assignments (1:N)

teachers
 ├── teacher_section (N:M with sections)
 └── teaching_assignments (1:N)

teaching_assignments
 ├── student_enrollments (1:N)
 └── class_sessions (1:N)

class_sessions
 └── attendance_records (1:N)

students
 ├── student_enrollments (1:N)
 ├── attendance_records (1:N)
 └── parent_student (N:M with parents)
```

## Tables

### users

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | Login identifier |
| password | string | Bcrypt hashed |
| role | enum | admin, teacher, student, parent |
| timestamps | | |

### sections

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | e.g. "Grade 10 - Malunggay" |
| grade_level | string | e.g. "10" |
| timestamps | | |

### subjects

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | e.g. "Mathematics" |
| timestamps | | |

### teachers

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK → users | Cascade delete |
| timestamps | | |

### students

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK → users | Cascade delete |
| student_number | string unique | e.g. GAFS-2026-001 |
| grade_level | string | |
| section_id | FK → sections | Cascade delete |
| student_type | string | regular or irregular |
| parent_email | string | For Gmail notifications |
| qr_token | string unique | Student identity QR (UUID) |
| timestamps | | |

### parents

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK → users | Cascade delete |
| email | string | Contact email |
| timestamps | | |

### teacher_section (pivot)

| Column | Type | Notes |
|--------|------|-------|
| teacher_id | FK → teachers | Composite PK |
| section_id | FK → sections | Composite PK |

### parent_student (pivot)

| Column | Type | Notes |
|--------|------|-------|
| parent_id | FK → parents | Composite PK |
| student_id | FK → students | Composite PK |

### teaching_assignments

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| teacher_id | FK → teachers | |
| subject_id | FK → subjects | |
| section_id | FK → sections | |
| timestamps | | |

**Unique:** `(teacher_id, subject_id, section_id)`

### student_enrollments

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| teaching_assignment_id | FK → teaching_assignments | |
| enrollment_type | enum | regular, irregular |
| timestamps | | |

**Unique:** `(student_id, teaching_assignment_id)`

### class_sessions

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| teacher_id | FK → users | Teacher user ID |
| teaching_assignment_id | FK → teaching_assignments | Nullable |
| subject_id | FK → subjects | |
| section_id | FK → sections | |
| session_date | date | |
| start_time | time | |
| end_time | time | |
| session_qr_token | string unique | Scanned by students |
| expires_at | timestamp | After this → expired |
| absent_processed | boolean | Scheduler flag |
| closed_at | timestamp nullable | Manual close time |
| timestamps | | |

**Status (computed):** active, expired, or closed

### attendance_records

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| class_session_id | FK → class_sessions | |
| scanned_at | timestamp nullable | Null if absent |
| status | enum | present, absent, late |
| timestamps | | |

**Unique:** `(student_id, class_session_id)`

### email_logs

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| recipient_email | string | |
| subject | string | |
| body | text | |
| status | enum | pending, sent, failed |
| sent_at | timestamp nullable | |
| timestamps | | |

## Model relationships

| Model | Relationships |
|-------|---------------|
| User | hasOne Student, Teacher, ParentModel |
| Student | belongsTo User, Section; hasMany Enrollments, AttendanceRecords |
| Teacher | belongsTo User; belongsToMany Sections; hasMany TeachingAssignments |
| ParentModel | belongsTo User; belongsToMany Students |
| Section | hasMany Students, TeachingAssignments |
| Subject | hasMany TeachingAssignments |
| TeachingAssignment | belongsTo Teacher, Subject, Section; hasMany Enrollments, ClassSessions |
| StudentEnrollment | belongsTo Student, TeachingAssignment |
| ClassSession | belongsTo User (teacher), TeachingAssignment, Subject, Section; hasMany AttendanceRecords |
| AttendanceRecord | belongsTo Student, ClassSession |

## Migrations

Located in `backend/database/migrations/`:

| File | Purpose |
|------|---------|
| `0001_01_01_000000_create_users_table.php` | Users + tokens |
| `2024_01_01_000003_create_gafs_tables.php` | Core GAFS schema |
| `2026_06_26_120000_add_closed_at_to_class_sessions_table.php` | Session close support |
| `2026_06_26_130000_create_teaching_assignments_table.php` | Assignment model |
| `2026_06_26_140000_add_college_enrollment_model.php` | Enrollments + student_type |

## Seed data

```bash
php artisan migrate:fresh --seed
```

Imports [`test-data/sample-data.json`](test-data/sample-data.json):
- 4 sections, 6 subjects, 3 teachers, 17 students, 5 parents
- 13 teaching assignments, 3 class sessions, 4 attendance records

Details: [`test-data/SAMPLE_DATA.md`](test-data/SAMPLE_DATA.md)

## Indexes and constraints

- All foreign keys use cascade or null-on-delete as appropriate
- Unique constraints prevent duplicate enrollments and attendance records
- `session_qr_token` and `qr_token` are globally unique UUIDs

## ERD reference

See also [`ERD.md`](ERD.md) for the original diagram (partially superseded by this document).
