import { type Page } from '@playwright/test';
import { test, expect, deleteUser, wpumFrontendLogin } from './fixtures';
import {
  isStripeConfigured,
  installTestBillingOverride,
  removeTestBillingOverride,
  configureStripeSettings,
  createStripeTestProduct,
  deleteStripeTestProduct,
  cleanupStripeCustomer,
  configureRegistrationFormWithStripe,
  removeStripeFromRegistrationForm,
  installE2eMuPlugin,
  removeE2eMuPlugin,
  createUnpaidCustomer,
  getPlanState,
  waitForPlanState,
  createDirectCheckoutSession,
  payOnStripeCheckout,
} from './helpers/stripe';

// The registration form offers the subscription and the premium one-time plan.
// The cheap one-time plan is on the site but on no form: paying for it must not
// count towards the premium plan (Patchstack 36497).
const prices = { subscription: '', premium: '', cheap: '' };
const productIds: string[] = [];

const PREMIUM_AMOUNT = 5000;
const PASSWORD = 'StrongP@ss123!';

// Unique per run, since CI retries and the PHP versions share one Stripe account.
const runId = `${Date.now()}${Math.floor(Math.random() * 1000)}`;

test.describe('Stripe Registration', () => {
  test.skip(!isStripeConfigured(), 'Stripe secrets are not set');

  test.beforeAll(async () => {
    if (!process.env.STRIPE_WEBHOOK_SECRET) {
      throw new Error('STRIPE_WEBHOOK_SECRET is empty, so WPUM would not load Stripe. Is stripe listen running?');
    }

    // Install the billing override mu-plugin (calls Stripe SDK directly)
    installTestBillingOverride();
    installE2eMuPlugin('wpum-e2e-stripe.php');

    const subscription = createStripeTestProduct('WPUM E2E Subscription', 999, true);
    const premium = createStripeTestProduct('WPUM E2E Premium', PREMIUM_AMOUNT, false);
    const cheap = createStripeTestProduct('WPUM E2E Cheap', 100, false);
    prices.subscription = subscription.priceId;
    prices.premium = premium.priceId;
    prices.cheap = cheap.priceId;
    productIds.push(subscription.productId, premium.productId, cheap.productId);

    configureStripeSettings(
      process.env.STRIPE_PUBLISHABLE_KEY!,
      process.env.STRIPE_SECRET_KEY!,
      process.env.STRIPE_WEBHOOK_SECRET,
      [prices.subscription, prices.premium, prices.cheap]
    );

    configureRegistrationFormWithStripe([prices.subscription, prices.premium]);
  });

  test.afterAll(async () => {
    if (!isStripeConfigured()) return;

    removeStripeFromRegistrationForm();
    removeTestBillingOverride();
    removeE2eMuPlugin('wpum-e2e-stripe.php');

    for (const productId of productIds) {
      deleteStripeTestProduct(productId);
    }

    for (const login of ['stripe_e2e_redirect', 'stripe_e2e_noplan', `stripe_e2e_sub_${runId}`, `stripe_e2e_cheap_${runId}`, `stripe_e2e_billing_${runId}`]) {
      deleteRegisteredUser(login);
    }
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

  // The registration form may have no username field, in which case the email is the login.
  function deleteRegisteredUser(login: string) {
    cleanupStripeCustomer(`${login}@example.com`);
    deleteUser(login);
    deleteUser(`${login}@example.com`);
  }

  async function fillRegistrationForm(page: Page, registerPage: string, login: string) {
    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    const usernameField = page.locator('#username');
    if (await usernameField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await usernameField.fill(login);
    }
    const emailField = page.locator('#user_email');
    if (await emailField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await emailField.fill(`${login}@example.com`);
    }
    const passwordField = page.locator('#user_password');
    if (await passwordField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await passwordField.fill(PASSWORD);
    }
    const privacyCheckbox = page.locator('#privacy');
    if (await privacyCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
      await privacyCheckbox.check();
    }
  }

  test('registration form shows only the form\'s plans', async ({ page, registerPage }) => {
    await page.goto(registerPage);
    await page.waitForLoadState('networkidle');

    await expect(page.locator(`input[name="wpum_stripe_plan"][value="${prices.subscription}"]`)).toBeVisible({ timeout: 10000 });
    await expect(page.locator(`input[name="wpum_stripe_plan"][value="${prices.premium}"]`)).toBeVisible();
    await expect(page.locator(`input[name="wpum_stripe_plan"][value="${prices.cheap}"]`)).toHaveCount(0);

    await expect(page.locator('text=Test Mode')).toBeVisible();
  });

  test('registration redirects to Stripe checkout', async ({ page, registerPage }) => {
    deleteRegisteredUser('stripe_e2e_redirect');

    await fillRegistrationForm(page, registerPage, 'stripe_e2e_redirect');
    await page.locator(`input[name="wpum_stripe_plan"][value="${prices.premium}"]`).check();
    await page.locator('input[name="submit_registration"]').click();

    await page.waitForURL(/checkout\.stripe\.com/, { timeout: 30000 });
    await expect(page.getByLabel('Card number')).toBeVisible({ timeout: 15000 });

    // Registering doesn't pay: the account is created with its plan unpaid and no role capabilities.
    const state = getPlanState('stripe_e2e_redirect@example.com');
    expect(state.plan).toBe(prices.premium);
    expect(state.paid).toBe(false);
  });

  test('subscription checkout activates the subscription straight away', async ({ page, registerPage }) => {
    test.setTimeout(180000);
    const login = `stripe_e2e_sub_${runId}`;

    await fillRegistrationForm(page, registerPage, login);
    await page.locator(`input[name="wpum_stripe_plan"][value="${prices.subscription}"]`).check();
    await page.locator('input[name="submit_registration"]').click();

    await payOnStripeCheckout(page);

    const currentUrl = page.url();
    expect(
      currentUrl.includes('registration=success') ||
      currentUrl.includes('updated=success') ||
      currentUrl.includes('billing')
    ).toBeTruthy();

    // The subscription row used to be stored with a zero end date, which reads as ended.
    const state = await waitForPlanState(`${login}@example.com`, (s) => !!s.subscription_plan);
    expect(state.subscription_plan).toBe(prices.subscription);
    expect(state.subscribed).toBe(true);
  });

  test('paying for a plan the form does not offer leaves the account locked', async ({ page }) => {
    test.setTimeout(180000);
    const login = `stripe_e2e_cheap_${runId}`;
    createUnpaidCustomer(login, `${login}@example.com`, PASSWORD, prices.premium, PREMIUM_AMOUNT);

    // An attacker can create a Checkout Session for any price on the account without the
    // site, through the Connect server's public /checkout endpoint.
    await page.goto(createDirectCheckoutSession(prices.cheap, `${login}@example.com`));
    await payOnStripeCheckout(page);

    const state = await waitForPlanState(login, (s) => !!s.rejected);
    expect(state.rejected).toBe(prices.cheap);
    expect(state.plan).toBe(prices.premium);
    expect(state.paid).toBe(false);
    expect(state.can_edit_posts).toBe(false);
  });

  test('billing tab offers only the customer\'s plans, and paying for one unlocks the account', async ({ page, loginPage }) => {
    test.setTimeout(180000);
    const login = `stripe_e2e_billing_${runId}`;
    createUnpaidCustomer(login, `${login}@example.com`, PASSWORD, prices.premium, PREMIUM_AMOUNT);
    expect(getPlanState(login).can_edit_posts).toBe(false);

    await wpumFrontendLogin(page, loginPage, login, PASSWORD);
    await page.goto('/wpum-account/billing/');

    const premiumButton = page.locator(`.wpum-stripe-checkout[data-plan-id="${prices.premium}"]`);
    await expect(premiumButton).toBeVisible({ timeout: 15000 });
    await expect(page.locator(`.wpum-stripe-checkout[data-plan-id="${prices.cheap}"]`)).toHaveCount(0);

    await premiumButton.click();
    await payOnStripeCheckout(page);

    const state = await waitForPlanState(login, (s) => !!s.paid);
    expect(state.plan).toBe(prices.premium);
    expect(state.can_edit_posts).toBe(true);
  });

  test('registration without plan skips Stripe', async ({ page, registerPage }) => {
    deleteRegisteredUser('stripe_e2e_noplan');

    // Remove the Stripe plans from the registration form so the plan radio
    // doesn't appear. When present, the radio auto-selects the first option
    // and JS always intercepts the form submit.
    removeStripeFromRegistrationForm();

    try {
      await page.goto(registerPage);
      await page.waitForLoadState('networkidle');
      await expect(page.locator('input[name="wpum_stripe_plan"]')).toHaveCount(0, { timeout: 3000 });

      await fillRegistrationForm(page, registerPage, 'stripe_e2e_noplan');
      await page.locator('input[name="submit_registration"]').click();

      await page.waitForURL(/registration=success/, { timeout: 15000 }).catch(() => {
        // May show success message instead of redirect
      });

      expect(page.url()).not.toContain('checkout.stripe.com');

      const hasSuccess = await page.locator('.wpum-message.success').isVisible({ timeout: 5000 }).catch(() => false);
      expect(hasSuccess || page.url().includes('registration=success')).toBeTruthy();
    } finally {
      configureRegistrationFormWithStripe([prices.subscription, prices.premium]);
    }
  });
});
