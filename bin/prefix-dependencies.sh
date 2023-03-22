#!/usr/bin/env bash
if [ -z "$1" ]
then
      echo "You must supply a version"
      exit 1
fi

VERSION=$1

cd ./release/$VERSION
PHAR_URL="https://github.com/humbug/php-scoper/releases/download/0.17.2/php-scoper.phar"
curl -O -L $PHAR_URL

composer install --no-dev --optimize-autoloader

php -d memory_limit=-1 ./php-scoper.phar add-prefix --no-interaction --force --output-dir=scoped
(
    composer dump-autoload -o --no-dev --working-dir=scoped/

  	cd ../../
    php ./bin/patch-scoper-autoloader-unique-array-key.php "version=$VERSION"
    php ./bin/patch-scoper-autoloader-namespace.php "version=$VERSION&prefix=WPUM"

    # Move to vendor-dist
    rm -rf ./release/$VERSION/vendor-dist
    mv ./release/$VERSION/scoped/vendor ./release/$VERSION/vendor-dist
    mv ./release/$VERSION/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json ./release/$VERSION/vendor-dist/dompdf/dompdf/lib/fonts/installed-fonts.dist.json
    rm -rf ./release/$VERSION/scoped
    rm -rf ./release/$VERSION/php-scoper.phar
    rm -rf ./release/$VERSION/vendor
    rm -rf ./release/$VERSION/composer.json
    rm -rf ./release/$VERSION/composer.lock
    rm -rf ./release/$VERSION/scoper.inc.php
)
