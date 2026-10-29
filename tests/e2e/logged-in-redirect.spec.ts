import { test, expect, wpCli, wpumFrontendLogin, createUser } from './fixtures';

/**
 * Redirects > Logged In Users sends logged in users away from the login and
 * registration pages.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/50
 */
test.describe('Logged in users on the login and registration pages', () => {
  const username = 'e2e_logged_in_redirect';

  test.beforeAll(() => {
    createUser(username, `${username}@example.com`, 'password123', 'subscriber');
  });

  test.afterEach(() => {
    wpCli('eval \'wpum_update_option( "logged_in_redirect", "" );\'');
  });

  test.afterAll(() => {
    try {
      wpCli(`user delete ${username} --yes`);
    } catch {
      // ignore
    }
  });

  test('redirects to the chosen page when set', async ({ page, loginPage, registerPage, accountPage }) => {
    wpCli('eval \'wpum_update_option( "logged_in_redirect", array( wpum_get_core_page_id( "account" ) ) );\'');
    await wpumFrontendLogin(page, loginPage, username, 'password123');
    await page.waitForLoadState('networkidle').catch(() => {});

    await page.goto(loginPage);
    await expect(page).toHaveURL(new RegExp(accountPage.replace(/\//g, '\\/')));

    await page.goto(registerPage);
    await expect(page).toHaveURL(new RegExp(accountPage.replace(/\//g, '\\/')));
  });

  test('shows the page as before when not set', async ({ page, loginPage }) => {
    await wpumFrontendLogin(page, loginPage, username, 'password123');
    await page.waitForLoadState('networkidle').catch(() => {});

    await page.goto(loginPage);
    await expect(page).toHaveURL(new RegExp(loginPage.replace(/\//g, '\\/')));
  });
});
