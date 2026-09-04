import { test, expect } from '@playwright/test';
import { loginAndExpectHome, loginViaAPI, credentials } from '../fixtures/auth';

const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';

test.describe('Admin portal', () => {
  test.beforeEach(async ({ page }) => {
    await loginAndExpectHome(page, 'admin');
  });

  test('HP-02 students page lists seeded students', async ({ page }) => {
    await page.goto('/admin/students');
    await expect(page.getByText('GAFS-2026-001')).toBeVisible();
    await expect(page.getByText('Juan Dela Cruz')).toBeVisible();
  });

  test('HP-03 admin management pages load', async ({ page }) => {
    const routes = [
      '/admin/teachers',
      '/admin/sections',
      '/admin/subjects',
      '/admin/assignments',
    ];

    for (const route of routes) {
      await page.goto(route);
      await expect(page.locator('main, .v-main, [class*="layout"]').first()).toBeVisible();
    }
  });

  test('HP-04 enrollments page loads', async ({ page }) => {
    await page.goto('/admin/enrollments');
    await expect(page.getByText(/enroll/i).first()).toBeVisible();
  });

  test('HP-05 reports page loads', async ({ page }) => {
    await page.goto('/admin/reports');
    await expect(page.getByText(/report|attendance/i).first()).toBeVisible();
  });

  test('NG-06 create student with missing fields returns 422', async ({ request }) => {
    const { token } = await loginViaAPI(
      apiURL,
      credentials.admin.email,
      credentials.admin.password,
    );

    const response = await request.post(`${apiURL}/api/admin/students`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
      data: {},
    });

    expect(response.status()).toBe(422);
  });
});
