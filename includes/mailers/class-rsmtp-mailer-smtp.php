<?php
/**
 * Other SMTP Mailer - Uses PHPMailer for custom SMTP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_SMTP extends RSMTP_Mailer {

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'other_smtp';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return __( 'Other SMTP', 'royal-smtp' );
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'Use any SMTP server, including Gmail with App Passwords.', 'royal-smtp' );
    }

    /**
     * Whether this mailer uses PHPMailer
     *
     * @return bool
     */
    public function uses_phpmailer() {
        return true;
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'smtp_host' => array(
                'label'       => __( 'SMTP Host', 'royal-smtp' ),
                'type'        => 'text',
                'placeholder' => 'smtp.gmail.com',
                'description' => __( 'For Gmail, use smtp.gmail.com', 'royal-smtp' ),
            ),
            'smtp_port' => array(
                'label'       => __( 'SMTP Port', 'royal-smtp' ),
                'type'        => 'number',
                'default'     => 587,
                'description' => __( 'Common ports: 587 (TLS), 465 (SSL), 25 (insecure)', 'royal-smtp' ),
            ),
            'smtp_encryption' => array(
                'label'   => __( 'Encryption', 'royal-smtp' ),
                'type'    => 'select',
                'default' => 'tls',
                'options' => array(
                    'none' => __( 'None', 'royal-smtp' ),
                    'ssl'  => 'SSL',
                    'tls'  => 'TLS',
                ),
            ),
            'smtp_auth' => array(
                'label'   => __( 'Authentication', 'royal-smtp' ),
                'type'    => 'checkbox',
                'default' => true,
                'description' => __( 'Enable SMTP authentication (recommended)', 'royal-smtp' ),
            ),
            'smtp_username' => array(
                'label'       => __( 'Username', 'royal-smtp' ),
                'type'        => 'text',
                'placeholder' => 'your-email@gmail.com',
                'description' => __( 'Usually your email address', 'royal-smtp' ),
            ),
            'smtp_password' => array(
                'label'       => __( 'Password', 'royal-smtp' ),
                'type'        => 'password',
                'description' => __( 'For Gmail, use an App Password (not your regular password)', 'royal-smtp' ),
            ),
        );
    }

    /**
     * Check if mailer is configured
     *
     * @return bool
     */
    public function is_configured() {
        $host = rsmtp_get_settings( 'smtp_host' );
        return ! empty( $host );
    }

    /**
     * Configure PHPMailer
     *
     * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
     */
    public function configure_phpmailer( $phpmailer ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = rsmtp_get_settings( 'smtp_host' );
        $phpmailer->Port       = absint( rsmtp_get_settings( 'smtp_port', 587 ) );

        $encryption = rsmtp_get_settings( 'smtp_encryption', 'tls' );
        if ( 'none' !== $encryption ) {
            $phpmailer->SMTPSecure = $encryption;
        } else {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        }

        if ( rsmtp_get_settings( 'smtp_auth', true ) ) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = rsmtp_get_settings( 'smtp_username' );
            $phpmailer->Password = rsmtp_get_settings( 'smtp_password' );
        } else {
            $phpmailer->SMTPAuth = false;
        }

        // Set timeout
        $phpmailer->Timeout = 30;

        // Debug mode (disabled by default)
        $phpmailer->SMTPDebug = 0;
    }

    /**
     * Send email (not used for SMTP mailers)
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        // SMTP mailers use PHPMailer via configure_phpmailer()
        return new WP_Error( 'not_implemented', __( 'SMTP mailers use PHPMailer directly.', 'royal-smtp' ) );
    }
}
