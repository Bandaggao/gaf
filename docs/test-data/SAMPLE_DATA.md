# GAFS A-Watch Sample Test Data

This document describes the realistic test dataset in [`../../backend/database/seeders/sample-data.json`](../../backend/database/seeders/sample-data.json), loaded by `TestDataSeeder`.

## Reset database

```bash
cd backend
php artisan migrate:fresh --seed
```

## Entity counts

| Entity | Count |
|--------|------:|
| Sections | 4 |
| Subjects | 6 |
| Users (total) | 26 |
| Teachers | 3 |
| Students | 17 |
| Parents | 5 |
| Teaching assignments | 13 |
| Class sessions | 3 |
| Attendance records | 4 |

## Primary login credentials

All accounts use password: **`password`**

| Role | Name | Email |
|------|------|-------|
| Admin | Dr. Elena Santos | admin@gafs.edu.ph |
| Teacher | Maria Cruz | teacher@gafs.edu.ph |
| Student | Juan Dela Cruz | student@gafs.edu.ph |
| Parent | Demo Parent | parent@gafs.edu.ph |

## Additional test accounts

### Teachers

| Email | Name |
|-------|------|
| roberto.reyes@gafs.edu.ph | Roberto Reyes |
| ana.garcia@gafs.edu.ph | Ana Garcia |

### Sample students

| Student # | Email | Section |
|-----------|-------|---------|
| GAFS-2026-001 | student@gafs.edu.ph | Grade 10 - Malunggay |
| GAFS-2026-002 | maria.santos.student@gafs.edu.ph | Grade 10 - Malunggay |
| GAFS-2026-017 | carlo.irregular@gafs.edu.ph | Grade 10 - Tilapia (irregular) |

### Parents

| Email | Linked children |
|-------|-----------------|
| parent.delacruz@gafs.edu.ph | Juan, Maria Santos, Diego |
| parent.ramos@gafs.edu.ph | Pedro, Miguel, Rafael |
| parent.villanueva@gafs.edu.ph | Ana, Isabel |
| parent.torres@gafs.edu.ph | Luis, Sofia, Carmen |
| parent@gafs.edu.ph | Elena, Jose, Patricia, Antonio, Rebecca, Carlo |

## Sections

| Key | Name | Grade |
|-----|------|-------|
| sec_g10_malunggay | Grade 10 - Malunggay | 10 |
| sec_g10_tilapia | Grade 10 - Tilapia | 10 |
| sec_g9_bamboo | Grade 9 - Bamboo | 9 |
| sec_g7_rice | Grade 7 - Rice | 7 |

## Pre-seeded session QR tokens

Use these for manual and E2E testing without a camera:

| Session | Token | Status | Use case |
|---------|-------|--------|----------|
| Active Agri (Grade 10 Malunggay) | `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee01` | Active | HP-07 scan success |
| Expired Math (Grade 10 Malunggay) | `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee02` | Expired | EC-05 rejection |
| Closed English (Grade 10 Malunggay) | `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee03` | Closed | EC-06 rejection |

## Irregular enrollment

**Carlo Irregular** (`carlo.irregular@gafs.edu.ph`, GAFS-2026-017) is in Grade 10 - Tilapia but irregularly enrolled in Agri-Fishery Technology in Grade 10 - Malunggay. Use for EC-03.

## Unenrolled student for negative tests

**Rebecca Tan** (GAFS-2026-016, Grade 7 - Rice) is not enrolled in Grade 10 Malunggay classes. Use `rebecca.tan@gafs.edu.ph` for EC-04 (not enrolled error).

## Expected state after seed

- **Active session**: Agri-Fishery, Grade 10 Malunggay, expires in ~2 hours
- **Expired session**: Mathematics, yesterday, `absent_processed=true`
- **Closed session**: English, closed ~1 hour ago
- Juan: present on closed session
- Maria Santos: late on closed session
- Pedro: absent on expired session
- Ana: present on expired session

## Relationship diagram

```
Section ──< Student >── User (student role)
Teacher ──< TeachingAssignment >── Subject
Student ──< StudentEnrollment >── TeachingAssignment
Teacher (user) ──< ClassSession
ClassSession ──< AttendanceRecord >── Student
Parent ──< parent_student >── Student
```

## File locations

- JSON: `docs/test-data/sample-data.json`
- Seeder: `backend/database/seeders/TestDataSeeder.php`
