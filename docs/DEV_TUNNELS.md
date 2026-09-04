# Dev Tunnels setup
#
# When accessing the frontend via a Dev Tunnel URL like:
#   https://s9gjchll-5173.asse.devtunnels.ms
#
# You must ALSO tunnel the backend (port 8000) and update both .env files.

## 1. Tunnel the backend (new terminal)

```bash
# VS Code / Cursor: Ports tab → forward port 8000 → set visibility to Public
# Or use devtunnel CLI for port 8000
```

Copy your backend tunnel URL, e.g.:
`https://abcd-8000.asse.devtunnels.ms`

## 2. Update frontend/.env

```env
VITE_API_URL=https://abcd-8000.asse.devtunnels.ms
```

Restart frontend: `npm run dev`

## 3. Update backend/.env

```env
FRONTEND_URL=https://s9gjchll-5173.asse.devtunnels.ms
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173,https://s9gjchll-5173.asse.devtunnels.ms
```

Restart backend: `php artisan serve`

## 4. Clear config cache (if CORS still fails)

```bash
cd backend
php artisan config:clear
```

## Why both tunnels?

| URL | Purpose |
|-----|---------|
| `https://...-5173...devtunnels.ms` | Frontend (Vue app) |
| `https://...-8000...devtunnels.ms` | Backend API (Laravel) |

The browser loads the frontend from the 5173 tunnel, then calls the API on the 8000 tunnel. Using `localhost:8000` from a dev tunnel page often causes CORS or connection errors on phones.
