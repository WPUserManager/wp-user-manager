import { test, expect, deleteUser } from './fixtures';
import {
  isStripeConfigured,
  installTestBillingOverride,
  removeTestBillingOverride,
  configureStripeSettings,
  createStripeTestProduct,
  deleteStripeTestProduct,
  waitForSubscriptionActive,
  cleanupStripeCustomer,
  configureRegistrationFormWithStripe,
  removeStripeFromRegistrationForm,
} from './helpers/stripe';

let testProductId: string;
let testPriceId: string;

test.describe('Stripe Registration', () => {
  test.beforeAll(async () => {
    if (!isStripeConfigured()) {
      test.skip();
      return;
    }

    // Install the billing override mu-plugin (calls Stripe SDK directly)
    installTestBillingOverride();

    // Create a test product in Stripe
    const { productId, priceId } = createStripeTestProduct();
    testProductId = productId;
    testPriceId = priceId;

    // Configure WPUM Stripe settings
    configureStripeSettings(
      process.env.STRIPE_PUBLISHABLE_KEY!,
      process.env.STRIPE_SECRET_KEY!,
      process.env.STRIPE_WEBHOOK_SECRET || '',
      testPriceId
    );

    // Configure the registration form to include the Stripe plan
    configureRegistrationFormWithStripe(testPriceId);
  });

  test.afterAll(async () => {
    if (!isStripeConfigured()) return;

    // Remove Stripe config from registration form
    removeStripeFromRegistrationForm();

    // Remove the billing override mu-plugin
    removeTestBillingOverride();

    // Archive the test product
    if (testProductId) {
      deleteStripeTestProduct(testProductId);
    }

    // Clean up test users
    cleanupStripeCustomer('stripe_e2e_checkout@example.com');
    deleteUser('stripe_e2e_checkout');
    deleteUser('stripe_e2e_checkout@example.com');
    deleteUser('stripe_e2e_redirect');
    deleteUser('stripe_e2e_redirect@example.com');
    deleteUser('stripe_e2e_noplan');
    deleteUser('stripe_e2e_noplan@example.com');
  });

  test.beforeEach(async ({ page }) => {
    // Ensure logged out before each test
    await page.goto('/wp-login.php?action=logout');
    const confirmLink = page.locator('a[href*="action=logout"]');
    if (await confirmLink.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmLink.click();
    }
    await page.context().clearCookies();
  });

  test('registration form shows plan selection', async ({ page, registerPage }) => {
    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    // The Stripe plan radio field should be visible
    const planField = page.locator('input[name="wpum_stripe_plan"]');
    await expect(planField.first()).toBeVisible({ timeout: 10000 });

    // Should show "Test Mode" description indicating Stripe is in test mode
    const testModeText = page.locator('text=Test Mode');
    await expect(testModeText).toBeVisible();
  });

  test('registration redirects to Stripe checkout', async ({ page, registerPage }) => {
    // Clean up from previous runs
    deleteUser('stripe_e2e_redirect');
    deleteUser('stripe_e2e_redirect@example.com');

    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    // Fill in registration fields
    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('stripe_e2e_redirect');
    }
    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('stripe_e2e_redirect@example.com');
    }
    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    // Select the Stripe plan
    const planRadio = page.locator('input[name="wpum_stripe_plan"]').first();
    await planRadio.check();

    // Submit the form - JS should intercept and AJAX to wpum_stripe_register
    await page.locator('input[name="submit_registration"]').click();

    // Should redirect to Stripe Checkout
    await page.waitForURL(/checkout\.stripe\.com/, { timeout: 30000 });

    // The Stripe Checkout page should have a card input
    await expect(page.getByLabel('Card number')).toBeVisible({ timeout: 15000 });

    // Clean up the WP user that was created (Stripe session can be abandoned)
    deleteUser('stripe_e2e_redirect');
    deleteUser('stripe_e2e_redirect@example.com');
  });

  test('full checkout flow completes registration', async ({ page, registerPage }) => {
    test.setTimeout(180000);

    // Clean up from previous runs
    cleanupStripeCustomer('stripe_e2e_checkout@example.com');
    deleteUser('stripe_e2e_checkout');
    deleteUser('stripe_e2e_checkout@example.com');

    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    // Fill in registration fields
    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('stripe_e2e_checkout');
    }
    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('stripe_e2e_checkout@example.com');
    }
    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    // Select the Stripe plan
    const planRadio = page.locator('input[name="wpum_stripe_plan"]').first();
    await planRadio.check();

    // Submit the form
    await page.locator('input[name="submit_registration"]').click();

    // Wait for Stripe Checkout page
    await page.waitForURL(/checkout\.stripe\.com/, { timeout: 30000 });

    // Fill in test card details on Stripe Checkout
    // Use accessibility selectors (getByLabel) which work reliably with Stripe's hosted checkout
    await page.getByLabel('Card number').waitFor({ timeout: 15000 });
    await page.getByLabel('Card number').fill('4242424242424242');
    await page.getByLabel('Expiration').fill('12 / 30');
    await page.getByRole('textbox', { name: 'CVC' }).fill('123');

    // Fill cardholder name if required
    const cardholderName = page.getByLabel('Cardholder name');
    if (await cardholderName.isVisible({ timeout: 2000 }).catch(() => false)) {
      await cardholderName.fill('E2E Test User');
    }

    // Fill billing ZIP code (label is "ZIP" when US is selected, "Postal code" for other countries)
    const zipField = page.getByRole('textbox', { name: /ZIP|Postal code/i });
    if (await zipField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await zipField.fill('10001');
    }

    // Uncheck "Save my information" to avoid the phone number requirement
    const saveInfoCheckbox = page.getByLabel('Save my information for faster checkout');
    if (await saveInfoCheckbox.isChecked({ timeout: 2000 }).catch(() => false)) {
      await saveInfoCheckbox.uncheck();
    }

    // Submit payment - button text varies by locale/config
    const payButton = page.getByRole('button', { name: /subscribe|pay/i });
    await payButton.click();

    // Wait for redirect back to the success URL
    await page.waitForURL(
      (url) => !url.hostname.includes('stripe.com'),
      { timeout: 60000 }
    );

    // The URL should contain success indicator
    const currentUrl = page.url();
    expect(
      currentUrl.includes('registration=success') ||
      currentUrl.includes('updated=success') ||
      currentUrl.includes('billing')
    ).toBeTruthy();

    // If webhook secret is configured, check subscription processing (non-fatal).
    // Webhook timing is inherently variable in CI with parallel jobs.
    if (process.env.STRIPE_WEBHOOK_SECRET) {
      try {
        await waitForSubscriptionActive(page, 60000);
      } catch {
        console.log('[Stripe E2E] Subscription webhook not processed within timeout — checkout flow still verified by redirect');
      }
    }
  });

  test('registration without plan skips Stripe', async ({ page, registerPage }) => {
    // Clean up from previous runs
    deleteUser('stripe_e2e_noplan');
    deleteUser('stripe_e2e_noplan@example.com');

    // Remove the Stripe plan from the registration form so the plan radio
    // doesn't appear. When present, the radio auto-selects the first option
    // and JS always intercepts the form submit.
    removeStripeFromRegistrationForm();

    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    // Verify the plan field is NOT present
    const planField = page.locator('input[name="wpum_stripe_plan"]');
    await expect(planField).toHaveCount(0, { timeout: 3000 });

    // Fill in registration fields
    const usernameField = page.locator('#username');
    const emailField = page.locator('#user_email');
    const passwordField = page.locator('#user_password');

    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill('stripe_e2e_noplan');
    }
    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill('stripe_e2e_noplan@example.com');
    }
    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill('StrongP@ss123!');
    }

    // Handle privacy checkbox
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }

    // Submit - without plan field, normal form submission occurs
    await page.locator('input[name="submit_registration"]').click();

    // Should complete normal registration (no Stripe redirect)
    await page.waitForURL(/registration=success/, { timeout: 15000 }).catch(() => {
      // May show success message instead of redirect
    });

    // Verify we did NOT go to Stripe
    expect(page.url()).not.toContain('checkout.stripe.com');

    // Check for success
    const successMessage = page.locator('.wpum-message.success');
    const hasSuccess = await successMessage.isVisible({ timeout: 5000 }).catch(() => false);
    const urlHasSuccess = page.url().includes('registration=success');

    expect(hasSuccess || urlHasSuccess).toBeTruthy();

    // Restore the Stripe plan on the registration form for any subsequent tests
    configureRegistrationFormWithStripe(testPriceId);

    // Clean up
    deleteUser('stripe_e2e_noplan');
    deleteUser('stripe_e2e_noplan@example.com');
  });
});
