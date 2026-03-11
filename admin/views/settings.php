<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to included file scope.

$settings       = rsmtp_get_settings();
$mailer_options = RSMTP_Admin::get_mailer_options();
$current_mailer = $settings['mailer'];
$mailers        = RSMTP_Core::instance()->get_mailers();
?>

<div class="wrap rsmtp-settings">
    <h1><?php esc_html_e( 'Royal SMTP Settings', 'royal-smtp' ); ?></h1>

    <form method="post" action="" id="rsmtp-settings-form">
        <?php wp_nonce_field( 'rsmtp_settings', 'rsmtp_settings_nonce' ); ?>

        <!-- From Settings -->
        <div class="rsmtp-card">
            <h2><?php esc_html_e( 'From Settings', 'royal-smtp' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure the default sender information for all outgoing emails.', 'royal-smtp' ); ?></p>

            <table class="form-table">
                <tr>
                    <th><label for="from_email"><?php esc_html_e( 'From Email', 'royal-smtp' ); ?></label></th>
                    <td>
                        <input type="email" name="from_email" id="from_email" class="regular-text" value="<?php echo esc_attr( $settings['from_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                        <br>
                        <label>
                            <input type="checkbox" name="force_from_email" value="1" <?php checked( $settings['force_from_email'] ); ?>>
                            <?php esc_html_e( 'Force From Email (override other plugins)', 'royal-smtp' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="from_name"><?php esc_html_e( 'From Name', 'royal-smtp' ); ?></label></th>
                    <td>
                        <input type="text" name="from_name" id="from_name" class="regular-text" value="<?php echo esc_attr( $settings['from_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                        <br>
                        <label>
                            <input type="checkbox" name="force_from_name" value="1" <?php checked( $settings['force_from_name'] ); ?>>
                            <?php esc_html_e( 'Force From Name (override other plugins)', 'royal-smtp' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Mailer Selection -->
        <div class="rsmtp-card">
            <h2><?php esc_html_e( 'Mailer', 'royal-smtp' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Select how you want to send emails from your site.', 'royal-smtp' ); ?></p>

            <table class="form-table">
                <tr>
                    <th><label for="mailer"><?php esc_html_e( 'Select Mailer', 'royal-smtp' ); ?></label></th>
                    <td>
                        <select name="mailer" id="mailer" class="regular-text">
                            <?php foreach ( $mailer_options as $id => $name ) : ?>
                                <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_mailer, $id ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Dynamic mailer settings -->
            <?php foreach ( $mailers as $id => $mailer ) : ?>
                <div class="rsmtp-mailer-settings" id="rsmtp-mailer-<?php echo esc_attr( $id ); ?>" style="<?php echo $current_mailer !== $id ? 'display:none;' : ''; ?>">
                    <?php if ( $mailer->get_description() ) : ?>
                        <p class="description" style="margin: 15px 0;"><?php echo esc_html( $mailer->get_description() ); ?></p>
                    <?php endif; ?>

                    <table class="form-table">
                        <?php foreach ( $mailer->get_settings_fields() as $field_id => $field ) : ?>
                            <tr>
                                <th><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                                <td>
                                    <?php
                                    $value = $settings[ $field_id ] ?? ( $field['default'] ?? '' );

                                    switch ( $field['type'] ) {
                                        case 'password':
                                            $display_value = RSMTP_Admin::mask_value( $value );
                                            ?>
                                            <input type="password" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" class="regular-text" value="<?php echo esc_attr( $display_value ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>" autocomplete="new-password">
                                            <?php
                                            break;

                                        case 'select':
                                            ?>
                                            <select name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>">
                                                <?php foreach ( $field['options'] as $opt_val => $opt_label ) : ?>
                                                    <option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>><?php echo esc_html( $opt_label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php
                                            break;

                                        case 'checkbox':
                                            ?>
                                            <label>
                                                <input type="checkbox" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="1" <?php checked( $value ); ?>>
                                                <?php echo isset( $field['description'] ) ? esc_html( $field['description'] ) : ''; ?>
                                            </label>
                                            <?php
                                            break;

                                        case 'number':
                                            ?>
                                            <input type="number" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" class="small-text" value="<?php echo esc_attr( $value ); ?>">
                                            <?php
                                            break;

                                        default: // text
                                            ?>
                                            <input type="text" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" class="regular-text" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>">
                                            <?php
                                    }

                                    if ( ! empty( $field['description'] ) && $field['type'] !== 'checkbox' ) :
                                        ?>
                                        <p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p>
                                        <?php
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Test Email -->
        <div class="rsmtp-card">
            <h2><?php esc_html_e( 'Test Email', 'royal-smtp' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Send a test email to verify your configuration is working correctly.', 'royal-smtp' ); ?></p>

            <table class="form-table">
                <tr>
                    <th><label for="test_email"><?php esc_html_e( 'Send To', 'royal-smtp' ); ?></label></th>
                    <td>
                        <input type="email" name="test_email" id="test_email" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter email address', 'royal-smtp' ); ?>">
                        <button type="button" class="button" id="rsmtp-send-test"><?php esc_html_e( 'Send Test Email', 'royal-smtp' ); ?></button>
                        <span id="rsmtp-test-result" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Email Logging -->
        <div class="rsmtp-card">
            <h2><?php esc_html_e( 'Email Logging', 'royal-smtp' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Log sent emails for debugging and monitoring purposes.', 'royal-smtp' ); ?></p>

            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enable Logging', 'royal-smtp' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_log" value="1" <?php checked( $settings['enable_log'] ); ?>>
                            <?php esc_html_e( 'Log all outgoing emails', 'royal-smtp' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="log_retention"><?php esc_html_e( 'Log Retention', 'royal-smtp' ); ?></label></th>
                    <td>
                        <input type="number" name="log_retention" id="log_retention" class="small-text" value="<?php echo esc_attr( $settings['log_retention'] ); ?>" min="10" max="1000">
                        <?php esc_html_e( 'emails', 'royal-smtp' ); ?>
                        <p class="description"><?php esc_html_e( 'Maximum number of email logs to keep. Older logs are automatically deleted.', 'royal-smtp' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="rsmtp_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'royal-smtp' ); ?>">
        </p>
    </form>
</div>
