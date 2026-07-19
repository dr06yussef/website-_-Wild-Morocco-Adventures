<?php
/**
 * Plugin Name: WMA Core
 * Description: Tours, enquiries, business settings and starter-content setup for Wild Morocco Adventures.
 * Version: 1.1.1
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Wild Morocco Adventures
 * Text Domain: wma-core
 */

defined( 'ABSPATH' ) || exit;

define( 'WMA_CORE_VERSION', '1.1.1' );
define( 'WMA_CORE_FILE', __FILE__ );
define( 'WMA_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WMA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once WMA_CORE_PATH . 'includes/class-wma-content.php';
require_once WMA_CORE_PATH . 'includes/class-wma-trip-fields.php';
require_once WMA_CORE_PATH . 'includes/class-wma-inquiries.php';
require_once WMA_CORE_PATH . 'includes/class-wma-admin.php';

/**
 * Return a client-editable business setting.
 */
function wma_get_setting( string $key, string $default = '' ): string {
	$value = get_option( 'wma_' . $key, $default );
	return is_scalar( $value ) ? (string) $value : $default;
}

/**
 * Bootstrap all plugin services.
 */
function wma_core_init(): void {
	load_plugin_textdomain( 'wma-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	WMA_Content::init();
	WMA_Trip_Fields::init();
	WMA_Inquiries::init();
	WMA_Admin::init();
}
add_action( 'plugins_loaded', 'wma_core_init' );

/**
 * Install roles, caps and schedules.
 */
function wma_core_activate(): void {
	WMA_Content::register();
	WMA_Content::install_roles();
	if ( ! wp_next_scheduled( 'wma_daily_privacy_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wma_daily_privacy_cleanup' );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wma_core_activate' );

function wma_core_deactivate(): void {
	$timestamp = wp_next_scheduled( 'wma_daily_privacy_cleanup' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wma_daily_privacy_cleanup' );
	}
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wma_core_deactivate' );
