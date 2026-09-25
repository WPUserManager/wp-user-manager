import { test, expect, wpAdminLogin } from './fixtures';

test.describe('Content Restriction Shortcodes', () => {
  test.describe('[wpum_restrict_logged_in] - Members Only Content', () => {
    test('shows restriction message to logged-out users', async ({
      page,
      loggedInContentPage,
    }) => {
      // Make sure we're logged out
      await page.context().clearCookies();
      await page.goto('/wp-login.php?action=logout');
      const confirmLink = page.locator('a[href*="action=logout"]');
      if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
        await confirmLink.click();
      }
      await page.context().clearCookies();

      await page.goto(loggedInContentPage);

      // The secret content should NOT be visible
      const pageContent = await page.content();
      expect(pageContent).not.toContain('This is secret members-only content.');

      // A warning/restriction message should be shown
      const warningMessage = page.locator('.wpum-message.warning');
      await expect(warningMessage).toBeVisible({ timeout: 5000 });

      // The warning should mention login or register
      await expect(warningMessage).toContainText(/login|register/i);
    });

    test('shows content to logged-in users', async ({ page, loggedInContentPage }) => {
      // Log in
      await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

      await page.goto(loggedInContentPage);

      // The secret content SHOULD be visible
      const pageContent = await page.content();
      expect(pageContent).toContain('This is secret members-only content.');

      // No warning message should be shown
      const warningMessage = page.locator('.wpum-message.warning');
      const hasWarning = await warningMessage.isVisible({ timeout: 2000 }).catch(() => false);
      expect(hasWarning).toBeFalsy();
    });
  });

  test.describe('[wpum_restrict_logged_out] - Guests Only Content', () => {
    test('shows content to logged-out users', async ({ page, loggedOutContentPage }) => {
      // Make sure we're logged out
      await page.context().clearCookies();
      await page.goto('/wp-login.php?action=logout');
      const confirmLink = page.locator('a[href*="action=logout"]');
      if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
        await confirmLink.click();
      }
      await page.context().clearCookies();

      await page.goto(loggedOutContentPage);

      // The guest-only content SHOULD be visible
      const pageContent = await page.content();
      expect(pageContent).toContain('This content is for guests only.');
    });

    test('hides content from logged-in users', async ({ page, loggedOutContentPage }) => {
      // Log in
      await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

      await page.goto(loggedOutContentPage);

      // The guest-only content should NOT be visible
      const pageContent = await page.content();
      expect(pageContent).not.toContain('This content is for guests only.');
    });
  });
});
