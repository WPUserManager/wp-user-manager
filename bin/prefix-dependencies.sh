#!/usr/bin/env bash
if [ -z "$1" ]
then
      echo "You must supply a version"
      exit 1
fi

VERSION=$1

rm -rf ./release/scoper
mkdir ./release/scoper
cp ./composer.json ./release/scoper/composer.json
cp ./composer.lock ./release/scoper/composer.lock
cp ./scoper.inc.php ./release/scoper/scoper.inc.php
cd ./release/scoper
PHAR_URL="https://github.com/humbug/php-scoper/releases/download/0.17.2/php-scoper.phar"
curl -O -L $PHAR_URL

composer install --no-dev --optimize-autoloader

php -d memory_limit=-1 ./php-scoper.phar add-prefix --no-interaction --force
(
    composer dump-autoload --classmap-authoritative --no-dev

    cd ../../
    # Fix Composer autoloader issues after scoping
    #php ./bin/patch-scoper-autoloader-unique-array-key.php
    php ./bin/patch-scoper-autoloader-namespace.php WPUM

    # Move to vendor-dist
    rm -rf ./release/$VERSION/vendor
    rm -rf ./release/$VERSION/vendor-dist
    mv ./release/scoper/build/vendor ./release/$VERSION/vendor-dist
    mv ./release/scoper/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json ./release/$VERSION/vendor-dist/dompdf/dompdf/lib/fonts/installed-fonts.dist.json
    rm -rf ./release/scoper
)
