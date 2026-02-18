import {
  activatePlugin,
  enableRegistration,
  ensureHtaccess,
  ensurePageWithShortcode,
  setupWpumPages,
  setupContentRestrictionPages,
  setupDirectoryPage,
  createUser,
  deleteUser,
  wpCli,
} from './fixtures';

/**
 * Global setup for Playwright E2E tests.
 *
 * This runs once before all tests. It:
 * 1. Ensures pretty permalinks work (.htaccess)
 * 2. Activates the WPUM plugin
 * 3. Enables WordPress user registration
 * 4. Creates the required WPUM pages with shortcodes
 * 5. Creates test users
 * 6. Sets up content-restriction test pages
 */
async function globalSetup(): Promise<void> {
  console.log('\n[WPUM E2E] Running global setup...');

  try {
    // Ensure .htaccess exists for pretty permalinks (wp-env containers may not have it)
    console.log('[WPUM E2E] Setting up pretty permalinks...');
    wpCli('rewrite structure "/%postname%/"');
    ensureHtaccess();

    // Activate the plugin
    console.log('[WPUM E2E] Activating wp-user-manager plugin...');
    activatePlugin();

    // Activate the delete-account addon
    console.log('[WPUM E2E] Activating wpum-delete-account addon...');
    try {
      wpCli('plugin activate wpum-delete-account');
    } catch {
      console.log('[WPUM E2E] wpum-delete-account addon not available, skipping...');
    }

    // Enable user registration
    console.log('[WPUM E2E] Enabling user registration...');
    enableRegistration();

    // Create WPUM pages
    console.log('[WPUM E2E] Setting up WPUM pages...');
    setupWpumPages();

    // Create content restriction test pages
    console.log('[WPUM E2E] Setting up content restriction pages...');
    setupContentRestrictionPages();

    // Create shortcode test pages
    console.log('[WPUM E2E] Setting up shortcode test pages...');
    ensurePageWithShortcode('wpum-profile-card', 'Profile Card', '[wpum_profile_card]');
    ensurePageWithShortcode('wpum-recent-users', 'Recent Users', '[wpum_recently_registered]');
    ensurePageWithShortcode('wpum-login-link', 'Login Link', '[wpum_login]');
    ensurePageWithShortcode('wpum-logout-link', 'Logout Link', '[wpum_logout]');

    // Create role-restricted page for role restriction tests
    console.log('[WPUM E2E] Setting up role-restricted page...');
    ensurePageWithShortcode(
      'wpum-role-restricted',
      'Role Restricted',
      '[wpum_restrict_to_user_roles roles="administrator"]Only admins can see this.[/wpum_restrict_to_user_roles]'
    );

    // Create user directory page with wpum_directory CPT
    console.log('[WPUM E2E] Setting up user directory page...');
    setupDirectoryPage();

    // Clean up any leftover test users from previous runs
    // Note: When no username field is shown, WPUM uses the full email as the username
    console.log('[WPUM E2E] Cleaning up test users...');
    deleteUser('testuser_e2e');
    deleteUser('testuser_reg');
    deleteUser('testuser_reg@example.com');
    deleteUser('testuser_login');
    deleteUser('testuser_redirect');
    deleteUser('testuser_redirect@example.com');
    deleteUser('testuser_delete');

    // Create a test user for login tests
    console.log('[WPUM E2E] Creating test user for login tests...');
    createUser('testuser_login', 'testuser_login@example.com', 'TestPass123!', 'subscriber');

    // Flush rewrite rules after page creation
    console.log('[WPUM E2E] Flushing rewrite rules...');
    wpCli('rewrite flush');

    console.log('[WPUM E2E] Global setup complete!\n');
  } catch (error) {
    console.error('[WPUM E2E] Global setup failed:', error);
    throw error;
  }
}

export default globalSetup;
