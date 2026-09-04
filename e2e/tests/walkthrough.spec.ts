import { test } from '@playwright/test';
import path from 'path';
import fs from 'fs/promises';
import { credentials, loginViaUI, loginViaAPI } from '../fixtures/auth';
import { sessionTokens, expectScanSuccess } from '../helpers/scan';
import { hold, typeSlow, visitNav, scrollThrough } from '../helpers/walkthrough';
import { startNarration, narrate, finalizeNarration } from '../helpers/narration';
import {
  acceptConfirms,
  demoId,
  demoProgramBlockCrud,
  demoSubjectCrud,
  demoTeacherCrud,
  demoStudentCrud,
  demoClassesViewAndAdd,
  demoEnrollmentsView,
  demoReportsView,
  demoTeacherSessionCrud,
} from '../helpers/crud';

const apiURL = process.env.E2E_API_URL ?? 'http://localhost:8000';
const videoDir = path.join(__dirname, '../../docs/user-manual/videos');

test.describe.configure({ mode: 'serial' });

test.afterEach(async ({ page }, testInfo) => {
  const slug = testInfo.title.replace(/\s+/g, '-').toLowerCase();
  const dest = path.join(videoDir, `${slug}.webm`);
  const video = page.video();
  await page.close();
  if (video) {
    const src = await video.path();
    if (src) await fs.copyFile(src, dest);
  }
  await finalizeNarration(slug);
});

test('Admin walkthrough', async ({ page }) => {
  startNarration('admin');
  acceptConfirms(page);
  const id = demoId();

  await narrate('intro');
  await page.goto('/login');
  await hold(page, 2500);
  await narrate('login');
  await typeSlow(page, 'Email', credentials.admin.email);
  await typeSlow(page, 'Password', credentials.admin.password);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForURL(/\/admin\/dashboard/);
  await hold(page, 4000);
  await narrate('dashboard');
  await scrollThrough(page);

  await demoProgramBlockCrud(page, id);
  await demoSubjectCrud(page, id);
  await demoTeacherCrud(page, id);
  await demoStudentCrud(page, id);
  await demoClassesViewAndAdd(page);
  await demoEnrollmentsView(page);
  await demoReportsView(page);

  await narrate('outro');
  await hold(page, 2000);
});

test('Teacher walkthrough', async ({ page }) => {
  startNarration('teacher');
  acceptConfirms(page);

  await narrate('intro');
  await page.goto('/login');
  await narrate('login');
  await loginViaUI(page, 'teacher');
  await page.waitForURL(/\/teacher\/dashboard/);
  await hold(page, 4000);
  await narrate('dashboard');
  await scrollThrough(page);

  await demoTeacherSessionCrud(page);
  await visitNav(page, 'Sessions', 3500);

  await narrate('profile');
  await visitNav(page, 'Profile', 3500);
  await narrate('outro');
  await hold(page, 2000);
});

test('Student walkthrough', async ({ page, request }) => {
  startNarration('student');

  await narrate('intro');
  await page.goto('/login');
  await narrate('login');
  await loginViaUI(page, 'student');
  await page.waitForURL(/\/student\/dashboard/);
  await hold(page, 4000);
  await narrate('dashboard');
  await scrollThrough(page);

  await narrate('scan');
  await visitNav(page, 'Scan QR', 4000);
  await hold(page, 3000);

  const { token } = await loginViaAPI(apiURL, credentials.student.email, credentials.student.password);
  await expectScanSuccess(request, apiURL, token, sessionTokens.active);

  await narrate('scan.done');
  await visitNav(page, 'Dashboard', 4000);
  await scrollThrough(page);

  await narrate('profile');
  await visitNav(page, 'Profile', 3500);
  await narrate('outro');
  await hold(page, 2000);
});

test('Parent walkthrough', async ({ page }) => {
  startNarration('parent');

  await narrate('intro');
  await page.goto('/login');
  await narrate('login');
  await loginViaUI(page, 'parent');
  await page.waitForURL(/\/parent\/dashboard/);
  await hold(page, 4000);
  await narrate('dashboard');
  await scrollThrough(page);
  await hold(page, 5000);
  await narrate('outro');
  await hold(page, 2000);
});
