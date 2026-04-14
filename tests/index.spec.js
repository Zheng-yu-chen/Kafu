const { test, expect } = require('@playwright/test');

test('index page shows identity cards and form action', async ({ page }) => {
  await page.goto('/index.php');

  await expect(page).toHaveTitle(/KaFu/);
  await expect(page.locator('h2.main-title')).toHaveText('請選擇您的身份');
  await expect(page.locator('button[name="identity"][value="user"]')).toBeVisible();
  await expect(page.locator('button[name="identity"][value="store"]')).toBeVisible();
  await expect(page.locator('button[name="identity"][value="admin"]')).toBeVisible();
  await expect(page.locator('form.identity-form')).toHaveAttribute('action', 'kafu_login.php');
});
