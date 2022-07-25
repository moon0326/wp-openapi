#!/bin/sh

rm wp-openapi.zip

echo "Building"
npm run build
composer install --no-dev

echo "Creating archive... 🎁"
zip -r "wp-openapi.zip" \
	wp-openapi.php \
	resources \
	vendor \
	composer.json \
	src \
	build/