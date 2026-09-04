import { test, expect } from '@playwright/test';
import { loginAndExpectHome, loginViaAPI, credentials } from '../fixtures/auth';
import { sessionTokens } from '../helpers/scan';

const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';

test.describe('Teacher portal', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndExpectHome(page, 'teacher');
  });

  test('HP-06 sessions list and active session detail with QR', async ({ page }) => {
    await page.goto('/teacher/sessions');
    await expect(page.getByText(/session/i).first()).toBeVisible();

    const activeRow = page.locator('tr').filter({ hasText: 'active' }).first();
    await activeRow.getByRole('button', { name: 'Open' }).click();
    await expect(page).toHaveURL(/\/teacher\/sessions\/\d+/);
    await expect(page.getByText('Session QR Code')).toBeVisible();
    await expect(page.locator('.qr-display svg').first()).toBeVisible({ timeout: 10_000 });
  });

  test('HP-09 close session via API', async ({ request }) => {
    const { token } = await loginViaAPI(
      apiURL,
      credentials.teacher.email,
      credentials.teacher.password,
    );

    const optionsResponse = await request.get(`${apiURL}/api/teacher/session-options`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    });
    const options = await optionsResponse.json();
    const classId = options.classes?.[0]?.id;
    expect(classId).toBeTruthy();

    const createResponse = await request.post(`${apiURL}/api/teacher/sessions`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      data: {
        teaching_assignment_id: classId,
        session_date: new Date().toISOString().slice(0, 10),
        start_time: '14:00',
        end_time: '15:00',
      },
    });
    expect(createResponse.ok()).toBeTruthy();
    const created = await createResponse.json();
    const sessionId = created.data?.id ?? created.id;

    const closeResponse = await request.post(
      `${apiURL}/api/teacher/sessions/${sessionId}/close`,
      {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
      },
    );
    expect(closeResponse.ok()).toBeTruthy();
  });
});

test.describe('Teacher dashboard', () => {
  test('dashboard loads stats', async ({ page }) => {
    await loginAndExpectHome(page, 'teacher');
    await expect(page.getByText(/dashboard|session|attendance/i).first()).toBeVisible();
  });
});
