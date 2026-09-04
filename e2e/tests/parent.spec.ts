import { test, expect } from '@playwright/test';
import { loginAndExpectHome } from '../fixtures/auth';

test.describe('Parent portal', () => {
  test('HP-08 parent dashboard shows linked children', async ({ page }) => {
    await loginAndExpectHome(page, 'parent');
    await expect(page.getByText(/attendance|child|student/i).first()).toBeVisible();
    await expect(page.getByText(/Elena|Juan|Jose|Patricia|Carlo/i).first()).toBeVisible();
  });
});
