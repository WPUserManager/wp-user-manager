import { test, expect } from './fixtures';

test.describe('Password Recovery Form', () => {
  test.beforeEach(async ({ page }) => {
    // Make sure we're logged out before each test
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();
  });

  test('password recovery form renders', async ({ page, passwordRecoveryPage }) => {
    await page.goto(passwordRecoveryPage);

    // The password recovery form container should be visible
    const form = page.locator('.wpum-password-recovery-form');
    await expect(form).toBeVisible();

    // The form element should exist
    const formElement = page.locator('#wpum-submit-password-recovery-form');
    await expect(formElement).toBeVisible();

    // Username/email field should be present
    const usernameEmailField = page.locator('#username_email');
    await expect(usernameEmailField).toBeVisible();

    // Submit button should be present
    const submitButton = page.locator('input[name="submit_password_recovery"]');
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveValue('Reset password');

    // The informational message should be displayed
    const message = page.locator('.wpum-password-recovery-form p');
    await expect(message).toContainText('Lost your password');
  });

  test('submitting valid email shows success message', async ({
    page,
    passwordRecoveryPage,
  }) => {
    await page.goto(passwordRecoveryPage);

    // Fill in with a valid user email
    await page.locator('#username_email').fill('testuser_login@example.com');
    await page.locator('input[name="submit_password_recovery"]').click();

    // Wait for the page to process
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Should show success message about email being sent
    const successMessage = page.locator('.wpum-password-reset-request-success');
    await expect(successMessage).toBeVisible({ timeout: 10000 });

    // The success message should mention that an email was sent
    await expect(successMessage).toContainText("sent an email");
  });

  test('submitting invalid email shows error', async ({
    page,
    passwordRecoveryPage,
  }) => {
    await page.goto(passwordRecoveryPage);

    // Fill in with an email that doesn't exist
    await page.locator('#username_email').fill('nonexistent@example.com');
    await page.locator('input[name="submit_password_recovery"]').click();

    // Wait for the page to process
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Should show an error message
    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });

    // Error should mention that the user doesn't exist
    await expect(errorMessage).toContainText('does not exist');

    // The form should still be visible for the user to try again
    const form = page.locator('.wpum-password-recovery-form');
    await expect(form).toBeVisible();
  });
});
