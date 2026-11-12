<?php
/**
 * SMTP.com Mailer - API-based email sending
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_SMTPcom extends RSMTP_Mailer {

    /**
     * API endpoint
     */
    const API_URL = 'https://api.smtp.com/v4/messages';

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'smtpcom';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return 'SMTP.com';
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'SMTP.com - enterprise email delivery service.', 'royal-smtp' );
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'smtpcom_api_key' => array(
                'label'       => __( 'API Key', 'royal-smtp' ),
                'type'        => 'password',
                'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                'description' => sprintf(
                    /* translators: %s: SMTP.com API keys URL */
                    __( 'Get your API key from %s', 'royal-smtp' ),
                    '<a href="https://my.smtp.com/settings/api" target="_blank">SMTP.com Dashboard</a>'
                ),
            ),
            'smtpcom_sender' => array(
                'label'       => __( 'Sender Name', 'royal-smtp' ),
                'type'        => 'text',
                'placeholder' => 'my-sender',
                'description' => __( 'The sender name (channel) from your SMTP.com account.', 'royal-smtp' ),
            ),
        );
    }

    /**
     * Check if mailer is configured
     *
     * @return bool
     */
    public function is_configured() {
        $api_key = rsmtp_get_settings( 'smtpcom_api_key' );
        $sender  = rsmtp_get_settings( 'smtpcom_sender' );
        return ! empty( $api_key ) && ! empty( $sender );
    }

    /**
     * Send email via SMTP.com API
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        $api_key = rsmtp_get_settings( 'smtpcom_api_key' );
        $sender  = rsmtp_get_settings( 'smtpcom_sender' );

        if ( empty( $api_key ) || empty( $sender ) ) {
            return new WP_Error( 'missing_credentials', __( 'SMTP.com API key or sender name is not configured.', 'royal-smtp' ) );
        }

        $headers = $this->parse_headers( isset( $atts['headers'] ) ? $atts['headers'] : array() );

        // Build from
        $from_email = isset( $atts['from_email'] ) ? $atts['from_email'] : rsmtp_get_settings( 'from_email' );
        $from_name  = isset( $atts['from_name'] ) ? $atts['from_name'] : rsmtp_get_settings( 'from_name' );

        $originator = array(
            'from' => array(
                'address' => $from_email,
            ),
        );
        if ( ! empty( $from_name ) ) {
            $originator['from']['name'] = $from_name;
        }

        // Build recipients
        $to = $this->parse_emails( $atts['to'] );
        $recipients = array(
            'to' => array_map( function( $recipient ) {
                $item = array( 'address' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $to ),
        );

        // Add CC
        if ( ! empty( $headers['cc'] ) ) {
            $recipients['cc'] = array_map( function( $cc ) {
                $item = array( 'address' => $cc['email'] );
                if ( ! empty( $cc['name'] ) ) {
                    $item['name'] = $cc['name'];
                }
                return $item;
            }, $headers['cc'] );
        }

        // Add BCC
        if ( ! empty( $headers['bcc'] ) ) {
            $recipients['bcc'] = array_map( function( $bcc ) {
                $item = array( 'address' => $bcc['email'] );
                if ( ! empty( $bcc['name'] ) ) {
                    $item['name'] = $bcc['name'];
                }
                return $item;
            }, $headers['bcc'] );
        }

        // Add reply-to
        if ( ! empty( $headers['reply_to'] ) ) {
            $reply_to = $headers['reply_to'][0];
            $originator['reply_to'] = array( 'address' => $reply_to['email'] );
            if ( ! empty( $reply_to['name'] ) ) {
                $originator['reply_to']['name'] = $reply_to['name'];
            }
        }

        // Build body content
        $body_content = array();
        if ( $headers['content_type'] === 'text/html' ) {
            $body_content['html'] = isset( $atts['message'] ) ? $atts['message'] : '';
        } else {
            $body_content['text'] = isset( $atts['message'] ) ? $atts['message'] : '';
        }

        // Build request body
        $body = array(
            'channel'    => $sender,
            'originator' => $originator,
            'recipients' => $recipients,
            'subject'    => isset( $atts['subject'] ) ? $atts['subject'] : '',
            'body'       => $body_content,
        );

        // Custom headers, such as one-click unsubscribe
        if ( ! empty( $headers['custom'] ) ) {
            $body['custom_headers'] = $headers['custom'];
        }

        // Make API request
        $response = $this->api_request( self::API_URL, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => $body,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // SMTP.com returns 200 for success
        if ( $response['code'] >= 200 && $response['code'] < 300 ) {
            return true;
        }

        $error_message = __( 'SMTP.com API error', 'royal-smtp' );
        if ( isset( $response['body']['message'] ) ) {
            $error_message = $response['body']['message'];
        } elseif ( isset( $response['body']['error'] ) ) {
            $error_message = $response['body']['error'];
        }

        return new WP_Error( 'api_error', $error_message );
    }
}
