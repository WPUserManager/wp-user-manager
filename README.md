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
