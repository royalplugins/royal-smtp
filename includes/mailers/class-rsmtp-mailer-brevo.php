<?php
/**
 * Brevo Mailer - API-based email sending (formerly Sendinblue)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_Brevo extends RSMTP_Mailer {

    /**
     * API endpoint
     */
    const API_URL = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'brevo';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return 'Brevo';
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'Brevo (formerly Sendinblue) - all-in-one marketing platform.', 'royal-smtp' );
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'brevo_api_key' => array(
                'label'       => __( 'API Key', 'royal-smtp' ),
                'type'        => 'password',
                'placeholder' => 'xkeysib-xxxxxxxx',
                'description' => sprintf(
                    /* translators: %s: Brevo API keys URL */
                    __( 'Get your API key from %s', 'royal-smtp' ),
                    '<a href="https://app.brevo.com/settings/keys/api" target="_blank">Brevo Dashboard</a>'
                ),
            ),
        );
    }

    /**
     * Check if mailer is configured
     *
     * @return bool
     */
    public function is_configured() {
        $api_key = rsmtp_get_settings( 'brevo_api_key' );
        return ! empty( $api_key );
    }

    /**
     * Send email via Brevo API
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        $api_key = rsmtp_get_settings( 'brevo_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Brevo API key is not configured.', 'royal-smtp' ) );
        }

        $headers = $this->parse_headers( isset( $atts['headers'] ) ? $atts['headers'] : array() );

        // Build recipients
        $to = $this->parse_emails( $atts['to'] );
        $to_list = array_map( function( $recipient ) {
            $item = array( 'email' => $recipient['email'] );
            if ( ! empty( $recipient['name'] ) ) {
                $item['name'] = $recipient['name'];
            }
            return $item;
        }, $to );

        // Build from
        $from_email = isset( $atts['from_email'] ) ? $atts['from_email'] : rsmtp_get_settings( 'from_email' );
        $from_name  = isset( $atts['from_name'] ) ? $atts['from_name'] : rsmtp_get_settings( 'from_name' );

        $sender = array( 'email' => $from_email );
        if ( ! empty( $from_name ) ) {
            $sender['name'] = $from_name;
        }

        // Build request body
        $body = array(
            'sender'  => $sender,
            'to'      => $to_list,
            'subject' => isset( $atts['subject'] ) ? $atts['subject'] : '',
        );

        // Set content based on type
        if ( $headers['content_type'] === 'text/html' ) {
            $body['htmlContent'] = isset( $atts['message'] ) ? $atts['message'] : '';
        } else {
            $body['textContent'] = isset( $atts['message'] ) ? $atts['message'] : '';
        }

        // Add CC
        if ( ! empty( $headers['cc'] ) ) {
            $body['cc'] = array_map( function( $recipient ) {
                $item = array( 'email' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $headers['cc'] );
        }

        // Add BCC
        if ( ! empty( $headers['bcc'] ) ) {
            $body['bcc'] = array_map( function( $recipient ) {
                $item = array( 'email' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $headers['bcc'] );
        }

        // Add reply-to
        if ( ! empty( $headers['reply_to'] ) ) {
            $reply_to = $headers['reply_to'][0];
            $body['replyTo'] = array( 'email' => $reply_to['email'] );
            if ( ! empty( $reply_to['name'] ) ) {
                $body['replyTo']['name'] = $reply_to['name'];
            }
        }

        // Custom headers, such as one-click unsubscribe
        if ( ! empty( $headers['custom'] ) ) {
            $body['headers'] = $headers['custom'];
        }

        // Make API request
        $response = $this->api_request( self::API_URL, array(
            'headers' => array(
                'api-key'      => $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => $body,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Brevo returns 201 for success
        if ( $response['code'] >= 200 && $response['code'] < 300 ) {
            return true;
        }

        $error_message = __( 'Brevo API error', 'royal-smtp' );
        if ( isset( $response['body']['message'] ) ) {
            $error_message = $response['body']['message'];
        }

        return new WP_Error( 'api_error', $error_message );
    }
}
