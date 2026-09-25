import { test, expect, wpAdminLogin } from './fixtures';

test.describe('Role-Based Content Restriction', () => {
  test.describe('[wpum_restrict_to_user_roles] - Admin Only Content', () => {
    test('role-restricted content hidden from wrong role', async ({
      page,
      roleRestrictedPage,
    }) => {
      // Log in as subscriber (wrong role for admin-only content)
      await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

      await page.goto(roleRestrictedPage);

      // The admin-only content should NOT be visible
      const pageContent = await page.content();
      expect(pageContent).not.toContain('Only admins can see this.');

      // A warning/restriction message should be shown
      const warningMessage = page.locator('.wpum-message.warning');
      await expect(warningMessage).toBeVisible({ timeout: 5000 });

      // The warning should mention login or register
      await expect(warningMessage).toContainText(/login|register/i);
    });

    test('role-restricted content visible to correct role', async ({
      page,
      roleRestrictedPage,
    }) => {
      // Log in as admin (correct role)
      await wpAdminLogin(page, 'admin', 'password');

      await page.goto(roleRestrictedPage);

      // The admin-only content SHOULD be visible
      const pageContent = await page.content();
      expect(pageContent).toContain('Only admins can see this.');

      // No warning message should be shown
      const warningMessage = page.locator('.wpum-message.warning');
      const hasWarning = await warningMessage.isVisible({ timeout: 2000 }).catch(() => false);
      expect(hasWarning).toBeFalsy();
    });

    test('role-restricted content hidden from logged-out users', async ({
      page,
      roleRestrictedPage,
    }) => {
      // Make sure we're logged out
      await page.context().clearCookies();
      await page.goto('/wp-login.php?action=logout');
      const confirmLink = page.locator('a[href*="action=logout"]');
      if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
        await confirmLink.click();
      }
      await page.context().clearCookies();

      await page.goto(roleRestrictedPage);

      // The admin-only content should NOT be visible
      const pageContent = await page.content();
      expect(pageContent).not.toContain('Only admins can see this.');

      // A warning/restriction message should be shown
      const warningMessage = page.locator('.wpum-message.warning');
      await expect(warningMessage).toBeVisible({ timeout: 5000 });

      // The warning should mention login or register
      await expect(warningMessage).toContainText(/login|register/i);
    });
  });
});
