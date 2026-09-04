# Deployment Guide (Hybrid)

## Architecture

```
[Student Phone] ──HTTPS──► [Frontend :5173 or Nginx]
                                │
                                ▼ API calls
                          [Backend :8000]
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
              [MySQL local]           [Gmail API]
```

## Local Development

Terminal 1 — Backend:
```bash
cd backend && php artisan serve
```

Terminal 2 — Frontend:
```bash
cd frontend && npm run dev
```

Terminal 3 — Queue:
```bash
cd backend && php artisan queue:work
```

Terminal 4 — Scheduler:
```bash
cd backend && php artisan schedule:work
```

## School Server (XAMPP)

1. Copy `backend/` to `htdocs/gafs-api/`
2. Point Apache document root or alias to `backend/public`
3. Build frontend: `cd frontend && npm run build`
4. Serve `frontend/dist/` via Apache or Nginx
5. Set `frontend/.env` → `VITE_API_URL=https://your-school-server/api`
6. Set `backend/.env` → `FRONTEND_URL=https://your-school-server`

## HTTPS (Required for QR Camera)

Options:
- **Ngrok:** `ngrok http 5173` for quick testing
- **mkcert:** Local SSL certificates
- **VPS + Let's Encrypt:** Production deployment

## Production Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] MySQL database created and migrated
- [ ] Gmail API credentials configured
- [ ] Queue worker running (supervisor/systemd)
- [ ] Scheduler cron: `* * * * * php artisan schedule:run`
- [ ] HTTPS enabled on frontend URL
- [ ] CORS / Sanctum stateful domains configured

## Sanctum Config

In `backend/.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,your-school-domain.com
FRONTEND_URL=http://localhost:5173
```
