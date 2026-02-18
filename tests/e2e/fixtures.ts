import { test as base, expect, Page } from '@playwright/test';
import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Run a WP-CLI command via wp-env against the test site.
 */
export function wpCli(command: string): string {
  try {
    const result = execSync(
      `npx @wordpress/env run tests-cli wp ${command}`,
      {
        cwd: process.env.WPUM_PLUGIN_DIR || process.cwd(),
        encoding: 'utf-8',
        timeout: 30000,
        stdio: ['pipe', 'pipe', 'pipe'],
      }
    );
    return result.trim();
  } catch (error: any) {
    // Some wp-cli commands return non-zero but still succeed (e.g., when post already exists)
    if (error.stdout) {
      return error.stdout.trim();
    }
    throw error;
  }
}

/**
 * Log in to the WordPress admin dashboard.
 */
export async function wpAdminLogin(
  page: Page,
  username = 'admin',
  password = 'password'
): Promise<void> {
  // Use Playwright's API request context to log in directly via HTTP POST.
  // This is much faster and more reliable than rendering wp-login.php in the
  // browser, which can time out on slow Docker containers.
  // Cookies from the response are automatically stored in the browser context.
  const response = await page.request.post('/wp-login.php', {
    form: {
      log: username,
      pwd: password,
      'wp-submit': 'Log In',
      redirect_to: '/wp-admin/',
      testcookie: '1',
    },
  });
  await response.dispose();
}

/**
 * Log out of WordPress by visiting the logout URL.
 */
export async function wpLogout(page: Page): Promise<void> {
  // Visit wp-login.php?action=logout to initiate logout
  await page.goto('/wp-login.php?action=logout');
  // Click the confirmation link if present
  const confirmLink = page.locator('a[href*="action=logout"]');
  if (await confirmLink.isVisible({ timeout: 3000 }).catch(() => false)) {
    await confirmLink.click();
  }
  await page.waitForURL(/wp-login\.php/);
}

/**
 * Log in using the WPUM frontend login form.
 */
export async function wpumFrontendLogin(
  page: Page,
  loginPageUrl: string,
  username: string,
  password: string
): Promise<void> {
  await page.goto(loginPageUrl);
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.locator('input[name="submit_login"]').click();
}

/**
 * Create a WordPress page with a given shortcode if it does not already exist.
 * Returns the page URL slug.
 */
export function ensurePageWithShortcode(
  slug: string,
  title: string,
  shortcode: string
): string {
  // Check if the page already exists
  try {
    const existing = wpCli(
      `post list --post_type=page --name="${slug}" --post_status=publish --field=ID`
    );
    if (existing && existing.match(/^\d+$/)) {
      return slug;
    }
  } catch {
    // Page doesn't exist, create it
  }

  // Create the page
  wpCli(
    `post create --post_type=page --post_title="${title}" --post_name="${slug}" --post_status=publish --post_content='${shortcode}'`
  );

  return slug;
}

/**
 * Ensure .htaccess exists in the wp-env container for pretty permalinks.
 * wp-env containers may not have mod_rewrite .htaccess by default.
 */
export function ensureHtaccess(): void {
  const htaccess = `# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress`;

  try {
    execSync(
      `npx @wordpress/env run tests-cli bash -c 'cat > /var/www/html/.htaccess << '"'"'HTEOF'"'"'\n${htaccess}\nHTEOF'`,
      {
        cwd: process.env.WPUM_PLUGIN_DIR || process.cwd(),
        encoding: 'utf-8',
        timeout: 15000,
        stdio: ['pipe', 'pipe', 'pipe'],
      }
    );
  } catch {
    // Fall back to writing via wp eval
    wpCli(`eval 'file_put_contents(ABSPATH . ".htaccess", "# BEGIN WordPress\\n<IfModule mod_rewrite.c>\\nRewriteEngine On\\nRewriteBase /\\nRewriteRule ^index\\\\.php$ - [L]\\nRewriteCond %{REQUEST_FILENAME} !-f\\nRewriteCond %{REQUEST_FILENAME} !-d\\nRewriteRule . /index.php [L]\\n</IfModule>\\n# END WordPress");'`);
  }
}

/**
 * Activate the WPUM plugin and configure it for testing.
 */
export function activatePlugin(): void {
  wpCli('plugin activate wp-user-manager');
}

/**
 * Enable user registration in WordPress settings.
 */
export function enableRegistration(): void {
  wpCli('option update users_can_register 1');
}

/**
 * Create all the required WPUM pages and set the WPUM options to point to them.
 */
export function setupWpumPages(): void {
  const pages: { slug: string; title: string; shortcode: string; option: string }[] = [
    {
      slug: 'wpum-login',
      title: 'Login',
      shortcode: '[wpum_login_form psw_link="yes" register_link="yes"]',
      option: 'login_page',
    },
    {
      slug: 'wpum-register',
      title: 'Register',
      shortcode: '[wpum_register login_link="yes" psw_link="yes"]',
      option: 'registration_page',
    },
    {
      slug: 'wpum-account',
      title: 'Account',
      shortcode: '[wpum_account]',
      option: 'account_page',
    },
    {
      slug: 'wpum-profile',
      title: 'Profile',
      shortcode: '[wpum_profile]',
      option: 'profile_page',
    },
    {
      slug: 'wpum-password-recovery',
      title: 'Password Recovery',
      shortcode: '[wpum_password_recovery login_link="yes" register_link="yes"]',
      option: 'password_recovery_page',
    },
  ];

  for (const p of pages) {
    // Check if page already exists
    let pageId = '';
    try {
      pageId = wpCli(
        `post list --post_type=page --name="${p.slug}" --post_status=publish --field=ID`
      );
    } catch {
      // ignore
    }

    if (!pageId || !pageId.match(/^\d+$/)) {
      pageId = wpCli(
        `post create --post_type=page --post_title="${p.title}" --post_name="${p.slug}" --post_status=publish --post_content='${p.shortcode}' --porcelain`
      );
    }

    // Set the WPUM option to reference this page
    if (pageId && pageId.match(/^\d+$/)) {
      // WPUM stores page IDs as serialized arrays in options
      wpCli(
        `option update wpum_settings --format=json < /dev/null 2>/dev/null || true`
      );
      // Use eval to set the specific option via WPUM's option API
      wpCli(
        `eval 'wpum_update_option("${p.option}", array(${pageId.trim()}));'`
      );
    }
  }
}

/**
 * Create a page with content-restriction shortcodes for testing.
 */
export function setupContentRestrictionPages(): void {
  // Page that shows content only to logged-in users
  let pageId = '';
  try {
    pageId = wpCli(
      'post list --post_type=page --name="wpum-logged-in-content" --post_status=publish --field=ID'
    );
  } catch {
    // ignore
  }

  if (!pageId || !pageId.match(/^\d+$/)) {
    wpCli(
      `post create --post_type=page --post_title="Logged In Content" --post_name="wpum-logged-in-content" --post_status=publish --post_content='[wpum_restrict_logged_in]This is secret members-only content.[/wpum_restrict_logged_in]'`
    );
  }

  // Page that shows content only to logged-out users
  try {
    pageId = wpCli(
      'post list --post_type=page --name="wpum-logged-out-content" --post_status=publish --field=ID'
    );
  } catch {
    // ignore
  }

  if (!pageId || !pageId.match(/^\d+$/)) {
    wpCli(
      `post create --post_type=page --post_title="Logged Out Content" --post_name="wpum-logged-out-content" --post_status=publish --post_content='[wpum_restrict_logged_out show_message="yes"]This content is for guests only.[/wpum_restrict_logged_out]'`
    );
  }
}

/**
 * Create a wpum_directory CPT post and a page with the [wpum_user_directory] shortcode.
 * The directory is configured with search form enabled.
 */
export function setupDirectoryPage(): void {
  // Check if the directory CPT post already exists
  let directoryId = '';
  try {
    directoryId = wpCli(
      'post list --post_type=wpum_directory --post_status=publish --field=ID --posts_per_page=1'
    );
  } catch {
    // ignore
  }

  if (!directoryId || !directoryId.match(/^\d+$/)) {
    directoryId = wpCli(
      'post create --post_type=wpum_directory --post_title="Test Directory" --post_status=publish --porcelain'
    );
  }

  directoryId = directoryId.trim();

  // Enable search form on the directory via Carbon Fields meta
  if (directoryId && directoryId.match(/^\d+$/)) {
    wpCli(
      `post meta update ${directoryId} _directory_search_form "yes"`
    );
    wpCli(
      `post meta update ${directoryId} _directory_display_sorter "yes"`
    );
  }

  // Create the page with the directory shortcode
  let pageId = '';
  try {
    pageId = wpCli(
      'post list --post_type=page --name="wpum-directory" --post_status=publish --field=ID'
    );
  } catch {
    // ignore
  }

  if (!pageId || !pageId.match(/^\d+$/)) {
    wpCli(
      `post create --post_type=page --post_title="User Directory" --post_name="wpum-directory" --post_status=publish --post_content='[wpum_user_directory id="${directoryId}"]'`
    );
  }
}

/**
 * Delete a user by username (for cleanup between test runs).
 */
export function deleteUser(username: string): void {
  try {
    wpCli(`user delete "${username}" --yes`);
  } catch {
    // User may not exist
  }
}

/**
 * Create a test user via WP-CLI.
 */
export function createUser(
  username: string,
  email: string,
  password: string,
  role = 'subscriber'
): void {
  try {
    wpCli(
      `user create "${username}" "${email}" --user_pass="${password}" --role="${role}"`
    );
  } catch {
    // User may already exist
  }
}

// Extend Playwright's base test with WPUM-specific fixtures
type WpumFixtures = {
  loginPage: string;
  registerPage: string;
  accountPage: string;
  profilePage: string;
  passwordRecoveryPage: string;
  loggedInContentPage: string;
  loggedOutContentPage: string;
  directoryPage: string;
  profileCardPage: string;
  recentUsersPage: string;
  loginLinkPage: string;
  logoutLinkPage: string;
  roleRestrictedPage: string;
};

export const test = base.extend<WpumFixtures>({
  loginPage: '/wpum-login/',
  registerPage: '/wpum-register/',
  accountPage: '/wpum-account/',
  profilePage: '/wpum-profile/',
  passwordRecoveryPage: '/wpum-password-recovery/',
  loggedInContentPage: '/wpum-logged-in-content/',
  loggedOutContentPage: '/wpum-logged-out-content/',
  directoryPage: '/wpum-directory/',
  profileCardPage: '/wpum-profile-card/',
  recentUsersPage: '/wpum-recent-users/',
  loginLinkPage: '/wpum-login-link/',
  logoutLinkPage: '/wpum-logout-link/',
  roleRestrictedPage: '/wpum-role-restricted/',
});

export { expect };
