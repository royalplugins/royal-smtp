<?php
/**
 * Royal SMTP Uninstall
 *
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Settings and the email log are kept unless the owner asked for removal.
$rsmtp_settings = get_option( 'rsmtp_settings', array() );
$rsmtp_remove   = ( is_array( $rsmtp_settings ) && ! empty( $rsmtp_settings['delete_data_on_uninstall'] ) )
    || ( defined( 'RSMTP_ALLOW_UNINSTALL' ) && RSMTP_ALLOW_UNINSTALL );

if ( ! $rsmtp_remove ) {
    return;
}

global $wpdb;

// Delete options
delete_option( 'rsmtp_settings' );
delete_option( 'rsmtp_db_version' );
delete_option( 'rsmtp_activated_time' );

// Drop custom table
$rsmtp_table_name = $wpdb->prefix . 'rsmtp_email_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL -- Table name from $wpdb->prefix is safe.
$wpdb->query( "DROP TABLE IF EXISTS {$rsmtp_table_name}" );

// Clear any cached data
wp_cache_flush();
