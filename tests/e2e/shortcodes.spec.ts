import { test, expect, wpAdminLogin, wpLogout } from './fixtures';

test.describe('Profile Card Shortcode', () => {
  test('profile card renders for logged-in user', async ({
    page,
    profileCardPage,
  }) => {
    // Log in as admin so the profile card has a user to display
    await wpAdminLogin(page);

    await page.goto(profileCardPage);

    // The profile card container should be visible
    const profileCard = page.locator('#wpum-profile-card');
    await expect(profileCard).toBeVisible({ timeout: 10000 });

    // The profile image section should be present
    const profileImg = page.locator('.wpum-profile-img');
    await expect(profileImg).toBeVisible({ timeout: 5000 });
  });

  test('profile card shows user display name', async ({
    page,
    profileCardPage,
  }) => {
    // Log in as admin
    await wpAdminLogin(page);

    await page.goto(profileCardPage);

    // The card name element should be visible and contain text
    const cardName = page.locator('.wpum-card-name');
    await expect(cardName).toBeVisible({ timeout: 10000 });
    await expect(cardName).not.toBeEmpty();
  });
});

test.describe('Recently Registered Shortcode', () => {
  test('recently registered users list renders', async ({
    page,
    recentUsersPage,
  }) => {
    await page.goto(recentUsersPage);

    // The recently registered container should be present
    const container = page.locator('#wpum-recent-users');
    await expect(container).toBeVisible({ timeout: 10000 });
  });

  test('shows at least one user', async ({ page, recentUsersPage }) => {
    await page.goto(recentUsersPage);

    // The users list should be present with at least one item
    const usersList = page.locator('.wpum-users-list');
    await expect(usersList).toBeVisible({ timeout: 10000 });

    const userItems = usersList.locator('li');
    const count = await userItems.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });
});

test.describe('Login Link Shortcode', () => {
  test('login link shows for logged-out users', async ({
    page,
    loginLinkPage,
  }) => {
    // Make sure we're logged out
    await page.context().clearCookies();
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();

    await page.goto(loginLinkPage);

    // The login link should be visible
    const loginLink = page.locator('a.wpum-login-link');
    await expect(loginLink).toBeVisible({ timeout: 10000 });
    await expect(loginLink).toContainText(/login/i);
  });

  test('login link hidden for logged-in users', async ({
    page,
    loginLinkPage,
  }) => {
    // Log in
    await wpAdminLogin(page);

    await page.goto(loginLinkPage);

    // The login link should NOT be visible when logged in
    const loginLink = page.locator('a.wpum-login-link');
    const isVisible = await loginLink.isVisible({ timeout: 3000 }).catch(() => false);
    expect(isVisible).toBeFalsy();
  });
});

test.describe('Logout Link Shortcode', () => {
  test('logout link shows for logged-in users', async ({
    page,
    logoutLinkPage,
  }) => {
    // Log in
    await wpAdminLogin(page);

    await page.goto(logoutLinkPage);

    // The logout link should be visible
    const logoutLink = page.locator('a[href*="action=logout"]');
    await expect(logoutLink).toBeVisible({ timeout: 10000 });
    await expect(logoutLink).toContainText(/logout/i);
  });

  test('logout link hidden for logged-out users', async ({
    page,
    logoutLinkPage,
  }) => {
    // Make sure we're logged out
    await page.context().clearCookies();
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();

    await page.goto(logoutLinkPage);

    // The logout link should NOT be visible when logged out
    // The shortcode renders an empty string for logged-out users, so no anchor tag should be in the content area
    const pageContent = await page.content();
    const logoutLink = page.locator('.entry-content a[href*="action=logout"], .post-content a[href*="action=logout"], article a[href*="action=logout"]');
    const isVisible = await logoutLink.isVisible({ timeout: 3000 }).catch(() => false);
    expect(isVisible).toBeFalsy();
  });
});

test.describe('Logout Flow', () => {
  test('logout via WPUM logout link', async ({ page, logoutLinkPage }) => {
    // Log in first
    await wpAdminLogin(page);

    // Verify we are logged in by checking wp-admin access
    await page.goto('/wp-admin/');
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    expect(page.url()).toContain('/wp-admin');

    // Visit the logout link page
    await page.goto(logoutLinkPage);

    // Click the logout link rendered by [wpum_logout]
    const logoutLink = page.locator('a[href*="action=logout"]');
    await expect(logoutLink).toBeVisible({ timeout: 10000 });
    await logoutLink.click();

    // WordPress may show a confirmation page — click through if so
    const logoutConfirm = page.locator('a[href*="action=logout"]');
    if (await logoutConfirm.isVisible({ timeout: 3000 }).catch(() => false)) {
      await logoutConfirm.click();
    }

    // Wait for navigation to complete
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Verify user is logged out — accessing wp-admin should redirect to login page
    await page.goto('/wp-admin/');
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    expect(page.url()).toContain('wp-login.php');
  });
});
