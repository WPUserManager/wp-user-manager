import { test, expect, wpCli, wpAdminLogin } from './fixtures';

/**
 * WPUM's utility pages are noindexed and left out of the sitemap.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/514
 */
// Every robots meta tag on the page, joined, so a second tag from an SEO plugin isn't missed.
const robotsMeta = (html: string): string =>
  [...html.matchAll(/<meta name=['"]robots['"] content=['"]([^'"]*)['"]/g)].map((match) => match[1]).join(' | ');

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

/**
 * Yoast SEO and Rank Math replace the core robots output and sitemap, so check
 * the final output with each one active.
 */
const seoPlugins = [
  { slug: 'wordpress-seo', name: 'Yoast SEO', marker: 'optimized with the Yoast SEO plugin', setup: '' },
  // Turn the sitemap module on rather than relying on Rank Math's install defaults.
  {
    slug: 'seo-by-rank-math',
    name: 'Rank Math',
    marker: 'Search Engine Optimization by Rank Math',
    setup: `eval 'update_option( "rank_math_modules", array_values( array_unique( array_merge( (array) get_option( "rank_math_modules", array() ), array( "sitemap" ) ) ) ) );'`,
  },
];

for (const plugin of seoPlugins) {
  test.describe.serial(`Utility pages are noindexed with ${plugin.name} active`, () => {
    test.beforeAll(() => {
      console.log(`[WPUM E2E] ${wpCli(`plugin install ${plugin.slug} --activate`, 120000)}`);
      if (plugin.setup) {
        wpCli(plugin.setup);
      }
      wpCli('rewrite flush');
    });

    test.afterAll(() => {
      wpCli(`plugin uninstall ${plugin.slug} --deactivate`, 60000);
      wpCli('rewrite flush');
    });

    test('plugin is active', () => {
      expect(wpCli('plugin list --status=active --field=name').split('\n').map((name) => name.trim())).toContain(plugin.slug);
    });

    test('robots meta', async ({ request, loginPage, registerPage, passwordRecoveryPage, profilePage, directoryPage, profileCardPage }) => {
      for (const path of [loginPage, registerPage, passwordRecoveryPage, profilePage]) {
        const html = await (await request.get(path)).text();
        // The plugin built the head, so its robots output is what's checked.
        expect(html, path).toContain(plugin.marker);
        expect(robotsMeta(html), path).toContain('noindex');
      }

      for (const path of [directoryPage, profileCardPage]) {
        const html = await (await request.get(path)).text();
        expect(html, path).toContain(plugin.marker);
        const robots = robotsMeta(html);
        expect(robots, path).not.toBe('');
        expect(robots, path).not.toContain('noindex');
      }
    });

    test('page sitemap', async ({ request, loginPage, registerPage, passwordRecoveryPage, accountPage, profilePage, directoryPage }) => {
      const response = await request.get('/page-sitemap.xml');
      const sitemap = await response.text();
      expect(response.status(), sitemap.slice(0, 500)).toBe(200);

      expect(sitemap).toContain(directoryPage);
      for (const path of [loginPage, registerPage, passwordRecoveryPage, accountPage, profilePage]) {
        expect(sitemap, path).not.toContain(`${path}<`);
      }
    });
  });
}
