import { test, expect, wpAdminLogin, wpCli, createUser } from './fixtures';

/**
 * "Send password reset (WPUM)" on the admin users list.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/370
 */
test.describe('Admin sends a WPUM password reset', () => {
  const username = 'e2e_admin_reset';

  test.beforeAll(() => {
    try {
      wpCli(`user delete ${username} --yes`);
    } catch {
      // ignore
    }
    createUser(username, `${username}@example.com`, 'password123', 'subscriber');
  });

  test.afterAll(() => {
    try {
      wpCli(`user delete ${username} --yes`);
    } catch {
      // ignore
    }
  });

  test('row action sends the email and confirms it', async ({ page }) => {
    await wpAdminLogin(page);
    await page.goto(`/wp-admin/users.php?s=${username}`);

    const row = page.locator('#the-list tr', { hasText: username });
    await expect(row).toBeVisible();
    await row.hover();

    const link = row.getByRole('link', { name: 'Send password reset (WPUM)' });
    await expect(link).toBeVisible();
    await link.click();

    await expect(page).toHaveURL(/users\.php\?wpum_password_reset=sent/);
    await expect(page.locator('.notice-success', { hasText: 'Password reset email sent.' })).toBeVisible();

    // A reset key was stored for the user.
    expect(wpCli(`eval 'echo get_user_by( "login", "${username}" )->user_activation_key ? "has-key" : "no-key";'`)).toContain('has-key');
  });

  test('the link does not work without a valid nonce', async ({ page }) => {
    await wpAdminLogin(page);
    const userId = wpCli(`user get ${username} --field=ID`).trim().split('\n').pop();

    const response = await page.goto(`/wp-admin/admin-post.php?action=wpum_send_password_reset&user=${userId}&_wpnonce=bad`);
    expect(response?.status()).toBe(403);
  });
});
