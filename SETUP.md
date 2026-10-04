# GAFS A-Watch — Run & Database Setup

Minimal guide to set up the database and run the app locally.

`.env` files are included in the repo for this student project — no need to copy from `.env.example`.

## Prerequisites

| Tool | Version |
|------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |

---

## 1. Database setup

Default is **SQLite** (no extra install). Laravel creates the file on migrate.

### Option A — SQLite (default)

`backend/.env` already uses:

```env
DB_CONNECTION=sqlite
# Leave DB_HOST / DB_DATABASE unset for the default file:
# backend/database/database.sqlite
```

Create the empty file if it does not exist yet:

```bash
cd backend
touch database/database.sqlite
```

### Option B — MySQL

Update `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gafs_awatch
DB_USERNAME=root
DB_PASSWORD=
```

Create the database first:

```sql
CREATE DATABASE gafs_awatch;
```

---

## 2. Backend

```bash
cd backend

composer install

# Create tables
php artisan migrate

# Load sample users, students, sessions, etc.
php artisan db:seed
```

**Wipe and rebuild from scratch:**

```bash
php artisan migrate:fresh --seed
```

---

## 3. Frontend

```bash
cd frontend
npm install
```

---

## 4. Run the app

Open **two** terminals:

```bash
# Terminal 1 — API (http://localhost:8000)
cd backend && php artisan serve

# Terminal 2 — UI (http://localhost:5173)
cd frontend && npm run dev
```

Open [http://localhost:5173](http://localhost:5173).

### Optional (email / auto-absent)

```bash
# Terminal 3 — queued parent emails
cd backend && php artisan queue:work

# Terminal 4 — mark absent when sessions expire
cd backend && php artisan schedule:work
```

---

## Default logins

| Role    | Email               | Password  |
|---------|---------------------|-----------|
| Admin   | admin@gafs.edu.ph   | password  |
| Teacher | teacher@gafs.edu.ph | password  |
| Student | student@gafs.edu.ph | password  |
| Parent  | parent@gafs.edu.ph  | password  |

---

## Quick checklist

1. `composer install` (backend) + `npm install` (frontend)  
2. `php artisan migrate` (+ `db:seed` for sample data)  
3. `php artisan serve` + `npm run dev`  

For full project docs, email/SMTP, and gate station setup, see [README.md](README.md).
