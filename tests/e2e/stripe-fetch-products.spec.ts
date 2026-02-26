import { test, expect, wpAdminLogin, wpCli, createUser, deleteUser } from './fixtures';
import {
  isStripeConfigured,
  installTestBillingOverride,
  removeTestBillingOverride,
  configureStripeSettings,
  createStripeTestProduct,
  deleteStripeTestProduct,
} from './helpers/stripe';

let testProductId: string;
let testPriceId: string;

test.describe('Stripe Fetch Products', () => {
  test.beforeAll(async () => {
    if (!isStripeConfigured()) {
      test.skip();
      return;
    }

    installTestBillingOverride();

    const { productId, priceId } = createStripeTestProduct();
    testProductId = productId;
    testPriceId = priceId;

    configureStripeSettings(
      process.env.STRIPE_PUBLISHABLE_KEY!,
      process.env.STRIPE_SECRET_KEY!,
      process.env.STRIPE_WEBHOOK_SECRET || '',
      testPriceId
    );
  });

  test.afterAll(async () => {
    if (!isStripeConfigured()) return;

    removeTestBillingOverride();

    if (testProductId) {
      deleteStripeTestProduct(testProductId);
    }

    deleteUser('stripe_e2e_subscriber');
    deleteUser('stripe_e2e_subscriber@example.com');
  });

  test('Fetch Stripe Products button appears on settings page', async ({ page }) => {
    await wpAdminLogin(page);
    await page.goto('/wp-admin/users.php?page=wpum-settings#/stripe');
    await page.waitForLoadState('networkidle');

    // The settings page uses wp-optionskit Vue SPA — click the Stripe tab
    const stripeTab = page.locator('a[href="#/stripe"]');
    if (await stripeTab.isVisible({ timeout: 3000 }).catch(() => false)) {
      await stripeTab.click();
      await page.waitForTimeout(1000);
    }

    // The Fetch Stripe Products button should be visible (appears in both test & live sections)
    const fetchButton = page.locator('a.button.button-secondary', { hasText: 'Fetch Stripe Products' }).first();
    await expect(fetchButton).toBeVisible({ timeout: 10000 });
  });

  test('Fetch button refreshes products and redirects back', async ({ page }) => {
    await wpAdminLogin(page);
    await page.goto('/wp-admin/users.php?page=wpum-settings#/stripe');
    await page.waitForLoadState('networkidle');

    // Navigate to Stripe tab
    const stripeTab = page.locator('a[href="#/stripe"]');
    if (await stripeTab.isVisible({ timeout: 3000 }).catch(() => false)) {
      await stripeTab.click();
      await page.waitForTimeout(1000);
    }

    // Click the Fetch button (appears in both test & live sections — use first)
    const fetchButton = page.locator('a.button.button-secondary', { hasText: 'Fetch Stripe Products' }).first();
    await fetchButton.click();

    // Should redirect back to the settings page (nonce and fetch-products params removed)
    await page.waitForURL(/page=wpum-settings/, { timeout: 15000 });
    expect(page.url()).not.toContain('fetch-products');

    // The settings page should still render correctly
    const heading = page.locator('h1');
    await expect(heading).toContainText(/Settings/i, { timeout: 5000 });
  });

  test('non-admin user cannot fetch products', async ({ page }) => {
    // Create a subscriber user
    deleteUser('stripe_e2e_subscriber');
    createUser('stripe_e2e_subscriber', 'stripe_e2e_subscriber@example.com', 'TestPass123!', 'subscriber');

    // Log in as subscriber
    await wpAdminLogin(page, 'stripe_e2e_subscriber', 'TestPass123!');

    // Try to access the fetch products URL directly
    await page.goto('/wp-admin/users.php?page=wpum-settings&fetch-products=true');

    // Subscriber should not see the settings page — WordPress redirects to admin
    // or shows "You do not have sufficient permissions"
    const url = page.url();
    const content = await page.content();
    const blocked =
      !url.includes('page=wpum-settings') ||
      content.includes('You need a higher level of permission') ||
      content.includes('Sorry, you are not allowed');

    expect(blocked).toBeTruthy();
  });
});
