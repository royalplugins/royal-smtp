<?php
/**
 * Mailgun Mailer - API-based email sending
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Mailer_Mailgun extends RSMTP_Mailer {

    /**
     * Get mailer ID
     *
     * @return string
     */
    public function get_id() {
        return 'mailgun';
    }

    /**
     * Get mailer name
     *
     * @return string
     */
    public function get_name() {
        return 'Mailgun';
    }

    /**
     * Get mailer description
     *
     * @return string
     */
    public function get_description() {
        return __( 'Mailgun - powerful email APIs for developers.', 'royal-smtp' );
    }

    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'mailgun_api_key' => array(
                'label'       => __( 'API Key', 'royal-smtp' ),
                'type'        => 'password',
                'placeholder' => 'key-xxxxxxxx',
                'description' => sprintf(
                    /* translators: %s: Mailgun API keys URL */
                    __( 'Get your API key from %s', 'royal-smtp' ),
                    '<a href="https://app.mailgun.com/app/account/security/api_keys" target="_blank">Mailgun Dashboard</a>'
                ),
            ),
            'mailgun_domain' => array(
                'label'       => __( 'Sending Domain', 'royal-smtp' ),
                'type'        => 'text',
                'placeholder' => 'mg.yourdomain.com',
                'description' => __( 'Your verified Mailgun sending domain.', 'royal-smtp' ),
            ),
            'mailgun_region' => array(
                'label'   => __( 'Region', 'royal-smtp' ),
                'type'    => 'select',
                'default' => 'us',
                'options' => array(
                    'us' => __( 'US', 'royal-smtp' ),
                    'eu' => __( 'EU', 'royal-smtp' ),
                ),
                'description' => __( 'Select the region where your Mailgun account is hosted.', 'royal-smtp' ),
            ),
        );
    }

    /**
     * Check if mailer is configured
     *
     * @return bool
     */
    public function is_configured() {
        $api_key = rsmtp_get_settings( 'mailgun_api_key' );
        $domain  = rsmtp_get_settings( 'mailgun_domain' );
        return ! empty( $api_key ) && ! empty( $domain );
    }

    /**
     * Get API URL based on region
     *
     * @return string
     */
    private function get_api_url() {
        $region = rsmtp_get_settings( 'mailgun_region', 'us' );
        $domain = rsmtp_get_settings( 'mailgun_domain' );

        if ( 'eu' === $region ) {
            return 'https://api.eu.mailgun.net/v3/' . $domain . '/messages';
        }

        return 'https://api.mailgun.net/v3/' . $domain . '/messages';
    }

    /**
     * Send email via Mailgun API
     *
     * @param array $atts Email attributes.
     * @return bool|WP_Error
     */
    public function send( $atts ) {
        $api_key = rsmtp_get_settings( 'mailgun_api_key' );
        $domain  = rsmtp_get_settings( 'mailgun_domain' );

        if ( empty( $api_key ) || empty( $domain ) ) {
            return new WP_Error( 'missing_credentials', __( 'Mailgun API key or domain is not configured.', 'royal-smtp' ) );
        }

        $headers = $this->parse_headers( isset( $atts['headers'] ) ? $atts['headers'] : array() );

        // Build from
        $from_email = isset( $atts['from_email'] ) ? $atts['from_email'] : rsmtp_get_settings( 'from_email' );
        $from_name  = isset( $atts['from_name'] ) ? $atts['from_name'] : rsmtp_get_settings( 'from_name' );
        $from       = ! empty( $from_name ) ? "{$from_name} <{$from_email}>" : $from_email;

        // Build recipients
        $to = $atts['to'];
        if ( is_array( $to ) ) {
            $to = implode( ', ', $to );
        }

        // Build form data
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
            $cc_list = array();
            foreach ( $headers['cc'] as $cc ) {
                $cc_list[] = ! empty( $cc['name'] ) ? "{$cc['name']} <{$cc['email']}>" : $cc['email'];
            }
            $body['cc'] = implode( ', ', $cc_list );
        }

        // Add BCC
        if ( ! empty( $headers['bcc'] ) ) {
            $bcc_list = array();
            foreach ( $headers['bcc'] as $bcc ) {
                $bcc_list[] = ! empty( $bcc['name'] ) ? "{$bcc['name']} <{$bcc['email']}>" : $bcc['email'];
            }
            $body['bcc'] = implode( ', ', $bcc_list );
        }

        // Add reply-to
        if ( ! empty( $headers['reply_to'] ) ) {
            $reply_to = $headers['reply_to'][0];
            $body['h:Reply-To'] = ! empty( $reply_to['name'] ) ? "{$reply_to['name']} <{$reply_to['email']}>" : $reply_to['email'];
        }

        // Make API request (Mailgun uses form-urlencoded, not JSON)
        $response = wp_remote_post( $this->get_api_url(), array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
            ),
            'body' => $body,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        // Mailgun returns 200 for success
        if ( $code >= 200 && $code < 300 ) {
            return true;
        }

        $error_message = __( 'Mailgun API error', 'royal-smtp' );
        if ( isset( $data['message'] ) ) {
            $error_message = $data['message'];
        }

        return new WP_Error( 'api_error', $error_message );
    }
}
