import { test, expect } from '@playwright/test';
import {
  loginViaUI,
  loginAndExpectHome,
  loginViaAPI,
  clearSession,
  credentials,
} from '../fixtures/auth';

const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';

test.describe('Authentication', () => {
  test('HP-01 admin login reaches dashboard', async ({ page }) => {
    await loginAndExpectHome(page, 'admin');
    await expect(page.getByText(/dashboard|overview|statistics/i).first()).toBeVisible();
  });

  test('HP-10 logout clears session', async ({ page }) => {
    await loginAndExpectHome(page, 'admin');
    await clearSession(page);
    await expect(page).toHaveURL(/\/login/);
  });

  test('EC-07 student blocked from admin routes', async ({ page }) => {
    await loginAndExpectHome(page, 'student');
    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/student\/dashboard/);
  });

  test('EC-08 login preserves redirect query', async ({ page }) => {
    await loginViaUI(page, 'student', { redirect: '/student/scan' });
    await expect(page).toHaveURL(/\/student\/scan/);
  });

  test('EC-09 empty login shows validation', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page.getByText('Email is required')).toBeVisible();
    await expect(page.getByText('Password is required')).toBeVisible();
  });

  test('NG-01 wrong password shows error', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(credentials.admin.email);
    await page.getByLabel('Password').fill('wrong-password');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page.getByText(/provided credentials are incorrect|unable to sign in/i)).toBeVisible();
  });

  test('NG-02 invalid email format blocked', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('not-an-email');
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL(/\/login/);
  });

  test('NG-04 unauthenticated API returns 401', async ({ request }) => {
    const response = await request.get(`${apiURL}/api/user`, {
      headers: { Accept: 'application/json' },
    });
    expect(response.status()).toBe(401);
  });

  test('NG-05 teacher cannot access admin dashboard', async ({ page }) => {
    await loginAndExpectHome(page, 'teacher');
    await page.goto('/admin/students');
    await expect(page).toHaveURL(/\/teacher\/dashboard/);
  });
});

test.describe('Role logins', () => {
  test('teacher reaches dashboard', async ({ page }) => {
    await loginAndExpectHome(page, 'teacher');
  });

  test('student reaches dashboard', async ({ page }) => {
    await loginAndExpectHome(page, 'student');
  });

  test('parent reaches dashboard', async ({ page }) => {
    await loginAndExpectHome(page, 'parent');
  });
});

test.describe('API login helper', () => {
  test('loginViaAPI returns token', async () => {
    const { token, user } = await loginViaAPI(
      apiURL,
      credentials.admin.email,
      credentials.admin.password,
    );
    expect(token).toBeTruthy();
    expect(user.role).toBe('admin');
  });
});
