import { test, expect, wpAdminLogin, wpCli } from './fixtures';

/**
 * The email content editor has a Text tab for editing the HTML directly.
 *
 * The email editor runs in the Customizer, which does not load with block
 * themes (#436), so this test switches to a classic theme for its run.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/63
 */
test.describe('Email editor Text tab', () => {
  let previousTheme = '';

  test.beforeAll(() => {
    previousTheme = wpCli('option get stylesheet').trim().split('\n').pop() as string;
    try {
      wpCli('theme activate twentytwentyone');
    } catch {
      wpCli('theme install twentytwentyone --activate');
    }
  });

  test.afterAll(() => {
    if (previousTheme) {
      wpCli(`theme activate ${previousTheme}`);
    }
  });

  test('switches to the Text tab and edits the HTML', async ({ page }) => {
    await wpAdminLogin(page);

    const email = 'registration_confirmation';
    const preview = encodeURIComponent(`/?wpum_email_customizer=true&email=${email}`);
    // Open the email's settings section directly: the editor button is inside it,
    // and stays hidden when only the panel is focused.
    await page.goto(`/wp-admin/customize.php?autofocus%5Bsection%5D=${email}_settings&wpum_email_customizer=true&email=${email}&url=${preview}`);

    const openEditor = page.locator('#wpum-email-editor-btn');
    await expect(openEditor).toBeVisible({ timeout: 30000 });
    await openEditor.click();

    const textTab = page.locator('#wpum-mail-content-editor-html');
    await expect(textTab).toBeVisible({ timeout: 15000 });
    await textTab.click();

    const textarea = page.locator('textarea#wpum-mail-content-editor');
    await expect(textarea).toBeVisible();
    await textarea.fill('<p><a href="{login_page_url}" class="button">Log in</a></p>');
    await expect(textarea).toHaveValue('<p><a href="{login_page_url}" class="button">Log in</a></p>');

    // The quicktags toolbar is present in Text mode.
    await expect(page.locator('#qt_wpum-mail-content-editor_toolbar')).toBeVisible();

    // Switching back to Visual keeps the content.
    await page.locator('#wpum-mail-content-editor-tmce').click();
    await expect(page.frameLocator('#wpum-mail-content-editor_ifr').locator('a.button')).toHaveText('Log in');
  });
});
