<?php
/**
 * Email Logger Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RSMTP_Logger {

    /**
     * Singleton instance
     *
     * @var RSMTP_Logger
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return RSMTP_Logger
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Log an email
     *
     * @param array $data Email data.
     * @return int|false Insert ID or false on failure.
     */
    public function log( $data ) {
        if ( ! rsmtp_get_settings( 'enable_log', true ) ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        $defaults = array(
            'to_email'      => '',
            'subject'       => '',
            'status'        => 'sent',
            'mailer'        => '',
            'error_message' => null,
            'headers'       => null,
            'created_at'    => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        // Sanitize data
        $data['to_email']      = sanitize_email( $data['to_email'] );
        $data['subject']       = sanitize_text_field( $data['subject'] );
        $data['status']        = sanitize_key( $data['status'] );
        $data['mailer']        = sanitize_key( $data['mailer'] );
        $data['error_message'] = $data['error_message'] ? sanitize_textarea_field( $data['error_message'] ) : null;
        $data['headers']       = $data['headers'] ? sanitize_textarea_field( $data['headers'] ) : null;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert(
            $table,
            $data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            $this->cleanup_old_logs();
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get email logs
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_logs( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        $defaults = array(
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'search'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = '(to_email LIKE %s OR subject LIKE %s)';
            $values[] = $search;
            $values[] = $search;
        }

        $where_sql = implode( ' AND ', $where );
        $orderby   = in_array( $args['orderby'], array( 'created_at', 'to_email', 'subject', 'status', 'mailer' ), true ) ? $args['orderby'] : 'created_at';
        $order     = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        $offset    = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
        $limit     = absint( $args['per_page'] );

        // Get total count
        // phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders -- Table name from $wpdb->prefix is safe. Dynamic WHERE built from whitelisted values.
        if ( ! empty( $values ) ) {
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
                $values
            ) );
        } else {
            $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
        }

        // Get logs
        $values[] = $limit;
        $values[] = $offset;

        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            $values
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders

        return array(
            'logs'       => $logs,
            'total'      => (int) $total,
            'total_pages' => ceil( $total / $args['per_page'] ),
            'page'       => $args['page'],
            'per_page'   => $args['per_page'],
        );
    }

    /**
     * Get a single log entry
     *
     * @param int $id Log ID.
     * @return object|null
     */
    public function get_log( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            absint( $id )
        ) );
    }

    /**
     * Delete a log entry
     *
     * @param int $id Log ID.
     * @return bool
     */
    public function delete_log( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    /**
     * Clear all logs
     *
     * @return bool
     */
    public function clear_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
        return $wpdb->query( "TRUNCATE TABLE {$table}" );
    }

    /**
     * Cleanup old logs based on retention setting
     */
    private function cleanup_old_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        $retention = absint( rsmtp_get_settings( 'log_retention', 100 ) );
        if ( $retention < 1 ) {
            $retention = 100;
        }

        // Get count of logs
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        if ( $count > $retention ) {
            $to_delete = $count - $retention;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d",
                $to_delete
            ) );
        }
    }

    /**
     * Get log statistics
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'rsmtp_email_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
        $sent = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'sent'" );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
        $failed = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" );

        return array(
            'total'  => (int) $total,
            'sent'   => (int) $sent,
            'failed' => (int) $failed,
        );
    }
}
