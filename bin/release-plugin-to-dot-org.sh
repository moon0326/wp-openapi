#!/bin/sh

CURRENT_PATH=`pwd`

# Pull down the SVN repository.
echo "Pulling down the SVN repository for wp-openapi"
SVN_WP_OPENAPI=/tmp/woocommerce/svn-wp-openapi
svn co https://plugins.svn.wordpress.org/wp-openapi/ $SVN_WP_OPENAPI
cd  $SVN_WP_OPENAPI

# Get the tagged version to release.
echo "Please enter the version number to release to wordpress.org, for example, 1.0.0: "
read -r VERSION

# Empty trunk/.
rm -rf trunk
mkdir trunk

# Download and unzip the plugin into trunk/.
echo "Downloading and unzipping the plugin"
PLUGIN_URL=https://github.com/moon0326/wp-openapi/releases/download/v${VERSION}-plugin/wp-openapi.zip
curl -Lo wp-openapi.zip $PLUGIN_URL
unzip wp-openapi.zip -d trunk
rm wp-openapi.zip

# Add files in trunk/ to SVN.
cd trunk
svn add --force .
cd ..

# Commit the changes, which will automatically release the plugin to wordpress.org.
echo "Checking in the new version"
svn ci -m "Release v${VERSION}"

# Tag the release
echo "Tagging the release"
svn cp trunk tags/$VERSION
svn ci -m "Tagging v${VERSION}"

# Clean up.
cd ..
rm -rf svn-wp-openapi

cd $CURRENT_PATH