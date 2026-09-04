# Frontend Documentation — GAFS A-Watch

Vue 3 single-page application with Vuetify 4, Pinia, and Vue Router 5.

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Vue 3 + Vite 8 |
| UI | Vuetify 4, MDI icons |
| State | Pinia |
| Routing | Vue Router 5 (history mode) |
| HTTP | Axios + Sanctum Bearer token |
| QR | html5-qrcode |
| Charts | Chart.js + vue-chartjs |

## Folder structure

```
frontend/src/
├── main.js              # App bootstrap, Vuetify, Pinia, Router
├── App.vue
├── api/                 # Axios API modules per role
│   ├── client.js        # Axios instance + interceptors
│   ├── auth.js          # login, logout, fetchUser
│   ├── authToken.js     # Token ref (localStorage)
│   ├── admin.js
│   ├── teacher.js
│   ├── student.js
│   └── parent.js
├── stores/
│   └── auth.js          # Pinia auth store
├── router/
│   └── index.js         # Routes + guards
├── layouts/             # Role-specific shells
│   ├── GuestLayout.vue
│   ├── AdminLayout.vue
│   ├── TeacherLayout.vue
│   ├── StudentLayout.vue
│   └── ParentLayout.vue
├── pages/               # Route views
│   ├── LoginPage.vue
│   ├── admin/           # 8 pages
│   ├── teacher/         # 4 pages
│   ├── student/         # 3 pages
│   └── parent/          # 1 page
├── components/
│   ├── QrScanner.vue    # Camera QR scanner (html5-qrcode)
│   ├── AttendanceChart.vue
│   ├── layout/AppShell.vue
│   └── ui/              # Shared UI primitives
└── constants/
    └── roles.js         # Role → home route map
```

## Authentication flow

1. User submits login form → `authStore.login()` → `POST /api/login`
2. Token stored in `localStorage` via `authToken.js`
3. Axios interceptor attaches `Authorization: Bearer {token}` on every request
4. Router `beforeEach` guard checks `requiresAuth` and `role` meta
5. Wrong role → redirect to role home from `roleHome` map
6. Logout → `POST /api/logout` + clear token

## Routes

| Path | Role | Page |
|------|------|------|
| `/login` | guest | LoginPage |
| `/admin/dashboard` | admin | DashboardPage |
| `/admin/students` | admin | StudentsPage |
| `/admin/teachers` | admin | TeachersPage |
| `/admin/sections` | admin | SectionsPage |
| `/admin/subjects` | admin | SubjectsPage |
| `/admin/assignments` | admin | AssignmentsPage |
| `/admin/enrollments` | admin | EnrollmentsPage |
| `/admin/reports` | admin | ReportsPage |
| `/teacher/dashboard` | teacher | DashboardPage |
| `/teacher/sessions` | teacher | SessionsPage |
| `/teacher/sessions/:id` | teacher | SessionDetailPage |
| `/teacher/profile` | teacher | ProfilePage |
| `/student/dashboard` | student | DashboardPage |
| `/student/scan` | student | ScanPage |
| `/student/profile` | student | ProfilePage |
| `/parent/dashboard` | parent | DashboardPage |

## Key components

### QrScanner.vue

Uses `html5-qrcode` with rear camera (`facingMode: 'environment'`). Emits `@scan` with decoded token string. Requires HTTPS for camera in production browsers.

### AttendanceChart.vue

Chart.js bar/line chart used on admin and teacher dashboards.

### AppShell.vue

Shared layout: sidebar navigation, app bar, logout, role portal title.

### UI components (`components/ui/`)

| Component | Purpose |
|-----------|---------|
| PageHeader | Page title + action slot |
| DataCard | Searchable v-data-table wrapper |
| FormDialog | Modal form with save/cancel |
| SearchSelect | Autocomplete for API-driven selects |
| StatCard | Dashboard metric card |
| StatusChip | Colored status badge (session/attendance) |

## Environment

```env
VITE_API_URL=http://localhost:8000
```

Set in `frontend/.env`. Must point to the Laravel API base (no `/api` suffix — client adds it).

## Running locally

```bash
cd frontend
npm install
npm run dev
```

Open http://localhost:5173

## Adding a new page

1. Create `src/pages/{role}/NewPage.vue`
2. Add route in `src/router/index.js` with `meta: { requiresAuth: true, role: '...' }`
3. Add nav item in the role's layout file
4. Add API functions in `src/api/{role}.js`
5. Create matching backend controller + route

## E2E tests

See [`../e2e/`](../e2e/) and run `npm run test:e2e` from project root.
