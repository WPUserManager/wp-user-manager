# WP User Manager

This is the code repo for the WordPress membership plugin [WP User Manager](https://wordpress.org/plugins/wp-user-manager/).

## Development

Checkout the repo
Run `yarn`
Run `composer install`
Run `grunt dev-build`

### PHPCS coding standards

Initially run `vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs`

Then run `vendor/bin/phpcs`, you can also supply a file path as an argument.

### Automated acceptance tests

Run `tests/bin/run-acceptancetests.sh`

## Security

Please report security bugs found in the source code of the WP User Manager plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/wp-user-manager/). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

