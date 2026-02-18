import { test, expect } from './fixtures';

test.describe('Login Form', () => {
  test.beforeEach(async ({ page }) => {
    // Make sure we're logged out before each test
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();
  });

  test('login form renders', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    // The login form container should be visible
    const form = page.locator('.wpum-login-form');
    await expect(form).toBeVisible();

    // The form element should exist
    const formElement = page.locator('#wpum-submit-login-form');
    await expect(formElement).toBeVisible();

    // Username/email field should be present
    const usernameField = page.locator('#username');
    await expect(usernameField).toBeVisible();

    // Password field should be present
    const passwordField = page.locator('#password');
    await expect(passwordField).toBeVisible();

    // Submit button should be present
    const submitButton = page.locator('input[name="submit_login"]');
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveValue('Login');

    // Remember me checkbox should be present
    const rememberMe = page.locator('#remember');
    await expect(rememberMe).toBeVisible();
  });

  test('successful login with username', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    await page.locator('#username').fill('testuser_login');
    await page.locator('#password').fill('TestPass123!');
    await page.locator('input[name="submit_login"]').click();

    // After successful login, WPUM redirects (typically to the login page or a configured redirect)
    // The user should be logged in - we verify by checking that we are no longer on the login form
    // or that we can access the admin bar / dashboard
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Verify login succeeded: check we're not seeing the login form anymore
    // or check for the WordPress admin bar
    const loginForm = page.locator('.wpum-login-form #wpum-submit-login-form');
    const isStillOnLoginForm = await loginForm.isVisible({ timeout: 3000 }).catch(() => false);

    // If redirected away from login form, login was successful
    // If still on page, check for "already logged in" message or admin bar
    if (isStillOnLoginForm) {
      // Check for errors - if there's an error, the login failed
      const errorMessage = page.locator('.wpum-message.error');
      const hasError = await errorMessage.isVisible({ timeout: 2000 }).catch(() => false);
      expect(hasError).toBeFalsy();
    }

    // Verify we are actually logged in by going to wp-admin
    await page.goto('/wp-admin/');
    await expect(page).not.toHaveURL(/wp-login\.php/);
  });

  test('successful login with email', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    await page.locator('#username').fill('testuser_login@example.com');
    await page.locator('#password').fill('TestPass123!');
    await page.locator('input[name="submit_login"]').click();

    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Verify we are actually logged in by going to wp-admin
    await page.goto('/wp-admin/');
    await expect(page).not.toHaveURL(/wp-login\.php/);
  });

  test('failed login with wrong password', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    await page.locator('#username').fill('testuser_login');
    await page.locator('#password').fill('WrongPassword123!');
    await page.locator('input[name="submit_login"]').click();

    // Wait for the page to process
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Should show an error message
    const errorMessage = page.locator('.wpum-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });

    // Should still be on the login page
    const loginForm = page.locator('.wpum-login-form');
    await expect(loginForm).toBeVisible();

    // Verify we are NOT logged in
    await page.goto('/wp-admin/');
    await expect(page).toHaveURL(/wp-login\.php/);
  });

  test('redirect after login', async ({ page, loginPage }) => {
    await page.goto(loginPage);

    await page.locator('#username').fill('testuser_login');
    await page.locator('#password').fill('TestPass123!');
    await page.locator('input[name="submit_login"]').click();

    // WPUM should redirect after login.
    // By default it redirects to the login page itself or a configured redirect page.
    // The key thing is the user should not stay on the login form.
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // After login, user should be redirected somewhere (not still on login form with fields visible)
    // Either: redirected to another page, or same page shows "already logged in"
    const loginFormStillVisible = page.locator('#wpum-submit-login-form');
    const alreadyLoggedIn = page.locator('.wpum-template');

    const formVisible = await loginFormStillVisible.isVisible({ timeout: 3000 }).catch(() => false);

    if (formVisible) {
      // If form is still visible, there should be an error
      // (which means redirect failed, not what we want)
      // But more likely, the page redirected and the form is gone
      const error = page.locator('.wpum-message.error');
      const hasError = await error.isVisible({ timeout: 1000 }).catch(() => false);
      expect(hasError).toBeFalsy();
    }

    // Confirm the user is logged in
    await page.goto('/wp-admin/');
    await expect(page).not.toHaveURL(/wp-login\.php/);
  });
});
