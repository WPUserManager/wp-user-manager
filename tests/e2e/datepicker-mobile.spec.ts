import { devices } from '@playwright/test';
import { test, expect, wpAdminLogin, wpCli } from './fixtures';

/**
 * Issue #202 — the datepicker did not show on mobile.
 *
 * Flatpickr replaces itself with a native `input[type=date]` on touch devices
 * unless `disableMobile` is set, so tapping a WPUM datepicker field on a phone
 * never opened the flatpickr calendar. These tests run the account page under
 * mobile emulation and assert the calendar is used there as it is on desktop.
 */

const FIELD_NAME = 'E2E Datepicker Field';

/**
 * Pixel 5 emulation: viewport, touch and the Android user agent flatpickr
 * sniffs for. `defaultBrowserType` is dropped because Playwright does not allow
 * it inside a describe group.
 */
const { defaultBrowserType, ...mobileDevice } = devices['Pixel 5'];

/**
 * Create a datepicker field in the primary field group so it renders on the
 * account form. Returns the field ID.
 */
function createDatepickerField(): string {
  const php = [
    '$groups = ( new WPUM_DB_Fields_Groups() )->get_groups( array( "primary" => true ) );',
    '$gid = $groups[0]->get_ID();',
    `$id = ( new WPUM_DB_Fields() )->insert( array( "group_id" => $gid, "type" => "datepicker", "name" => "${FIELD_NAME}", "field_order" => 99 ) );`,
    '$field = new WPUM_Field( $id );',
    '$field->add_meta( "user_meta_key", "wpum_e2e_datepicker" );',
    '$field->add_meta( "editing", "public" );',
    '$field->add_meta( "visibility", "public" );',
    'echo "FIELD:" . $id;',
  ].join( ' ' );

  const output = wpCli(`eval '${php}'`);
  const match = output.match(/FIELD:(\d+)/);

  if (!match) {
    throw new Error(`Could not create the datepicker field: ${output}`);
  }

  return match[1];
}

/**
 * Remove the datepicker field created for these tests.
 */
function deleteDatepickerField(fieldId: string): void {
  try {
    wpCli(`eval '( new WPUM_DB_Fields() )->delete( ${fieldId} );'`);
  } catch {
    // Nothing to clean up.
  }
}

let fieldId = '';

test.beforeAll(() => {
  fieldId = createDatepickerField();
});

test.afterAll(() => {
  if (fieldId) {
    deleteDatepickerField(fieldId);
  }
});

/**
 * The visible input flatpickr leaves behind is its alt input, which carries the
 * original classes plus flatpickr's own `form-control input`.
 */
const ALT_INPUT = 'input.wpum-datepicker.form-control';

/**
 * The original input, kept as a hidden field holding the Y-m-d value that is
 * submitted to WordPress.
 */
const VALUE_INPUT = 'input.wpum-datepicker.flatpickr-input';

test.describe('Datepicker on mobile', () => {
  test.use(mobileDevice);

  test.beforeEach(async ({ page }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
  });

  test('uses the flatpickr calendar rather than the native date input', async ({
    page,
    accountPage,
  }) => {
    await page.goto(accountPage);

    const altInput = page.locator(ALT_INPUT).first();
    await expect(altInput).toBeVisible({ timeout: 10000 });
    await expect(altInput).toHaveAttribute('type', 'text');

    // Flatpickr's native mobile fallback must not be in play.
    await expect(page.locator('input.flatpickr-mobile')).toHaveCount(0);
  });

  test('tapping the field opens the calendar', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    const altInput = page.locator(ALT_INPUT).first();
    await expect(altInput).toBeVisible({ timeout: 10000 });
    await altInput.tap();

    await expect(page.locator('.flatpickr-calendar.open')).toBeVisible();
  });

  test('picking a date fills the field and closes the calendar', async ({
    page,
    accountPage,
  }) => {
    await page.goto(accountPage);

    const altInput = page.locator(ALT_INPUT).first();
    await expect(altInput).toBeVisible({ timeout: 10000 });
    await altInput.tap();

    const calendar = page.locator('.flatpickr-calendar.open');
    await expect(calendar).toBeVisible();

    await calendar
      .locator('.flatpickr-day:not(.prevMonthDay):not(.nextMonthDay)')
      .first()
      .click();

    // The hidden input carries the Y-m-d value that WPUM stores.
    await expect(page.locator(VALUE_INPUT).first()).toHaveValue(
      /^\d{4}-\d{2}-\d{2}$/
    );
    // The visible input shows the date in the site's configured format
    // (altFormat), not the device locale format the native fallback used.
    const expected = await page.evaluate((selector) => {
      const input = document.querySelector(selector) as any;
      const fp = input._flatpickr;
      return fp.formatDate(
        fp.selectedDates[0],
        (window as any).wpumFrontend.dateFormat
      );
    }, VALUE_INPUT);
    expect(expected).not.toBe('');
    await expect(altInput).toHaveValue(expected);
    await expect(calendar).toBeHidden();
  });
});

test.describe('Datepicker on desktop', () => {
  test.beforeEach(async ({ page }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
  });

  test('still opens the calendar on click', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    const altInput = page.locator(ALT_INPUT).first();
    await expect(altInput).toBeVisible({ timeout: 10000 });
    await altInput.click();

    await expect(page.locator('.flatpickr-calendar.open')).toBeVisible();
    await expect(page.locator('input.flatpickr-mobile')).toHaveCount(0);
  });
});
