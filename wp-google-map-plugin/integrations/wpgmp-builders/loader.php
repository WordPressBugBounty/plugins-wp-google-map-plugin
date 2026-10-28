<?php
/**
 * WP Maps – Builder integrations (Gutenberg, Elementor, Divi).
 * Include from the main plugin file:
 *   require_once plugin_dir_path( __FILE__ ) . 'wpgmp-builders/loader.php';
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPGMP_BUILDERS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Shared helper: returns [ map_id => map_title ].
 * VERIFY table/column names against your plugin's schema.
 */
function wpgmp_builders_get_maps() {
	global $wpdb;
	$table = $wpdb->prefix . 'create_map';
	$rows  = $wpdb->get_results( "SELECT map_id, map_title FROM {$table} ORDER BY map_id DESC" );
	$maps  = array();
	if ( $rows ) {
		foreach ( $rows as $r ) {
			$maps[ (int) $r->map_id ] = $r->map_title ? $r->map_title : 'Map #' . $r->map_id;
		}
	}
	return $maps;
}

/**
 * Shared renderer: calls the plugin's own shortcode callback directly.
 * Returns an HTML comment when nothing is produced, so you can debug in "view source".
 */
function wpgmp_builders_render( $map_id ) {
	$map_id = absint( $map_id );
	if ( ! $map_id ) {
		return '';
	}

	// wpgmp_show_location_in_map is a class method, so go through the registered shortcode.
	$html = do_shortcode( '[put_wpgm id="' . $map_id . '"]' );

	if ( '' === trim( (string) $html ) ) {
		return '<!-- WPGMP builder: map ' . $map_id . ' rendered empty output -->';
	}
	return $html;
}

/* ---------- Gutenberg ---------- */
add_action( 'init', function () {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type( 'wpgmp/map', array(
		'attributes'      => array(
			'mapId' => array( 'type' => 'number', 'default' => 0 ),
		),
		'render_callback' => function ( $atts ) {
			if ( empty( $atts['mapId'] ) ) {
				return '';
			}
			$map_id = absint( $atts['mapId'] );

			// Inside the_content: return the shortcode text so it is processed like a normal shortcode.
			if ( doing_filter( 'the_content' ) ) {
				return '[put_wpgm id="' . $map_id . '"]';
			}

			// Anywhere else (widgets, block templates, etc.): render directly.
			return wpgmp_builders_render( $map_id );
		},
	) );
} );

add_action( 'enqueue_block_editor_assets', function () {
	$options = array( array( 'value' => 0, 'label' => __( '— Select a map —', 'wp-google-map-plugin' ) ) );
	foreach ( wpgmp_builders_get_maps() as $id => $title ) {
		$options[] = array( 'value' => $id, 'label' => $title );
	}

	// 'before' on wp-blocks guarantees it exists before any script that registers the block.
	wp_add_inline_script(
		'wp-blocks',
		'window.wpgmpBlockData = ' . wp_json_encode( array( 'maps' => $options ) ) . ';',
		'before'
	);
} );

/* ---------- Elementor ---------- */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once WPGMP_BUILDERS_DIR . 'class-elementor-widget.php';
	$widgets_manager->register( new WPGMP_Elementor_Map_Widget() );
} );

/* ---------- Divi ---------- */
add_action( 'et_builder_ready', function () {
	require_once WPGMP_BUILDERS_DIR . 'class-divi-module.php';
	new WPGMP_Divi_Map_Module();
} );

/* ---------- Live preview (shared by Gutenberg, Elementor and Divi) ----------
 * The preview is a real URL on this site (not srcdoc), so Google's referrer/API-key
 * restrictions see the site's own domain. The URL carries a signed, expiring token, so it
 * works without login cookies (no domain/www/https cookie problems).
 */

function wpgmp_builders_sign( $map_id, $exp ) {
	return hash_hmac( 'sha256', absint( $map_id ) . '|' . absint( $exp ), wp_salt( 'auth' ) );
}

/** Signed preview URL; stable within the same hour so iframes don't reload needlessly. */
function wpgmp_builders_preview_url( $map_id ) {
	$map_id = absint( $map_id );
	$exp    = ( (int) floor( time() / HOUR_IN_SECONDS ) + 2 ) * HOUR_IN_SECONDS;

	return add_query_arg( array(
		'wpgmp_builder_preview' => $map_id,
		'exp'                   => $exp,
		'sig'                   => wpgmp_builders_sign( $map_id, $exp ),
	), home_url( '/' ) );
}

/** Iframe markup for builders that render PHP output (Elementor, Divi). */
function wpgmp_builders_preview_iframe( $map_id ) {
	$iframe = '<iframe title="WP Map preview" scrolling="no" style="width:100%;height:450px;border:0;display:block;pointer-events:none" src="'
		. esc_url( wpgmp_builders_preview_url( $map_id ) ) . '"></iframe>';

	// Resize listener (works when scripts in the output are executed, e.g. Elementor).
	$listener = '<script>(function(){if(window.__wpgmpMsg)return;window.__wpgmpMsg=1;'
		. 'window.addEventListener("message",function(e){if(!e.data||!e.data.wpgmpHeight)return;'
		. 'var f=document.getElementsByTagName("iframe");for(var i=0;i<f.length;i++){'
		. 'if(f[i].contentWindow===e.source){f[i].style.height=e.data.wpgmpHeight+"px";}}});})();</script>';

	return $iframe . $listener;
}

/** Serves the preview page itself: a minimal frontend page containing only the map. */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['wpgmp_builder_preview'] ) ) {
		return;
	}

	$map_id = absint( wp_unslash( $_GET['wpgmp_builder_preview'] ) );
	$exp    = isset( $_GET['exp'] ) ? absint( wp_unslash( $_GET['exp'] ) ) : 0;
	$sig    = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';

	if ( ! $map_id || $exp < time() || ! hash_equals( wpgmp_builders_sign( $map_id, $exp ), $sig ) ) {
		wp_die( esc_html__( 'Preview link is invalid or has expired. Please reload the editor.', 'wp-google-map-plugin' ), '', array( 'response' => 403 ) );
	}

	show_admin_bar( false );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );

	// Render first so scripts/styles enqueued by the shortcode are printed by wp_head/wp_footer.
	$map_html = wpgmp_builders_render( $map_id );
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<style>html,body{margin:0!important;padding:0!important;background:#fff!important;overflow:hidden}</style>
<script>window.wpgmpBuilderPreview=true;</script>
<?php wp_head(); ?>
</head>
<body>
<?php echo $map_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php wp_footer(); ?>
<script>
(function(){
	function r(){
		var h=Math.max(document.body.scrollHeight,document.body.offsetHeight);
		if(!h){return;}
		try{window.frameElement.style.height=h+"px";}catch(e){}
		try{window.parent.postMessage({wpgmpHeight:h},"*");}catch(e){}
	}
	window.addEventListener("load",function(){
		r();setTimeout(r,500);setTimeout(r,1500);setTimeout(r,3000);
		if(window.ResizeObserver){new ResizeObserver(r).observe(document.body);}
	});
})();
</script>
</body>
</html>
	<?php
	exit;
} );

/** Gutenberg editor: REST route that returns the signed preview URL. */
add_action( 'rest_api_init', function () {
	register_rest_route( 'wpgmp/v1', '/markup/(?P<map_id>\\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
		'callback'            => function ( WP_REST_Request $request ) {
			$map_id = absint( $request['map_id'] );
			if ( ! $map_id || ! isset( wpgmp_builders_get_maps()[ $map_id ] ) ) {
				return new WP_Error( 'wpgmp_map_not_found', 'Map not found.', array( 'status' => 404 ) );
			}
			return rest_ensure_response( array(
				'map_id' => $map_id,
				'url'    => wpgmp_builders_preview_url( $map_id ),
			) );
		},
	) );
} );