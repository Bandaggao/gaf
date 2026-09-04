import { test, expect } from '@playwright/test';
import {
  loginAndExpectHome,
  loginViaAPI,
  credentials,
} from '../fixtures/auth';
import {
  sessionTokens,
  expectScanSuccess,
  expectScanFailure,
  scanAsStudent,
} from '../helpers/scan';

const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';

async function studentToken(email: string) {
  const { token } = await loginViaAPI(apiURL, email, credentials.student.password);
  return token;
}

test.describe('Student scan API', () => {
  test('HP-07 scan active session succeeds', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    await expectScanSuccess(request, apiURL, token, sessionTokens.active);
  });

  test('EC-01 duplicate scan succeeds', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    await expectScanSuccess(request, apiURL, token, sessionTokens.active);
    await expectScanSuccess(request, apiURL, token, sessionTokens.active);
  });

  test('EC-03 irregular student can scan cross-section class', async ({ request }) => {
    const token = await studentToken(credentials.irregularStudent.email);
    await expectScanSuccess(request, apiURL, token, sessionTokens.active);
  });

  test('EC-04 unenrolled student rejected', async ({ request }) => {
    const token = await studentToken(credentials.unenrolledStudent.email);
    await expectScanFailure(request, apiURL, token, sessionTokens.active);
  });

  test('EC-05 expired session rejected', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    await expectScanFailure(request, apiURL, token, sessionTokens.expired);
  });

  test('EC-06 closed session rejected', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    await expectScanFailure(request, apiURL, token, sessionTokens.closed);
  });

  test('NG-03 invalid token rejected', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    await expectScanFailure(request, apiURL, token, sessionTokens.invalid);
  });

  test('EC-02 scan returns present or late status', async ({ request }) => {
    const token = await studentToken(credentials.student.email);
    const response = await scanAsStudent(request, apiURL, token, sessionTokens.active);
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(['present', 'late']).toContain(body.data?.status);
  });
});

test.describe('Student portal UI', () => {
  test('student dashboard and scan page load', async ({ page }) => {
    await loginAndExpectHome(page, 'student');
    await expect(page.getByText(/attendance|dashboard/i).first()).toBeVisible();

    await page.goto('/student/scan');
    await expect(page.getByText(/scan attendance/i)).toBeVisible();
  });
});
