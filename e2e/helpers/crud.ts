import { Page, expect } from '@playwright/test';
import { hold } from './walkthrough';
import { narrate } from './narration';

const DEMO = () => Date.now().toString().slice(-6);

export function demoId() {
  return DEMO();
}

/** Accept browser confirm() dialogs for delete actions. */
export function acceptConfirms(page: Page) {
  page.on('dialog', (dialog) => dialog.accept());
}

export async function saveDialog(page: Page) {
  const dialog = page.getByRole('dialog');
  await dialog.getByRole('button', { name: 'Save', exact: true }).click();
  await expect(dialog).toBeHidden({ timeout: 15_000 });
  await hold(page, 1500);
}

export async function cancelDialog(page: Page) {
  await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();
  await hold(page, 1000);
}

function dialog(page: Page) {
  return page.getByRole('dialog');
}

/** View list — pause on table. */
export async function viewList(page: Page, searchText?: string) {
  if (searchText) {
    const search = page.getByPlaceholder(/search/i).first();
    if (await search.isVisible()) {
      await search.fill(searchText);
      await hold(page, 2000);
    }
  }
  await hold(page, 3000);
}

/** Click row action button by icon title or order. */
async function rowAction(
  page: Page,
  rowText: string,
  action: 'edit' | 'delete',
  editIndex = 0,
) {
  const row = page.locator('tr').filter({ hasText: rowText }).first();
  await expect(row).toBeVisible();
  const buttons = row.getByRole('button');
  if (action === 'edit') {
    await buttons.nth(editIndex).click();
  } else {
    await buttons.last().click();
  }
  await hold(page, 1500);
}

/** Full CRUD on Program Blocks (simplest entity). */
export async function demoProgramBlockCrud(page: Page, id: string) {
  const created = `Grade 8 - ${id} Block`;
  const updated = `Grade 8 - ${id} Updated`;

  await page.goto('/admin/sections');
  await hold(page, 3000);
  await narrate('blocks.view');
  await viewList(page);

  await narrate('blocks.add');
  await page.getByRole('button', { name: 'Add Program Block' }).click();
  await hold(page, 2000);
  await page.getByRole('dialog').getByLabel('Block Name (e.g. BSIT 2-A)').fill(created);
  await page.getByRole('dialog').getByLabel('Year Level', { exact: true }).fill('8');
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: created })).toBeVisible();
  await hold(page, 3000);

  await narrate('blocks.edit');
  await rowAction(page, created, 'edit', 0);
  await hold(page, 1500);
  await page.getByRole('dialog').getByLabel('Block Name (e.g. BSIT 2-A)').fill(updated);
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: updated })).toBeVisible();
  await hold(page, 3000);

  await narrate('blocks.delete');
  await rowAction(page, updated, 'delete');
  await hold(page, 2000);
  await expect(page.locator('tr').filter({ hasText: updated })).toHaveCount(0);
  await hold(page, 2000);
}

/** Full CRUD on Subjects. */
export async function demoSubjectCrud(page: Page, id: string) {
  const created = `${id} Agriculture`;
  const updated = `${id} Agriculture Updated`;

  await page.goto('/admin/subjects');
  await hold(page, 3000);
  await narrate('subjects.view');
  await viewList(page);

  await narrate('subjects.add');
  await page.getByRole('button', { name: 'Add Subject' }).click();
  await hold(page, 2000);
  await dialog(page).getByLabel('Subject Name', { exact: true }).fill(created);
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: created })).toBeVisible();
  await hold(page, 2500);

  await narrate('subjects.edit');
  await rowAction(page, created, 'edit', 0);
  await dialog(page).getByLabel('Subject Name', { exact: true }).fill(updated);
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: updated })).toBeVisible();
  await hold(page, 2500);

  await narrate('subjects.delete');
  await rowAction(page, updated, 'delete');
  await hold(page, 2000);
}

/** Full CRUD on Teachers. */
export async function demoTeacherCrud(page: Page, id: string) {
  const name = `Teacher ${id}`;
  const email = `teacher.${id}@gafs.edu.ph`;
  const updatedName = `Teacher ${id} Updated`;

  await page.goto('/admin/teachers');
  await hold(page, 3000);
  await narrate('teachers.view');
  await viewList(page);

  await narrate('teachers.add');
  await page.getByRole('button', { name: 'Add Teacher' }).click();
  await hold(page, 2000);
  await dialog(page).getByLabel('Full Name', { exact: true }).fill(name);
  await dialog(page).getByLabel('Email', { exact: true }).fill(email);
  await dialog(page).getByLabel('Password', { exact: true }).fill('password');
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: name })).toBeVisible();
  await hold(page, 2500);

  await narrate('teachers.edit');
  await rowAction(page, name, 'edit', 0);
  await dialog(page).getByLabel('Full Name', { exact: true }).fill(updatedName);
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: updatedName })).toBeVisible();
  await hold(page, 2500);

  await narrate('teachers.delete');
  await rowAction(page, updatedName, 'delete');
  await hold(page, 2000);
}

/** Full CRUD on Students. */
export async function demoStudentCrud(page: Page, id: string) {
  const name = `Student ${id}`;
  const email = `student.${id}@gafs.edu.ph`;
  const number = `GAFS-DEMO-${id}`;
  const updatedName = `Student ${id} Updated`;

  await page.goto('/admin/students');
  await hold(page, 3000);
  await narrate('students.view');
  await viewList(page, 'GAFS-2026');

  await narrate('students.add');
  await page.getByRole('button', { name: 'Add Student' }).click();
  await hold(page, 2000);
  await dialog(page).getByLabel('Full Name', { exact: true }).fill(name);
  await dialog(page).getByLabel('Email', { exact: true }).fill(email);
  await dialog(page).getByLabel('Password', { exact: true }).fill('password');
  await dialog(page).getByLabel('Student Number', { exact: true }).fill(number);
  await dialog(page).getByLabel('Year Level', { exact: true }).fill('8');
  await dialog(page).getByLabel('Parent Email', { exact: true }).fill('parent@gafs.edu.ph');

  await dialog(page).getByRole('combobox', { name: 'Home Program Block' }).click();
  await hold(page, 500);
  await page.getByRole('listbox').getByRole('option').first().click();
  await hold(page, 1500);

  await saveDialog(page);

  const search = page.getByPlaceholder(/search students/i);
  if (await search.isVisible()) {
    await search.clear();
    await hold(page, 1500);
  }

  await expect(page.locator('tr').filter({ hasText: number })).toBeVisible({ timeout: 10_000 });
  await hold(page, 2500);

  await narrate('students.edit');
  await rowAction(page, number, 'edit', 1);
  await dialog(page).getByLabel('Full Name', { exact: true }).fill(updatedName);
  await hold(page, 1500);
  await saveDialog(page);
  await expect(page.locator('tr').filter({ hasText: updatedName })).toBeVisible();
  await hold(page, 2500);

  await narrate('students.delete');
  await rowAction(page, updatedName, 'delete');
  await hold(page, 2000);
}

/** View classes + open assign dialog (add demo without deleting seed data). */
export async function demoClassesViewAndAdd(page: Page) {
  await page.goto('/admin/assignments');
  await hold(page, 3000);
  await narrate('classes.view');
  await viewList(page);

  await narrate('classes.add');
  await page.getByRole('button', { name: 'Assign Classes' }).click();
  await hold(page, 3000);
  await cancelDialog(page);
  await hold(page, 1500);
}

/** View enrollments — select student and show enrollment panel. */
export async function demoEnrollmentsView(page: Page) {
  await page.goto('/admin/enrollments');
  await hold(page, 3000);
  await narrate('enrollments.view');

  await page.getByRole('combobox', { name: 'Student' }).click();
  await hold(page, 1000);
  await page.getByRole('option', { name: /Juan Dela Cruz/i }).click();
  await hold(page, 4000);
}

/** View reports — generate with date range. */
export async function demoReportsView(page: Page) {
  await page.goto('/admin/reports');
  await hold(page, 3000);
  await narrate('reports.view');

  const today = new Date().toISOString().slice(0, 10);
  const monthAgo = new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10);

  await page.getByLabel('From').fill(monthAgo);
  await hold(page, 1000);
  await page.getByLabel('To').fill(today);
  await hold(page, 1000);
  await page.getByRole('button', { name: 'Generate' }).click();
  await hold(page, 4000);
}

/** Teacher: create session, view QR, close session. */
function activeSessionTimes() {
  const now = new Date();
  const start = new Date(now.getTime() - 5 * 60_000);
  const end = new Date(now.getTime() + 2 * 60 * 60_000);
  const pad = (n: number) => n.toString().padStart(2, '0');
  const toTime = (d: Date) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  return {
    date: now.toISOString().slice(0, 10),
    start: toTime(start),
    end: toTime(end),
  };
}

export async function demoTeacherSessionCrud(page: Page) {
  await page.goto('/teacher/sessions');
  await hold(page, 3000);
  await narrate('sessions.view');

  await narrate('sessions.create');
  await page.getByRole('button', { name: 'New Session' }).click();
  await hold(page, 2500);

  const { date, start, end } = activeSessionTimes();
  await page.getByLabel('Date', { exact: true }).fill(date);
  await page.getByLabel('Start Time', { exact: true }).fill(start);
  await page.getByLabel('End Time', { exact: true }).fill(end);
  await hold(page, 2000);

  await page.getByRole('button', { name: 'Start Session' }).click();
  await page.waitForURL(/\/teacher\/sessions\/\d+/);
  await hold(page, 5000);

  await narrate('sessions.qr');
  await expect(page.getByText('Session QR Code')).toBeVisible();
  await expect(page.locator('.qr-display svg, .qr-display img').first()).toBeVisible({ timeout: 15_000 });
  await hold(page, 4000);

  await narrate('sessions.close');
  await page.getByRole('button', { name: 'Close Session' }).click();
  await hold(page, 3000);
}
