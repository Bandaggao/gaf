# Gmail API Setup

## 1. Google Cloud Console

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create project: **GAFS A-Watch**
3. Enable **Gmail API**
4. Configure OAuth consent screen (External, add test users)

## 2. OAuth Credentials

1. APIs & Services → Credentials → Create OAuth client ID
2. Type: **Web application**
3. Authorized redirect URI: `http://localhost:8000/gmail/callback`
4. Copy **Client ID** and **Client Secret**

## 3. Get Refresh Token

Use [Google OAuth Playground](https://developers.google.com/oauthplayground/):

1. Settings → Use your own OAuth credentials
2. Select scope: `https://mail.google.com/`
3. Authorize and exchange for tokens
4. Copy **Refresh token**

## 4. Backend `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="GAFS A-Watch"

GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REFRESH_TOKEN=
GMAIL_EMAIL=your@gmail.com
```

## 5. Queue Worker

Emails are queued. Run:

```bash
cd backend
php artisan queue:work
```

## Email Triggers

| Event | Recipient |
|-------|-----------|
| Student scans in | Parent email on student record |
| Session ends, student absent | Parent email |
| Weekly summary (Sunday 6 PM) | Parents + admin |
