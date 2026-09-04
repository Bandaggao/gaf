# Wireframes (Role Dashboards)

## Admin
```
┌─────────────────────────────────────────┐
│ [≡] GAFS A-Watch Admin          [Logout]│
├──────────┬──────────────────────────────┤
│ Dashboard│  Stats: Students | Teachers  │
│ Students │  | Sessions | Present Today  │
│ Teachers │  ┌─────────────────────────┐ │
│ Sections │  │ Attendance Chart (week)│ │
│ Subjects │  └─────────────────────────┘ │
│ Reports  │  Recent attendance table    │
└──────────┴──────────────────────────────┘
```

## Teacher
```
┌─────────────────────────────────────────────────┐
│ Sessions | Dashboard              [Logout]│
├─────────────────────────────────────────┤
│ [+ New Session]                           │
│ ┌─────────────────────────────────────┐ │
│ │ Math 10-A | Today 8:00 AM | [View QR]│ │
│ └─────────────────────────────────────┘ │
│ Session Detail: Fullscreen QR dialog      │
│ Attendance roster: Present/Absent/Late    │
└─────────────────────────────────────────┘
```

## Student (mobile-first)
```
┌──────────────────┐
│ GAFS A-Watch     │
├──────────────────┤
│  [ SCAN QR CODE ]│  ← large button
│                  │
│ My Attendance:   │
│ ✓ Math - Present │
│ ✗ Science - Abs  │
└──────────────────┘
```

## Parent (read-only)
```
┌─────────────────────────────────┐
│ Child: Juan Dela Cruz           │
├─────────────────────────────────┤
│ Date       Subject    Status    │
│ 2026-06-26 Math       Present   │
│ 2026-06-26 Science    Absent    │
└─────────────────────────────────┘
```
