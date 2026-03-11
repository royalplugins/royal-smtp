<?php
/**
 * Resend Mailer - API-based email sending
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_Resend extends RSMTP_Mailer {

    /**
     * API endpoint
     */
    const API_URL = 'https://api.resend.com/emails';

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'resend';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return 'Resend';
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'Resend - email for developers, built for reliability.', 'royal-smtp' );
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'resend_api_key' => array(
                'label'       => __( 'API Key', 'royal-smtp' ),
                'type'        => 'password',
                'placeholder' => 're_xxxxxxxx',
                'description' => sprintf(
                    /* translators: %s: Resend API keys URL */
                    __( 'Get your API key from %s', 'royal-smtp' ),
                    '<a href="https://resend.com/api-keys" target="_blank">Resend Dashboard</a>'
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
        $api_key = rsmtp_get_settings( 'resend_api_key' );
        return ! empty( $api_key );
    }

    /**
     * Send email via Resend API
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        $api_key = rsmtp_get_settings( 'resend_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'Resend API key is not configured.', 'royal-smtp' ) );
        }

        $headers = $this->parse_headers( isset( $atts['headers'] ) ? $atts['headers'] : array() );

        // Build from
        $from_email = isset( $atts['from_email'] ) ? $atts['from_email'] : rsmtp_get_settings( 'from_email' );
        $from_name  = isset( $atts['from_name'] ) ? $atts['from_name'] : rsmtp_get_settings( 'from_name' );
        $from       = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;

        // Build recipients
        $to = $atts['to'];
        if ( ! is_array( $to ) ) {
            $to = array_map( 'trim', explode( ',', $to ) );
        }

        // Build request body
        $body = array(
            'from'    => $from,
            'to'      => $to,
            'subject' => isset( $atts['subject'] ) ? $atts['subject'] : '',
        );

        // Set content based on type
        if ( $headers['content_type'] === 'text/html' ) {
            $body['html'] = isset( $atts['message'] ) ? $atts['message'] : '';
        } else {
            $body['text'] = isset( $atts['message'] ) ? $atts['message'] : '';
        }

        // Add CC
        if ( ! empty( $headers['cc'] ) ) {
            $body['cc'] = array_map( function( $cc ) {
                return ! empty( $cc['name'] ) ? "{$cc['name']} <{$cc['email']}>" : $cc['email'];
            }, $headers['cc'] );
        }

        // Add BCC
        if ( ! empty( $headers['bcc'] ) ) {
            $body['bcc'] = array_map( function( $bcc ) {
                return ! empty( $bcc['name'] ) ? "{$bcc['name']} <{$bcc['email']}>" : $bcc['email'];
            }, $headers['bcc'] );
        }

        // Add reply-to
        if ( ! empty( $headers['reply_to'] ) ) {
            $reply_to = $headers['reply_to'][0];
            $body['reply_to'] = ! empty( $reply_to['name'] ) ? "{$reply_to['name']} <{$reply_to['email']}>" : $reply_to['email'];
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

        // Resend returns 200 for success
        if ( $response['code'] >= 200 && $response['code'] < 300 ) {
            return true;
        }

        $error_message = __( 'Resend API error', 'royal-smtp' );
        if ( isset( $response['body']['message'] ) ) {
            $error_message = $response['body']['message'];
        } elseif ( isset( $response['body']['error']['message'] ) ) {
            $error_message = $response['body']['error']['message'];
        }

        return new WP_Error( 'api_error', $error_message );
    }
}
