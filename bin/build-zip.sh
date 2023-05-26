#!/bin/sh

rm wp-openapi.zip

echo "Building"
npm run build
composer install --no-dev

echo "Creating archive... 🎁"
zip -r "wp-openapi.zip" \
	wp-openapi.php \
	resources \
	assets \
	vendor \
	composer.json \
	readme.md \
	readme.txt \
	src \
	build/