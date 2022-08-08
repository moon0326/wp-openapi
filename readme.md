# WP OpenAPI

WP OpenAPI is a WordPress plugin that generates OpenAPI 3.1.0 compatible documentation for your WordPress REST APIs.

It has two main features.

1. Outputs OpenAPI 3.1.0 spec at `/wp-json-openapi`
2. Provides OpenAPI viewer using Stoplight's awesome [Elements](https://github.com/stoplightio/elements) viewer

## Screenshots

![screenshot](./screenshots/screenshot1.jpg)

## Usage

1. Download the latest zip archive from [releases](https://github.com/moon0326/wp-openapi/releases) page and activate it.
2. You should see `WP OpenAPI` menu on the sidebar.
3. Click `All` to view all the endpoints or choose a namespace to view the endpoints registered in the namespace.

## Optional Settings

WP OpenAPI has two optional settings -- Enable Try It and Enable Callback Discovery.

Both settings are turned off by default.
You can enable them in `Settings -> WP OpenAPI`

**Enable Try It**: It enables [Elements](https://github.com/stoplightio/elements)'s TryIt feature. It lets you try the REST API endpoints.

<img src='./screenshots/tryit.jpg' width='300'>

**Enable Callback Discovery**: It shows you callback information about the endpoint you're viewing.
It comes in handy if you want to look at the source code of the endpoint.

<img src='./screenshots/callback.jpg' width='300'>

## Extending

WP OpenAPI has the following filters to modify the output.

| Name                         |               Argument                | Equivalent Filters Method |
| ---------------------------- | :-----------------------------------: | ------------------------- |
| wp-openapi-filter-operations  | [Operation[]](./src/Spec/Operation.php) | AddOperationsFilter        |
| wp-openapi-filter-paths       |      [Path[]](./src/Spec/Path.php)      | AddPathsFilter             |
| wp-openapi-filter-servers     |    [Server[]](./src/Spec/Server.php)    | AddServersFilter           |
| wp-openapi-filter-info       |      [Info](./src/Spec/Info.php)      | AddInfoFilter             |
| wp-openapi-filter-tags        |       [Tag[]](./src/Spec/Tag.php)        | AddTagsFilter              |
| wp-openapi-filter-security   |                 Array                 | AddSecurityFilter         |
| wp-oepnapi-filter-components |                 Array                 | AddComponentsFilter       |
| wp-openapi-filters-elements-props | Array||
You can use individual filters by calling [add_filter](https://developer.wordpress.org/reference/functions/add_filter/).

You can also use [Filters](./src/Filters.php).

```php
Filters::getInstance()->addPathsFilter(function(array $paths, array $args) {
    if ($args['requestedNamespace'] === 'all) {
        foreach ($paths as $path) {
            foreach ($path->getOperations() as $operation) {
                $operation->setDescription('test');
            }
        }
    }
});
```

## Export

WP OpenAPI comes with a [WP CLI](https://wp-cli.org/) command to export a single HTML file.

You can use the file offline or host it on a webserver.

1. Install [WP CLI](https://wp-cli.org/)
2. Open a terminal session and cd to your WordPress installtion directory.
3. Run `wp openapi export --namespace=all --save_to=./export.html`

## Development

### Getting Started

To get started, run the following commands:

```
npm i
composer install
npm start
```

See [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts) for more usage information.

### Deploying

Prerequisites:

- [Hub](https://github.com/github/hub)
- Write access to this repository

Simply run `./bin/release-to-github.sh`


You can also build a zip file for testing purposes by runnning `./bin/build-zip.sh`





## Todo

- [ ] Code clean up
- [ ] Write tests
- [ ] Make a publicly accessible endpoint
- [ ] Remove this [dirty hack](https://github.com/moon0326/wp-openapi/blob/main/resources/scripts/wp-openapi.js#L12) :sweat_smile:
