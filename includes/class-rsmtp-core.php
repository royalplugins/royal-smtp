<?php
/**
 * Core Class - Handles mail interception and routing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Core {

    /**
     * Singleton instance
     *
     * @var RSMTP_Core
     */
    private static $instance = null;

    /**
     * Available mailers
     *
     * @var array
     */
    private $mailers = array();

    /**
     * Current mailer instance
     *
     * @var RSMTP_Mailer|null
     */
    private $current_mailer = null;

    /**
     * Get singleton instance
     *
     * @return RSMTP_Core
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
     * Initialize core functionality
     */
    private function init() {
        // Register built-in mailers
        $this->register_mailers();

        // Initialize the current mailer
        $this->current_mailer = $this->get_mailer( rsmtp_get_settings( 'mailer', 'other_smtp' ) );

        // Hook into WordPress mail system
        if ( $this->current_mailer && $this->current_mailer->is_configured() ) {
            if ( $this->current_mailer->uses_phpmailer() ) {
                // SMTP-based mailers configure PHPMailer
                add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ), 999 );
                add_action( 'wp_mail_succeeded', array( $this, 'log_success' ) );
                add_action( 'wp_mail_failed', array( $this, 'log_failure' ) );
            } else {
                // API-based mailers intercept wp_mail via pre_wp_mail (WP 5.9+)
                add_filter( 'pre_wp_mail', array( $this, 'intercept_mail' ), 10, 2 );
            }
        }

        // Always apply from name/email overrides
        add_filter( 'wp_mail_from', array( $this, 'filter_from_email' ), 1000 );
        add_filter( 'wp_mail_from_name', array( $this, 'filter_from_name' ), 1000 );
    }

    /**
     * Register built-in mailers
     */
    private function register_mailers() {
        $this->mailers = array(
            'other_smtp' => 'RSMTP_Mailer_SMTP',
            'sendgrid'   => 'RSMTP_Mailer_SendGrid',
            'brevo'      => 'RSMTP_Mailer_Brevo',
            'mailgun'    => 'RSMTP_Mailer_Mailgun',
            'resend'     => 'RSMTP_Mailer_Resend',
            'smtpcom'    => 'RSMTP_Mailer_SMTPcom',
        );

        $this->mailers = apply_filters( 'rsmtp_mailers', $this->mailers );
    }

    /**
     * Get a mailer instance
     *
     * @param string $id Mailer ID.
     * @return RSMTP_Mailer|null
     */
    public function get_mailer( $id ) {
        if ( ! isset( $this->mailers[ $id ] ) ) {
            return null;
        }

        $class = $this->mailers[ $id ];
        if ( class_exists( $class ) ) {
            return new $class();
        }

        return null;
    }

    /**
     * Get all registered mailers
     *
     * @return array
     */
    public function get_mailers() {
        $instances = array();
        foreach ( $this->mailers as $id => $class ) {
            if ( class_exists( $class ) ) {
                $instances[ $id ] = new $class();
            }
        }
        return $instances;
    }

    /**
     * Get current active mailer
     *
     * @return RSMTP_Mailer|null
     */
    public function get_current_mailer() {
        return $this->current_mailer;
    }

    /**
     * Configure PHPMailer for SMTP mailers
     *
     * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
     */
    public function configure_phpmailer( $phpmailer ) {
        if ( $this->current_mailer && $this->current_mailer->uses_phpmailer() ) {
            $this->current_mailer->configure_phpmailer( $phpmailer );
        }
    }

    /**
     * Intercept wp_mail for API-based mailers
     *
     * @param null|bool $return Short-circuit return value.
     * @param array     $atts   Email attributes.
     * @return bool|null
     */
    public function intercept_mail( $return, $atts ) {
        if ( ! $this->current_mailer || $this->current_mailer->uses_phpmailer() ) {
            return null; // Let WordPress handle it
        }

        // Apply from filters
        $from_email = apply_filters( 'wp_mail_from', rsmtp_get_settings( 'from_email' ) );
        $from_name  = apply_filters( 'wp_mail_from_name', rsmtp_get_settings( 'from_name' ) );

        $atts['from_email'] = $from_email;
        $atts['from_name']  = $from_name;

        $result = $this->current_mailer->send( $atts );

        // Log the email
        $to = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'];
        $headers = isset( $atts['headers'] ) ? ( is_array( $atts['headers'] ) ? implode( "\n", $atts['headers'] ) : $atts['headers'] ) : '';

        if ( is_wp_error( $result ) ) {
            RSMTP_Logger::instance()->log( array(
                'to_email'      => $to,
                'subject'       => isset( $atts['subject'] ) ? $atts['subject'] : '',
                'status'        => 'failed',
                'mailer'        => $this->current_mailer->get_id(),
                'error_message' => $result->get_error_message(),
                'headers'       => $headers,
            ) );

            do_action( 'wp_mail_failed', new WP_Error(
                'wp_mail_failed',
                $result->get_error_message(),
                array(
                    'to'          => $atts['to'],
                    'subject'     => isset( $atts['subject'] ) ? $atts['subject'] : '',
                    'message'     => isset( $atts['message'] ) ? $atts['message'] : '',
                    'headers'     => $headers,
                    'attachments' => isset( $atts['attachments'] ) ? $atts['attachments'] : array(),
                )
            ) );

            return false;
        }

        RSMTP_Logger::instance()->log( array(
            'to_email' => $to,
            'subject'  => isset( $atts['subject'] ) ? $atts['subject'] : '',
            'status'   => 'sent',
            'mailer'   => $this->current_mailer->get_id(),
            'headers'  => $headers,
        ) );

        do_action( 'wp_mail_succeeded', array(
            'to'          => $atts['to'],
            'subject'     => isset( $atts['subject'] ) ? $atts['subject'] : '',
            'message'     => isset( $atts['message'] ) ? $atts['message'] : '',
            'headers'     => isset( $atts['headers'] ) ? $atts['headers'] : '',
            'attachments' => isset( $atts['attachments'] ) ? $atts['attachments'] : array(),
        ) );

        return true;
    }

    /**
     * Log successful email (for PHPMailer-based mailers)
     *
     * @param array $mail_data Mail data.
     */
    public function log_success( $mail_data ) {
        $to = isset( $mail_data['to'] ) ? ( is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'] ) : '';
        $headers = isset( $mail_data['headers'] ) ? ( is_array( $mail_data['headers'] ) ? implode( "\n", $mail_data['headers'] ) : $mail_data['headers'] ) : '';

        RSMTP_Logger::instance()->log( array(
            'to_email' => $to,
            'subject'  => isset( $mail_data['subject'] ) ? $mail_data['subject'] : '',
            'status'   => 'sent',
            'mailer'   => $this->current_mailer ? $this->current_mailer->get_id() : 'unknown',
            'headers'  => $headers,
        ) );
    }

    /**
     * Log failed email (for PHPMailer-based mailers)
     *
     * @param WP_Error $error Error object.
     */
    public function log_failure( $error ) {
        $mail_data = $error->get_error_data();
        $to = isset( $mail_data['to'] ) ? ( is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'] ) : '';
        $headers = isset( $mail_data['headers'] ) ? ( is_array( $mail_data['headers'] ) ? implode( "\n", $mail_data['headers'] ) : $mail_data['headers'] ) : '';

        RSMTP_Logger::instance()->log( array(
            'to_email'      => $to,
            'subject'       => isset( $mail_data['subject'] ) ? $mail_data['subject'] : '',
            'status'        => 'failed',
            'mailer'        => $this->current_mailer ? $this->current_mailer->get_id() : 'unknown',
            'error_message' => $error->get_error_message(),
            'headers'       => $headers,
        ) );
    }

    /**
     * Filter from email
     *
     * @param string $from_email Original from email.
     * @return string
     */
    public function filter_from_email( $from_email ) {
        $settings_email = rsmtp_get_settings( 'from_email' );
        $force = rsmtp_get_settings( 'force_from_email' );

        if ( $force || empty( $from_email ) || strpos( $from_email, 'wordpress@' ) === 0 ) {
            return $settings_email ? $settings_email : $from_email;
        }

        return $from_email;
    }

    /**
     * Filter from name
     *
     * @param string $from_name Original from name.
     * @return string
     */
    public function filter_from_name( $from_name ) {
        $settings_name = rsmtp_get_settings( 'from_name' );
        $force = rsmtp_get_settings( 'force_from_name' );

        if ( $force || empty( $from_name ) || $from_name === 'WordPress' ) {
            return $settings_name ? $settings_name : $from_name;
        }

        return $from_name;
    }

    /**
     * Send a test email
     *
     * @param string $to Recipient email.
     * @return bool|WP_Error
     */
    public function send_test_email( $to ) {
        $subject = sprintf(
            /* translators: %s: Site name */
            __( 'Test Email from %s', 'royal-smtp' ),
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: Mailer name, 2: Site URL */
            __( "This is a test email sent via Royal SMTP.\n\nMailer: %1\$s\nSite: %2\$s\n\nIf you received this email, your email configuration is working correctly!", 'royal-smtp' ),
            $this->current_mailer ? $this->current_mailer->get_name() : __( 'Not configured', 'royal-smtp' ),
            home_url()
        );

        $result = wp_mail( $to, $subject, $message );

        if ( ! $result ) {
            global $phpmailer;
            $error_message = __( 'Unknown error', 'royal-smtp' );

            if ( isset( $phpmailer ) && $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) {
                $error_message = $phpmailer->ErrorInfo;
            }

            return new WP_Error( 'send_failed', $error_message );
        }

        return true;
    }
}
