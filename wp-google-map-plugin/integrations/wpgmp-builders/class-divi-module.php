<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'ET_Builder_Module' ) ) {

	class WPGMP_Divi_Map_Module extends ET_Builder_Module {

		public $slug       = 'wpgmp_divi_map';
		public $vb_support = 'partial'; // shows a placeholder in Visual Builder; use 'on' + React for live preview

		protected $module_credits = array(
			'module_uri' => '',
			'author'     => 'Flipper Code',
			'author_uri' => '',
		);

		public function init() {
			$this->name = esc_html__( 'WP Maps', 'wp-google-map-plugin' );
		}

		public function get_fields() {
			return array(
				'map_id' => array(
					'label'           => esc_html__( 'Choose Map', 'wp-google-map-plugin' ),
					'type'            => 'select',
					'option_category' => 'basic_option',
					'options'         => array( '' => esc_html__( '— Select a map —', 'wp-google-map-plugin' ) ) + wpgmp_builders_get_maps(),
					'default'         => '',
					'toggle_slug'     => 'main_content',
					'description'     => esc_html__( 'Select the map to display.', 'wp-google-map-plugin' ),
				),
			);
		}

		public function render( $attrs, $content = null, $render_slug = '' ) {
			$map_id = isset( $this->props['map_id'] ) ? $this->props['map_id'] : '';
			if ( ! $map_id ) {
				return '<p>' . esc_html__( 'Please select a map.', 'wp-google-map-plugin' ) . '</p>';
			}

			// Visual Builder (incl. its ajax module renders): live preview inside an iframe.
			$action  = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$in_vb   = ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() )
				|| ( wp_doing_ajax() && 0 === strpos( $action, 'et_fb' ) );

			if ( $in_vb ) {
				return wpgmp_builders_preview_iframe( $map_id );
			}
			return wpgmp_builders_render( $map_id );
		}
	}
}