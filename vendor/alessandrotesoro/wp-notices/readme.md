# wp-notices

![Packagist](https://img.shields.io/packagist/dt/alessandrotesoro/wp-notices.svg) ![Packagist3](https://img.shields.io/packagist/v/alessandrotesoro/wp-notices.svg) ![Packagist2](https://img.shields.io/packagist/l/alessandrotesoro/wp-notices.svg) ![PHP from Packagist](https://img.shields.io/packagist/php-v/alessandrotesoro/wp-notices.svg) ![Github commits (since latest release)](https://img.shields.io/github/commits-since/alessandrotesoro/wp-notices/latest.svg)

> An helper library to create persistent and dismissible WordPress admin notices. 

## Installation
Composer is required.

```bash
composer require alessandrotesoro/wp-notices
```

## Usage

Import the library and assign it your own namespace:

```php
use TDP\WP_Notice as MYNOTICES;
```

Create a wrapper function:

```php
function mynotices() {
	return MYNOTICES::instance();
}
```

Create a global notice for all users:

```php
mynotices()->register_notice( 'my_notice', 'warning', 'This is the message' ) );
```

Or create a notice for the currently logged in user only:

```php
mynotices()->register_notice( 'my_notice', 'warning', 'This is the message', array( 'scope' => 'user' ) ) );
```

## Available parameters	
| Parameter | Type   | Options                                                    | Defaults                           | Description                                      |
| --------- | ------ | ---------------------------------------------------------- | ---------------------------------- | ------------------------------------------------ |
| id        | string |                                                            |                                    | Required ID to identify the notice               |
| type      | string | success, warning, error, info                              |                                    | Determine the type of notice                     |
| message   | string |                                                            |                                    | The message you wish to display within WordPress |
| args      | array  | scope (global, user), dismissible (true/false), cap, class | scope = global, dismissible = true | Additional settings available for the notice     |
