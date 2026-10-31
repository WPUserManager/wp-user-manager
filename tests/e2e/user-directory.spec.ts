import { test, expect, wpCli } from './fixtures';

test.describe('User Directory Shortcode', () => {
  test('directory page renders the directory container', async ({
    page,
    directoryPage,
  }) => {
    await page.goto(directoryPage);

    // The main directory wrapper should be visible
    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // The users list section should be present
    const usersList = page.locator('#wpum-directory-users-list');
    await expect(usersList).toBeVisible();
  });

  test('directory shows registered users', async ({
    page,
    directoryPage,
  }) => {
    await page.goto(directoryPage);

    // Wait for the directory to load
    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // At least one user card should be displayed (admin user at minimum)
    const userCards = page.locator('.wpum-directory-single-user');
    await expect(userCards.first()).toBeVisible({ timeout: 5000 });

    // Verify there is at least one user listed
    const userCount = await userCards.count();
    expect(userCount).toBeGreaterThanOrEqual(1);

    // The top bar should show the user count
    const topBar = page.locator('#wpum-directory-top-bar');
    if (await topBar.isVisible({ timeout: 3000 }).catch(() => false)) {
      await expect(topBar).toContainText(/Found \d+ users/);
    }
  });

  test('directory has search functionality', async ({
    page,
    directoryPage,
  }) => {
    await page.goto(directoryPage);

    // Wait for the directory to load
    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // The search form should be present
    const searchForm = page.locator('#wpum-directory-search-form');
    await expect(searchForm).toBeVisible({ timeout: 5000 });

    // The search input should be present
    const searchInput = page.locator('#wpum-directory-search');
    await expect(searchInput).toBeVisible();

    // The search submit button should be present
    const searchButton = page.locator('#wpum-submit-user-search');
    await expect(searchButton).toBeVisible();

    // Fill in a search term and submit
    await searchInput.fill('admin');
    await searchButton.click();

    // Wait for the page to reload with search results
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The directory should still be visible after search
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // The search input should retain the search value
    await expect(searchInput).toHaveValue('admin');
  });

  test('user profile links work', async ({
    page,
    directoryPage,
  }) => {
    await page.goto(directoryPage);

    // Wait for the directory to load
    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // Find the first user card
    const firstUserCard = page.locator('.wpum-directory-single-user').first();
    await expect(firstUserCard).toBeVisible({ timeout: 5000 });

    // Get the profile link from the "View profile" button
    const viewProfileLink = firstUserCard.locator('a.button');
    await expect(viewProfileLink).toBeVisible();

    // Get the href before clicking to verify it points to a profile
    const href = await viewProfileLink.getAttribute('href');
    expect(href).toBeTruthy();

    // Click the "View profile" button
    await viewProfileLink.click();

    // Wait for navigation
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The URL should have changed away from the directory page
    const currentUrl = page.url();
    expect(currentUrl).not.toContain('wpum-directory');
  });

  test('directory shows user display names', async ({
    page,
    directoryPage,
  }) => {
    await page.goto(directoryPage);

    // Wait for the directory to load
    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    // Each user card should have a name element with text content
    const firstUserCard = page.locator('.wpum-directory-single-user').first();
    await expect(firstUserCard).toBeVisible({ timeout: 5000 });

    // The user name element should be visible and contain text
    const userName = firstUserCard.locator('.wpum-name');
    await expect(userName).toBeVisible();
    const nameText = await userName.textContent();
    expect(nameText?.trim()).toBeTruthy();

    // The user name should contain a link to the profile
    const nameLink = userName.locator('a');
    await expect(nameLink).toBeVisible();
    const nameLinkHref = await nameLink.getAttribute('href');
    expect(nameLinkHref).toBeTruthy();

    // Each user card should also have an avatar section
    const avatar = firstUserCard.locator('#directory-avatar');
    await expect(avatar).toBeVisible();
  });

  test.describe('email privacy', () => {
    const domain = 'wpum-private-domain.example';
    const named = 'wpum-e2e-named';
    const emailLogin = `wpum-e2e-email@${domain}`;
    const searchFieldsKey = '"_directory_search_fields|||0|value"';
    let directoryId = '';

    const searchFor = (directoryPage: string, term: string) =>
      `${directoryPage}?directory-search=${encodeURIComponent(term)}`;

    test.beforeEach(() => {
      wpCli(`user create "${named}" "${named}@${domain}" --first_name="Quillon" --display_name="Marlowe Vance" --role="subscriber"`);
      wpCli(`user create "${emailLogin}" "${emailLogin}" --display_name="Thessaly Noor" --role="subscriber"`);

      // Search a configured field, as a directory saved in the admin does.
      directoryId = wpCli('post list --post_type=wpum_directory --post_status=publish --field=ID --posts_per_page=1').trim();
      wpCli(`post meta update ${directoryId} ${searchFieldsKey} "first_name"`);
    });

    test.afterEach(() => {
      wpCli(`post meta delete ${directoryId} ${searchFieldsKey}`);
      wpCli(`user delete "${named}" --yes`);
      wpCli(`user delete "${emailLogin}" --yes`);
    });

    test('guests find members by name, not by email address', async ({
      page,
      directoryPage,
    }) => {
      const userCards = page.locator('.wpum-directory-single-user');

      // The configured field proves the search reached that query path.
      await page.goto(searchFor(directoryPage, 'Quillon'));
      await expect(userCards).toHaveCount(1);

      await page.goto(searchFor(directoryPage, 'Marlowe'));
      await expect(userCards).toHaveCount(1);

      for (const term of [`@${domain}`, domain, `${named}@${domain}`, named]) {
        await page.goto(searchFor(directoryPage, term));
        await expect(page.locator('#wpum-user-directory')).toBeVisible({ timeout: 10000 });
        await expect(userCards).toHaveCount(0);
      }
    });

    test('guests can not sort members by private user meta', async ({
      page,
      directoryPage,
    }) => {
      await page.goto(`${directoryPage}?sortby=wpum_e2e_no_such_key`);

      await expect(page.locator('.wpum-directory-single-user').first()).toBeVisible({ timeout: 10000 });
    });

    test('email addresses used as names stay out of public pages', async ({
      page,
      directoryPage,
    }) => {
      const emailName = `wpum-e2e-masked@${domain}`;
      wpCli(`user create "${emailName}" "${emailName}" --first_name="Isolde" --display_name="${emailName}" --role="subscriber"`);

      try {
        await page.goto(searchFor(directoryPage, 'Isolde'));

        const viewProfileLink = page.locator('.wpum-directory-single-user a.button');
        await expect(viewProfileLink).toHaveCount(1);
        expect(await page.content()).not.toContain(domain);

        await viewProfileLink.click();
        await expect(page.locator('#header-name-container')).toBeVisible({ timeout: 10000 });
        expect(await page.content()).not.toContain(domain);
      } finally {
        wpCli(`user delete "${emailName}" --yes`);
      }
    });

    test('profile links leave out an email address used as a login', async ({
      page,
      directoryPage,
    }) => {
      await page.goto(searchFor(directoryPage, 'Thessaly'));

      const viewProfileLink = page.locator('.wpum-directory-single-user a.button');
      await expect(viewProfileLink).toHaveCount(1);

      const href = await viewProfileLink.getAttribute('href');
      expect(href).not.toContain(domain);
      expect(await page.content()).not.toContain(domain);

      await viewProfileLink.click();
      await expect(page.locator('#header-name-container')).toContainText('Thessaly Noor');
    });
  });

  test('plugin deactivation and reactivation', async ({
    page,
    directoryPage,
  }) => {
    // Deactivate the plugin
    wpCli('plugin deactivate wp-user-manager');

    // Reactivate the plugin
    wpCli('plugin activate wp-user-manager');

    // Verify the directory page still renders after reactivation
    await page.goto(directoryPage);

    const directoryContainer = page.locator('#wpum-user-directory');
    await expect(directoryContainer).toBeVisible({ timeout: 10000 });

    const usersList = page.locator('#wpum-directory-users-list');
    await expect(usersList).toBeVisible();
  });
});
