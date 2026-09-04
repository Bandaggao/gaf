import { Page } from '@playwright/test';

/** Pause so viewers can read each screen in walkthrough videos. */
export async function hold(page: Page, ms = 2500) {
  await page.waitForTimeout(ms);
}

/** Navigate via sidebar link and pause on the page. */
export async function visitNav(page: Page, label: string, holdMs = 3000) {
  await page.getByRole('link', { name: label, exact: true }).click();
  await page.waitForLoadState('load');
  await hold(page, holdMs);
}

/** Brief scroll so table content is visible in the recording. */
export async function scrollThrough(page: Page) {
  await page.evaluate(async () => {
    const step = window.innerHeight * 0.5;
    window.scrollTo({ top: step, behavior: 'smooth' });
    await new Promise((r) => setTimeout(r, 600));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  await hold(page, 1000);
}

/** Type into a field slowly for visibility in recordings. */
export async function typeSlow(page: Page, label: string, value: string) {
  const field = page.getByLabel(label);
  await field.click();
  await hold(page, 500);
  await field.fill(value);
  await hold(page, 800);
}
