import { execSync } from 'child_process';
import path from 'path';

export default async function globalSetup() {
  const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';
  const backendDir = path.join(__dirname, '../backend');

  if (process.env.E2E_SKIP_SEED !== '1') {
    try {
      execSync('php artisan migrate:fresh --seed --no-interaction', {
        cwd: backendDir,
        stdio: 'inherit',
      });
    } catch (error) {
      console.warn('[e2e] Could not reset database. Set E2E_SKIP_SEED=1 to skip.');
      console.warn(error);
    }
  }

  try {
    const response = await fetch(`${apiURL}/api/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ email: 'admin@gafs.edu.ph', password: 'password' }),
    });

    if (!response.ok) {
      console.warn(
        `[e2e] Backend not reachable at ${apiURL}. Start backend + frontend before running tests.`,
      );
    }
  } catch {
    console.warn(
      `[e2e] Could not connect to ${apiURL}. Ensure php artisan serve and npm run dev are running.`,
    );
  }
}
