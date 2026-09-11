# GAFS A-Watch

Real-time attendance monitoring system for Gamu Agri-Fishery School.

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

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Install PHP dependencies
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

#### Background workers (optional for email alerts and scheduled jobs)

Open two extra terminal tabs:

```bash
# Tab 2 — Process queued email jobs
php artisan queue:work

# Tab 3 — Run scheduled commands (auto-mark absent after session expires)
php artisan schedule:work
```

---

### 2. Frontend

```bash
cd frontend

# Copy environment file
cp .env.example .env
# Edit .env: set VITE_API_URL to your backend URL
# Local:       VITE_API_URL=http://localhost:8000
# Dev Tunnel:  VITE_API_URL=https://YOUR-BACKEND-TUNNEL-8000.asse.devtunnels.ms

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

**How it works:**
- USB mode: the scanner types the QR text into a hidden field and presses Enter
- Camera mode: the laptop camera reads the student QR via `html5-qrcode`
- Both modes call `POST /api/gate/scan` with the same gate key

| Status | Screen |
|--------|--------|
| `ok` | Green — Welcome + student name |
| `already_scanned` | Amber — already recorded today |
| `invalid` | Red — invalid or expired QR |

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
                                          Teacher opens session → sees who is at school
                                                              ↓
                                          Teacher marks absent anyone who missed class
                                                              ↓
                                          Teacher closes session → remaining students
                                          auto-finalized (at school = present, else absent)
```

---

## Tech Stack

| Layer    | Technology |
|----------|------------|
| Backend  | Laravel 11, Sanctum, SQLite/MySQL, DomPDF, QR Code, Gmail API |
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
