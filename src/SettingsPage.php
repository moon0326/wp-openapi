<?php

namespace WPOpenAPI;

class SettingsPage {

	const PAGE_ID   = 'wp-oepnapi-settings';
	const GROUP_ID  = 'wp-openapi';
	const OPTION_ID = 'wp-openapi-options';

	private string $sectionId;

	public function __construct() {
		$this->sectionId = self::PAGE_ID . '-section';
		$this->initActions();
	}

	public static function getOption( $name ) {
		$options = get_option( self::OPTION_ID );
		return $options[ $name ] ?? null;
	}

	private function addTryItOption() {
		add_settings_field(
			'wp-openapi-settings-tryit',
			'Enable Try It',
			function() {
				$options = get_option( self::OPTION_ID );
				$checked = isset( $options['enableTryIt'] ) && $options['enableTryIt'] === 'on';
				echo ( new View( 'settings-tryit' ) )->render( array( 'checked' => $checked ) );
			},
			self::PAGE_ID,
			$this->sectionId
		);
	}

	private function addDiscoveryOption() {
		add_settings_field(
			'wp-openapi-settings-discovery',
			'Enable Callback Discovery',
			function() {
				$options = get_option( self::OPTION_ID );
				$checked = isset( $options['enableCallbackDiscovery'] ) && $options['enableCallbackDiscovery'] === 'on';
				echo ( new View( 'settings-discovery' ) )->render( array( 'checked' => $checked ) );
			},
			self::PAGE_ID,
			$this->sectionId
		);
	}

	private function initActions() {
		add_action(
			'plugin_action_links_wp-openapi/wp-openapi.php',
			function( $links ) {
				// Build and escape the URL.
				$url = esc_url(
					add_query_arg(
						'page',
						'wp-openapi-settings',
						get_admin_url() . 'admin.php'
					)
				);
				// Create the link.
				$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
				// Adds the link to the end of the array.
				$links[] = $settings_link;
				return $links;
			}
		);

		add_action(
			'admin_menu',
			function() {
				$title = 'WP OpenAPI';
				add_options_page(
					$title,
					$title,
					'manage_options',
					self::PAGE_ID,
					function() {
						echo ( new View( 'settings' ) )->render(
							array(
								'groupId' => self::GROUP_ID,
								'pageId'  => self::PAGE_ID,
							)
						);
					}
				);
			}
		);

		add_action(
			'admin_init',
			function() {
				register_setting( self::GROUP_ID, self::OPTION_ID );
				add_settings_section( $this->sectionId, '', function(){}, self::PAGE_ID );
				$this->addTryItOption();
				$this->addDiscoveryOption();
			}
		);
	}
}
