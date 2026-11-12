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
            'custom'       => array(),
        );
        $has_type = false;

        if ( empty( $headers ) ) {
            $headers = array();
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
                    $has_type               = true;
                    $parsed['content_type'] = $this->normalize_content_type( $value );
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

                default:
                    if ( '' !== $value && 0 === preg_match( '/[^\P{Cc}\t]/u', $value ) && preg_match( '/^[a-z][a-z0-9-]*$/', $name ) && ! in_array( $name, $this->reserved_headers(), true ) ) {
                        $parsed['custom'][ $this->header_case( $name ) ] = $value;
                    }
                    break;
            }
        }

        // Same fallback as wp_mail(): a type set by filter counts when no header sets one.
        if ( ! $has_type ) {
            $parsed['content_type'] = $this->normalize_content_type( (string) apply_filters( 'wp_mail_content_type', 'text/plain' ) );
        }

        return $parsed;
    }

    /**
     * The two body types mail APIs accept. A multipart type without its own
     * boundary is an HTML message whose plain-text part PHPMailer would add.
     *
     * @param string $value Content type.
     * @return string text/html or text/plain.
     */
    private function normalize_content_type( $value ) {
        $value = strtolower( $value );
        if ( false !== strpos( $value, 'text/html' ) ) {
            return 'text/html';
        }
        return false !== strpos( $value, 'multipart/' ) && false === strpos( $value, 'boundary' ) ? 'text/html' : 'text/plain';
    }

    /**
     * Headers mail APIs set themselves or refuse.
     *
     * @return string[]
     */
    private function reserved_headers() {
        return array( 'mime-version', 'content-transfer-encoding', 'to', 'subject', 'date', 'message-id', 'received', 'dkim-signature', 'return-path', 'sender', 'x-sg-id', 'x-sg-eid' );
    }

    /**
     * @param string $name Lowercase header name.
     * @return string For example list-unsubscribe-post becomes List-Unsubscribe-Post.
     */
    private function header_case( $name ) {
        return implode( '-', array_map( 'ucfirst', explode( '-', $name ) ) );
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
