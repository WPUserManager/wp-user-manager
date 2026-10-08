import { test, expect, wpCli, wpAdminLogin } from './fixtures';
import { installE2eMuPlugin, removeE2eMuPlugin } from './helpers/stripe';

/**
 * The Stripe Connect return must accept the state from the Connect button the
 * admin clicked. It once failed with "This Stripe connection request has
 * expired" because the button URL lost its base64 padding, and because any
 * REST request while the admin was at Stripe issued new states over it.
 *
 * The Connect server is replaced by a closed local port, so a return that
 * passes the state check stops at fetching the credentials.
 */
test.describe('Stripe Connect return', () => {
  test.beforeAll(() => {
    installE2eMuPlugin('wpum-e2e-connect.php');
  });

  test.afterAll(() => {
    removeE2eMuPlugin('wpum-e2e-connect.php');
  });

  function returnUrl(connectUrl: string): string {
    // The Connect server hands the state back URL-decoded, so a '+' arrives as a space.
    const state = new URL(connectUrl).searchParams.get('state') || '';
    return `/wp-admin/users.php?page=wpum-settings&action=stripe_connect&state=${encodeURIComponent(state)}`;
  }

  test('accepts the state from the Connect button, once', async ({ page }) => {
    await wpAdminLogin(page);

    // Several site URLs, so the base64 state ends with each amount of padding.
    for (const suffix of ['', 'a', 'ab']) {
      const output = wpCli(`--user=admin eval 'add_filter( "wpum_stripe_connect_return_url", function ( $url ) { return $url . "${suffix}"; } ); echo wpum_e2e_connect_url();'`);
      const connectUrl = (output.match(/https?:\/\/\S*state=\S+/) || [''])[0];
      expect(connectUrl, output).toContain('state=');

      // Settings loads and REST requests rebuild the Connect buttons while the admin is at Stripe.
      wpCli(`--user=admin eval 'wpum_e2e_rebuild_connect_buttons( 20 );'`);

      await page.goto(returnUrl(connectUrl));
      await expect(page.locator('body')).toContainText('error getting your Stripe credentials');
      await expect(page.locator('body')).not.toContainText('expired');

      // The state is single use.
      await page.goto(returnUrl(connectUrl));
      await expect(page.locator('body')).toContainText('has expired or was not started from this site');
    }
  });
});
