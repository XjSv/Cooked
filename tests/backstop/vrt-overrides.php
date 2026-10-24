<?php
/**
 * Request-scoped Cooked setting overrides for BackstopJS (DDEV only).
 *
 * @package Cooked
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this request may apply visual-test overrides.
 *
 * @return bool
 */
function cooked_vrt_request_allowed() {
	if ( php_sapi_name() === 'cli' ) {
		return false;
	}

	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	return false !== strpos( $host, '.ddev.site' );
}

add_filter(
	'cooked_get_settings',
	static function ( $settings ) {
		if ( ! cooked_vrt_request_allowed() ) {
			return $settings;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only DDEV visual-test query vars.
		if ( isset( $_GET['vrt_dark'] ) ) {
			$dark = sanitize_key( wp_unslash( $_GET['vrt_dark'] ) );
			if ( in_array( $dark, [ 'off', 'enabled', 'auto' ], true ) ) {
				$settings['dark_mode'] = $dark;
			}
		}
		// phpcs:enable

		return $settings;
	}
);
