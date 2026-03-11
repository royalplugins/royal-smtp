<?php
/**
 * Plugin Name: Royal SMTP
 * Plugin URI: https://royalplugins.com/royal-smtp
 * Description: Simple, reliable SMTP and transactional email for WordPress. Supports SendGrid, Brevo, Mailgun, Resend, SMTP.com, and custom SMTP.
 * Version: 1.0.0
 * Author: Royal Plugins
 * Author URI: https://royalplugins.com
 * License: GPL v2 or later
 * Text Domain: royal-smtp
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RSMTP_VERSION', '1.0.0' );
define( 'RSMTP_PLUGIN_FILE', __FILE__ );
define( 'RSMTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RSMTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RSMTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes
 */
spl_autoload_register( function( $class ) {
    if ( strpos( $class, 'RSMTP_' ) !== 0 ) {
        return;
    }

    $file_map = array(
        // Core
        'RSMTP_Core'              => 'includes/class-rsmtp-core.php',
        'RSMTP_Admin'             => 'includes/class-rsmtp-admin.php',
        'RSMTP_Ajax'              => 'includes/class-rsmtp-ajax.php',
        'RSMTP_Logger'            => 'includes/class-rsmtp-logger.php',
        'RSMTP_Installer'         => 'includes/class-rsmtp-installer.php',
        'RSMTP_Mailer'            => 'includes/class-rsmtp-mailer.php',

        // Mailers
        'RSMTP_Mailer_SMTP'       => 'includes/mailers/class-rsmtp-mailer-smtp.php',
        'RSMTP_Mailer_SendGrid'   => 'includes/mailers/class-rsmtp-mailer-sendgrid.php',
        'RSMTP_Mailer_Brevo'      => 'includes/mailers/class-rsmtp-mailer-brevo.php',
        'RSMTP_Mailer_Mailgun'    => 'includes/mailers/class-rsmtp-mailer-mailgun.php',
        'RSMTP_Mailer_Resend'     => 'includes/mailers/class-rsmtp-mailer-resend.php',
        'RSMTP_Mailer_SMTPcom'    => 'includes/mailers/class-rsmtp-mailer-smtpcom.php',
    );

    if ( isset( $file_map[ $class ] ) ) {
        $file = RSMTP_PLUGIN_DIR . $file_map[ $class ];
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

// Activation/Deactivation hooks
register_activation_hook( __FILE__, array( 'RSMTP_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RSMTP_Installer', 'deactivate' ) );

/**
 * Initialize the plugin
 */
add_action( 'plugins_loaded', function() {
    // Initialize core (mail hooks)
    RSMTP_Core::instance();

    // Initialize admin
    if ( is_admin() ) {
        RSMTP_Admin::instance();
        RSMTP_Ajax::instance();
    }

    do_action( 'rsmtp_initialized' );
}, 10 );

/**
 * Add settings link on plugin page
 */
add_filter( 'plugin_action_links_' . RSMTP_PLUGIN_BASENAME, function( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=royal-smtp' ) . '">' . __( 'Settings', 'royal-smtp' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );

/**
 * Add Docs link to plugin row meta
 */
add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( $file === RSMTP_PLUGIN_BASENAME ) {
        $links[] = '<a href="https://royalplugins.com/support/royal-smtp/">' . __( 'Docs', 'royal-smtp' ) . '</a>';
    }
    return $links;
}, 10, 2 );

/**
 * Get plugin settings
 *
 * @param string|null $key Specific setting key to retrieve.
 * @param mixed       $default Default value if key doesn't exist.
 * @return mixed
 */
function rsmtp_get_settings( $key = null, $default = null ) {
    $settings = get_option( 'rsmtp_settings', array() );

    $defaults = array(
        'from_email'       => get_option( 'admin_email' ),
        'from_name'        => get_bloginfo( 'name' ),
        'force_from_email' => false,
        'force_from_name'  => false,
        'mailer'           => 'other_smtp',

        // SendGrid
        'sendgrid_api_key' => '',

        // Brevo
        'brevo_api_key'    => '',

        // Mailgun
        'mailgun_api_key'  => '',
        'mailgun_domain'   => '',
        'mailgun_region'   => 'us',

        // Resend
        'resend_api_key'   => '',

        // SMTP.com
        'smtpcom_api_key'  => '',
        'smtpcom_sender'   => '',

        // Other SMTP
        'smtp_host'        => '',
        'smtp_port'        => 587,
        'smtp_encryption'  => 'tls',
        'smtp_auth'        => true,
        'smtp_username'    => '',
        'smtp_password'    => '',

        // Logging
        'enable_log'       => true,
        'log_retention'    => 100,
    );

    $settings = wp_parse_args( $settings, $defaults );

    if ( null !== $key ) {
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    return $settings;
}

/**
 * Update plugin settings
 *
 * @param array $new_settings Settings to update.
 * @return bool
 */
function rsmtp_update_settings( $new_settings ) {
    $settings = rsmtp_get_settings();
    $settings = wp_parse_args( $new_settings, $settings );
    return update_option( 'rsmtp_settings', $settings );
}
