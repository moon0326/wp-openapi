const config = require("@wordpress/scripts/config/webpack.config");
const WooCommerceDependencyExtractionWebpackPlugin = require("@woocommerce/dependency-extraction-webpack-plugin");
module.exports = {
  ...config,
  entry: {
    "resources/scripts/app": "./resources/scripts/wp-openapi.js",
    "resources/css/app": "./resources/sass/wp-openapi.scss",
  },
  plugins: [
    ...config.plugins.filter(
      (plugin) =>
        plugin.constructor.name !== "DependencyExtractionWebpackPlugin"
    ),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};
