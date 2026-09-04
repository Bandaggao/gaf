# Entity Relationship Diagram

## Tables

```
users (id, name, email, password, role)
  ├── students (user_id, student_number, grade_level, section_id, parent_email, qr_token)
  ├── teachers (user_id)
  └── parents (user_id, email)

sections (id, name, grade_level)
  └── students.section_id

subjects (id, name, teacher_id → users.id)

teacher_section (teacher_id, section_id) — pivot

parent_student (parent_id, student_id) — pivot

class_sessions (teacher_id, subject_id, section_id, session_date, start_time, end_time, session_qr_token, expires_at)
  └── attendance_records (student_id, class_session_id, scanned_at, status)

email_logs (recipient_email, subject, body, status, sent_at)
```

## Relationships

- User 1:1 Student | Teacher | Parent
- Section 1:N Students
- Teacher N:M Sections (teacher_section)
- Subject N:1 User (teacher)
- ClassSession N:1 Teacher, Subject, Section
- AttendanceRecord N:1 Student, ClassSession (unique pair)
- Parent N:M Students (parent_student)
