# WP User Manager — Test Suite

## Prerequisites

- **Docker Desktop** (recent version with Docker Compose v2)
- **Node.js >= 20** (`nvm use 20`)
- **Composer dependencies installed** (`composer install` in plugin root)

## Environment

Tests run inside **wp-env** Docker containers. The test site runs on `localhost:8889` with its own MySQL database (`tests-mysql` container).

```bash
# Start the wp-env environment (from plugin root)
nvm use 20 && npx @wordpress/env start
```

## Test Suites

### 1. Unit Tests (wpunit) — WordPress integration tests

These tests bootstrap a full WordPress + WPUM environment via the WPLoader Codeception module. They test form handlers, field CRUD, validation, shortcodes, authentication, and more.

**Framework:** Codeception 4.2 + wp-browser 3.x + PHPUnit 9.5

**Run all wpunit tests:**

```bash
npx @wordpress/env run tests-cli \
  --env-cwd=/var/www/html/wp-content/plugins/wp-user-manager/tests \
  bash -c "php ../vendor/bin/codecept run wpunit --no-colors 2>&1"
```

**Run a specific test suite (e.g. Registration only):**

```bash
npx @wordpress/env run tests-cli \
  --env-cwd=/var/www/html/wp-content/plugins/wp-user-manager/tests \
  bash -c "php ../vendor/bin/codecept run wpunit Registration --no-colors 2>&1"
```

**Run a single test file:**

```bash
npx @wordpress/env run tests-cli \
  --env-cwd=/var/www/html/wp-content/plugins/wp-user-manager/tests \
  bash -c "php ../vendor/bin/codecept run wpunit wpunit/Registration/SecurityTest.php --no-colors 2>&1"
```

**Run with verbose output:**

```bash
npx @wordpress/env run tests-cli \
  --env-cwd=/var/www/html/wp-content/plugins/wp-user-manager/tests \
  bash -c "php ../vendor/bin/codecept run wpunit -v --no-colors 2>&1"
```

#### wpunit Configuration

- **Suite config:** `tests/wpunit.suite.yml`
- **Environment vars:** `tests/.env.local` (wp-env container credentials)
- **Bootstrap:** `tests/wpunit/_bootstrap.php`

Key WPLoader settings:
- `isolatedInstall: false` — required to avoid PHP 8.x deprecation issues during plugin activation
- `wpDebug: false` — suppresses debug output during tests
- Plugin loaded: `wp-user-manager/wp-user-manager.php`

#### wpunit Directory Structure

```
tests/wpunit/
├── _bootstrap.php
├── WPUMTestCase.php           # Shared base class (tables, default data)
├── Registration/              # Registration form tests
│   ├── RegistrationTestCase.php
│   ├── SuccessfulRegistrationTest.php
│   ├── ValidationTest.php
│   ├── SecurityTest.php
│   ├── EmailNotificationTest.php
│   ├── RoleAssignmentTest.php
│   └── HooksTest.php
├── Login/                     # Login form tests
├── PasswordRecovery/          # Password recovery flow tests
├── PasswordChange/            # Password change (logged-in) tests
├── Privacy/                   # Privacy form tests
├── Auth/                      # Authentication filter tests
├── Fields/                    # Field & field group CRUD tests
├── Database/                  # Abstract DB layer tests
├── Shortcodes/                # Shortcode registration & content restriction
├── Emails/                    # Email tags & templates
├── Functions/                 # Helper function tests
└── Validation/                # Strong password validation
```

### 2. Acceptance Tests — Browser-based end-to-end tests

These tests use WPWebDriver (ChromeDriver) to automate a real browser against the running WordPress site. They test full user flows including UI rendering, form submissions, redirects, and email delivery (via Mailtrap).

**Framework:** Codeception + WPWebDriver + WPDb + Mailtrap

**These tests require additional setup:**

1. **ChromeDriver** running on port 9515
2. **Mailtrap** account with API credentials (for email tests)
3. **Database credentials** configured in `tests/.env` (not `.env.local`)

**Existing acceptance tests:**

| File | What it tests |
|------|--------------|
| `HTMLFormErrorMessagesCept.php` | Login error messages don't contain raw HTML tags (issue #298) |
| `PreventSiteAccessCept.php` | Site lockdown redirects, wp-login.php lock, registration page access |
| `ResetPasswordCept.php` | Full password reset flow: request, email link, new password |

**Run acceptance tests** (requires ChromeDriver + Mailtrap):

```bash
npx @wordpress/env run tests-cli \
  --env-cwd=/var/www/html/wp-content/plugins/wp-user-manager/tests \
  bash -c "php ../vendor/bin/codecept run acceptance --no-colors 2>&1"
```

> **Note:** Acceptance tests are not currently wired up with wp-env — they were written for a local dev environment with ChromeDriver and Mailtrap. Getting them running requires configuring `tests/.env` with correct database, URL, and Mailtrap credentials, plus having ChromeDriver accessible from the test container.

#### Acceptance Configuration

- **Suite config:** `tests/acceptance.suite.yml`
- **Environment vars:** `tests/.env` (local dev credentials — NOT for wp-env)
- **Step objects:** `tests/_support/Step/Acceptance/Administrator.php`

### 3. Functional & Unit Suites (not currently used)

- `functional.suite.yml` — configured but no test files exist
- `unit.suite.yml` — for pure PHP unit tests (no WordPress). No test files exist.

## Configuration Files

| File | Purpose |
|------|---------|
| `codeception.dist.yml` | Default Codeception config (paths, extensions, params from `.env`) |
| `codeception.yml` | Local override — points params to `.env.local` instead |
| `.env` | Original environment vars (local dev setup with ChromeDriver/Mailtrap) |
| `.env.local` | wp-env container credentials (used by wpunit suite) |
| `wpunit.suite.yml` | wpunit suite config (WPLoader module) |
| `acceptance.suite.yml` | Acceptance suite config (WPWebDriver, WPDb, Mailtrap) |
| `functional.suite.yml` | Functional suite config (unused) |
| `unit.suite.yml` | Pure unit suite config (unused) |

## Troubleshooting

### `$wpdb` is null / "Attempt to assign property on null"

The WPLoader module isn't bootstrapping WordPress. Make sure `isolatedInstall: false` is set in `wpunit.suite.yml` and that `.env.local` has the correct wp-env database credentials.

### "The plugin generated unexpected output"

This happens with `isolatedInstall: true` on PHP 8.x due to deprecation notices during plugin activation. Keep `isolatedInstall: false`.

### "Class WPUM_Form_Registration not found"

The test's `_setUp()` method isn't running. Make sure you're using `_setUp()` / `_tearDown()` (Codeception convention), NOT `set_up()` / `tear_down()` (WordPress 5.9+ convention). wp-browser 3.x uses the Codeception convention.

### WPUM custom tables missing

WPLoader drops all tables on bootstrap. Your test base class must call `ensure_tables()` in `_setUp()` to recreate the 6 WPUM custom tables before each test.

### wp-env not starting

Make sure Docker Desktop is running and you're using Node.js >= 20:

```bash
nvm use 20
npx @wordpress/env start
```

If the environment is corrupted, destroy and recreate:

```bash
npx @wordpress/env destroy
npx @wordpress/env start
```
