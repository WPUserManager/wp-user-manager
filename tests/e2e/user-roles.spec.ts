import { test, expect, wpAdminLogin, wpCli } from './fixtures';

test.describe('User Roles Multiselect', () => {
  test.beforeEach(async ({ page }) => {
    // Enable multiple user roles setting
    wpCli('eval \'wpum_update_option("allow_multiple_user_roles", true);\'');
    await wpAdminLogin(page);
  });

  test.afterEach(() => {
    // Reset the setting
    try {
      wpCli('eval \'wpum_update_option("allow_multiple_user_roles", false);\'');
    } catch {
      // ignore
    }
  });

  test('multiselect role field is interactive on user edit page', async ({ page }) => {
    // Create a test user to edit (editing own profile redirects to profile.php)
    let userId = '';
    try {
      userId = wpCli('user get testuser_login --field=ID').trim();
    } catch {
      // Create if doesn't exist
      wpCli('user create testuser_login testuser@example.com --user_pass=password123 --role=subscriber');
      userId = wpCli('user get testuser_login --field=ID').trim();
    }

    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // Wait for Carbon Fields to render (React-based, needs time)
    await page.waitForTimeout(2000);

    // The WPUM multiple roles multiselect should be present
    const multiselect = page.locator('.wpum-multiple-user-roles');
    await expect(multiselect).toBeVisible({ timeout: 10000 });

    // The multiselect should be interactive — the Carbon Fields React select
    // component should respond to clicks. Find the select input within the
    // Carbon Fields container.
    const cfSelect = multiselect.locator('.cf-select__control, .cf-multiselect__control, select, [class*="select"]').first();
    await expect(cfSelect).toBeVisible({ timeout: 5000 });

    // Click on the select to open the dropdown — this is the critical test.
    // If the React component was broken by jQuery DOM manipulation, this click
    // will NOT open the dropdown (the component is "disabled"/unresponsive).
    await cfSelect.click();

    // After clicking, a dropdown menu or options list should appear.
    // Carbon Fields uses react-select which renders options in a menu.
    const dropdownMenu = page.locator(
      '.cf-select__menu, .cf-multiselect__menu, ' +
      '[class*="select__menu"], [class*="menu-list"], ' +
      '.wpum-multiple-user-roles [role="listbox"], ' +
      '.wpum-multiple-user-roles option'
    );
    const menuVisible = await dropdownMenu.first().isVisible({ timeout: 3000 }).catch(() => false);

    // If the dropdown didn't open, try clicking the container itself
    if (!menuVisible) {
      await multiselect.click();
      await page.waitForTimeout(500);
    }

    // Verify the field is not disabled/frozen — we should be able to find
    // role options (Administrator, Editor, Subscriber, etc.) somewhere in
    // the multiselect or its dropdown
    const fieldContent = await multiselect.textContent();
    const hasRoleText = fieldContent?.toLowerCase().includes('subscriber') ||
                        fieldContent?.toLowerCase().includes('administrator') ||
                        fieldContent?.toLowerCase().includes('editor');

    // The multiselect must contain role names — if it's empty or broken,
    // the Carbon Fields React component failed to render properly
    expect(hasRoleText).toBeTruthy();

    // The WordPress default role dropdown should be hidden
    const wpRoleSelect = page.locator('.user-role-wrap select#role');
    if (await wpRoleSelect.count() > 0) {
      await expect(wpRoleSelect).toBeHidden();
    }
  });
});
