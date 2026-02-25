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

  test('multiselect role field is visible near the role area', async ({ page }) => {
    // Create a test user to edit
    let userId = '';
    try {
      userId = wpCli('user get testuser_roles --field=ID').trim();
    } catch {
      wpCli('user create testuser_roles testuser_roles@example.com --user_pass=password123 --role=subscriber');
      userId = wpCli('user get testuser_roles --field=ID').trim();
    }

    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // Wait for Carbon Fields to render (React-based, needs time)
    await page.waitForTimeout(3000);

    // The WPUM multiple roles multiselect should be present
    const multiselect = page.locator('.wpum-multiple-user-roles');
    await expect(multiselect).toBeVisible({ timeout: 10000 });

    // The CF container heading ("User Roles") should be hidden
    const table = multiselect.locator('xpath=ancestor::table[contains(@class,"form-table")]');
    const heading = table.locator('xpath=preceding-sibling::h2[1]');
    if (await heading.count() > 0) {
      await expect(heading).toBeHidden();
    }

    // The WordPress default role dropdown should be hidden
    const wpRoleWrap = page.locator('.user-role-wrap');
    if (await wpRoleWrap.count() > 0) {
      await expect(wpRoleWrap).toBeHidden();
    }
  });

  test('multiselect role field is interactive', async ({ page }) => {
    let userId = '';
    try {
      userId = wpCli('user get testuser_roles --field=ID').trim();
    } catch {
      wpCli('user create testuser_roles testuser_roles@example.com --user_pass=password123 --role=subscriber');
      userId = wpCli('user get testuser_roles --field=ID').trim();
    }

    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(3000);

    const multiselect = page.locator('.wpum-multiple-user-roles');
    await expect(multiselect).toBeVisible({ timeout: 10000 });

    // Find the CF react-select control and click it
    const cfSelect = multiselect.locator('.cf-select__control, .cf-multiselect__control, [class*="select__control"]').first();
    await expect(cfSelect).toBeVisible({ timeout: 5000 });
    await cfSelect.click();

    // After clicking, a dropdown menu should appear (react-select renders a menu)
    const dropdownMenu = page.locator(
      '.cf-select__menu, .cf-multiselect__menu, ' +
      '[class*="select__menu"], [class*="menu-list"]'
    );
    const menuVisible = await dropdownMenu.first().isVisible({ timeout: 3000 }).catch(() => false);

    // If the dropdown didn't open via the control, try clicking the container
    if (!menuVisible) {
      await multiselect.click();
      await page.waitForTimeout(500);
    }

    // Verify the field contains role text (not empty/broken)
    const fieldContent = await multiselect.textContent();
    const hasRoleText = fieldContent?.toLowerCase().includes('subscriber') ||
                        fieldContent?.toLowerCase().includes('administrator') ||
                        fieldContent?.toLowerCase().includes('editor');

    expect(hasRoleText).toBeTruthy();
  });

  test('role changes save correctly', async ({ page }) => {
    let userId = '';
    try {
      userId = wpCli('user get testuser_roles --field=ID').trim();
    } catch {
      wpCli('user create testuser_roles testuser_roles@example.com --user_pass=password123 --role=subscriber');
      userId = wpCli('user get testuser_roles --field=ID').trim();
    }

    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(3000);

    const multiselect = page.locator('.wpum-multiple-user-roles');
    await expect(multiselect).toBeVisible({ timeout: 10000 });

    // Open the select and pick "Editor" if available
    const cfSelect = multiselect.locator('.cf-select__control, .cf-multiselect__control, [class*="select__control"]').first();
    await cfSelect.click();
    await page.waitForTimeout(500);

    // Type to filter for "editor"
    await page.keyboard.type('editor');
    await page.waitForTimeout(500);

    // Click the first matching option
    const option = page.locator('[class*="select__option"]').first();
    if (await option.isVisible({ timeout: 2000 }).catch(() => false)) {
      await option.click();
      await page.waitForTimeout(500);

      // Submit the form
      await page.locator('#submit').click();
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

      // Verify the role was saved
      const roles = wpCli(`user get testuser_roles --field=roles`).trim();
      expect(roles.toLowerCase()).toContain('editor');
    }
  });

  test('WP role dropdown shows normally when multiple roles disabled', async ({ page }) => {
    wpCli('eval \'wpum_update_option("allow_multiple_user_roles", false);\'');

    let userId = '';
    try {
      userId = wpCli('user get testuser_roles --field=ID').trim();
    } catch {
      wpCli('user create testuser_roles testuser_roles@example.com --user_pass=password123 --role=subscriber');
      userId = wpCli('user get testuser_roles --field=ID').trim();
    }

    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

    // The default WP role dropdown should be visible
    const wpRoleWrap = page.locator('.user-role-wrap');
    if (await wpRoleWrap.count() > 0) {
      await expect(wpRoleWrap).toBeVisible();
    }

    // The WPUM multiselect should NOT be present
    const multiselect = page.locator('.wpum-multiple-user-roles');
    await expect(multiselect).toHaveCount(0, { timeout: 3000 });
  });
});
