<?php
/**
 * WP Maps Review Notice
 *
 * @package WP_Maps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGMP_Review_Notice {

	/**
	 * Option names.
	 */
	const ACTIVATION_OPTION = 'wpgmp_first_activation_time';
	const STATUS_OPTION     = 'wpgmp_review_notice_status';
	const REMINDER_OPTION   = 'wpgmp_review_notice_next';

	/**
	 * Review URL.
	 */
	const REVIEW_URL = 'https://wordpress.org/support/plugin/wp-google-map-plugin/reviews/';

	/**
	 * Number of days before first review notice.
	 */
	const NOTICE_DAYS = 10;

	/**
	 * Number of days for "Remind Me Later".
	 */
	const REMINDER_DAYS = 7;

	/**
	 * Initialize.
	 */
	public static function init() {

		// Handle notice actions.
		add_action( 'admin_init', array( __CLASS__, 'wpgmp_handle_review_actions' ) );

		// Display the notice.
		add_action( 'admin_notices', array( __CLASS__, 'wpgmp_review_display_notice' ) );
	}


	/**
	 * Handle notice actions.
	 */
	public static function wpgmp_handle_review_actions() {

		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    return;
		}

		if ( empty( $_GET['wpgmp_review_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET['wpgmp_review_action'] ) );

		$allowed_actions = array(
			'remind',
			'dismiss',
			'review',
		);

		if ( ! in_array( $action, $allowed_actions, true ) ) {
			return;
		}

		// Verify nonce.
		if (
			empty( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
				'wpgmp_review_notice_action'
			)
		) {
			return;
		}



		switch ( $action ) {

			/**
			 * Remind after 7 days.
			 */
			case 'remind':

				update_option(
					self::REMINDER_OPTION,
					time() + ( self::REMINDER_DAYS * DAY_IN_SECONDS ),
					false
				);

				// Remove any permanent status.
				delete_option( self::STATUS_OPTION );

				break;

			/**
			 * Never show again.
			 */
			case 'dismiss':

				update_option(
					self::STATUS_OPTION,
					'dismissed',
					false
				);

				// Remove reminder if it exists.
				delete_option( self::REMINDER_OPTION );

				break;

			/**
			 * User clicked Leave a Review.
			 *
			 * We permanently hide the notice and then redirect
			 * the user to WordPress.org.
			 */
			case 'review':

				update_option(
					self::STATUS_OPTION,
					'reviewed',
					false
				);

				delete_option( self::REMINDER_OPTION );

				wp_redirect( self::REVIEW_URL );
				exit;

				break;
		}

		/**
		 * Remove our action parameters from the URL.
		 */
		if ( ! wp_doing_ajax() ) {

			$redirect_url = remove_query_arg(
				array(
					'wpgmp_review_action',
					'_wpnonce',
				)
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Check whether the current page belongs to WP Maps.
	 */
	private static function is_wpgmp_admin_page() {

		
		if ( ! is_admin() ) {
			return false;
		}

		$allowed_pages = array(
			'wpgmp_view_overview',
			'wpgmp_form_group_map',
			'wpgmp_manage_group_map',
			'wpgmp_form_location',
			'wpgmp_manage_location',
			'wpgmp_import_location',
			'wpgmp_form_map',
			'wpgmp_manage_map',
			'wpgmp_form_route',
			'wpgmp_manage_drawing',
			'wpgmp_manage_permissions',
			'wpgmp_manage_settings',
			'wpgmp_manage_tools',
			'wpgmp_manage_extentions',
			'wpgmp_form_integration',
		);

		if ( empty( $_GET['page'] ) ) {
			return false;
		}

		$page = sanitize_key(
			wp_unslash( $_GET['page'] )
		);

		$is_wpgmp_page = in_array(
			$page,
			$allowed_pages,
			true
		);

		return (bool) apply_filters(
			'wpgmp_review_notice_is_plugin_page',
			$is_wpgmp_page,
			$page
		);
	}

	/**
	 * Determine whether notice should be displayed.
	 */
	private static function wpgmp_review_should_display() {

		/**
		 * Only WP Maps admin pages.
		 */
		if ( ! self::is_wpgmp_admin_page() ) {
			return false;
		}

		/**
		 * Only users who can manage WP Maps.
		 *
		 * If your plugin uses a custom capability, replace
		 * 'manage_options' with your plugin capability.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		/**
		 * Permanently dismissed or already reviewed.
		 */
		$status = get_option( self::STATUS_OPTION, '' );

		if ( in_array( $status, array( 'dismissed', 'reviewed' ), true ) ) {
			return false;
		}

		/**
		 * First activation timestamp.
		 */
		$first_activation = (int) get_option(
			self::ACTIVATION_OPTION,
			0
		);

		if ( ! $first_activation ) {
			return false;
		}

		/**
		 * Wait 10 days after first activation.
		 */
		$notice_after = $first_activation + (
			self::NOTICE_DAYS * DAY_IN_SECONDS
		);

		if ( time() < $notice_after ) {
			return false;
		}

		/**
		 * Check "Remind Me Later".
		 */
		$reminder_time = (int) get_option(
			self::REMINDER_OPTION,
			0
		);

		if ( $reminder_time && time() < $reminder_time ) {
			return false;
		}

		return true;
	}

	/**
	 * Display admin notice.
	 */
	public static function wpgmp_review_display_notice() {

		if ( ! self::wpgmp_review_should_display() ) {
			return;
		}

		$review_url = self::REVIEW_URL;

		$remind_url = wp_nonce_url(
			add_query_arg(
				array(
					'wpgmp_review_action' => 'remind',
				)
			),
			'wpgmp_review_notice_action'
		);

		$dismiss_url = wp_nonce_url(
			add_query_arg(
				array(
					'wpgmp_review_action' => 'dismiss',
				)
			),
			'wpgmp_review_notice_action'
		);
		?>

		<div class="notice notice-info wpgmp-review-notice">

			<h3 style="margin-bottom:8px;">
				<?php esc_html_e( 'Enjoying WP Maps?', 'wp-google-map-plugin' ); ?>
			</h3>

			<p>
				<?php
				echo esc_html__(
					'If you find WP Maps useful, we would really appreciate it if you could take a moment to leave us a review on WordPress.org. Your feedback helps us improve the plugin and helps other WordPress users discover WP Maps.',
					'wp-google-map-plugin'
				);
				?>
			</p>

			<p>

				<a
					href="<?php echo esc_url( $review_url ); ?>"
					class="button button-primary"
					target="_blank"
					rel="noopener noreferrer"
				>
					<?php esc_html_e( 'Leave a Review', 'wp-google-map-plugin' ); ?>
				</a>

				<a
					href="<?php echo esc_url( $remind_url ); ?>"
					class="button"
				>
					<?php esc_html_e( 'Remind Me Later', 'wp-google-map-plugin' ); ?>
				</a>

				<a
					href="<?php echo esc_url( $dismiss_url ); ?>"
					class="button"
				>
					<?php esc_html_e( 'Don’t Show Again', 'wp-google-map-plugin' ); ?>
				</a>

			</p>

		</div>

		<?php
	}
}

/**
 * Initialize review notice.
 */
WPGMP_Review_Notice::init();