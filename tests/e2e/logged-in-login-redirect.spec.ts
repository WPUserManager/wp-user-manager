import { test, expect, wpAdminLogin } from './fixtures';

/**
 * Logged in visitors on the login page follow its redirect_to parameter.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/513
 */
test.describe('Login page redirect_to for logged in visitors', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
  });

  test('a local redirect_to is followed', async ({ page, loginPage, profileCardPage }) => {
    await page.goto(`${loginPage}?redirect_to=${encodeURIComponent(profileCardPage)}`);

    await expect(page).toHaveURL(new RegExp(`${profileCardPage}$`));
  });

  test('an external redirect_to shows the logged in message', async ({ page, loginPage }) => {
    await page.goto(`${loginPage}?redirect_to=${encodeURIComponent('https://example.org/')}`);

    await expect(page).toHaveURL(new RegExp(loginPage));
    await expect(page.locator('.wpum-already-logged-in')).toBeVisible();
  });

  test('reauth shows the logged in message', async ({ page, loginPage, profileCardPage }) => {
    await page.goto(`${loginPage}?redirect_to=${encodeURIComponent(profileCardPage)}&reauth=1`);

    await expect(page).toHaveURL(new RegExp(loginPage));
    await expect(page.locator('.wpum-already-logged-in')).toBeVisible();
  });

  test('no redirect_to shows the logged in message', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    await expect(page.locator('.wpum-already-logged-in')).toBeVisible();
  });
});
