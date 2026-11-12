<?php
/**
 * Admin Class - Handles admin interface
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Admin {

    /**
     * Singleton instance
     *
     * @var RSMTP_Admin
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return RSMTP_Admin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Initialize admin
     */
    private function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ) );
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            __( 'Royal SMTP', 'royal-smtp' ),
            __( 'Royal SMTP', 'royal-smtp' ),
            'manage_options',
            'royal-smtp',
            array( $this, 'settings_page' ),
            'dashicons-email-alt',
            81
        );

        add_submenu_page(
            'royal-smtp',
            __( 'Settings', 'royal-smtp' ),
            __( 'Settings', 'royal-smtp' ),
            'manage_options',
            'royal-smtp',
            array( $this, 'settings_page' )
        );

        add_submenu_page(
            'royal-smtp',
            __( 'Email Log', 'royal-smtp' ),
            __( 'Email Log', 'royal-smtp' ),
            'manage_options',
            'rsmtp-email-log',
            array( $this, 'email_log_page' )
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'royal-smtp' ) === false && strpos( $hook, 'rsmtp-' ) === false ) {
            return;
        }

        $css_file = RSMTP_PLUGIN_DIR . 'admin/css/admin.css';
        $js_file  = RSMTP_PLUGIN_DIR . 'admin/js/admin.js';

        $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : RSMTP_VERSION;
        $js_version  = file_exists( $js_file ) ? filemtime( $js_file ) : RSMTP_VERSION;

        wp_enqueue_style( 'rsmtp-admin', RSMTP_PLUGIN_URL . 'admin/css/admin.css', array(), $css_version );
        wp_enqueue_script( 'rsmtp-admin', RSMTP_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), $js_version, true );

        wp_localize_script( 'rsmtp-admin', 'rsmtp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rsmtp_nonce' ),
            'strings'  => array(
                'sending'        => __( 'Sending...', 'royal-smtp' ),
                'send_test'      => __( 'Send Test Email', 'royal-smtp' ),
                'test_success'   => __( 'Test email sent successfully!', 'royal-smtp' ),
                'test_failed'    => __( 'Test email failed:', 'royal-smtp' ),
                'saving'         => __( 'Saving...', 'royal-smtp' ),
                'save_settings'  => __( 'Save Settings', 'royal-smtp' ),
                'saved'          => __( 'Settings saved!', 'royal-smtp' ),
                'confirm_clear'  => __( 'Are you sure you want to clear all email logs?', 'royal-smtp' ),
                'clearing'       => __( 'Clearing...', 'royal-smtp' ),
                'cleared'        => __( 'Email log cleared!', 'royal-smtp' ),
            ),
        ) );
    }

    /**
     * Render settings page
     */
    public function settings_page() {
        // Handle settings save
        if ( isset( $_POST['rsmtp_save_settings'] ) && check_admin_referer( 'rsmtp_settings', 'rsmtp_settings_nonce' ) ) {
            $this->save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'royal-smtp' ) . '</p></div>';
        }

        include RSMTP_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render email log page
     */
    public function email_log_page() {
        include RSMTP_PLUGIN_DIR . 'admin/views/email-log.php';
    }

    /**
     * Save settings
     *
     * Note: This method is only called after nonce verification in settings_page().
     * phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in settings_page() before calling this method.
     */
    private function save_settings() {
        $settings = rsmtp_get_settings();

        // From settings
        $settings['from_email']       = sanitize_email( wp_unslash( $_POST['from_email'] ?? '' ) );
        $settings['from_name']        = sanitize_text_field( wp_unslash( $_POST['from_name'] ?? '' ) );
        $settings['force_from_email'] = isset( $_POST['force_from_email'] );
        $settings['force_from_name']  = isset( $_POST['force_from_name'] );

        // Mailer selection
        $settings['mailer'] = sanitize_key( wp_unslash( $_POST['mailer'] ?? 'other_smtp' ) );

        // SendGrid
        $new_sendgrid_key = sanitize_text_field( wp_unslash( $_POST['sendgrid_api_key'] ?? '' ) );
        if ( ! empty( $new_sendgrid_key ) && strpos( $new_sendgrid_key, '****' ) !== 0 ) {
            $settings['sendgrid_api_key'] = $new_sendgrid_key;
        }

        // Brevo
        $new_brevo_key = sanitize_text_field( wp_unslash( $_POST['brevo_api_key'] ?? '' ) );
        if ( ! empty( $new_brevo_key ) && strpos( $new_brevo_key, '****' ) !== 0 ) {
            $settings['brevo_api_key'] = $new_brevo_key;
        }

        // Mailgun
        $new_mailgun_key = sanitize_text_field( wp_unslash( $_POST['mailgun_api_key'] ?? '' ) );
        if ( ! empty( $new_mailgun_key ) && strpos( $new_mailgun_key, '****' ) !== 0 ) {
            $settings['mailgun_api_key'] = $new_mailgun_key;
        }
        $settings['mailgun_domain'] = sanitize_text_field( wp_unslash( $_POST['mailgun_domain'] ?? '' ) );
        $settings['mailgun_region'] = sanitize_key( wp_unslash( $_POST['mailgun_region'] ?? 'us' ) );

        // Resend
        $new_resend_key = sanitize_text_field( wp_unslash( $_POST['resend_api_key'] ?? '' ) );
        if ( ! empty( $new_resend_key ) && strpos( $new_resend_key, '****' ) !== 0 ) {
            $settings['resend_api_key'] = $new_resend_key;
        }

        // SMTP.com
        $new_smtpcom_key = sanitize_text_field( wp_unslash( $_POST['smtpcom_api_key'] ?? '' ) );
        if ( ! empty( $new_smtpcom_key ) && strpos( $new_smtpcom_key, '****' ) !== 0 ) {
            $settings['smtpcom_api_key'] = $new_smtpcom_key;
        }
        $settings['smtpcom_sender'] = sanitize_text_field( wp_unslash( $_POST['smtpcom_sender'] ?? '' ) );

        // Other SMTP
        $settings['smtp_host']       = sanitize_text_field( wp_unslash( $_POST['smtp_host'] ?? '' ) );
        $settings['smtp_port']       = absint( $_POST['smtp_port'] ?? 587 );
        $settings['smtp_encryption'] = sanitize_key( wp_unslash( $_POST['smtp_encryption'] ?? 'tls' ) );
        $settings['smtp_auth']       = isset( $_POST['smtp_auth'] );
        $settings['smtp_username']   = sanitize_text_field( wp_unslash( $_POST['smtp_username'] ?? '' ) );

        // Password - only update if not masked
        $new_password = isset( $_POST['smtp_password'] ) ? wp_unslash( $_POST['smtp_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! empty( $new_password ) && strpos( $new_password, '****' ) !== 0 ) {
            $settings['smtp_password'] = $new_password;
        }

        // Logging
        $settings['enable_log']    = isset( $_POST['enable_log'] );
        $settings['log_retention'] = absint( $_POST['log_retention'] ?? 100 );

        // Data removal on delete (off unless the owner ticks it)
        $settings['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] );

        rsmtp_update_settings( $settings );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Customize admin footer text on plugin pages
     *
     * @param string $text Default footer text.
     * @return string
     */
    public function admin_footer_text( $text ) {
        $screen = get_current_screen();

        if ( $screen && ( strpos( $screen->id, 'royal-smtp' ) !== false || strpos( $screen->id, 'rsmtp-' ) !== false ) ) {
            return 'Built by <a href="https://royalplugins.com" target="_blank">Royal Plugins</a> | Thank you for creating with WordPress.';
        }

        return $text;
    }

    /**
     * Get available mailers for dropdown
     *
     * @return array
     */
    public static function get_mailer_options() {
        return array(
            'other_smtp' => __( 'Other SMTP (Gmail, custom)', 'royal-smtp' ),
            'sendgrid'   => 'SendGrid',
            'brevo'      => 'Brevo (Sendinblue)',
            'mailgun'    => 'Mailgun',
            'resend'     => 'Resend',
            'smtpcom'    => 'SMTP.com',
        );
    }

    /**
     * Mask sensitive value for display
     *
     * @param string $value Value to mask.
     * @return string
     */
    public static function mask_value( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $length = strlen( $value );
        if ( $length <= 4 ) {
            return str_repeat( '*', $length );
        }

        return str_repeat( '*', $length - 4 ) . substr( $value, -4 );
    }
}
