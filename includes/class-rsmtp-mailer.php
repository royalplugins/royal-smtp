<?php
/**
 * Abstract Mailer Class - Base class for all mailers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class RSMTP_Mailer {

    /**
     * Get mailer ID
     *
     * @return string
     */
    abstract public function get_id();

    /**
     * Get mailer name for display
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Get settings fields for this mailer
     *
     * @return array
     */
    abstract public function get_settings_fields();

    /**
     * Send email
     *
     * @param array $atts Email attributes (to, subject, message, headers, attachments).
     * @return bool|WP_Error
     */
    abstract public function send( $atts );

    /**
     * Check if mailer is configured
     *
     * @return bool
     */
    abstract public function is_configured();

    /**
     * Whether this mailer uses PHPMailer directly
     *
     * @return bool
     */
    public function uses_phpmailer() {
        return false;
    }

    /**
     * Configure PHPMailer for SMTP-based mailers
     *
     * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
     * @return void
     */
    public function configure_phpmailer( $phpmailer ) {
        // Override in subclasses that use PHPMailer
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return '';
    }

    /**
     * Parse email address from string like "Name <email@example.com>"
     *
     * @param string $email_string Email string.
     * @return array Array with 'email' and 'name' keys.
     */
    protected function parse_email( $email_string ) {
        $email_string = trim( $email_string );

        if ( preg_match( '/^(.+)\s*<(.+)>$/', $email_string, $matches ) ) {
            return array(
                'name'  => trim( $matches[1], ' "' ),
                'email' => trim( $matches[2] ),
            );
        }

        return array(
            'name'  => '',
            'email' => $email_string,
        );
    }

    /**
     * Parse multiple email addresses
     *
     * @param string|array $emails Emails as string or array.
     * @return array Array of parsed emails.
     */
    protected function parse_emails( $emails ) {
        if ( is_string( $emails ) ) {
            $emails = explode( ',', $emails );
        }

        $parsed = array();
        foreach ( (array) $emails as $email ) {
            $parsed[] = $this->parse_email( $email );
        }

        return $parsed;
    }

    /**
     * Extract email headers
     *
     * @param array|string $headers Headers array or string.
     * @return array Parsed headers.
     */
    protected function parse_headers( $headers ) {
        $parsed = array(
            'content_type' => 'text/plain',
            'charset'      => get_bloginfo( 'charset' ),
            'cc'           => array(),
            'bcc'          => array(),
            'reply_to'     => array(),
            'from_email'   => '',
            'from_name'    => '',
        );

        if ( empty( $headers ) ) {
            return $parsed;
        }

        if ( is_string( $headers ) ) {
            $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
        }

        foreach ( $headers as $header ) {
            if ( strpos( $header, ':' ) === false ) {
                continue;
            }

            list( $name, $value ) = explode( ':', $header, 2 );
            $name  = strtolower( trim( $name ) );
            $value = trim( $value );

            switch ( $name ) {
                case 'content-type':
                    if ( strpos( $value, 'text/html' ) !== false ) {
                        $parsed['content_type'] = 'text/html';
                    }
                    if ( preg_match( '/charset=([^\s;]+)/i', $value, $matches ) ) {
                        $parsed['charset'] = $matches[1];
                    }
                    break;

                case 'cc':
                    $parsed['cc'] = array_merge( $parsed['cc'], $this->parse_emails( $value ) );
                    break;

                case 'bcc':
                    $parsed['bcc'] = array_merge( $parsed['bcc'], $this->parse_emails( $value ) );
                    break;

                case 'reply-to':
                    $parsed['reply_to'] = array_merge( $parsed['reply_to'], $this->parse_emails( $value ) );
                    break;

                case 'from':
                    $from = $this->parse_email( $value );
                    $parsed['from_email'] = $from['email'];
                    $parsed['from_name']  = $from['name'];
                    break;
            }
        }

        return $parsed;
    }

    /**
     * Make HTTP request to API
     *
     * @param string $url     API URL.
     * @param array  $args    Request arguments.
     * @param string $method  HTTP method.
     * @return array|WP_Error
     */
    protected function api_request( $url, $args = array(), $method = 'POST' ) {
        $defaults = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );

        $args = wp_parse_args( $args, $defaults );

        if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
            $args['body'] = wp_json_encode( $args['body'] );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return array(
            'code' => $code,
            'body' => $data,
            'raw'  => $body,
        );
    }
}
