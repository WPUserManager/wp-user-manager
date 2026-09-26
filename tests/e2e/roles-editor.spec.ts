import { test, expect, wpAdminLogin, wpCli } from './fixtures';

/**
 * Renaming a role from the Users > Roles screen.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/381
 */
test.describe('Roles editor rename', () => {
  test.beforeEach(async ({ page }) => {
    wpCli('eval \'wpum_update_option("roles_editor", true);\'');
    wpCli('eval \'remove_role("e2e_rename_role"); add_role("e2e_rename_role", "E2E Rename Role", array("read" => true));\'');
    await wpAdminLogin(page);
  });

  test.afterEach(() => {
    try {
      wpCli('eval \'remove_role("e2e_rename_role");\'');
    } catch {
      // ignore
    }
  });

  test('renames a role and keeps its slug', async ({ page }) => {
    await page.goto('/wp-admin/users.php?page=wpum-roles');

    const row = page.locator('tr', { has: page.locator('td', { hasText: /^\s*e2e_rename_role\s*$/ }) });
    await expect(row).toBeVisible({ timeout: 15000 });

    await row.hover();
    await row.getByRole('link', { name: 'Rename', exact: true }).click();

    const input = page.locator('#role-name');
    await expect(input).toHaveValue('E2E Rename Role');
    await input.fill('E2E Members');
    await page.locator('#edit-role button', { hasText: 'Save Changes' }).click();

    await expect(row.locator('strong')).toHaveText('E2E Members', { timeout: 10000 });

    // The name is stored and the slug is unchanged.
    expect(wpCli('eval \'echo wp_roles()->roles["e2e_rename_role"]["name"];\'')).toContain('E2E Members');

    await page.reload();
    await expect(page.locator('tr', { hasText: 'e2e_rename_role' }).locator('strong')).toHaveText('E2E Members', { timeout: 15000 });
  });

  test('rejects a name another role already uses', async ({ page }) => {
    await page.goto('/wp-admin/users.php?page=wpum-roles');

    const row = page.locator('tr', { has: page.locator('td', { hasText: /^\s*e2e_rename_role\s*$/ }) });
    await expect(row).toBeVisible({ timeout: 15000 });

    await row.hover();
    await row.getByRole('link', { name: 'Rename', exact: true }).click();
    await page.locator('#role-name').fill('Editor');
    await page.locator('#edit-role button', { hasText: 'Save Changes' }).click();

    await expect(page.getByText('Another role already uses this name.')).toBeVisible({ timeout: 10000 });
    expect(wpCli('eval \'echo wp_roles()->roles["e2e_rename_role"]["name"];\'')).toContain('E2E Rename Role');
  });
});
