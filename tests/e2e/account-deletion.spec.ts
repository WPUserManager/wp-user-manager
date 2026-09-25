import { test, expect, wpAdminLogin, wpCli, createUser, deleteUser } from './fixtures';

let addonInstalled = false;

test.describe('Account Deletion', () => {
  test.beforeAll(async () => {
    // Check if the wpum-delete-account addon is available
    try {
      const result = wpCli('plugin list --field=name');
      addonInstalled = result.includes('wpum-delete-account');
    } catch {
      addonInstalled = false;
    }
  });

  test('privacy/delete tab renders on account page', async ({
    page,
    accountPage,
  }) => {
    test.skip(!addonInstalled, 'wpum-delete-account addon not installed');

    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
    await page.goto(accountPage);

    const accountNav = page.locator('.wpum-account-navigation');
    await expect(accountNav).toBeVisible({ timeout: 5000 });

    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    await expect(deleteTab).toBeVisible({ timeout: 3000 });
    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    const passwordField = page.locator('#password');
    await expect(passwordField).toBeVisible({ timeout: 3000 });

    const submitButton = page.locator('input[name="submit_delete_account"]');
    await expect(submitButton).toBeVisible();
  });

  test('account deletion requires password', async ({
    page,
    accountPage,
  }) => {
    test.skip(!addonInstalled, 'wpum-delete-account addon not installed');

    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');
    await page.goto(accountPage);

    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    const submitButton = page.locator('input[name="submit_delete_account"]');
    await submitButton.click();
    await page.waitForTimeout(2000);

    const errorMessage = page.locator('.wpum-message.error');
    const hasError = await errorMessage.isVisible({ timeout: 3000 }).catch(() => false);
    const stillOnForm = await deleteForm.isVisible({ timeout: 3000 }).catch(() => false);

    expect(stillOnForm || hasError).toBeTruthy();

    deleteUser('testuser_delete');
  });

  test('account deletion with wrong password shows error', async ({
    page,
    accountPage,
  }) => {
    test.skip(!addonInstalled, 'wpum-delete-account addon not installed');

    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');
    await page.goto(accountPage);

    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    const passwordField = page.locator('#password');
    await passwordField.fill('WrongPassword999!');

    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    const submitButton = page.locator('input[name="submit_delete_account"]');
    await submitButton.click();
    await page.waitForTimeout(2000);

    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });

    const errorText = await errorMessage.textContent();
    expect(errorText).toBeTruthy();
    expect(errorText!.toLowerCase()).toContain('password');

    deleteUser('testuser_delete');
  });

  test('successful account deletion', async ({
    page,
    accountPage,
  }) => {
    test.skip(!addonInstalled, 'wpum-delete-account addon not installed');

    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');
    await page.goto(accountPage);

    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    const passwordField = page.locator('#password');
    await passwordField.fill('DeleteMe123!');

    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    const submitButton = page.locator('input[name="submit_delete_account"]');
    await submitButton.click();

    await page.waitForURL(/.*/, { timeout: 15000 });
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    await page.goto(accountPage);
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    const loginForm = page.locator('#user_login, #loginform, .wpum-login-form');
    const isOnLoginPage = page.url().includes('wp-login.php') || page.url().includes('wpum-login');
    const hasLoginForm = await loginForm.isVisible({ timeout: 5000 }).catch(() => false);

    expect(isOnLoginPage || hasLoginForm).toBeTruthy();

    deleteUser('testuser_delete');
  });
});
