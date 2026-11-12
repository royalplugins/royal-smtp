<?php
/**
 * AJAX Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Ajax {

    /**
     * Singleton instance
     *
     * @var RSMTP_Ajax
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return RSMTP_Ajax
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
     * Initialize AJAX handlers
     */
    private function init() {
        add_action( 'wp_ajax_rsmtp_send_test_email', array( $this, 'send_test_email' ) );
        add_action( 'wp_ajax_rsmtp_clear_log', array( $this, 'clear_log' ) );
    }

    /**
     * Verify AJAX request
     *
     * @return bool
     */
    private function verify_request() {
        if ( ! current_user_can( 'manage_options' ) ) { // audit:multisite-manage-options-safe -- per-site scope: this site's own mail settings and log
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'royal-smtp' ) ) );
            return false;
        }

        if ( ! check_ajax_referer( 'rsmtp_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'royal-smtp' ) ) );
            return false;
        }

        return true;
    }

    /**
     * Send test email
     */
    public function send_test_email() {
        if ( ! $this->verify_request() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request() above.
        $to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';

        if ( empty( $to ) || ! is_email( $to ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'royal-smtp' ) ) );
            return;
        }

        $result = RSMTP_Core::instance()->send_test_email( $to );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            return;
        }

        wp_send_json_success( array( 'message' => __( 'Test email sent successfully!', 'royal-smtp' ) ) );
    }

    /**
     * Clear email log
     */
    public function clear_log() {
        if ( ! $this->verify_request() ) {
            return;
        }

        RSMTP_Logger::instance()->clear_logs();

        wp_send_json_success( array( 'message' => __( 'Email log cleared successfully!', 'royal-smtp' ) ) );
    }
}
