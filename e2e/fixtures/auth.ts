import { Page, expect } from '@playwright/test';

export const credentials = {
  admin: { email: 'admin@gafs.edu.ph', password: 'password' },
  teacher: { email: 'teacher@gafs.edu.ph', password: 'password' },
  student: { email: 'student@gafs.edu.ph', password: 'password' },
  parent: { email: 'parent@gafs.edu.ph', password: 'password' },
  unenrolledStudent: { email: 'rebecca.tan@gafs.edu.ph', password: 'password' },
  irregularStudent: { email: 'carlo.irregular@gafs.edu.ph', password: 'password' },
} as const;

export type AccountKey = keyof typeof credentials;

const roleHome: Record<string, RegExp> = {
  admin: /\/admin\/dashboard/,
  teacher: /\/teacher\/dashboard/,
  student: /\/student\/dashboard/,
  parent: /\/parent\/dashboard/,
};

const accountRole: Record<AccountKey, keyof typeof roleHome> = {
  admin: 'admin',
  teacher: 'teacher',
  student: 'student',
  parent: 'parent',
  unenrolledStudent: 'student',
  irregularStudent: 'student',
};

export async function loginViaUI(
  page: Page,
  account: AccountKey,
  options?: { redirect?: string },
) {
  const query = options?.redirect ? `?redirect=${encodeURIComponent(options.redirect)}` : '';
  await page.goto(`/login${query}`);
  await page.getByLabel('Email').fill(credentials[account].email);
  await page.getByLabel('Password').fill(credentials[account].password);
  await page.getByRole('button', { name: 'Sign In' }).click();
}

export async function loginAndExpectHome(page: Page, account: AccountKey) {
  await loginViaUI(page, account);
  await expect(page).toHaveURL(roleHome[accountRole[account]]);
}

export async function loginViaAPI(apiURL: string, email: string, password: string) {
  const response = await fetch(`${apiURL}/api/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    throw new Error(`Login failed for ${email}: ${response.status}`);
  }

  const data = await response.json();
  return { token: data.token as string, user: data.user };
}

export async function clearSession(page: Page) {
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });
  await page.goto('/login');
}
