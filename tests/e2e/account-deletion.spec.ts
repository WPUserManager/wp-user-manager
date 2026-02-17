import { test, expect, wpAdminLogin, createUser, deleteUser } from './fixtures';

test.describe('Account Deletion', () => {
  test('privacy/delete tab renders on account page', async ({
    page,
    accountPage,
  }) => {
    // Log in as subscriber
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    await page.goto(accountPage);

    // The account page should render with tabs
    const accountNav = page.locator('.wpum-account-navigation');
    await expect(accountNav).toBeVisible({ timeout: 5000 });

    // Look for the Delete account tab (provided by wpum-delete-account addon)
    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    const hasDeleteTab = await deleteTab.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasDeleteTab) {
      // Click the delete account tab
      await deleteTab.click();
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

      // The delete account form should appear
      const deleteForm = page.locator('.wpum-delete-account-form');
      await expect(deleteForm).toBeVisible({ timeout: 5000 });

      // The form should contain a password field
      const passwordField = page.locator('#password');
      await expect(passwordField).toBeVisible({ timeout: 3000 });

      // The submit button should be present
      const submitButton = page.locator('input[name="submit_delete_account"]');
      await expect(submitButton).toBeVisible();
    } else {
      // Fall back to checking for the Privacy tab (built into WPUM core)
      const privacyTab = page.locator('a.tab-privacy, a[href*="tab/privacy"]');
      const hasPrivacyTab = await privacyTab.isVisible({ timeout: 3000 }).catch(() => false);

      // At least one of the tabs must be present
      expect(hasDeleteTab || hasPrivacyTab).toBeTruthy();

      if (hasPrivacyTab) {
        await privacyTab.click();
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

        // The privacy form should appear
        const privacyForm = page.locator('.wpum-profile-privacy-form');
        await expect(privacyForm).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('account deletion requires password', async ({
    page,
    accountPage,
  }) => {
    // Create a disposable user for this test
    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    // Log in as the disposable user
    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');

    await page.goto(accountPage);

    // Navigate to the delete account tab
    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    const hasDeleteTab = await deleteTab.isVisible({ timeout: 3000 }).catch(() => false);

    if (!hasDeleteTab) {
      test.skip();
      return;
    }

    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The delete form should be visible
    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    // Try to submit without entering a password
    const submitButton = page.locator('input[name="submit_delete_account"]');

    // Dismiss the browser confirmation dialog automatically
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    await submitButton.click();

    // Wait for response
    await page.waitForTimeout(2000);

    // The password field is required, so either HTML5 validation prevents submission
    // or the server returns an error
    const errorMessage = page.locator('.wpum-message.error');
    const hasError = await errorMessage.isVisible({ timeout: 3000 }).catch(() => false);

    const stillOnForm = await deleteForm.isVisible({ timeout: 3000 }).catch(() => false);

    // Either HTML5 validation prevented submission (still on form) or server-side error shown
    expect(stillOnForm || hasError).toBeTruthy();

    // Clean up
    deleteUser('testuser_delete');
  });

  test('account deletion with wrong password shows error', async ({
    page,
    accountPage,
  }) => {
    // Create a disposable user for this test
    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    // Log in as the disposable user
    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');

    await page.goto(accountPage);

    // Navigate to the delete account tab
    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    const hasDeleteTab = await deleteTab.isVisible({ timeout: 3000 }).catch(() => false);

    if (!hasDeleteTab) {
      test.skip();
      return;
    }

    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The delete form should be visible
    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    // Enter a wrong password
    const passwordField = page.locator('#password');
    await passwordField.fill('WrongPassword999!');

    // Dismiss the browser confirmation dialog automatically
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    // Submit the form
    const submitButton = page.locator('input[name="submit_delete_account"]');
    await submitButton.click();

    // Wait for response
    await page.waitForTimeout(2000);

    // An error message should be shown about incorrect password
    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });

    const errorText = await errorMessage.textContent();
    expect(errorText).toBeTruthy();
    expect(errorText!.toLowerCase()).toContain('password');

    // Clean up
    deleteUser('testuser_delete');
  });

  test('successful account deletion', async ({
    page,
    accountPage,
  }) => {
    // Create a disposable user for this test
    createUser('testuser_delete', 'testuser_delete@example.com', 'DeleteMe123!', 'subscriber');

    // Log in as the disposable user
    await wpAdminLogin(page, 'testuser_delete', 'DeleteMe123!');

    await page.goto(accountPage);

    // Navigate to the delete account tab
    const deleteTab = page.locator('a.tab-delete-account, a[href*="delete-account"]');
    const hasDeleteTab = await deleteTab.isVisible({ timeout: 3000 }).catch(() => false);

    if (!hasDeleteTab) {
      test.skip();
      return;
    }

    await deleteTab.click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The delete form should be visible
    const deleteForm = page.locator('.wpum-delete-account-form');
    await expect(deleteForm).toBeVisible({ timeout: 5000 });

    // Enter the correct password
    const passwordField = page.locator('#password');
    await passwordField.fill('DeleteMe123!');

    // Dismiss the browser confirmation dialog automatically
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    // Submit the form
    const submitButton = page.locator('input[name="submit_delete_account"]');
    await submitButton.click();

    // After successful deletion, the user should be logged out and redirected
    // The plugin redirects to home_url() or a configured redirect page
    await page.waitForURL(/.*/, { timeout: 15000 });

    // Wait for the redirect to complete
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Verify the user is logged out by checking they cannot access the account page
    await page.goto(accountPage);
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The account page should redirect to login or show a login form
    // since the user is no longer logged in
    const loginForm = page.locator('#user_login, #loginform, .wpum-login-form');
    const isOnLoginPage = page.url().includes('wp-login.php') || page.url().includes('wpum-login');
    const hasLoginForm = await loginForm.isVisible({ timeout: 5000 }).catch(() => false);

    expect(isOnLoginPage || hasLoginForm).toBeTruthy();

    // Clean up just in case the deletion did not complete
    deleteUser('testuser_delete');
  });
});
