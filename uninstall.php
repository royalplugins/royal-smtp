<?php
/**
 * Royal SMTP Uninstall
 *
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
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
