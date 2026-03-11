<?php
/**
 * Plugin Installer - Handles activation and deactivation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Installer {

    /**
     * Plugin activation
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();

        // Store activation time
        if ( ! get_option( 'rsmtp_activated_time' ) ) {
            update_option( 'rsmtp_activated_time', time() );
        }

        // Clear any cached data
        wp_cache_flush();

        do_action( 'rsmtp_activated' );
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        do_action( 'rsmtp_deactivated' );
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Email log table
        $table_email_log = $wpdb->prefix . 'rsmtp_email_log';
        $sql_email_log   = "CREATE TABLE $table_email_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'sent',
            mailer varchar(50) NOT NULL,
            error_message text DEFAULT NULL,
            headers text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY mailer (mailer)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_email_log );

        update_option( 'rsmtp_db_version', RSMTP_VERSION );
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'from_email'       => get_option( 'admin_email' ),
            'from_name'        => get_bloginfo( 'name' ),
            'force_from_email' => false,
            'force_from_name'  => false,
            'mailer'           => 'other_smtp',
            'smtp_port'        => 587,
            'smtp_encryption'  => 'tls',
            'smtp_auth'        => true,
            'enable_log'       => true,
            'log_retention'    => 100,
        );

        if ( get_option( 'rsmtp_settings' ) === false ) {
            update_option( 'rsmtp_settings', $defaults );
        }
    }
}
