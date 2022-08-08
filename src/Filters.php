<?php

namespace WPOpenAPI;

use WPOpenAPI\Spec\Info;
use WPOpenAPI\Spec\Operation;
use WPOpenAPI\Spec\Path;
use WPOpenAPI\Spec\Tag;

class Filters {

	const PREFIX = 'wp-openapi-';

	private static ?Filters $instance = null;

	public static function getInstance(): Filters {
		if ( static::$instance === null ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @param $callback
	 * @param int $priority
	 * @return void
	 */
	public function addOperationsFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-operations', $callback, $priority, 2 );
	}

	/**
	 * @param Operation[] $operations
	 * @param array $args
	 * @return Operation[]
	 */
	public function applyOperationsFilters( array $operations, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-operations', $operations, $args );
	}

	public function addPathsFilter( $callback, $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-paths', $callback, $priority, 2 );
	}

	/**
	 * @param Path[] $paths
	 * @param array  $args
	 * @return Path[]
	 */
	public function applyPathsFilters( array $paths, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-paths', $paths, $args );
	}

	/**
	 * @param $callback
	 * @param int      $priority
	 * @return void
	 */
	public function addServersFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-servers', $callback, $priority, 2 );
	}

	/**
	 * @param array $servers
	 * @param array $args
	 * @return array
	 */
	public function applyServersFilters( array $servers, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-servers', $servers, $args );
	}

	/**
	 * @param $callback
	 * @param int      $priority
	 * @return void
	 */
	public function addInfoFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-info', $callback, $priority, 2 );
	}

	/**
	 * @param Info  $info
	 * @param array $args
	 * @return Info
	 */
	public function applyInfoFilters( Info $info, array $args = array() ): Info {
		return apply_filters( self::PREFIX . 'filter-info', $info, $args );
	}

	/**
	 * @param array $components
	 * @param array $args
	 * @return array
	 */
	public function applyComponentsFilters( array $components, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-components', $components, $args );
	}

	/**
	 * @param $callback
	 * @param int      $priority
	 * @return bool|true|void
	 */
	public function addComponentsFilter( $callback, int $priority = 10 ) {
		return add_filter( self::PREFIX . 'filter-components', $callback, $priority, 2 );
	}

	/**
	 * @param array $security
	 * @param array $args
	 * @return array
	 */
	public function applySecurityFilters( array $security, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-security', $security, $args );
	}

	/**
	 * @param $callback
	 * @param int      $priority
	 * @return bool|true|void
	 */
	public function addSecurityFilter( $callback, int $priority = 10 ) {
		return add_filter( self::PREFIX . 'filter-security', $callback, $priority, 2 );
	}

	/**
	 * @param Tag[] $tags
	 * @param array $args
	 * @return Tag[]
	 */
	public function applyTagsFilters( array $tags, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-tags', $tags, $args );
	}

	/**
	 * @param $callback
	 * @param int      $priority
	 * @return bool|true|void
	 */
	public function addTagsFilter( $callback, int $priority = 10 ) {
		return add_filter( self::PREFIX . 'filter-tags', $callback, $priority, 2 );
	}
}
