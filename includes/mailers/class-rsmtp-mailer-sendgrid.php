<?php
/**
 * SendGrid Mailer - API-based email sending
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_SendGrid extends RSMTP_Mailer {

    /**
     * API endpoint
     */
    const API_URL = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'sendgrid';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return 'SendGrid';
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'Twilio SendGrid - reliable email delivery at scale.', 'royal-smtp' );
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'sendgrid_api_key' => array(
                'label'       => __( 'API Key', 'royal-smtp' ),
                'type'        => 'password',
                'placeholder' => 'SG.xxxxxxxx',
                'description' => sprintf(
                    /* translators: %s: SendGrid API keys URL */
                    __( 'Get your API key from %s', 'royal-smtp' ),
                    '<a href="https://app.sendgrid.com/settings/api_keys" target="_blank">SendGrid Dashboard</a>'
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
        $api_key = rsmtp_get_settings( 'sendgrid_api_key' );
        return ! empty( $api_key );
    }

    /**
     * Send email via SendGrid API
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        $api_key = rsmtp_get_settings( 'sendgrid_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'SendGrid API key is not configured.', 'royal-smtp' ) );
        }

        $headers = $this->parse_headers( isset( $atts['headers'] ) ? $atts['headers'] : array() );

        // Build recipients
        $to = $this->parse_emails( $atts['to'] );
        $personalizations = array(
            'to' => array_map( function( $recipient ) {
                $item = array( 'email' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $to ),
        );

        // Add CC
        if ( ! empty( $headers['cc'] ) ) {
            $personalizations['cc'] = array_map( function( $recipient ) {
                $item = array( 'email' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $headers['cc'] );
        }

        // Add BCC
        if ( ! empty( $headers['bcc'] ) ) {
            $personalizations['bcc'] = array_map( function( $recipient ) {
                $item = array( 'email' => $recipient['email'] );
                if ( ! empty( $recipient['name'] ) ) {
                    $item['name'] = $recipient['name'];
                }
                return $item;
            }, $headers['bcc'] );
        }

        // Build from
        $from = array(
            'email' => isset( $atts['from_email'] ) ? $atts['from_email'] : rsmtp_get_settings( 'from_email' ),
        );
        $from_name = isset( $atts['from_name'] ) ? $atts['from_name'] : rsmtp_get_settings( 'from_name' );
        if ( ! empty( $from_name ) ) {
            $from['name'] = $from_name;
        }

        // Build content
        $content_type = $headers['content_type'];
        $content = array(
            array(
                'type'  => $content_type,
                'value' => isset( $atts['message'] ) ? $atts['message'] : '',
            ),
        );

        // Build request body
        $body = array(
            'personalizations' => array( $personalizations ),
            'from'             => $from,
            'subject'          => isset( $atts['subject'] ) ? $atts['subject'] : '',
            'content'          => $content,
        );

        // Add reply-to
        if ( ! empty( $headers['reply_to'] ) ) {
            $reply_to = $headers['reply_to'][0];
            $body['reply_to'] = array( 'email' => $reply_to['email'] );
            if ( ! empty( $reply_to['name'] ) ) {
                $body['reply_to']['name'] = $reply_to['name'];
            }
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

        // SendGrid returns 202 for success
        if ( $response['code'] >= 200 && $response['code'] < 300 ) {
            return true;
        }

        $error_message = __( 'SendGrid API error', 'royal-smtp' );
        if ( isset( $response['body']['errors'][0]['message'] ) ) {
            $error_message = $response['body']['errors'][0]['message'];
        }

        return new WP_Error( 'api_error', $error_message );
    }
}
