import { test, expect, wpAdminLogin, wpLogout, wpCli } from './fixtures';

test.describe('Password Change on Account Page', () => {
  /**
   * Ensure the "current_password" WPUM option is enabled before each test,
   * so the form includes the Current Password field.
   */
  test.beforeEach(async () => {
    wpCli("eval 'wpum_update_option(\"current_password\", true);'");
  });

  test('password tab renders on account page', async ({ page, accountPage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
    await page.goto(accountPage);

    // The account page should render with tabs
    const accountContainer = page.locator('.wpum-account-page');
    await expect(accountContainer).toBeVisible({ timeout: 5000 });

    // Click the Password tab
    const passwordTab = page.locator('a.tab-password');
    await expect(passwordTab).toBeVisible();
    await passwordTab.click();
    await page.waitForLoadState('networkidle').catch(() => {});

    // Verify the password change form appears
    const passwordForm = page.locator('#wpum-submit-password-form');
    await expect(passwordForm).toBeVisible({ timeout: 5000 });

    // Verify all three password fields are present
    const currentPasswordField = page.locator('#current_password');
    await expect(currentPasswordField).toBeVisible();

    const newPasswordField = page.locator('#password');
    await expect(newPasswordField).toBeVisible();

    const confirmPasswordField = page.locator('#password_repeat');
    await expect(confirmPasswordField).toBeVisible();

    // Verify the submit button is present
    const submitButton = page.locator('input[name="submit_password"]');
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveValue('Change password');
  });

  test('successful password change', async ({ page, accountPage }) => {
    const originalPassword = 'TestPass123!';
    const newPassword = 'NewTestPass456!';

    try {
      // Log in with the current password
      await wpAdminLogin(page, 'testuser_login', originalPassword);

      // Navigate to the password tab
      await page.goto(accountPage + 'password');
      await page.waitForLoadState('networkidle').catch(() => {});

      // Fill in the password change form
      await page.locator('#current_password').fill(originalPassword);
      await page.locator('#password').fill(newPassword);
      await page.locator('#password_repeat').fill(newPassword);

      // Submit the form
      await page.locator('input[name="submit_password"]').click();
      await page.waitForLoadState('networkidle').catch(() => {});

      // Verify success message is displayed
      const successMessage = page.locator('.wpum-message.success');
      await expect(successMessage).toBeVisible({ timeout: 10000 });
      await expect(successMessage).toContainText('Password successfully updated');

      // Log out and verify login with the NEW password works
      await wpLogout(page);
      await wpAdminLogin(page, 'testuser_login', newPassword);

      // Confirm we are logged in by visiting the account page
      await page.goto(accountPage);
      const accountContainer = page.locator('.wpum-account-page');
      await expect(accountContainer).toBeVisible({ timeout: 5000 });

      // Now change the password BACK to the original via the form
      await page.goto(accountPage + 'password');
      await page.waitForLoadState('networkidle').catch(() => {});

      await page.locator('#current_password').fill(newPassword);
      await page.locator('#password').fill(originalPassword);
      await page.locator('#password_repeat').fill(originalPassword);
      await page.locator('input[name="submit_password"]').click();
      await page.waitForLoadState('networkidle').catch(() => {});

      // Verify the password was reset back successfully
      const resetSuccessMessage = page.locator('.wpum-message.success');
      await expect(resetSuccessMessage).toBeVisible({ timeout: 10000 });
      await expect(resetSuccessMessage).toContainText('Password successfully updated');
    } catch (e) {
      // If anything fails, reset the password via WP-CLI to avoid breaking other tests
      wpCli('user update testuser_login --user_pass=TestPass123!');
      throw e;
    }
  });

  test('wrong current password shows error', async ({ page, accountPage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Navigate to the password tab
    await page.goto(accountPage + 'password');
    await page.waitForLoadState('networkidle').catch(() => {});

    // Fill in with a wrong current password
    await page.locator('#current_password').fill('WrongPassword99!');
    await page.locator('#password').fill('SomeNewPass789!');
    await page.locator('#password_repeat').fill('SomeNewPass789!');

    // Submit the form
    await page.locator('input[name="submit_password"]').click();
    await page.waitForLoadState('networkidle').catch(() => {});

    // Verify error message is displayed
    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    await expect(errorMessage).toContainText('incorrect current password');

    // The form should still be visible for the user to try again
    const passwordForm = page.locator('#wpum-submit-password-form');
    await expect(passwordForm).toBeVisible();
  });

  test('password mismatch shows error', async ({ page, accountPage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Navigate to the password tab
    await page.goto(accountPage + 'password');
    await page.waitForLoadState('networkidle').catch(() => {});

    // Fill in correct current password but mismatched new passwords
    await page.locator('#current_password').fill('TestPass123!');
    await page.locator('#password').fill('NewPassword111!');
    await page.locator('#password_repeat').fill('DifferentPassword222!');

    // Submit the form
    await page.locator('input[name="submit_password"]').click();
    await page.waitForLoadState('networkidle').catch(() => {});

    // Verify error message about password mismatch
    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    await expect(errorMessage).toContainText('passwords do not match');

    // The form should still be visible for the user to try again
    const passwordForm = page.locator('#wpum-submit-password-form');
    await expect(passwordForm).toBeVisible();
  });

  test('empty fields validation prevents submission', async ({ page, accountPage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Navigate to the password tab
    await page.goto(accountPage + 'password');
    await page.waitForLoadState('networkidle').catch(() => {});

    // Verify the form is present
    const passwordForm = page.locator('#wpum-submit-password-form');
    await expect(passwordForm).toBeVisible({ timeout: 5000 });

    // Try to submit without filling in any fields
    await page.locator('input[name="submit_password"]').click();

    // The fields have the HTML5 "required" attribute, so the browser should
    // prevent submission. Verify we are still on the password tab with the form visible.
    await expect(passwordForm).toBeVisible();

    // Verify that no success message appeared (form was not submitted)
    const successMessage = page.locator('.wpum-message.success');
    const hasSuccess = await successMessage.isVisible({ timeout: 1000 }).catch(() => false);
    expect(hasSuccess).toBe(false);

    // Check that the current_password field is marked as invalid by the browser
    // (HTML5 required validation)
    const currentPasswordField = page.locator('#current_password');
    const isInvalid = await currentPasswordField.evaluate(
      (el: HTMLInputElement) => !el.validity.valid
    );
    expect(isInvalid).toBe(true);
  });
});
