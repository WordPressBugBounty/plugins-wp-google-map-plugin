<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGMP_Elementor_Map_Widget extends \Elementor\Widget_Base {

	public function get_name()       { return 'wpgmp_map'; }
	public function get_title()      { return __( 'WP Maps', 'wp-google-map-plugin' ); }
	public function get_icon()       { return 'eicon-google-maps'; }
	public function get_categories() { return array( 'general' ); }
	public function get_keywords()   { return array( 'map', 'google', 'location' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_map', array(
			'label' => __( 'Map', 'wp-google-map-plugin' ),
		) );

		$this->add_control( 'map_id', array(
			'label'   => __( 'Choose Map', 'wp-google-map-plugin' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => array( 0 => __( '— Select a map —', 'wp-google-map-plugin' ) ) + wpgmp_builders_get_maps(),
			'default' => '0',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( empty( $settings['map_id'] ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Please select a map.', 'wp-google-map-plugin' ) . '</p>';
			}
			return;
		}

		$in_editor = \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();

		if ( $in_editor ) {
			// Live preview: real map (with its scripts) inside an iframe.
			echo wpgmp_builders_preview_iframe( $settings['map_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}
		echo wpgmp_builders_render( $settings['map_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}