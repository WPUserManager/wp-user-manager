import { test, expect, wpCli, createUser } from './fixtures';

/**
 * WPUM form pages are sent with no-cache headers, and a valid password reset
 * link works straight after an invalid one.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/117
 * @see https://github.com/WPUserManager/wp-user-manager/issues/304
 */
test.describe('Form pages are not cached', () => {
  test('login, register and password pages send no-cache headers', async ({ request, loginPage, registerPage, passwordRecoveryPage }) => {
    for (const path of [loginPage, registerPage, passwordRecoveryPage]) {
      const response = await request.get(path);
      expect(response.ok(), path).toBeTruthy();
      expect(response.headers()['cache-control'], path).toContain('no-store');
    }
  });

  test('profile page stays cacheable', async ({ request, profilePage }) => {
    const response = await request.get(profilePage);
    expect(response.headers()['cache-control'] || '').not.toContain('no-store');
  });

  test('a valid reset link works right after an invalid one', async ({ page, passwordRecoveryPage }) => {
    const username = 'e2e_reset_after_invalid';
    try {
      wpCli(`user delete ${username} --yes`);
    } catch {
      // ignore
    }
    createUser(username, `${username}@example.com`, 'password123', 'subscriber');

    const key = wpCli(`eval 'echo get_password_reset_key( get_user_by( "login", "${username}" ) );'`).trim().split('\n').pop() as string;
    const resetUrl = (k: string) => `${passwordRecoveryPage}?action=wpum-reset&login=${username}&key=${k}`;

    await page.context().clearCookies();

    await page.goto(resetUrl('not-a-valid-key'));
    await expect(page.getByText('The reset key is wrong or expired')).toBeVisible();

    await page.goto(resetUrl(key));
    await expect(page.getByText('The reset key is wrong or expired')).toHaveCount(0);
    await expect(page.locator('input[type="password"]').first()).toBeVisible();

    wpCli(`user delete ${username} --yes`);
  });
});
