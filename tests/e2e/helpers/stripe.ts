import { type Page } from '@playwright/test';
import { execSync } from 'child_process';
import { createHash, createDecipheriv } from 'crypto';
import * as fs from 'fs';
import * as path from 'path';
import { wpCli } from '../fixtures';

/**
 * Check if Stripe test environment is configured.
 */
export function isStripeConfigured(): boolean {
  return !!process.env.STRIPE_SECRET_KEY;
}

// Encrypted billing override mu-plugin (AES-256-GCM).
// Decrypted at runtime using WPUM_BILLING_OVERRIDE_KEY env var.
// This keeps the Stripe SDK direct-call pattern out of the public repo.
const ENCRYPTED_BILLING_OVERRIDE =
  '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';

/**
 * Decrypt the billing override PHP code using WPUM_BILLING_OVERRIDE_KEY.
 * Format: base64(iv[12] + authTag[16] + ciphertext), AES-256-GCM.
 */
function decryptBillingOverride(): string {
  const key = process.env.WPUM_BILLING_OVERRIDE_KEY;
  if (!key) {
    throw new Error(
      'WPUM_BILLING_OVERRIDE_KEY env var is required for Stripe tests'
    );
  }
  const raw = Buffer.from(ENCRYPTED_BILLING_OVERRIDE, 'base64');
  const iv = raw.subarray(0, 12);
  const tag = raw.subarray(12, 28);
  const ciphertext = raw.subarray(28);
  const derivedKey = createHash('sha256').update(key).digest();
  const decipher = createDecipheriv('aes-256-gcm', derivedKey, iv);
  decipher.setAuthTag(tag);
  let decrypted = decipher.update(ciphertext);
  decrypted = Buffer.concat([decrypted, decipher.final()]);
  return decrypted.toString('utf8');
}

/**
 * Install the test billing mu-plugin into the wp-env container.
 * Decrypts the override at runtime and writes it into the container.
 */
export function installTestBillingOverride(): void {
  const php = decryptBillingOverride();
  const encoded = Buffer.from(php).toString('base64');
  wpCli(
    `eval '
      $code = base64_decode("${encoded}");
      $path = WPMU_PLUGIN_DIR . "/wpum-stripe-test-billing.php";
      file_put_contents($path, $code);
    '`
  );
}

/**
 * Remove the test billing mu-plugin from the wp-env container.
 */
export function removeTestBillingOverride(): void {
  try {
    wpCli(
      `eval '
        $path = WPMU_PLUGIN_DIR . "/wpum-stripe-test-billing.php";
        if (file_exists($path)) unlink($path);
      '`
    );
  } catch {
    // May not exist
  }
}

/**
 * Configure WPUM Stripe settings via WP-CLI.
 */
export function configureStripeSettings(
  publishableKey: string,
  secretKey: string,
  webhookSecret: string,
  priceIds: string | string[]
): void {
  wpCli(`eval 'wpum_update_option("stripe_gateway_mode", "test");'`);
  wpCli(`eval 'wpum_update_option("test_stripe_publishable_key", "${publishableKey}");'`);
  wpCli(`eval 'wpum_update_option("test_stripe_secret_key", "${secretKey}");'`);
  wpCli(`eval 'wpum_update_option("test_stripe_webhook_secret", "${webhookSecret}");'`);
  wpCli(`eval 'wpum_update_option("test_stripe_products", array(${phpStringList(priceIds)}));'`);
  // Clear the products transient so WPUM fetches fresh data from Stripe
  wpCli(`eval 'delete_transient("wpum_test_stripe_products");'`);
}

/**
 * Create a Stripe test product and price via the Stripe CLI.
 * Returns the product ID and price ID.
 */
export function createStripeTestProduct(
  name = 'WPUM E2E Test Plan',
  unitAmount = 999,
  recurring = true
): { productId: string; priceId: string } {
  const secretKey = process.env.STRIPE_SECRET_KEY!;

  const productJson = execSync(
    `stripe products create --name="${name}" --api-key="${secretKey}"`,
    { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
  );
  const product = JSON.parse(productJson);

  const interval = recurring ? ' -d "recurring[interval]=month"' : '';
  const priceJson = execSync(
    `stripe prices create -d product="${product.id}" -d unit_amount=${unitAmount} -d currency=usd${interval} --api-key="${secretKey}"`,
    { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
  );
  const price = JSON.parse(priceJson);

  return { productId: product.id, priceId: price.id };
}

/**
 * Archive a Stripe test product (Stripe doesn't allow hard deletes on products with prices).
 */
export function deleteStripeTestProduct(productId: string): void {
  try {
    const secretKey = process.env.STRIPE_SECRET_KEY!;
    execSync(
      `stripe products update "${productId}" -d active=false --api-key="${secretKey}"`,
      { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
    );
  } catch {
    // Product may not exist or already archived
  }
}

/**
 * Wait for a user's subscription to become active by polling the account billing page.
 * Stripe CLI forwards webhooks locally, but processing takes a few seconds.
 */
export async function waitForSubscriptionActive(
  page: Page,
  timeout = 60_000,
  interval = 3_000
): Promise<void> {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    try {
      await page.goto('/wpum-account/billing/', { waitUntil: 'domcontentloaded' });
      const content = await page.content();
      if (
        content.includes('Manage Billing') ||
        content.includes('Current Plan') ||
        content.includes('subscription')
      ) {
        return;
      }
    } catch {
      // Navigation may abort due to redirects; retry
    }
    await new Promise((r) => setTimeout(r, interval));
  }
  throw new Error(`Subscription did not become active within ${timeout}ms`);
}

/**
 * Clean up a Stripe test customer by email.
 * Deletes the customer from Stripe to avoid test data accumulation.
 */
export function cleanupStripeCustomer(email: string): void {
  try {
    const userId = wpCli(`user get "${email}" --field=ID`);
    if (!userId || !userId.match(/^\d+$/)) return;

    const customerId = wpCli(`user meta get ${userId} wpum_stripe_customer_id`);
    if (!customerId || !customerId.startsWith('cus_')) return;

    const secretKey = process.env.STRIPE_SECRET_KEY!;
    try {
      execSync(
        `stripe customers delete "${customerId}" --api-key="${secretKey}"`,
        { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
      );
    } catch {
      // Customer may not exist in Stripe test mode
    }
  } catch {
    // User may not exist yet
  }
}

/**
 * Configure the default WPUM registration form to include a Stripe plan.
 * Registration forms are stored in a custom DB table (not a CPT).
 */
export function configureRegistrationFormWithStripe(priceIds: string | string[]): void {
  wpCli(
    `eval '
      $forms = WPUM()->registration_forms->get_forms();
      if (empty($forms)) { WP_CLI::error("No WPUM registration forms found"); }
      $form = new WPUM_Registration_Form($forms[0]->id);
      $form->update_meta("stripe_plan_id", array(${phpStringList(priceIds)}));
    '`
  );
}

/**
 * Remove Stripe plan configuration from the default registration form.
 */
export function removeStripeFromRegistrationForm(): void {
  try {
    wpCli(
      `eval '
        $forms = WPUM()->registration_forms->get_forms();
        if (empty($forms)) return;
        $form = new WPUM_Registration_Form($forms[0]->id);
        $form->update_meta("stripe_plan_id", "");
      '`
    );
  } catch {
    // Form may not exist
  }
}

/**
 * A PHP array body of double-quoted strings, for wp eval.
 */
function phpStringList(values: string | string[]): string {
  return (Array.isArray(values) ? values : [values]).map((v) => `"${v}"`).join(', ');
}

/**
 * Copy one of the helper mu-plugins in tests/e2e/helpers/mu-plugins into the
 * test site. tests/mu-plugins is mapped to wp-content/mu-plugins by .wp-env.json.
 */
export function installE2eMuPlugin(file: string): void {
  const root = process.env.WPUM_PLUGIN_DIR || process.cwd();
  const target = path.join(root, 'tests', 'mu-plugins');
  fs.mkdirSync(target, { recursive: true });
  fs.copyFileSync(path.join(__dirname, 'mu-plugins', file), path.join(target, file));
}

export function removeE2eMuPlugin(file: string): void {
  const root = process.env.WPUM_PLUGIN_DIR || process.cwd();
  fs.rmSync(path.join(root, 'tests', 'mu-plugins', file), { force: true });
}

/**
 * A Contributor who registered on the default form for a one-time plan and
 * hasn't paid. Needs wpum-e2e-stripe.php installed.
 */
export function createUnpaidCustomer(
  login: string,
  email: string,
  password: string,
  priceId: string,
  amount: number
): void {
  const id = wpCli(
    `eval 'echo wpum_e2e_create_unpaid_customer("${login}", "${email}", "${password}", "${priceId}", ${amount});'`
  );
  if (!id.match(/^\d+$/) || id === '0') {
    throw new Error(`Could not create unpaid customer ${login}: ${id}`);
  }
}

export type PlanState = {
  exists: boolean;
  plan?: string | null;
  paid?: boolean;
  subscribed?: boolean;
  subscription_plan?: string | null;
  can_edit_posts?: boolean;
  rejected?: string;
};

/**
 * The user's stored Stripe plan and access. Needs wpum-e2e-stripe.php installed.
 */
export function getPlanState(login: string): PlanState {
  const out = wpCli(`eval 'echo wpum_e2e_plan_state("${login}");'`);
  const json = out.slice(out.indexOf('{'));
  return JSON.parse(json);
}

/**
 * Poll the user's plan state until the condition holds, for webhooks forwarded by stripe listen.
 */
export async function waitForPlanState(
  login: string,
  condition: (state: PlanState) => boolean,
  timeout = 90_000
): Promise<PlanState> {
  const start = Date.now();
  let state = getPlanState(login);
  while (!condition(state)) {
    if (Date.now() - start > timeout) {
      throw new Error(`Plan state for ${login} did not change in time: ${JSON.stringify(state)}`);
    }
    await new Promise((r) => setTimeout(r, 3_000));
    state = getPlanState(login);
  }
  return state;
}

/**
 * Create a Checkout Session for any price straight from the Stripe API, as a direct call to
 * the Connect server's /checkout endpoint can, without going through the site. Returns its URL.
 */
export function createDirectCheckoutSession(priceId: string, email: string): string {
  const secretKey = process.env.STRIPE_SECRET_KEY!;
  const json = execSync(
    `stripe checkout sessions create -d mode=payment -d "line_items[0][price]=${priceId}" -d "line_items[0][quantity]=1" -d "payment_method_types[0]=card" -d success_url=http://localhost:8889/ -d customer_email="${email}" --api-key="${secretKey}"`,
    { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
  );
  return JSON.parse(json).url;
}

/**
 * Pay on a Stripe hosted Checkout page with a test card and wait to come back to the site.
 */
export async function payOnStripeCheckout(page: Page): Promise<void> {
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 30000 });

  // Accessibility selectors work reliably with Stripe's hosted checkout.
  await page.getByLabel('Card number').waitFor({ timeout: 15000 });
  await page.getByLabel('Card number').fill('4242424242424242');
  await page.getByLabel('Expiration').fill('12 / 30');
  await page.getByRole('textbox', { name: 'CVC' }).fill('123');

  const cardholderName = page.getByLabel('Cardholder name');
  if (await cardholderName.isVisible({ timeout: 2000 }).catch(() => false)) {
    await cardholderName.fill('E2E Test User');
  }

  // The label is "ZIP" when US is selected, "Postal code" for other countries.
  const zipField = page.getByRole('textbox', { name: /ZIP|Postal code/i });
  if (await zipField.isVisible({ timeout: 2000 }).catch(() => false)) {
    await zipField.fill('10001');
  }

  // Unchecking "Save my information" avoids the phone number requirement.
  const saveInfoCheckbox = page.getByLabel('Save my information for faster checkout');
  if (await saveInfoCheckbox.isChecked({ timeout: 2000 }).catch(() => false)) {
    await saveInfoCheckbox.uncheck();
  }

  await page.getByRole('button', { name: /subscribe|pay/i }).click();

  await page.waitForURL((url) => !url.hostname.includes('stripe.com'), { timeout: 60000 });
}
