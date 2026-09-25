import { test, expect, wpAdminLogin, wpCli } from './fixtures';

test.describe('WP Admin Pages', () => {
  test.beforeEach(async ({ page }) => {
    await wpAdminLogin(page);
  });

  test('user edit page renders Carbon Fields containers', async ({ page }) => {
    // user_id=1 is the logged-in admin — WP redirects to profile.php.
    // Use a different user so WP stays on user-edit.php, or fall back to profile.php.
    // First try to get testuser_login's ID; fall back to the admin profile page.
    let userId = '';
    try {
      userId = wpCli('user get testuser_login --field=ID').trim();
    } catch {
      // ignore
    }

    if (userId && userId.match(/^\d+$/)) {
      await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    } else {
      // Fall back to admin's own profile page (profile.php shows same CF containers)
      await page.goto('/wp-admin/profile.php');
    }
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // The page should load without fatal errors — heading is "Edit User" or "Profile"
    const heading = page.locator('h1');
    await expect(heading).toContainText(/Edit User|Profile/i, { timeout: 5000 });

    // Carbon Fields containers for Avatar and Cover should render
    const avatarContainer = page.locator(
      '.carbon-container:has(h2:text-is("Avatar")), ' +
      '.postbox:has(.hndle:text-is("Avatar")), ' +
      'h2:text-is("Avatar"), ' +
      '.cf-container:has(h2:text-is("Avatar"))'
    );
    const coverContainer = page.locator(
      '.carbon-container:has(h2:text-is("Cover")), ' +
      '.postbox:has(.hndle:text-is("Cover")), ' +
      'h2:text-is("Cover"), ' +
      '.cf-container:has(h2:text-is("Cover"))'
    );

    // At least one of the Carbon Fields containers should be visible
    const hasAvatar = await avatarContainer.first().isVisible({ timeout: 5000 }).catch(() => false);
    const hasCover = await coverContainer.first().isVisible({ timeout: 5000 }).catch(() => false);
    expect(hasAvatar || hasCover).toBeTruthy();
  });

  test('WPUM settings page loads', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=wpum-settings');
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // The page should not be a WP error / white screen
    const body = page.locator('body');
    await expect(body).toBeVisible();

    // The settings page should have content — look for the WPUM settings wrapper
    const settingsContent = page.locator(
      '.wpum-settings-wrap, #wpum-settings, .wrap h1, .wrap h2'
    );
    await expect(settingsContent.first()).toBeVisible({ timeout: 5000 });
  });

  test('directory editor loads Carbon Fields meta boxes', async ({ page }) => {
    // Get a wpum_directory post ID via WP-CLI
    let directoryId = '';
    try {
      directoryId = wpCli(
        'post list --post_type=wpum_directory --post_status=publish --field=ID --posts_per_page=1'
      );
    } catch {
      // ignore
    }

    if (!directoryId || !directoryId.match(/^\d+$/)) {
      test.skip();
      return;
    }

    await page.goto(`/wp-admin/post.php?post=${directoryId.trim()}&action=edit`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // The edit page should load
    const heading = page.locator('h1');
    await expect(heading).toBeVisible({ timeout: 5000 });

    // Carbon Fields meta boxes should render on the directory edit page
    const metaBoxes = page.locator(
      '.postbox .cf-container, ' +
      '.postbox .carbon-container, ' +
      '.postbox .cf-field, ' +
      '#poststuff .postbox'
    );
    const metaBoxCount = await metaBoxes.count();
    expect(metaBoxCount).toBeGreaterThanOrEqual(1);
  });

  test('WPUM licenses page loads', async ({ page }) => {
    // Licenses page lives under Settings → WPUM Licenses and only exists
    // when premium addons are installed and register it.
    await page.goto('/wp-admin/options-general.php?page=wpum-licenses');
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // When no addons are installed the page slug is never registered.
    // WP still serves the URL but the page body has no .wrap container
    // and may show "You do not have sufficient permissions" or be empty.
    const wrap = page.locator('.wrap');
    const hasWrap = await wrap.isVisible({ timeout: 3000 }).catch(() => false);
    if (!hasWrap) {
      test.skip();
      return;
    }

    // If the page rendered, verify it isn't a fatal error
    const bodyText = await page.locator('body').textContent();
    expect(bodyText?.toLowerCase()).not.toContain('fatal error');
  });

  test('WPUM emails page loads', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=wpum-emails');
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    const body = page.locator('body');
    await expect(body).toBeVisible();

    // The emails page should render with email type listings
    const content = page.locator(
      '.wpum-settings-wrap, .wrap h1, .wrap h2, #wpum-emails, table'
    );
    await expect(content.first()).toBeVisible({ timeout: 5000 });
  });

  test('no JS console errors on admin pages', async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    // Visit key admin pages
    const adminPages = [
      '/wp-admin/',
      '/wp-admin/admin.php?page=wpum-settings',
      '/wp-admin/profile.php',
    ];

    for (const adminPage of adminPages) {
      consoleErrors.length = 0;
      await page.goto(adminPage);
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

      // Filter out known benign errors (e.g., favicon 404, third-party scripts)
      const realErrors = consoleErrors.filter(
        (e) =>
          !e.includes('favicon') &&
          !e.includes('Failed to load resource') &&
          !e.includes('net::ERR_')
      );

      expect(
        realErrors,
        `JS console errors on ${adminPage}: ${realErrors.join('; ')}`
      ).toHaveLength(0);
    }
  });
});
