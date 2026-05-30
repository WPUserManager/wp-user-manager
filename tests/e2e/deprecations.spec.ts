import { test, expect, wpAdminLogin, wpCli } from './fixtures';
import { execSync } from 'child_process';

/**
 * Read debug.log from the wp-env container.
 */
function readDebugLog(): string {
  try {
    return execSync(
      'npx @wordpress/env run tests-cli bash -c "cat /var/www/html/wp-content/debug.log 2>/dev/null || echo \'\'"',
      {
        cwd: process.env.WPUM_PLUGIN_DIR || process.cwd(),
        encoding: 'utf-8',
        timeout: 15000,
        stdio: ['pipe', 'pipe', 'pipe'],
      }
    ).trim();
  } catch {
    return '';
  }
}

/**
 * Clear debug.log in the wp-env container.
 */
function clearDebugLog(): void {
  try {
    execSync(
      'npx @wordpress/env run tests-cli bash -c "truncate -s 0 /var/www/html/wp-content/debug.log 2>/dev/null || true"',
      {
        cwd: process.env.WPUM_PLUGIN_DIR || process.cwd(),
        encoding: 'utf-8',
        timeout: 15000,
        stdio: ['pipe', 'pipe', 'pipe'],
      }
    );
  } catch {
    // ignore
  }
}

test.describe('Deprecation and Fatal Error Checks', () => {
  test.beforeAll(() => {
    clearDebugLog();
  });

  test('no Cortex or Carbon Fields deprecations after exercising key pages', async ({
    page,
    profilePage,
    accountPage,
  }) => {
    // Clear log before this test
    clearDebugLog();

    // Exercise frontend pages
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
    await page.goto(profilePage + 'testuser_login/');
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    await page.goto(accountPage);
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // Exercise admin page (user edit with Carbon Fields)
    await wpAdminLogin(page);
    await page.goto('/wp-admin/user-edit.php?user_id=1');
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // Read and check debug.log
    const log = readDebugLog();
    const lines = log.split('\n');

    const cortexDeprecations = lines.filter(
      (l) => /Deprecated/i.test(l) && /Brain\\?Cortex|WPUM\\?Brain\\?Cortex/i.test(l)
    );
    const carbonDeprecations = lines.filter(
      (l) => /Deprecated/i.test(l) && /Carbon_Fields|WPUM\\?Carbon_Fields/i.test(l)
    );
    const fatalErrors = lines.filter((l) => /Fatal error/i.test(l));

    expect(
      cortexDeprecations,
      `Cortex deprecation warnings found:\n${cortexDeprecations.join('\n')}`
    ).toHaveLength(0);

    expect(
      carbonDeprecations,
      `Carbon Fields deprecation warnings found:\n${carbonDeprecations.join('\n')}`
    ).toHaveLength(0);

    expect(
      fatalErrors,
      `Fatal errors found:\n${fatalErrors.join('\n')}`
    ).toHaveLength(0);
  });
});
