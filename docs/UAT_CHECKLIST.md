# UAT Checklist & ISO/IEC 25010:2023 Evaluation

## Participants (from proposal)

| Group | Count |
|-------|-------|
| Students | 90 |
| Teachers | 25 |
| Parents | 90 |
| IT Experts | 10 |

## UAT Test Cases

### Admin
- [ ] Login as admin
- [ ] Create/edit/delete student with parent email
- [ ] View student QR code
- [ ] Create teacher, section, subject
- [ ] Generate attendance report (PDF)
- [ ] View dashboard chart

### Teacher
- [ ] Login as teacher
- [ ] Create class session
- [ ] Display session QR on screen
- [ ] View attendance roster
- [ ] Manually override attendance status

### Student
- [ ] Login on phone browser (HTTPS)
- [ ] Scan session QR code
- [ ] See confirmation message
- [ ] View personal attendance history
- [ ] Duplicate scan rejected

### Parent
- [ ] Receive email on student scan-in
- [ ] Receive email on absence
- [ ] View child attendance in portal (read-only)

## ISO/IEC 25010:2023 Criteria

Rate 1–5 (Likert scale):

1. Functional Suitability
2. Performance Efficiency
3. Compatibility
4. Reliability
5. Security
6. Maintainability
7. Flexibility
8. Interaction Capability
9. Safety

## Training Sessions

| Role | Duration | Topics |
|------|----------|--------|
| Admin | 60 min | CRUD, reports, Gmail config |
| Teacher | 45 min | Session QR, roster |
| Student | 30 min | Phone login, scan QR |
| Parent | 30 min | Portal, email alerts |

## Sign-off

- [ ] Critical bugs fixed
- [ ] Survey responses collected
- [ ] Documentation finalized
