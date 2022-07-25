<?php

namespace WPOpenAPI;

class CallbackFinder {

	private array $routes;
	private array $composerLoaders = array();

	public function __construct( $routes ) {
		$this->routes = $routes;
		$this->loadComposerLoaders();
	}

	private function loadComposerLoaders() {
		$plugins = array_filter( glob( WP_PLUGIN_DIR . '/*' ), 'is_dir' );
		foreach ( $plugins as $plugin ) {
			$autoloadPath = $plugin . '/vendor/autoload.php';
			if ( is_file( $autoloadPath ) ) {
				$this->composerLoaders[] = require $autoloadPath;
			}
		}
	}

	private function findFilePath( $classname ) {
		foreach ( $this->composerLoaders as $composerLoader ) {
			$filepath = $composerLoader->findFile( $classname );
			if ( $filepath ) {
				return str_replace( WP_PLUGIN_DIR, '', realpath( $filepath ) );
			}
		}

		return null;
	}

	/**
	 * @param string $path
	 * @return array An array of Callback object
	 */
	public function find( string $path ): array {
		// Remove escape char
		$path = trim( $path, '\\' );

		$callbacks = array();

		foreach ( $this->routes as $route => $handlers ) {
			$match = preg_match( '@^' . $route . '$@i', $path );

			if ( ! $match ) {
				continue;
			}

			foreach ( $handlers as $handler ) {
				$filepath = null;
				if ( is_string( $handler['callback'] ) ) {
					$callable = array( $handler['callback'] );
					$filepath = '';
				} elseif ( is_array( $handler['callback'] ) ) {
					if ( is_object( $handler['callback'][0] ) ) {
						$handler['callback'][0] = get_class( $handler['callback'][0] );
					}
					$callable = array( $handler['callback'][0], $handler['callback'][1] );
					$filepath = $this->findFilePath( $handler['callback'][0] );
				} else {
					break;
				}

				foreach ( $handler['methods'] as $handlerMethod => $bool ) {
					$callbacks[ strtolower( $handlerMethod ) ] = new Callback( $callable, $filepath );
				}
			}
			break;
		}
		return $callbacks;
	}
}
