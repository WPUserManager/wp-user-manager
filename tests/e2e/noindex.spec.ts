import { test, expect, wpCli, wpAdminLogin } from './fixtures';

/**
 * WPUM's utility pages are noindexed and left out of the sitemap.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/514
 */
const robotsMeta = (html: string): string => {
  const match = html.match(/<meta name=['"]robots['"] content=['"]([^'"]*)['"]/);
  return match ? match[1] : '';
};

test.describe('Utility pages are noindexed', () => {
  test('login, register, password and profile pages are noindexed for guests', async ({ request, loginPage, registerPage, passwordRecoveryPage, profilePage }) => {
    for (const path of [loginPage, registerPage, passwordRecoveryPage, profilePage]) {
      const response = await request.get(path);
      expect(response.ok(), path).toBeTruthy();
      expect(robotsMeta(await response.text()), path).toContain('noindex');
    }
  });

  test('account page and its tabs are noindexed', async ({ page, accountPage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    for (const path of [accountPage, `${accountPage}password/`]) {
      await page.goto(path);
      expect(page.url(), path).toContain(path);
      await expect(page.locator('meta[name="robots"]'), path).toHaveAttribute('content', /noindex/);
    }
  });

  test('normal pages and the member directory stay indexable', async ({ request, directoryPage, profileCardPage }) => {
    for (const path of [directoryPage, profileCardPage]) {
      const response = await request.get(path);
      expect(response.ok(), path).toBeTruthy();
      expect(robotsMeta(await response.text()), path).not.toContain('noindex');
    }
  });

  test('member profiles are indexable only when guests can view them', async ({ request }) => {
    const profileUrl = wpCli(`eval 'echo wpum_get_profile_url( get_user_by( "login", "testuser_login" ) );'`).trim().split('\n').pop() as string;
    const original = wpCli(`eval 'echo wpum_get_option( "guests_can_view_profiles" ) ? 1 : 0;'`).trim().split('\n').pop();

    try {
      wpCli(`eval 'wpum_update_option( "guests_can_view_profiles", true );'`);
      expect(robotsMeta(await (await request.get(profileUrl)).text())).not.toContain('noindex');

      wpCli(`eval 'wpum_update_option( "guests_can_view_profiles", false );'`);
      expect(robotsMeta(await (await request.get(profileUrl)).text())).toContain('noindex');
    } finally {
      wpCli(`eval 'wpum_update_option( "guests_can_view_profiles", ${original === '1' ? 'true' : 'false'} );'`);
    }
  });

  test('utility pages are left out of the page sitemap', async ({ request, loginPage, registerPage, passwordRecoveryPage, accountPage, profilePage, directoryPage }) => {
    const response = await request.get('/wp-sitemap-posts-page-1.xml');
    expect(response.ok()).toBeTruthy();
    const sitemap = await response.text();

    expect(sitemap).toContain(directoryPage);
    for (const path of [loginPage, registerPage, passwordRecoveryPage, accountPage, profilePage]) {
      expect(sitemap, path).not.toContain(`${path}<`);
    }
  });
});
