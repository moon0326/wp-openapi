<?php
/**
 * WP OpenAPI
 *
 * @package     WP OpenAPI
 * @author      Moon
 * @copyright   Moon
 * @license     GPL-2.0-or-later
 *
 * Plugin Name: WP OpenAPI
 * Plugin URI: https://github.com/moon0326/wp-openapi
 * Version:     1.0.6
 * Author:      Moon K
 * Author URI: https://github.com/moon0326
 * License:     GPL v2 or later
 * Requires PHP: 7.1
 * Description: WP OpenAPI outputs the OpenAPI 3.1.0 spec and provides a viewer for your WordPress REST APIs.
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

use WPOpenAPI\CLI\ExportAsHTML;
use WPOpenAPI\Filters;
use WPOpenAPI\Filters\AddCallbackInfoToDescription;
use WPOpenAPI\SchemaGenerator;
use WPOpenAPI\SettingsPage;
use WPOpenAPI\View;

require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'WP_OPENAPI_PLUGIN_PATH' ) ) {
	define( 'WP_OPENAPI_PLUGIN_PATH', __DIR__ );
}

new SettingsPage();

class WPOpenAPI {
	public static function pluginPath( $append = '' ) {
		return plugin_dir_path( __FILE__ ) . $append;
	}

	public static function pluginUrl( $path = null ) {
		return plugin_dir_url( __FILE__ ) . $path;
	}

	public function registerRoutes() {
		add_rewrite_tag( '%openapi%', '([^&]+)' );
		add_rewrite_rule( '^' . 'wp-json-openapi/?', 'index.php?openapi=schema', 'top' );
	}

	public function registerRestAPIEndpoint() {
		register_rest_route(
			'wp-openapi/v1',
			'schema',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'sendOpenAPISchema' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'namespace' => array(
						'type' => 'string',
					),
				),
			)
		);
	}

	private function getNamespace() {
		$namespace = 'all';

		$requestedNamespace = isset( $_GET['namespace'] ) ? str_replace( 'http://', '', sanitize_url( $_GET['namespace'], 'http' ) ) : null;
		$requestedPage      = isset( $_GET['page'] ) ? str_replace( 'http://', '', sanitize_url( $_GET['page'], 'http' ) ) : null;

		if ( $requestedNamespace ) {
			$namespace = $requestedNamespace;
		} elseif ( $requestedPage && str_contains( $requestedPage, 'wp-openapi/' ) ) {
			$namespace = str_replace( 'wp-openapi/', '', $requestedPage );
		}

		return $namespace;
	}

	public function addAdminMenu() {
		$logo     = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCI+PGcgdHJhbnNmb3JtPSJtYXRyaXgoLjU3MTM2MSAwIDAgLjU3MTM2MSAtMS43MTQwNDMgLTEuNzEzOTM1KSI+PHBhdGggZD0iTTMyLjYgNjcuNEgzLjA0NGwuMDE3LjQzMy4wNS44MzMuMDIyLjM1Ni4wOC45NjIuMDIuMjE1LjExNSAxLjA2NWExLjE2IDEuMTYgMCAwIDAgLjAxMS4xMDNsLjE1IDEuMTQ0Yy4zNDIgMi4zNy44NSA0LjcwNCAxLjUzIDYuOTk4bC4wMS4wMy4zNTMgMS4xNC4wMi4wNjMuMzI1Ljk2OC4wODYuMjQ0LjMwNC44MzcuMTQzLjM3OC4yNy43LjIxMy41Mi4yMjYuNTQuMy42NTguMTc1LjQuMzY4Ljc4Ny4xMjIuMjUyLjQ0My44OTUuMDcuMTM3LjU0IDEuMDI1LjExNS4yTDM0LjQ0NyA3NGMtLjk5Ni0yLjEtMS42MTYtNC4zMzItMS44NDYtNi42ek00LjQ0IDgwLjk2MmwuMDA4LjAyNy45NTQtLjN6IiBmaWxsPSIjYTdhYWFkIi8+PHBhdGggZD0iTTM5Ljg2NSA4MC45NzdsLTIwLjkgMjAuODkuMzIzLjI5OC44OTMuNzkyLjcxLjYwMi4xOTIuMTYuODEyLjY1NC4xMDUuMDgzLjg5NC42ODZhLjIzLjIzIDAgMCAxIC4wMzQuMDI1bDMuODk1IDIuNjMzLjE0LjA4NS43NjcuNDUzLjQ3LjI2OC40NDYuMjUuODE0LjQ0LjExMy4wNiAyLjMwMyAxLjExOCAxMS4yNDctMjcuMzJjLTEuMTQyLS42MS0yLjIzOC0xLjMzNC0zLjI3LTIuMTh6IiBmaWxsPSIjYTdhYWFkIi8+PHBhdGggZD0iTTM3LjY5NyA3OC45MDRsLS42NS0uNzY1LS41NC0uNjk3LS41NzgtLjgxOC0uNTQtLjg0My0yNS4yNzYgMTUuMjI1IDEuMzAyIDIuMDQ1LjEwNS4xNTQuMDA2LjAxIDEuMzU4IDEuOTE0LjAzLjA0LjAzLjA0LjYxNC44LjczNy45MTUuMTU4LjE5LjYwNC43MTMuMjY2LjMwMi41MjQuNi4yOTcuMzIyLjEzNS4xNDYuNC40Mi4xNi4xNjQuNy43MTYgMjAuODUtMjAuODUtLjY4My0uNzN6IiBmaWxsPSIjYTdhYWFkIi8+PHBhdGggZD0iTTY1LjMzNCA4MC45NjZsLS42OTcuNTQuMDUyLjA4NyAxNS4yMDIgMjUuMjM1IDIuMDgtMS40MzJjMS40Ny0xLjA4NSAyLjg5NS0yLjI2IDQuMjY3LTMuNTI3bC0yMC44MjItMjAuODJ6IiBmaWxsPSIjYTdhYWFkIi8+PGcgZmlsbD0iI2E3YWFhZCI+PHBhdGggZD0iTTc3Ljk3IDEwOC4wM2wuMDM0LS4wMi0uMDAxLS4wMDEtLjAzMy4wMnptLS4wMTUuMDFoLjAwMSAweiIvPjxwYXRoIGQ9Ik03OC4wMDMgMTA4bC4yLS4xLS4wMDQtLjAwNy0uMTk2LjExN3ptLS4wNDMuMDM1bC4wMS0uMDA1eiIvPjxwYXRoIGQ9Ik03Ny45NTUgMTA4LjA0aC4wMDFsLS4wMzctLjA2MnptLjAxNS0uMDFsLjAzNC0uMDE4LS4wMzQuMDE4em0tLjAyLjAxbC0uMDEuMDA1LjAxLS4wMDR6Ii8+PHBhdGggZD0iTTc3Ljk1IDEwOC4wNHYuMDAxbC4wMDUtLjAwM2gtLjAwMXoiLz48cGF0aCBkPSJNNzcuOTYgMTA4LjAzNWwtLjAwNi4wMDMuMDE1LS4wMXptLjA0NC0uMDIzbC4xOTgtLjExMi0uMTk4LjExMnptLjE5OC0uMTEybC0uMDA0LS4wMDd6bS0uMjY4LjE1aC4wMDJ6Ii8+PHBhdGggZD0iTTc3Ljk0MiAxMDguMDQ3bC0uMDA2LjAwMy4wMDYtLjAwM3oiLz48cGF0aCBkPSJNNzcuNjgyIDEwNy4wMzZsLTE0LjcwNi0yNC40LS44NzQuNDg3LS44OTYuNDVhMjAuMTUgMjAuMTUgMCAwIDEtOC42MzIgMS45NTMgMjAuMSAyMC4xIDAgMCAxLTUuNzQyLS44MzhsLS45NDUtLjMzMi0uOTQ0LS4zMzUtMTEuMjI2IDI3LjI2NS4wODIuMDMuMDg2LjAzM2guMDAxbC4wMjIuMDEgMi4xNTUuODI1YTQ5Ljk0IDQ5Ljk0IDAgMCAwIDEwLjEyIDIuNDIyIDE3LjgzIDE3LjgzIDAgMCAxIC40MDQuMDU0bDEuMjIyLjEzMy4xOTguMDE3Ljk4LjA4Mi4zMzMuMDIuODU4LjA1LjUyNi4wMi42ODIuMDI0IDEuMjE4LjAxNWE0OS43NiA0OS43NiAwIDAgMCA4LjIzMy0uNjg2bC4xMzgtLjAyLjg2NC0uMTU3LjUtLjA5NS41MDUtLjEwNi44NDItLjE4NS4xNTItLjAzNWE0OS44NSA0OS44NSAwIDAgMCAxMS44OTctNC40MzZsLjAwMS0uMDAxLjczLS40IDEuNDgzLS44MzYtLjAyNC0uMDcuMDM3LjA2My4wNDgtLjAyOC4xOTUtLjExN3pNMzkuODc2IDQ5Ljg1MmwuNjk3LS41NC0uMDUyLS4wODdMMjUuMzIgMjRsLTIuMDg1IDEuNDM2Yy0xLjQ3IDEuMDgyLTIuODkzIDIuMjU3LTQuMjYyIDMuNTIyTDM5Ljc5NSA0OS43N3oiLz48L2c+PHBhdGggZD0iTTE3LjUzIDMwLjMzNGwtMS4zODQgMS40NDVjLTEuNTM4IDEuNjY1LTIuOTQ0IDMuNC00LjIxMiA1LjIybC0uMTgyLjI2LS40MjMuNjI1LS40My42NTYtLjE1Ni4yNGE0OS40OCA0OS40OCAwIDAgMC03LjcgMjQuNjI3bC0uMDI3IDEtLjAxNyAxaDI5LjQ4OGwuMDQ4LTEgLjA1NS0xYTIwLjAxIDIwLjAxIDAgMCAxIDQuNDQ3LTEwLjc0Yy4yLS4yNTYuNDQ3LS40OTYuNjcyLS43NDNzLjQzMi0uNTA0LjY3LS43NDJ6bTYwLjg0My03LjMybC0uMTIyLS4wNzMtLjc4My0uNDY1LS40NTctLjI2LS40Ni0uMjU2LS44LS40MzItLjEyNi0uMDY2Yy0xLjczLS45MDQtMy41MDUtMS43LTUuMzE4LTIuNGwtLjE0LS4wNTQtMS4yLS40MzNjLTMuMjItMS4xMjUtNi41NTItMS45MTQtOS45MzYtMi4zNThsLS40MTUtLjA1Ni0uNDE1LS4wNDYtLjgtLjA4Ny0uMjI0LS4wMi0uOTUzLS4wOC0xLjItLjA3My0uNDM0LS4wMTd2MjkuNTZjMS41MTcuMTU3IDMuMDIuNDggNC40NzguOTc1TDgwLjgzIDI0LjYzYTQ4Ljk3IDQ4Ljk3IDAgMCAwLTIuNDY0LTEuNjE0eiIgZmlsbD0iI2E3YWFhZCIvPjxwYXRoIGQ9Ik0yNy41MyAyMy43OGwtLjUxNi0uODU3em0tLjUzLS44NmwuMDAzLjAwNXptMjQuNjA2LTcuMDk1bC0xIC4wMmMtMi4wODYuMDg0LTQuMTY4LjI5Ny02LjIzMy42NDNsLS4xMzYuMDIzLS44NjUuMTU3LS41LjA5NC0uNTA4LjEwNi0uODQuMTg0LS4xNTMuMDM0YTQ5Ljg5IDQ5Ljg5IDAgMCAwLTExLjg5OCA0LjQzOGwtMi40NyAxLjM5NC4wMDMuMDA1IDE1LjIyIDI1LjI3Ljg3NC0uNDg3YTE5LjcxIDE5LjcxIDAgMCAxIC44OTUtLjQ1MSAyMC4wNSAyMC4wNSAwIDAgMSA2LjYwMS0xLjg1bDEtLjA4IDEtLjAzNC4wMDEtMjkuNDg3Yy0uMzM0LS4wMDEtLjY2Ny4wMTMtMSAuMDJ6IiBmaWxsPSIjYTdhYWFkIi8+PHBhdGggZD0iTTEwMi4xNSA2Mi45N2wtLjA0OC0uNzk1LS4wMjYtLjM5Ny0uMDc3LS45MzQtLjAyLS4yNDItLjExLTEuMDQzLS4wMS0uMDg0LS4wMDUtLjA0LS4xNS0xLjEyNGMtLjAwMS0uMDEtLjAwMi0uMDI1LS4wMDUtLjAzNWE0OS41NiA0OS41NiAwIDAgMC0xLjUyNC02Ljk3MmwtLjAxNC0uMDQ0LS4zMjgtMS4wNjMtLjA0NS0uMTQtLjMyLS45NTQtLjA5LS4yNTgtLjMtLjgyMy0uMTUtLjM5LS4yNjUtLjY3OC0uMjE4LS41MzQtLjIyLS41MjYtLjI5NC0uNjctLjE3LS4zNzgtLjM3My0uNzk3LS4xMTYtLjI0Mi0uNDQ4LS45MDYtLjA2NS0uMTI1LS41Mi0xYy0uMDA3LS4wMS0uMDEyLS4wMjQtLjAyLS4wMzUtLjg2LTEuNTgtMS44MDItMy4xMDgtMi44MjItNC41NzhMNzEuNjMyIDU4LjkzYTIwLjUgMjAuNSAwIDAgMSAuOTc0IDQuNDc3aDI5LjU2bC0uMDE2LS40Mzd6IiBmaWxsPSIjYTdhYWFkIi8+PHBhdGggZD0iTTcyLjcyMiA2NS40MDhsLS4wNDggMS0uMDU1IDFjLS4zOCAzLjg0LTEuODYzIDcuNi00LjQ0NiAxMC43NC0uMi4yNTYtLjQ0Ny40OTYtLjY3Mi43NDNzLS40MzIuNTA0LS42Ny43NDJsMjAuODUgMjAuODVjLjIzNy0uMjM3LjQ2LS40ODIuNjktLjcyM2wuNjktLjcyM2MxLjU0My0xLjY3IDIuOTUzLTMuNDIgNC4yMjQtNS4yMzZsLjE0OC0uMjE0LjQ1OC0uNjczLjM5NS0uNjA1LjItLjI5NWM0Ljc4Mi03LjUxIDcuMzQtMTYuMDIgNy42ODUtMjQuNjA3bC4wMjctMSAuMDE3LTFINzIuNzIyeiIgZmlsbD0iI2E3YWFhZCIvPjxwYXRoIGQ9Ik0xMTAuOTY3IDcuMDQ2Yy01LjM5NS01LjM5NS0xNC4xNDItNS4zOTUtMTkuNTM2IDAtNC4zMDQgNC4zMDMtNS4xNjQgMTAuNzM2LTIuNiAxNS45MDJMNTguNzQgNTMuMDRjLTUuMTY2LTIuNTYyLTExLjYtMS43MDMtMTUuOTAzIDIuNmExMy44MiAxMy44MiAwIDAgMCAwIDE5LjUzNyAxMy44MiAxMy44MiAwIDAgMCAxOS41MzgtLjAwMWM0LjMwMy00LjMwMyA1LjE2Mi0xMC43MzYgMi42LTE1LjkwM2wzMC4wOS0zMC4wOWM1LjE2NyAyLjU2MiAxMS42IDEuNzAzIDE1LjkwMi0yLjYgNS4zOTYtNS4zOTMgNS4zOTYtMTQuMTQuMDAxLTE5LjUzNXoiIGZpbGw9IiNhN2FhYWQiLz48L2c+PC9zdmc+';
		$slug     = 'wp-openapi';
		$callback = function () {
			$this->enqueueScritps();
			echo "<div id='elements-app'></div>";
		};

		add_menu_page( 'WP OpenAPI', 'WP OpenAPI', 'read', $slug, $callback, $logo );
		add_submenu_page( $slug, 'All', 'All', 'read', $slug, $callback );

		foreach ( rest_get_server()->get_namespaces() as $namespace ) {
			if (strpos($namespace, 'wp-openapi') === 0) {
				continue;
			}
			add_submenu_page(
				$slug,
				$namespace,
				$namespace,
				'read',
				$slug . '/' . $namespace,
				$callback
			);
		}
	}

	public function getAssetInfo( $name = '' ) {
		global $wp_version;
		$info = array(
			'dependencies' => array(),
			'version'      => $wp_version,
		);

		$file = self::pluginPath( $name . '.asset.php' );
		if ( is_readable( $file ) ) {
			$info = include $file;
		}

		return $info;
	}

	public function sendOpenAPISchema() {
		global $wp_version;

		$siteInfo = array(
			'admin_email'     => get_option( 'admin_email' ),
			'blogname'        => get_option( 'blogname' ),
			'blogdescription' => get_option( 'blogdescription' ),
			'siteurl'         => get_option( 'siteurl' ),
			'wp_version'      => $wp_version,
		);

		$restServer = rest_get_server();
		$hooks      = Filters::getInstance();

		if ( SettingsPage::getOption( 'enableCallbackDiscovery' ) === 'on' ) {
			new AddCallbackInfoToDescription( $hooks, new View( 'callback' ), $restServer->get_routes() );
		}

		$schemaGenerator = new SchemaGenerator( $hooks, $siteInfo, $restServer );
		wp_send_json( $schemaGenerator->generate( $this->getNamespace() ) );
	}

	public function enqueueScritps() {
		$infoCss = $this->getAssetInfo( 'build/resources/css/app' );
		$infoJs  = $this->getAssetInfo( 'build/resources/scripts/app' );

		wp_enqueue_style(
			'wp-openapi-scss',
			self::pluginUrl( 'build/resources/css/app.css' ),
			$infoCss['dependencies'],
			$infoCss['version']
		);
		wp_enqueue_style(
			'wp-openapi-js-css',
			self::pluginUrl( 'build/resources/scripts/app.css' ),
			array(),
			$infoJs['version']
		);
		wp_enqueue_script(
			'wp-openapi-js',
			self::pluginUrl( 'build/resources/scripts/app.js' ),
			$infoJs['dependencies'],
			$infoJs['version']
		);

		$permalink_structure = get_option( 'permalink_structure' );
		$namespace           = $this->getNamespace();
		if ( $permalink_structure === '' ) {
			$endpoint = site_url( '?rest_route=/wp-openapi/v1/schema&namespace=' . $namespace );
		} else {
			$endpoint = site_url( '/wp-json-openapi?namespace=' . $namespace );
		}

		$data = array(
			'options'  => array(
				'hideTryIt' => SettingsPage::getOption( 'enableTryIt' ) === null,
			),
			'endpoint' => $endpoint,
		);

		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			$data['nonce'] = wp_create_nonce( 'wp_rest' );
		} else {
			$data['options']['hideTryIt'] = true;
		}

		$data = apply_filters( 'wp-openapi-filters-elements-props', $data );

		wp_localize_script( 'wp-openapi-js', 'wpOpenApi', $data );
	}
}

$wpOpenAPI = new WPOpenAPI();

register_activation_hook(
	__FILE__,
	function () use ( $wpOpenAPI ) {
		update_option( 'wp-openapi-rewrite-flushed', false );
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'admin_menu', array( $wpOpenAPI, 'addAdminMenu' ) );
add_action(
	'init',
	function() use ( $wpOpenAPI ) {
		$wpOpenAPI->registerRoutes();

		if ( !get_option( 'wp-openapi-rewrite-flushed' ) ) {
			flush_rewrite_rules();
			update_option( 'wp-openapi-rewrite-flushed', true );
		}
	}
);
add_action( 'rest_api_init', array( $wpOpenAPI, 'registerRestAPIEndpoint' ) );
add_action(
	'wp',
	function () use ( $wpOpenAPI ) {
		if ( get_query_var( 'openapi' ) === 'schema' ) {
			$wpOpenAPI->sendOpenAPISchema();
		}
	}
);

add_action(
	'cli_init',
	function () {
		WP_CLI::add_command(
			'openapi export',
			function ( $args, $assoc_args ) {
				$namespace = $assoc_args['namespace'];
				$saveTo    = $assoc_args['save_to'];
				$command   = new ExportAsHTML();
				$command->execute( $namespace, $saveTo );
			},
			array(
				'synopsis' => array(
					array(
						'name'     => 'namespace',
						'type'     => 'assoc',
						'optional' => false,
					),
					array(
						'name'     => 'save_to',
						'type'     => 'assoc',
						'optional' => false,
					),
				),
			)
		);
	}
);
