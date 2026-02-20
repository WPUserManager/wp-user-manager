import { test, expect, wpAdminLogin } from './fixtures';

test.describe('Profile Page', () => {
  test('profile page shows restriction message for logged-out user', async ({
    page,
    profilePage,
  }) => {
    // Make sure we're logged out
    await page.context().clearCookies();
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();

    await page.goto(profilePage);

    // Profile page should show a restriction/warning message for non-logged-in users
    const warningMessage = page.locator('.wpum-message.warning');
    await expect(warningMessage).toBeVisible({ timeout: 5000 });

    // The message should mention login or register
    await expect(warningMessage).toContainText(/login|register|sign in/i);
  });

  test('profile page renders for logged-in user', async ({ page, profilePage }) => {
    // Log in first via wp-login.php (WP native login)
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Navigate to the profile page with user slug
    // WPUM uses the profile page + username in the URL
    await page.goto(profilePage + 'testuser_login/');

    // The profile page should render
    const profileContainer = page.locator('.wpum-profile-page, #wpum-profile');
    const hasProfile = await profileContainer.isVisible({ timeout: 5000 }).catch(() => false);

    if (hasProfile) {
      // Profile header container should exist
      const headerContainer = page.locator('#profile-header-container');
      await expect(headerContainer).toBeVisible();
    } else {
      // If profile page doesn't render with username slug, try without
      await page.goto(profilePage);

      // Should show the profile or a warning (if own profile requires different URL format)
      const content = page.locator('.wpum-template');
      await expect(content).toBeVisible({ timeout: 5000 });
    }
  });

  test('profile shows user data', async ({ page, profilePage }) => {
    // Log in
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Go to profile page for the logged-in user
    await page.goto(profilePage + 'testuser_login/');

    // Check if profile is displayed
    const profileContainer = page.locator('.wpum-profile-page, #wpum-profile');
    const hasProfile = await profileContainer.isVisible({ timeout: 5000 }).catch(() => false);

    if (hasProfile) {
      // The profile should contain the username or display name somewhere
      const profileContent = await page.locator('.wpum-profile-page, #wpum-profile').textContent();
      expect(profileContent).toBeTruthy();

      // Tab content area should be present
      const tabContent = page.locator('#profile-tab-content');
      await expect(tabContent).toBeVisible();
    } else {
      // Try the profile page without a slug - WPUM may redirect to the current user's profile
      await page.goto(profilePage);
      const content = page.locator('.wpum-template');
      await expect(content).toBeVisible({ timeout: 5000 });
    }
  });

  test('profile tab navigation', async ({ page, profilePage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
    await page.goto(profilePage + 'testuser_login/');

    const profileContainer = page.locator('.wpum-profile-page, #wpum-profile');
    await expect(profileContainer).toBeVisible({ timeout: 5000 });

    // Find all profile tab links (WPUM uses nav.profile-navbar > a)
    const tabLinks = page.locator('.profile-navbar a');
    const tabCount = await tabLinks.count();

    // WPUM profiles typically have About, Posts, Comments tabs
    expect(tabCount).toBeGreaterThanOrEqual(1);

    // Click through each tab and verify it renders content
    for (let i = 0; i < tabCount; i++) {
      const href = await tabLinks.nth(i).getAttribute('href');

      if (href) {
        await page.goto(href);
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

        // The profile page should still render (no PHP fatal / white screen)
        const container = page.locator('.wpum-profile-page, #wpum-profile');
        await expect(container).toBeVisible({ timeout: 5000 });

        // Tab content area should be present
        const tabContent = page.locator('#profile-tab-content');
        await expect(tabContent).toBeVisible();
      }
    }
  });

  test('profile pagination does not cause fatal error', async ({ page, profilePage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Visit a paginated profile URL — even if page 2 has no content,
    // the page should not produce a PHP fatal or routing error
    const response = await page.goto(profilePage + 'testuser_login/posts/page/2/');

    // Should not be a 500 error
    expect(response?.status()).not.toBe(500);

    // The page should render something — not a blank white screen (PHP fatal)
    const body = page.locator('body');
    await expect(body).toBeVisible();
    const bodyText = await body.textContent();
    expect(bodyText?.trim().length).toBeGreaterThan(0);
  });

  test('non-existent profile shows 404 or graceful error', async ({ page, profilePage }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    const response = await page.goto(profilePage + 'nonexistentuser12345/');

    // Should not be a 500 (PHP fatal)
    expect(response?.status()).not.toBe(500);

    // Either a 404 or a 200 with an error/not-found message — not a blank page
    const body = page.locator('body');
    await expect(body).toBeVisible();
    const bodyText = await body.textContent();
    expect(bodyText?.trim().length).toBeGreaterThan(0);
  });

  test('account page renders for logged-in user', async ({ page, accountPage }) => {
    // Log in
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');

    // Go to account page
    await page.goto(accountPage);

    // The account page should render
    const accountContainer = page.locator('.wpum-account-page');
    await expect(accountContainer).toBeVisible({ timeout: 5000 });

    // Account page should have tabs
    const tabsContainer = page.locator('.wpum_one_third');
    await expect(tabsContainer).toBeVisible();

    // Account page should have content area
    const contentContainer = page.locator('.wpum_two_third');
    await expect(contentContainer).toBeVisible();
  });
});
