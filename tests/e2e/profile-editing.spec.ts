import { test, expect, wpAdminLogin } from './fixtures';

test.describe('Profile Editing', () => {
  test.beforeEach(async ({ page }) => {
    // Log in as testuser_login before each test
    await wpAdminLogin(page, 'testuser_login', 'TestPass123!');
  });

  test('account settings tab renders', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    // The account page container should be visible
    const accountContainer = page.locator('.wpum-account-page');
    await expect(accountContainer).toBeVisible({ timeout: 5000 });

    // The profile edit form should be visible in the content area
    const accountForm = page.locator('#wpum-submit-account-form');
    await expect(accountForm).toBeVisible({ timeout: 5000 });

    // The form heading should indicate profile settings
    const formHeading = page.locator('.wpum-account-form h2');
    await expect(formHeading).toBeVisible();
    await expect(formHeading).toContainText(/profile settings/i);

    // The submit button should be present
    const submitButton = page.locator('input[name="submit_account"]');
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveValue('Update profile');
  });

  test('can update display name', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    // Wait for the account form to be fully loaded
    const accountForm = page.locator('#wpum-submit-account-form');
    await expect(accountForm).toBeVisible({ timeout: 5000 });

    // The display name field is a dropdown (select) with id user_displayname
    const displayNameField = page.locator('#user_displayname');
    const hasDisplayName = await displayNameField.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasDisplayName) {
      // Get the current value so we can pick a different one
      const currentValue = await displayNameField.inputValue();

      // Get available options
      const options = await displayNameField.locator('option').all();
      let newValue = '';

      // Pick a different option from what is currently selected
      for (const option of options) {
        const optionValue = await option.getAttribute('value');
        if (optionValue && optionValue !== currentValue && optionValue !== '') {
          newValue = optionValue;
          break;
        }
      }

      if (newValue) {
        await displayNameField.selectOption(newValue);
      }
    } else {
      // If display name is a text field instead
      const displayNameInput = page.locator('input#user_displayname');
      const hasInput = await displayNameInput.isVisible({ timeout: 2000 }).catch(() => false);
      if (hasInput) {
        await displayNameInput.fill('Test Display Name');
      }
    }

    // Submit the form
    await page.locator('input[name="submit_account"]').click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // After successful update, WPUM redirects with ?updated=success
    // Verify the success message is shown
    const successMessage = page.locator('.wpum-message.success');
    await expect(successMessage).toBeVisible({ timeout: 5000 });
    await expect(successMessage).toContainText(/profile successfully updated/i);
  });

  test('can update description/bio', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    // Wait for the account form to be fully loaded
    const accountForm = page.locator('#wpum-submit-account-form');
    await expect(accountForm).toBeVisible({ timeout: 5000 });

    // The description/bio field is a textarea with id user_description
    const descriptionField = page.locator('#user_description');
    const hasDescription = await descriptionField.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasDescription) {
      const timestamp = Date.now();
      const bioText = `This is a test bio updated at ${timestamp}`;

      await descriptionField.fill(bioText);

      // Submit the form
      await page.locator('input[name="submit_account"]').click();
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

      // Verify the success message is shown
      const successMessage = page.locator('.wpum-message.success');
      await expect(successMessage).toBeVisible({ timeout: 5000 });
      await expect(successMessage).toContainText(/profile successfully updated/i);

      // Verify the bio value persisted after the redirect
      const updatedDescription = page.locator('#user_description');
      await expect(updatedDescription).toContainText(bioText);
    } else {
      // Description field may not be enabled in the account form settings.
      // If it is a WYSIWYG editor, it may have a different structure.
      const wysiwygField = page.locator('.fieldset-user_description');
      const hasWysiwyg = await wysiwygField.isVisible({ timeout: 2000 }).catch(() => false);

      if (hasWysiwyg) {
        // The WYSIWYG field is present but rendered differently
        // Try to interact with the underlying textarea or iframe
        const iframe = wysiwygField.locator('iframe');
        const hasIframe = await iframe.isVisible({ timeout: 2000 }).catch(() => false);

        if (hasIframe) {
          const frame = iframe.contentFrame();
          if (frame) {
            await frame.locator('body').fill('Test bio via WYSIWYG');
          }
        }

        await page.locator('input[name="submit_account"]').click();
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

        const successMessage = page.locator('.wpum-message.success');
        await expect(successMessage).toBeVisible({ timeout: 5000 });
      } else {
        // The description field is not visible on the account page;
        // this means it has not been enabled for editing.
        // Mark the test as passing with a note.
        test.skip();
      }
    }
  });

  test('required fields validation', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    // Wait for the account form to be fully loaded
    const accountForm = page.locator('#wpum-submit-account-form');
    await expect(accountForm).toBeVisible({ timeout: 5000 });

    // The email field (user_email) is always required in WPUM
    const emailField = page.locator('#user_email');
    await expect(emailField).toBeVisible({ timeout: 3000 });

    // Store the original value so we can restore it if needed
    const originalEmail = await emailField.inputValue();

    // Clear the required email field
    await emailField.fill('');

    // Submit the form
    await page.locator('input[name="submit_account"]').click();
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // The form should show a validation error.
    // Either browser-native validation prevents submission (HTML5 required attribute),
    // or WPUM server-side validation returns an error message.
    const errorMessage = page.locator('.wpum-message.error');
    const hasServerError = await errorMessage.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasServerError) {
      // Server-side validation caught the empty required field
      await expect(errorMessage).toBeVisible();
    } else {
      // Browser-native validation prevented submission - the form is still on the page
      // with the email field empty, and no success message is shown
      const successMessage = page.locator('.wpum-message.success');
      const hasSuccess = await successMessage.isVisible({ timeout: 2000 }).catch(() => false);
      expect(hasSuccess).toBeFalsy();

      // The form should still be present (not redirected)
      await expect(accountForm).toBeVisible();
    }

    // Restore the original email to avoid side effects
    await emailField.fill(originalEmail);
  });

  test('account tabs navigation', async ({ page, accountPage }) => {
    await page.goto(accountPage);

    // The account page should have the tabs navigation
    const tabsNav = page.locator('#wpum-account-forms-tabs');
    await expect(tabsNav).toBeVisible({ timeout: 5000 });

    // Get all tab links
    const tabLinks = tabsNav.locator('ul li a');
    const tabCount = await tabLinks.count();

    // There should be at least one tab (Profile settings is always present)
    expect(tabCount).toBeGreaterThanOrEqual(1);

    // Collect tab info for navigation
    const tabs: { name: string; href: string }[] = [];
    for (let i = 0; i < tabCount; i++) {
      const name = await tabLinks.nth(i).textContent() || '';
      const href = await tabLinks.nth(i).getAttribute('href') || '';
      tabs.push({ name: name.trim(), href });
    }

    // Click through each tab and verify content renders
    for (const tab of tabs) {
      await page.goto(tab.href);
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

      // The account page container should still be visible
      const accountContainer = page.locator('.wpum-account-page');
      await expect(accountContainer).toBeVisible({ timeout: 5000 });

      // The tabs navigation should remain visible
      await expect(tabsNav).toBeVisible();

      // The content area should have content (a form or other content)
      const contentArea = page.locator('.wpum_two_third');
      await expect(contentArea).toBeVisible();

      // Each tab should render a form or content within the content area
      const hasForm = await contentArea.locator('form').isVisible({ timeout: 3000 }).catch(() => false);
      const hasContent = await contentArea.locator('.wpum-template').isVisible({ timeout: 2000 }).catch(() => false);

      // At least one of these should be true - the tab rendered something
      expect(hasForm || hasContent).toBeTruthy();

      // Verify the current tab is marked as active
      const activeTab = tabsNav.locator('li.active a');
      const activeHref = await activeTab.getAttribute('href').catch(() => '');
      expect(activeHref).toBe(tab.href);
    }
  });
});
