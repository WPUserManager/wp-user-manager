import { test, expect, wpCli, wpAdminLogin } from './fixtures';
import { installE2eMuPlugin, removeE2eMuPlugin } from './helpers/stripe';

/**
 * The newsletter opt-in on WP User Manager's admin screens. The mu-plugin answers
 * the subscribe request as Sendy does, so nothing reaches the real list.
 */
test.describe('Newsletter opt-in', () => {
  test.beforeAll(() => {
    installE2eMuPlugin('wpum-e2e-newsletter.php');
  });

  test.afterAll(() => {
    removeE2eMuPlugin('wpum-e2e-newsletter.php');
    wpCli('option delete wpum_newsletter_optin wpum_e2e_newsletter_request');
  });

  test.beforeEach(async ({ page }) => {
    wpCli('option delete wpum_newsletter_optin wpum_e2e_newsletter_request');
    wpCli(`option update wpum_activation_date ${Math.floor(Date.now() / 1000) - 30 * 86400}`);
    await wpAdminLogin(page);
  });

  test('subscribes from the notice without leaving the page', async ({ page }) => {
    const adminEmail = wpCli('user get admin --field=user_email');

    await page.goto('/wp-admin/users.php?page=wpum-settings');
    const notice = page.locator('#wpum-newsletter-notice');
    await expect(notice).toBeVisible();
    await expect(notice.locator('input[name="email"]')).toHaveValue(adminEmail);

    await notice.locator('button[type="submit"]').click();
    await expect(notice.locator('.wpum-newsletter-optin__message')).toContainText('Check your inbox');
    await expect(page).toHaveURL(/page=wpum-settings/);

    expect(wpCli('option get wpum_newsletter_optin')).toBe('subscribed');
    const sent = JSON.parse(wpCli('option get wpum_e2e_newsletter_request --format=json'));
    expect(sent.email).toBe(adminEmail);
    expect(sent.Source).toBe('plugin-notice');

    await page.reload();
    await expect(page.locator('#wpum-newsletter-notice')).toHaveCount(0);
  });

  test('"No thanks" stops the notice for good', async ({ page }) => {
    await page.goto('/wp-admin/users.php?page=wpum-settings');
    const notice = page.locator('#wpum-newsletter-notice');
    await notice.locator('.wpum-newsletter-dismiss').click();
    await expect(notice).toHaveCount(0);

    expect(wpCli('option get wpum_newsletter_optin')).toBe('dismissed');
    // Nothing was sent to the list.
    expect(wpCli('option list --search=wpum_e2e_newsletter_request --field=option_name')).toBe('');

    await page.goto('/wp-admin/users.php?page=wpum-custom-fields');
    await expect(page.locator('#wpum-newsletter-notice')).toHaveCount(0);
  });

  test('does not show outside WP User Manager screens', async ({ page }) => {
    await page.goto('/wp-admin/index.php');
    await expect(page.locator('#wpum-newsletter-notice')).toHaveCount(0);
  });

  test('Getting Started page has the form and no social embeds', async ({ page }) => {
    await page.goto('/wp-admin/index.php?page=wpum-getting-started');
    await expect(page.locator('.wpum-newsletter-panel input[name="email"]')).toBeVisible();
    await expect(page.locator('#wpum-newsletter-notice')).toHaveCount(0);
    await expect(page.locator('script[src*="facebook.net"], script[src*="twitter.com"]')).toHaveCount(0);
  });
});
