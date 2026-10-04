# GAFS A-Watch

Real-time attendance monitoring system for Gamu Agri-Fishery School.

**Quick start (run + database only):** see [`SETUP.md`](SETUP.md).

## Project Structure

```
gafs-awatch/
├── backend/                 ← Laravel REST API (PHP)
├── frontend/                ← Vue 3 + Vuetify SPA (JavaScript)
├── e2e/                     ← Playwright E2E tests
├── docs/
│   ├── test-data/           ← Realistic sample JSON + guide
│   ├── user-manual/         ← Screenshots + walkthrough videos
│   ├── FRONTEND.md          ← Vue architecture
│   ├── BACKEND.md           ← Laravel API docs
│   ├── DATABASE.md          ← Schema reference
│   ├── DEVELOPER_GUIDE.md   ← Onboarding for new devs
│   └── E2E_TEST_SCENARIOS.md
└── package.json             ← E2E test scripts
```

---

## Full Setup — Step by Step

### Prerequisites

| Tool | Version |
|------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |

---

### 1. Backend

```bash
cd backend

# Install PHP dependencies (.env is already in the repo)
composer install

# Run all migrations (creates SQLite DB at database/database.sqlite by default)
php artisan migrate

# Seed with test data (admin, teachers, students, sessions, attendance)
# Data file: backend/database/seeders/sample-data.json
php artisan db:seed

# Start the API server
php artisan serve
# → API available at http://localhost:8000/api
```

> **Fresh start (wipe and re-seed everything):**
> ```bash
> php artisan migrate:fresh --seed
> ```

#### Email alerts (SMTP)

Parent emails are sent through Laravel Mail (SMTP). For Gmail, use an [App Password](https://myaccount.google.com/apppasswords) — see [`docs/GMAIL_SETUP.md`](docs/GMAIL_SETUP.md).

In `backend/.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD="your app password"
MAIL_FROM_ADDRESS="your@gmail.com"
MAIL_FROM_NAME="GAFS A-Watch"
```

Queued emails (gate arrival + absence) require a worker. Scheduled jobs need the scheduler:

```bash
# Tab 2 — Process queued email jobs (required for parent alerts)
php artisan queue:work

# Tab 3 — Run scheduled commands (auto-mark absent after session expires)
php artisan schedule:work
```

| Event | When | Recipient |
|-------|------|-----------|
| Gate arrival | First successful gate scan of the day | `students.parent_email` |
| Absence | Session finalized / expired, student absent | `students.parent_email` |
| Weekly summary | Sunday 6 PM | Parent accounts |

---

### 2. Frontend

```bash
cd frontend

# .env is already in the repo (VITE_API_URL=http://localhost:8000)

# Install JS dependencies
npm install

# Start the dev server
npm run dev
# → App available at http://localhost:5173
```

> **Production build:**
> ```bash
> npm run build
> # Output → frontend/dist/
> ```

---

### 3. Gate Station (Laptop + USB QR Scanner)

Use a laptop at the school entrance with a USB QR scanner (keyboard-wedge type).

1. Set the shared key in `backend/.env`:
   ```env
   GATE_SCANNER_KEY=your-secret-key-here
   ```
2. Start backend + frontend.
3. On the gate laptop, open:
   ```
   http://localhost:5173/gate
   ```
   (or your frontend tunnel / production URL + `/gate`)
4. Enter the same `GATE_SCANNER_KEY` once — it is saved in the browser.
5. Choose scan mode:
   - **USB** — plug in a keyboard-wedge QR scanner (default)
   - **Camera** — use the laptop webcam if no hardware scanner is available
6. Student opens **My QR Code** on their phone → scan → Gate Station shows Welcome / Already recorded / Failed.
7. On the first successful scan of the day, the backend queues a parent arrival email (`SendGateArrivalEmail`) to `students.parent_email` (requires `php artisan queue:work`).

**How it works:**
- USB mode: the scanner types the QR text into a hidden field and presses Enter
- Camera mode: the laptop camera reads the student QR via `html5-qrcode`
- Both modes call `POST /api/gate/scan` with the same gate key
- Duplicate scans the same day return `already_scanned` and do **not** send another email

| Status | Screen | Email |
|--------|--------|-------|
| `ok` | Green — Welcome + student name | Parent arrival email queued |
| `already_scanned` | Amber — already recorded today | None |
| `invalid` | Red — invalid or expired QR | None |

Student QR tokens rotate every midnight.

---

## Default Logins

| Role    | Email               | Password  |
|---------|---------------------|-----------|
| Admin   | admin@gafs.edu.ph   | password  |
| Teacher | teacher@gafs.edu.ph | password  |
| Student | student@gafs.edu.ph | password  |
| Parent  | parent@gafs.edu.ph  | password  |

Full test dataset (17 students, 3 teachers): [`docs/test-data/SAMPLE_DATA.md`](docs/test-data/SAMPLE_DATA.md)

---

## Attendance Flow

```
Student shows QR on phone → Gate scanner reads it → Gate entry recorded (once/day)
                                                              ↓
                                          Parent notified by email (first scan only)
                                                              ↓
                                          Teacher opens session → sees who is at school
                                                              ↓
                                          Teacher marks absent anyone who missed class
                                                              ↓
                                          Teacher closes session → remaining students
                                          auto-finalized (at school = present, else absent)
                                                              ↓
                                          Absent students → parent absence email queued
```

---

## Tech Stack

| Layer    | Technology |
|----------|------------|
| Backend  | Laravel 11, Sanctum, SQLite/MySQL, DomPDF, QR Code, Laravel Mail (SMTP) |
| Frontend | Vue 3, Vuetify 3, Vue Router, Pinia, Axios, Chart.js |
| Testing  | Playwright E2E |

---

## Testing

```bash
# Requires backend + frontend running
npm run test:e2e              # automated scenarios
npm run test:e2e:walkthrough  # user manual videos + screenshots
```

---

## Documentation

| Document | Description |
|----------|-------------|
| [Setup / Run](SETUP.md) | Database + how to run the app |
| [Developer Guide](docs/DEVELOPER_GUIDE.md) | Step-by-step onboarding |
| [User Manual](docs/user-manual/README.md) | Screenshots + videos per role |
| [Frontend](docs/FRONTEND.md) | Vue architecture |
| [Backend](docs/BACKEND.md) | Laravel API |
| [Database](docs/DATABASE.md) | Schema + relationships |
| [API Routes](docs/API_ROUTES.md) | Endpoint reference |
| [Test Data](docs/test-data/SAMPLE_DATA.md) | Sample dataset |
| [E2E Scenarios](docs/E2E_TEST_SCENARIOS.md) | Test catalog |
| [Deployment](docs/DEPLOYMENT.md) | Local + production |
| [Gmail Setup](docs/GMAIL_SETUP.md) | Email configuration |
| [ERD](docs/ERD.md) | Entity diagram |
| [UAT Checklist](docs/UAT_CHECKLIST.md) | Manual test cases |
