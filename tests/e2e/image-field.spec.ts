import * as path from 'path';
import { test, expect, wpAdminLogin, wpCli } from './fixtures';

/**
 * Issue #419: the Image field type, powered by FilePond.
 *
 * The first group runs against core alone: FilePond renders on the account
 * form and filters files client side. Saving a custom field value on the
 * account form is done by the wpum-custom-fields addon, so the save tests
 * only run when that addon is available in the test environment.
 */

const FIELD_NAME = 'E2E Image Field';
const META_KEY = 'wpum_e2e_image';
const IMAGE = path.join(__dirname, 'fixtures', 'test-avatar.jpg');

function createImageField(): string {
  const php = [
    '$groups = ( new WPUM_DB_Fields_Groups() )->get_groups( array( "primary" => true ) );',
    '$gid = $groups[0]->get_ID();',
    `$id = ( new WPUM_DB_Fields() )->insert( array( "group_id" => $gid, "type" => "image", "name" => "${FIELD_NAME}", "field_order" => 99 ) );`,
    '$field = new WPUM_Field( $id );',
    `$field->add_meta( "user_meta_key", "${META_KEY}" );`,
    '$field->add_meta( "editing", "public" );',
    '$field->add_meta( "visibility", "public" );',
    'echo "FIELD:" . $id;',
  ].join(' ');

  const output = wpCli(`eval '${php}'`);
  const match = output.match(/FIELD:(\d+)/);

  if (!match) {
    throw new Error(`Could not create the image field: ${output}`);
  }

  return match[1];
}

function userId(): string {
  return wpCli('user get testuser_login --field=ID');
}

function storedValue(): string {
  return wpCli(`eval 'echo wp_json_encode( get_user_meta( ${userId()}, "_${META_KEY}", true ) ?: get_user_meta( ${userId()}, "${META_KEY}", true ) );'`);
}

function uploadCount(): number {
  const out = wpCli(
    `eval '$d = wp_upload_dir()["basedir"] . "/wp-user-manager-uploads"; echo "COUNT:" . count( glob( $d . "/*/*/test-avatar*" ) ?: array() );'`
  );
  const match = out.match(/COUNT:(\d+)/);
  return match ? parseInt(match[1], 10) : -1;
}

function clearStoredValue(): void {
  const id = userId();
  wpCli(`eval 'delete_user_meta( ${id}, "_${META_KEY}" ); delete_user_meta( ${id}, "${META_KEY}" ); delete_user_meta( ${id}, "${META_KEY}_path" );'`);
}

let fieldId = '';
let customFieldsAddon = false;

test.beforeAll(() => {
  fieldId = createImageField();

  // Only trust the active plugin list: a failed activate prints "No plugins activated".
  try {
    wpCli('plugin activate wpum-custom-fields');
  } catch {
    // Not installed here: the save tests are skipped.
  }
  customFieldsAddon = wpCli('plugin list --status=active --field=name').split(/\s+/).includes('wpum-custom-fields');
});

test.afterAll(() => {
  if (fieldId) {
    try {
      wpCli(`eval '( new WPUM_DB_Fields() )->delete( ${fieldId} );'`);
    } catch {
      // Nothing to clean up.
    }
  }
  try {
    clearStoredValue();
  } catch {
    // Nothing to clean up.
  }
});

test.describe('Image field', () => {
  test.beforeEach(async ({ page }) => {
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
  });

  test('renders the FilePond uploader on the account form', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    const pond = page.locator('.filepond--root').first();
    await expect(pond).toBeVisible({ timeout: 10000 });
    await expect(page.locator('.filepond--credits')).toHaveCount(0);
  });

  test('rejects a non-image file client side', async ({ page, accountPage }) => {
    await page.goto(accountPage);
    await expect(page.locator('.filepond--root').first()).toBeVisible({ timeout: 10000 });

    await page.locator('.filepond--browser').first().setInputFiles({
      name: 'document.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('%PDF-1.4\n%%EOF'),
    });

    await expect(page.locator('.filepond--file-status-main').first()).toContainText(/invalid|type/i, { timeout: 5000 });
  });

  test('previews a chosen image before submit', async ({ page, accountPage }) => {
    await page.goto(accountPage);
    await expect(page.locator('.filepond--root').first()).toBeVisible({ timeout: 10000 });

    await page.locator('.filepond--browser').first().setInputFiles(IMAGE);

    await expect(page.locator('.filepond--image-preview').first()).toBeAttached({ timeout: 10000 });
  });

  test.describe('saving (needs wpum-custom-fields)', () => {
    test.beforeEach(() => {
      test.skip(!customFieldsAddon, 'wpum-custom-fields is not available in this environment');
      clearStoredValue();
    });

    test('uploads an image, keeps it on a resave without duplicating it, and can remove it', async ({ page, accountPage }) => {
      const before = uploadCount();

      await page.goto(accountPage);
      await expect(page.locator('.filepond--root').first()).toBeVisible({ timeout: 10000 });
      await page.locator('.filepond--browser').first().setInputFiles(IMAGE);
      await expect(page.locator('.filepond--item').first()).toBeVisible();

      await page.locator('input[name="submit_account"]').click();
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

      await expect(page.locator('.wpum-message.success')).toBeVisible({ timeout: 10000 });
      expect(uploadCount()).toBe(before + 1);
      expect(storedValue()).toContain('test-avatar');

      // The saved image is shown again in the uploader.
      await expect(page.locator(`input[name="current_${META_KEY}"]`)).toHaveValue(/test-avatar/);
      await expect(page.locator('.filepond--item').first()).toBeVisible({ timeout: 10000 });

      // Saving again without touching the field must not upload the image a second time.
      await page.waitForTimeout(1000);
      await page.locator('input[name="submit_account"]').click();
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
      await expect(page.locator('.wpum-message.success')).toBeVisible({ timeout: 10000 });
      expect(uploadCount()).toBe(before + 1);
      expect(storedValue()).toContain('test-avatar');

      // Removing it in the uploader clears the value.
      await expect(page.locator('.filepond--item').first()).toBeVisible({ timeout: 10000 });
      await page.locator('.filepond--action-remove-item').first().click();
      await expect(page.locator(`input[name="current_${META_KEY}"]`)).toHaveCount(0);
      await page.locator('input[name="submit_account"]').click();
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
      await expect(page.locator('.wpum-message.success')).toBeVisible({ timeout: 10000 });
      expect(storedValue()).not.toContain('test-avatar');
    });
  });
});
