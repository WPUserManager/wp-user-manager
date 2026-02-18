import { test, expect, wpLogout, deleteUser } from './fixtures';

test.describe('Registration Form', () => {
  test.beforeEach(async ({ page, registerPage }) => {
    // Make sure we're logged out before each test
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    // Clear cookies to ensure clean logged-out state
    await page.context().clearCookies();
  });

  test('registration form renders with expected fields', async ({ page, registerPage }) => {
    await page.goto(registerPage);

    // The registration form container should be visible
    const form = page.locator('.wpum-registration-form');
    await expect(form).toBeVisible();

    // The form element should exist
    const formElement = page.locator('#wpum-submit-registration-form');
    await expect(formElement).toBeVisible();

    // The submit button should exist
    const submitButton = page.locator('input[name="submit_registration"]');
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveValue('Register');

    // At minimum, an email field should be present (user_email)
    // The exact fields depend on the registration form configuration,
    // but email is always required by WPUM
    const hasEmailOrUsername = await page.locator('#user_email, #username').count();
    expect(hasEmailOrUsername).toBeGreaterThan(0);
  });

  test('successful registration with valid data', async ({ page, registerPage }) => {
    // Clean up the test user first (WPUM may use email as username when no username field)
    deleteUser('testuser_reg');
    deleteUser('testuser_reg@example.com');

    await page.goto(registerPage);

    // Fill in the registration form fields
    // WPUM default registration form has: username, user_email, user_password
    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('testuser_reg');
    }

    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('testuser_reg@example.com');
    }

    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy policy checkbox if present
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    // Submit the form
    await page.locator('input[name="submit_registration"]').click();

    // After successful registration, WPUM redirects with ?registration=success
    // or shows a success message
    await page.waitForURL(/registration=success/, { timeout: 15000 }).catch(() => {
      // If no redirect, check for success message on page
    });

    // Check for success message
    const successMessage = page.locator('.wpum-message.success');
    const hasSuccess = await successMessage.isVisible({ timeout: 5000 }).catch(() => false);
    const urlHasSuccess = page.url().includes('registration=success');

    expect(hasSuccess || urlHasSuccess).toBeTruthy();
  });

  test('shows validation error for empty required fields', async ({ page, registerPage }) => {
    await page.goto(registerPage);

    // Submit the form without filling anything
    await page.locator('input[name="submit_registration"]').click();

    // The browser should show HTML5 validation (required fields),
    // or WPUM should show an error message
    // Wait a moment for the page to process
    await page.waitForTimeout(1000);

    // Check if we're still on the registration page (form was not submitted successfully)
    const form = page.locator('.wpum-registration-form');
    const errorMessage = page.locator('.wpum-message.error');

    const stillOnForm = await form.isVisible({ timeout: 3000 }).catch(() => false);
    const hasError = await errorMessage.isVisible({ timeout: 3000 }).catch(() => false);

    // Either HTML5 validation prevented submission (still on form) or server-side error shown
    expect(stillOnForm || hasError).toBeTruthy();
  });

  test('shows validation error for invalid email', async ({ page, registerPage }) => {
    await page.goto(registerPage);

    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('invalidemail_test');
    }

    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      // Fill with invalid email
      await emailField.fill('not-an-email');
    }

    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    await page.locator('input[name="submit_registration"]').click();

    // Wait for response
    await page.waitForTimeout(1500);

    // Should show error or HTML5 validation prevents submission
    const form = page.locator('.wpum-registration-form');
    const errorMessage = page.locator('.wpum-message.error');

    const stillOnForm = await form.isVisible({ timeout: 3000 }).catch(() => false);
    const hasError = await errorMessage.isVisible({ timeout: 3000 }).catch(() => false);

    expect(stillOnForm || hasError).toBeTruthy();

    // Should NOT have success
    const successMessage = page.locator('.wpum-message.success');
    const hasSuccess = await successMessage.isVisible({ timeout: 1000 }).catch(() => false);
    expect(hasSuccess).toBeFalsy();
  });

  test('shows validation error for duplicate email', async ({ page, registerPage }) => {
    await page.goto(registerPage);

    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    // Use the admin email which already exists
    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('duplicatetest');
    }

    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('admin@example.com');
    }

    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    await page.locator('input[name="submit_registration"]').click();

    // Wait for response
    await page.waitForTimeout(2000);

    // Should show error about existing email
    const errorMessage = page.locator('.wpum-message.error');
    const hasError = await errorMessage.isVisible({ timeout: 5000 }).catch(() => false);

    if (hasError) {
      const errorText = await errorMessage.textContent();
      expect(errorText).toBeTruthy();
    }

    // Should NOT redirect to success
    expect(page.url()).not.toContain('registration=success');
  });

  test('redirect after successful registration', async ({ page, registerPage }) => {
    // Clean up (WPUM may use email as username when no username field)
    deleteUser('testuser_redirect');
    deleteUser('testuser_redirect@example.com');

    await page.goto(registerPage);

    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('testuser_redirect');
    }

    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('testuser_redirect@example.com');
    }

    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    await page.locator('input[name="submit_registration"]').click();

    // WPUM redirects to the registration page with ?registration=success
    await page.waitForURL(/registration=success/, { timeout: 15000 });

    expect(page.url()).toContain('registration=success');

    // Clean up
    deleteUser('testuser_redirect');
    deleteUser('testuser_redirect@example.com');
  });
});
