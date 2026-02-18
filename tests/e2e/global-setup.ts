import {
  activatePlugin,
  enableRegistration,
  ensureHtaccess,
  setupWpumPages,
  setupContentRestrictionPages,
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

    // Enable user registration
    console.log('[WPUM E2E] Enabling user registration...');
    enableRegistration();

    // Create WPUM pages
    console.log('[WPUM E2E] Setting up WPUM pages...');
    setupWpumPages();

    // Create content restriction test pages
    console.log('[WPUM E2E] Setting up content restriction pages...');
    setupContentRestrictionPages();

    // Clean up any leftover test users from previous runs
    console.log('[WPUM E2E] Cleaning up test users...');
    deleteUser('testuser_e2e');
    deleteUser('testuser_reg');
    deleteUser('testuser_login');
    deleteUser('testuser_redirect');

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
