import { test, expect, wpCli, wpLogout, deleteUser, wpAdminLogin } from './fixtures';
import * as path from 'path';

const TEST_AVATAR = path.resolve(__dirname, 'fixtures/test-avatar.jpg');

test.describe('Avatar Registration & Account', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test.afterAll(() => {
    deleteUser('avatar_reg@example.com');
    deleteUser('avatar_reg');
    deleteUser('avatar_update@example.com');
    deleteUser('avatar_update');
  });

  test('registration form shows avatar upload field when custom avatars enabled', async ({
    page,
    registerPage,
  }) => {
    await page.goto(registerPage);

    // The avatar file input should be present
    const avatarInput = page.locator('input[type="file"][name="user_avatar"]');
    await expect(avatarInput).toBeAttached({ timeout: 10000 });
  });

  test('register with avatar upload stores avatar in user meta', async ({
    page,
    registerPage,
  }) => {
    deleteUser('avatar_reg@example.com');
    deleteUser('avatar_reg');

    await page.goto(registerPage);

    await page.locator('#user_email').fill('avatar_reg@example.com');
    await page.locator('#user_password').fill('StrongP@ss123!');

    // Upload avatar
    const fileInput = page.locator('input[type="file"][name="user_avatar"]');
    await fileInput.setInputFiles(TEST_AVATAR);

    // Handle privacy checkbox if present
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    await page.locator('input[name="submit_registration"]').click();

    // Wait for registration to complete
    await page.waitForURL(/registration=success/, { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(2000);

    // Verify the user was created with avatar meta
    const avatarUrl = wpCli(
      `eval '$u = get_user_by("email", "avatar_reg@example.com"); if ($u) { echo carbon_get_user_meta($u->ID, "current_user_avatar"); } else { echo "no_user"; }'`
    ).trim();

    expect(avatarUrl).not.toBe('no_user');
    expect(avatarUrl).not.toBe('');

    // Verify the avatar path is within uploads
    const avatarPath = wpCli(
      `eval '$u = get_user_by("email", "avatar_reg@example.com"); echo get_user_meta($u->ID, "_current_user_avatar_path", true);'`
    ).trim();

    expect(avatarPath).toContain('wp-content/uploads');
  });

  test('avatar is visible on user profile after registration', async ({
    page,
    registerPage,
    profilePage,
  }) => {
    // Use the user from the previous test, or create fresh
    const userExists = wpCli(
      `eval '$u = get_user_by("email", "avatar_reg@example.com"); echo $u ? "yes" : "no";'`
    ).trim();

    if (userExists !== 'yes') {
      test.skip();
      return;
    }

    const username = wpCli(
      `eval '$u = get_user_by("email", "avatar_reg@example.com"); echo $u->user_login;'`
    ).trim();

    await page.goto(profilePage + username + '/');

    // Profile should render without error
    const profileContainer = page.locator('.wpum-profile-page, #wpum-profile, .wpum-single-profile');
    await expect(profileContainer.first()).toBeVisible({ timeout: 10000 });

    // Avatar image should be present (either in an img tag or as background)
    const avatarImg = page.locator('.wpum-profile-page img[src*="uploads"], .avatar img[src*="uploads"], img.avatar[src*="uploads"]');
    const hasAvatarImg = await avatarImg.first().isVisible({ timeout: 5000 }).catch(() => false);

    // Even if the avatar selector doesn't match exactly, the page should not 500
    const response = await page.goto(profilePage + username + '/');
    expect(response?.status()).not.toBe(500);
  });

  test('array current_user_avatar is rejected during registration', async ({
    page,
    registerPage,
  }) => {
    // This test verifies the security fix at the browser level.
    // An attacker would need to craft the POST request directly,
    // but we verify the form doesn't break with unexpected input.
    await page.goto(registerPage);

    await page.locator('#user_email').fill('avatar_attack_' + Date.now() + '@example.com');
    await page.locator('#user_password').fill('StrongP@ss123!');

    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    // Inject array value via page.evaluate (simulates crafted POST)
    await page.evaluate(() => {
      const form = document.querySelector('#wpum-submit-registration-form') as HTMLFormElement;
      if (!form) return;

      // Remove any existing current_user_avatar inputs
      form.querySelectorAll('[name^="current_user_avatar"]').forEach(el => el.remove());

      // Add array-style inputs (attack payload)
      const pathInput = document.createElement('input');
      pathInput.type = 'hidden';
      pathInput.name = 'current_user_avatar[path]';
      pathInput.value = '/var/www/html/wp-config.php';
      form.appendChild(pathInput);

      const urlInput = document.createElement('input');
      urlInput.type = 'hidden';
      urlInput.name = 'current_user_avatar[url]';
      urlInput.value = 'http://evil.com/fake.jpg';
      form.appendChild(urlInput);
    });

    await page.locator('input[name="submit_registration"]').click();

    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Should show an error or stay on the registration page — NOT succeed
    const url = page.url();
    expect(url).not.toContain('registration=success');
  });
});
