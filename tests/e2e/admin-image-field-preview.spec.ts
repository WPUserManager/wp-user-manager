import { test, expect, wpAdminLogin, wpCli, createUser, deleteUser } from './fixtures';

/**
 * Carbon Fields' image and file fields load the stored file's details from its
 * attachment REST route. That route checks who is asking, so the request must
 * carry the REST nonce or the field looks empty.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/573
 */
test.describe('Image field preview on Edit User screen', () => {
  const username = 'cover_preview_user';
  let userId: string;
  let attachmentId: string;

  test.beforeAll(() => {
    deleteUser(username);
    createUser(username, 'cover_preview_user@example.com', 'password123!');
    userId = wpCli(`user get ${username} --field=ID`).trim();

    // A Media Library image, as the admin's media picker would store it.
    const result = wpCli(
      `eval '
        require_once ABSPATH . "wp-admin/includes/image.php";
        require_once ABSPATH . "wp-admin/includes/file.php";
        require_once ABSPATH . "wp-admin/includes/media.php";
        $tmp = wp_tempnam( "e2e-cover.jpg" );
        copy( WPUM_PLUGIN_DIR . "tests/e2e/fixtures/test-avatar.jpg", $tmp );
        $id = media_handle_sideload( array( "name" => "e2e-cover.jpg", "tmp_name" => $tmp ), 0 );
        echo $id . " " . wp_get_attachment_url( $id );
      '`
    ).trim();
    // The last line, past any notices WP_DEBUG prints first.
    const [id, url] = result.split('\n').pop()!.trim().split(' ');
    attachmentId = id;
    expect(attachmentId).toMatch(/^\d+$/);

    wpCli(`user meta update ${userId} user_cover "${url}"`);
  });

  test.afterAll(() => {
    if (attachmentId) {
      wpCli(`post delete ${attachmentId} --force`);
    }
    deleteUser(username);
  });

  test('shows the stored cover image', async ({ page }) => {
    await wpAdminLogin(page);

    const attachmentResponse = page.waitForResponse((response) =>
      response.url().includes('carbon-fields/v1/attachment')
    );
    await page.goto(`/wp-admin/user-edit.php?user_id=${userId}`);

    expect((await attachmentResponse).status()).toBe(200);
    await expect(page.locator('.cf-file__name').filter({ hasText: /^e2e-cover.*\.jpg$/ })).toBeVisible();
  });
});
