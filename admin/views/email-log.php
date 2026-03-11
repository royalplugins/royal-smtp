<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to included file scope.

$page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page = 20;
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$log_data = RSMTP_Logger::instance()->get_logs( array(
    'page'     => $page,
    'per_page' => $per_page,
    'search'   => $search,
    'status'   => $status,
) );

$logs        = $log_data['logs'];
$total       = $log_data['total'];
$total_pages = $log_data['total_pages'];
$stats       = RSMTP_Logger::instance()->get_stats();
?>

<div class="wrap rsmtp-email-log">
    <h1>
        <?php esc_html_e( 'Email Log', 'royal-smtp' ); ?>
        <?php if ( ! empty( $logs ) ) : ?>
            <button type="button" class="button" id="rsmtp-clear-log" style="margin-left: 10px;"><?php esc_html_e( 'Clear Log', 'royal-smtp' ); ?></button>
        <?php endif; ?>
    </h1>

    <!-- Stats -->
    <div class="rsmtp-stats">
        <span class="rsmtp-stat">
            <strong><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></strong>
            <?php esc_html_e( 'Total', 'royal-smtp' ); ?>
        </span>
        <span class="rsmtp-stat rsmtp-stat-success">
            <strong><?php echo esc_html( number_format_i18n( $stats['sent'] ) ); ?></strong>
            <?php esc_html_e( 'Sent', 'royal-smtp' ); ?>
        </span>
        <span class="rsmtp-stat rsmtp-stat-error">
            <strong><?php echo esc_html( number_format_i18n( $stats['failed'] ) ); ?></strong>
            <?php esc_html_e( 'Failed', 'royal-smtp' ); ?>
        </span>
    </div>

    <!-- Filters -->
    <form method="get" class="rsmtp-filters">
        <input type="hidden" name="page" value="rsmtp-email-log">

        <select name="status">
            <option value=""><?php esc_html_e( 'All Statuses', 'royal-smtp' ); ?></option>
            <option value="sent" <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'royal-smtp' ); ?></option>
            <option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'royal-smtp' ); ?></option>
        </select>

        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search emails...', 'royal-smtp' ); ?>">

        <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'royal-smtp' ); ?>">
    </form>

    <?php if ( empty( $logs ) ) : ?>
        <div class="rsmtp-no-logs">
            <span class="dashicons dashicons-email-alt"></span>
            <p><?php esc_html_e( 'No email logs found.', 'royal-smtp' ); ?></p>
            <?php if ( ! rsmtp_get_settings( 'enable_log' ) ) : ?>
                <p class="description"><?php esc_html_e( 'Email logging is currently disabled. Enable it in settings to start logging emails.', 'royal-smtp' ); ?></p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-date"><?php esc_html_e( 'Date', 'royal-smtp' ); ?></th>
                    <th class="column-to"><?php esc_html_e( 'To', 'royal-smtp' ); ?></th>
                    <th class="column-subject"><?php esc_html_e( 'Subject', 'royal-smtp' ); ?></th>
                    <th class="column-mailer"><?php esc_html_e( 'Mailer', 'royal-smtp' ); ?></th>
                    <th class="column-status"><?php esc_html_e( 'Status', 'royal-smtp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td class="column-date">
                            <?php
                            $date = strtotime( $log->created_at );
                            echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date ) );
                            ?>
                        </td>
                        <td class="column-to">
                            <?php echo esc_html( $log->to_email ); ?>
                        </td>
                        <td class="column-subject">
                            <?php echo esc_html( $log->subject ); ?>
                            <?php if ( ! empty( $log->error_message ) ) : ?>
                                <div class="rsmtp-error-message">
                                    <small><?php echo esc_html( $log->error_message ); ?></small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-mailer">
                            <?php echo esc_html( ucfirst( $log->mailer ) ); ?>
                        </td>
                        <td class="column-status">
                            <?php if ( 'sent' === $log->status ) : ?>
                                <span class="rsmtp-status rsmtp-status-sent"><?php esc_html_e( 'Sent', 'royal-smtp' ); ?></span>
                            <?php else : ?>
                                <span class="rsmtp-status rsmtp-status-failed"><?php esc_html_e( 'Failed', 'royal-smtp' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        /* translators: %s: Number of items */
                        printf( esc_html( _n( '%s item', '%s items', $total, 'royal-smtp' ) ), esc_html( number_format_i18n( $total ) ) );
                        ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        echo wp_kses_post( paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'current'   => $page,
                            'total'     => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ) ) );
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
