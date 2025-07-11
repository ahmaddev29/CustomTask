<?php

/**
 * Enhanced Reporting System
 *
 * @since      1.0.0
 */

/**
 * Reports management.
 *
 * This class defines all reporting and analytics functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Reports {

    /**
     * Database instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Booking_Master_Database    $db    Database operations.
     */
    private $db;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->db = new Booking_Master_Database();
    }

    /**
     * Initialize hooks.
     *
     * @since    1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_bm_generate_report', array( $this, 'ajax_generate_report' ) );
        add_action( 'wp_ajax_bm_export_report', array( $this, 'ajax_export_report' ) );
        add_action( 'wp_ajax_bm_get_dashboard_stats', array( $this, 'ajax_get_dashboard_stats' ) );
        add_action( 'wp_ajax_bm_get_mentor_stats', array( $this, 'ajax_get_mentor_stats' ) );
        add_action( 'wp_ajax_bm_get_revenue_chart', array( $this, 'ajax_get_revenue_chart' ) );
        add_action( 'wp_ajax_bm_get_booking_trends', array( $this, 'ajax_get_booking_trends' ) );
    }

    /**
     * Generate report (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_generate_report() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $report_type = sanitize_text_field( $_POST['report_type'] );
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );
        $mentor_id = isset( $_POST['mentor_id'] ) ? intval( $_POST['mentor_id'] ) : null;

        $report_data = $this->generate_report( $report_type, $start_date, $end_date, $mentor_id );
        
        wp_send_json_success( array( 'report' => $report_data ) );
    }

    /**
     * Export report (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_export_report() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $report_type = sanitize_text_field( $_POST['report_type'] );
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );
        $format = sanitize_text_field( $_POST['format'] ); // 'csv' or 'pdf'
        $mentor_id = isset( $_POST['mentor_id'] ) ? intval( $_POST['mentor_id'] ) : null;

        $export_url = $this->export_report( $report_type, $start_date, $end_date, $format, $mentor_id );
        
        if ( $export_url ) {
            wp_send_json_success( array( 'download_url' => $export_url ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to generate export' ) );
        }
    }

    /**
     * Get dashboard statistics (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_dashboard_stats() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $period = sanitize_text_field( $_POST['period'] ); // 'today', 'week', 'month', 'year'
        $stats = $this->get_dashboard_statistics( $period );
        
        wp_send_json_success( array( 'stats' => $stats ) );
    }

    /**
     * Get mentor statistics (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_mentor_stats() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = get_current_user_id();
        $period = sanitize_text_field( $_POST['period'] );
        $stats = $this->get_mentor_statistics( $mentor_id, $period );
        
        wp_send_json_success( array( 'stats' => $stats ) );
    }

    /**
     * Get revenue chart data (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_revenue_chart() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $period = sanitize_text_field( $_POST['period'] );
        $chart_data = $this->get_revenue_chart_data( $period );
        
        wp_send_json_success( array( 'chart' => $chart_data ) );
    }

    /**
     * Get booking trends (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_booking_trends() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_reports_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $period = sanitize_text_field( $_POST['period'] );
        $trends = $this->get_booking_trends( $period );
        
        wp_send_json_success( array( 'trends' => $trends ) );
    }

    /**
     * Generate report based on type and parameters
     *
     * @since    1.0.0
     * @param    string   $report_type    Report type.
     * @param    string   $start_date     Start date.
     * @param    string   $end_date       End date.
     * @param    int      $mentor_id      Mentor ID (optional).
     * @return   array                    Report data.
     */
    public function generate_report( $report_type, $start_date, $end_date, $mentor_id = null ) {
        switch ( $report_type ) {
            case 'bookings':
                return $this->generate_bookings_report( $start_date, $end_date, $mentor_id );
            case 'revenue':
                return $this->generate_revenue_report( $start_date, $end_date, $mentor_id );
            case 'mentors':
                return $this->generate_mentors_report( $start_date, $end_date );
            case 'services':
                return $this->generate_services_report( $start_date, $end_date, $mentor_id );
            case 'cancellations':
                return $this->generate_cancellations_report( $start_date, $end_date, $mentor_id );
            case 'payments':
                return $this->generate_payments_report( $start_date, $end_date, $mentor_id );
            default:
                return array();
        }
    }

    /**
     * Generate bookings report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @param    int      $mentor_id     Mentor ID (optional).
     * @return   array                   Report data.
     */
    private function generate_bookings_report( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        $where_clause = "WHERE b.booking_date BETWEEN %s AND %s";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND b.mentor_id = %d";
            $params[] = $mentor_id;
        }

        $query = "
            SELECT 
                b.*,
                s.service_name,
                mentor.display_name as mentor_name,
                mentee.display_name as mentee_name,
                mentee.user_email as mentee_email
            FROM $bookings_table b
            LEFT JOIN $services_table s ON b.service_id = s.id
            LEFT JOIN {$wpdb->users} mentor ON b.mentor_id = mentor.ID
            LEFT JOIN {$wpdb->users} mentee ON b.mentee_id = mentee.ID
            $where_clause
            ORDER BY b.booking_date DESC
        ";

        $bookings = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        // Summary statistics
        $total_bookings = count( $bookings );
        $confirmed_bookings = count( array_filter( $bookings, function( $booking ) {
            return $booking->status === 'confirmed';
        } ) );
        $cancelled_bookings = count( array_filter( $bookings, function( $booking ) {
            return $booking->status === 'cancelled';
        } ) );
        $completed_bookings = count( array_filter( $bookings, function( $booking ) {
            return $booking->status === 'completed';
        } ) );

        return array(
            'summary' => array(
                'total_bookings' => $total_bookings,
                'confirmed_bookings' => $confirmed_bookings,
                'cancelled_bookings' => $cancelled_bookings,
                'completed_bookings' => $completed_bookings,
                'cancellation_rate' => $total_bookings > 0 ? round( ( $cancelled_bookings / $total_bookings ) * 100, 2 ) : 0,
                'completion_rate' => $confirmed_bookings > 0 ? round( ( $completed_bookings / $confirmed_bookings ) * 100, 2 ) : 0,
            ),
            'bookings' => $bookings,
        );
    }

    /**
     * Generate revenue report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @param    int      $mentor_id     Mentor ID (optional).
     * @return   array                   Report data.
     */
    private function generate_revenue_report( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $where_clause = "WHERE booking_date BETWEEN %s AND %s AND status IN ('confirmed', 'completed') AND payment_status = 'paid'";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND mentor_id = %d";
            $params[] = $mentor_id;
        }

        // Total revenue
        $total_revenue = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_amount) FROM $bookings_table $where_clause",
            $params
        ) );

        // Revenue by month
        $monthly_revenue = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as bookings
             FROM $bookings_table 
             $where_clause
             GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
             ORDER BY month",
            $params
        ) );

        // Revenue by mentor
        $mentor_revenue = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                b.mentor_id,
                u.display_name as mentor_name,
                SUM(b.total_amount) as revenue,
                COUNT(*) as bookings,
                AVG(b.total_amount) as avg_booking_value
             FROM $bookings_table b
             LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID
             $where_clause
             GROUP BY b.mentor_id
             ORDER BY revenue DESC",
            $params
        ) );

        // Payment methods breakdown
        $payment_methods = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                payment_method,
                SUM(total_amount) as revenue,
                COUNT(*) as bookings
             FROM $bookings_table 
             $where_clause
             GROUP BY payment_method
             ORDER BY revenue DESC",
            $params
        ) );

        return array(
            'summary' => array(
                'total_revenue' => $total_revenue ?: 0,
                'total_bookings' => array_sum( array_column( $monthly_revenue, 'bookings' ) ),
                'average_booking_value' => $total_revenue && count( $monthly_revenue ) > 0 
                    ? round( $total_revenue / array_sum( array_column( $monthly_revenue, 'bookings' ) ), 2 ) 
                    : 0,
            ),
            'monthly_revenue' => $monthly_revenue,
            'mentor_revenue' => $mentor_revenue,
            'payment_methods' => $payment_methods,
        );
    }

    /**
     * Generate mentors report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @return   array                   Report data.
     */
    private function generate_mentors_report( $start_date, $end_date ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        $mentor_stats = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                u.ID as mentor_id,
                u.display_name as mentor_name,
                u.user_email as mentor_email,
                u.user_registered,
                COUNT(DISTINCT s.id) as total_services,
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) as completed_bookings,
                COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.id END) as cancelled_bookings,
                SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE NULL END) as avg_booking_value,
                MIN(b.booking_date) as first_booking,
                MAX(b.booking_date) as last_booking
             FROM {$wpdb->users} u
             LEFT JOIN $services_table s ON u.ID = s.mentor_id
             LEFT JOIN $bookings_table b ON u.ID = b.mentor_id AND b.booking_date BETWEEN %s AND %s
             WHERE u.ID IN (SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%mentor%')
             GROUP BY u.ID
             ORDER BY total_revenue DESC",
            $start_date,
            $end_date
        ) );

        // Calculate additional metrics
        foreach ( $mentor_stats as &$mentor ) {
            $mentor->cancellation_rate = $mentor->total_bookings > 0 
                ? round( ( $mentor->cancelled_bookings / $mentor->total_bookings ) * 100, 2 ) 
                : 0;
            $mentor->completion_rate = $mentor->total_bookings > 0 
                ? round( ( $mentor->completed_bookings / $mentor->total_bookings ) * 100, 2 ) 
                : 0;
        }

        return array(
            'mentors' => $mentor_stats,
            'summary' => array(
                'total_mentors' => count( $mentor_stats ),
                'active_mentors' => count( array_filter( $mentor_stats, function( $mentor ) {
                    return $mentor->total_bookings > 0;
                } ) ),
                'total_revenue' => array_sum( array_column( $mentor_stats, 'total_revenue' ) ),
                'total_bookings' => array_sum( array_column( $mentor_stats, 'total_bookings' ) ),
            ),
        );
    }

    /**
     * Generate services report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @param    int      $mentor_id     Mentor ID (optional).
     * @return   array                   Report data.
     */
    private function generate_services_report( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        $where_clause = "WHERE b.booking_date BETWEEN %s AND %s";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND s.mentor_id = %d";
            $params[] = $mentor_id;
        }

        $service_stats = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                s.id as service_id,
                s.service_name,
                s.price,
                s.duration,
                s.status,
                u.display_name as mentor_name,
                COUNT(b.id) as total_bookings,
                COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled_bookings,
                SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE NULL END) as avg_revenue_per_booking
             FROM $services_table s
             LEFT JOIN $bookings_table b ON s.id = b.service_id $where_clause
             LEFT JOIN {$wpdb->users} u ON s.mentor_id = u.ID
             GROUP BY s.id
             ORDER BY total_revenue DESC",
            $params
        ) );

        // Calculate additional metrics
        foreach ( $service_stats as &$service ) {
            $service->booking_rate = $service->total_bookings; // Could be enhanced with views data
            $service->cancellation_rate = $service->total_bookings > 0 
                ? round( ( $service->cancelled_bookings / $service->total_bookings ) * 100, 2 ) 
                : 0;
        }

        return array(
            'services' => $service_stats,
            'summary' => array(
                'total_services' => count( $service_stats ),
                'active_services' => count( array_filter( $service_stats, function( $service ) {
                    return $service->total_bookings > 0;
                } ) ),
                'total_revenue' => array_sum( array_column( $service_stats, 'total_revenue' ) ),
                'total_bookings' => array_sum( array_column( $service_stats, 'total_bookings' ) ),
            ),
        );
    }

    /**
     * Generate cancellations report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @param    int      $mentor_id     Mentor ID (optional).
     * @return   array                   Report data.
     */
    private function generate_cancellations_report( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        $where_clause = "WHERE b.status = 'cancelled' AND b.booking_date BETWEEN %s AND %s";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND b.mentor_id = %d";
            $params[] = $mentor_id;
        }

        $cancellations = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                b.*,
                s.service_name,
                mentor.display_name as mentor_name,
                mentee.display_name as mentee_name,
                DATEDIFF(b.booking_date, b.created_at) as days_in_advance
             FROM $bookings_table b
             LEFT JOIN $services_table s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} mentor ON b.mentor_id = mentor.ID
             LEFT JOIN {$wpdb->users} mentee ON b.mentee_id = mentee.ID
             $where_clause
             ORDER BY b.updated_at DESC",
            $params
        ) );

        // Cancellation patterns analysis
        $cancellation_by_time = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN DATEDIFF(booking_date, created_at) <= 1 THEN 'Same day'
                    WHEN DATEDIFF(booking_date, created_at) <= 7 THEN '1-7 days'
                    WHEN DATEDIFF(booking_date, created_at) <= 30 THEN '1-4 weeks'
                    ELSE 'More than 30 days'
                END as cancellation_timing,
                COUNT(*) as count
             FROM $bookings_table b
             $where_clause
             GROUP BY cancellation_timing
             ORDER BY count DESC",
            $params
        ) );

        return array(
            'cancellations' => $cancellations,
            'cancellation_patterns' => $cancellation_by_time,
            'summary' => array(
                'total_cancellations' => count( $cancellations ),
                'revenue_lost' => array_sum( array_column( $cancellations, 'total_amount' ) ),
                'average_cancellation_value' => count( $cancellations ) > 0 
                    ? round( array_sum( array_column( $cancellations, 'total_amount' ) ) / count( $cancellations ), 2 )
                    : 0,
            ),
        );
    }

    /**
     * Generate payments report
     *
     * @since    1.0.0
     * @param    string   $start_date    Start date.
     * @param    string   $end_date      End date.
     * @param    int      $mentor_id     Mentor ID (optional).
     * @return   array                   Report data.
     */
    private function generate_payments_report( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $where_clause = "WHERE booking_date BETWEEN %s AND %s";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND mentor_id = %d";
            $params[] = $mentor_id;
        }

        // Payment status breakdown
        $payment_status = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as amount
             FROM $bookings_table 
             $where_clause
             GROUP BY payment_status
             ORDER BY amount DESC",
            $params
        ) );

        // Payment method breakdown
        $payment_methods = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as amount,
                AVG(total_amount) as avg_amount
             FROM $bookings_table 
             $where_clause AND payment_status = 'paid'
             GROUP BY payment_method
             ORDER BY amount DESC",
            $params
        ) );

        // Failed payments
        $failed_payments = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                b.*,
                s.service_name,
                u.display_name as mentee_name
             FROM $bookings_table b
             LEFT JOIN {$wpdb->prefix}bm_services s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} u ON b.mentee_id = u.ID
             $where_clause AND payment_status = 'failed'
             ORDER BY b.created_at DESC",
            $params
        ) );

        // Refunds
        $refunds = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                b.*,
                s.service_name,
                u.display_name as mentee_name
             FROM $bookings_table b
             LEFT JOIN {$wpdb->prefix}bm_services s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} u ON b.mentee_id = u.ID
             $where_clause AND payment_status = 'refunded'
             ORDER BY b.refund_date DESC",
            $params
        ) );

        return array(
            'payment_status' => $payment_status,
            'payment_methods' => $payment_methods,
            'failed_payments' => $failed_payments,
            'refunds' => $refunds,
            'summary' => array(
                'total_processed' => array_sum( array_column( $payment_status, 'amount' ) ),
                'total_successful' => $wpdb->get_var( $wpdb->prepare(
                    "SELECT SUM(total_amount) FROM $bookings_table $where_clause AND payment_status = 'paid'",
                    $params
                ) ) ?: 0,
                'total_failed' => count( $failed_payments ),
                'total_refunded' => array_sum( array_column( $refunds, 'refund_amount' ) ),
            ),
        );
    }

    /**
     * Get dashboard statistics for a period
     *
     * @since    1.0.0
     * @param    string   $period    Period (today, week, month, year).
     * @return   array               Statistics.
     */
    public function get_dashboard_statistics( $period ) {
        $date_range = $this->get_date_range_for_period( $period );
        
        return array(
            'bookings' => $this->get_booking_stats( $date_range['start'], $date_range['end'] ),
            'revenue' => $this->get_revenue_stats( $date_range['start'], $date_range['end'] ),
            'users' => $this->get_user_stats( $date_range['start'], $date_range['end'] ),
            'popular_services' => $this->get_popular_services( $date_range['start'], $date_range['end'] ),
        );
    }

    /**
     * Get mentor statistics for a period
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $period       Period.
     * @return   array                  Statistics.
     */
    public function get_mentor_statistics( $mentor_id, $period ) {
        $date_range = $this->get_date_range_for_period( $period );
        
        return array(
            'bookings' => $this->get_booking_stats( $date_range['start'], $date_range['end'], $mentor_id ),
            'revenue' => $this->get_revenue_stats( $date_range['start'], $date_range['end'], $mentor_id ),
            'services' => $this->get_mentor_service_stats( $mentor_id, $date_range['start'], $date_range['end'] ),
            'availability' => $this->get_mentor_availability_stats( $mentor_id, $date_range['start'], $date_range['end'] ),
        );
    }

    /**
     * Get revenue chart data
     *
     * @since    1.0.0
     * @param    string   $period    Period.
     * @return   array               Chart data.
     */
    public function get_revenue_chart_data( $period ) {
        global $wpdb;
        
        $date_range = $this->get_date_range_for_period( $period );
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $interval = $period === 'year' ? '%Y-%m' : '%Y-%m-%d';
        
        $chart_data = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                DATE_FORMAT(booking_date, %s) as period,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
                COUNT(*) as bookings
             FROM $bookings_table 
             WHERE booking_date BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(booking_date, %s)
             ORDER BY period",
            $interval,
            $date_range['start'],
            $date_range['end'],
            $interval
        ) );

        return array(
            'labels' => array_column( $chart_data, 'period' ),
            'revenue' => array_column( $chart_data, 'revenue' ),
            'bookings' => array_column( $chart_data, 'bookings' ),
        );
    }

    /**
     * Get booking trends
     *
     * @since    1.0.0
     * @param    string   $period    Period.
     * @return   array               Trends data.
     */
    public function get_booking_trends( $period ) {
        global $wpdb;
        
        $date_range = $this->get_date_range_for_period( $period );
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Booking trends by status
        $status_trends = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m-%d') as date,
                status,
                COUNT(*) as count
             FROM $bookings_table 
             WHERE booking_date BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(booking_date, '%Y-%m-%d'), status
             ORDER BY date",
            $date_range['start'],
            $date_range['end']
        ) );

        // Peak booking hours
        $peak_hours = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                HOUR(booking_date) as hour,
                COUNT(*) as count
             FROM $bookings_table 
             WHERE booking_date BETWEEN %s AND %s
             GROUP BY HOUR(booking_date)
             ORDER BY count DESC",
            $date_range['start'],
            $date_range['end']
        ) );

        return array(
            'status_trends' => $status_trends,
            'peak_hours' => $peak_hours,
        );
    }

    /**
     * Export report in specified format
     *
     * @since    1.0.0
     * @param    string   $report_type    Report type.
     * @param    string   $start_date     Start date.
     * @param    string   $end_date       End date.
     * @param    string   $format         Export format.
     * @param    int      $mentor_id      Mentor ID (optional).
     * @return   string|false             Download URL or false on failure.
     */
    private function export_report( $report_type, $start_date, $end_date, $format, $mentor_id = null ) {
        $report_data = $this->generate_report( $report_type, $start_date, $end_date, $mentor_id );
        
        if ( $format === 'csv' ) {
            return $this->export_to_csv( $report_type, $report_data );
        } elseif ( $format === 'pdf' ) {
            return $this->export_to_pdf( $report_type, $report_data );
        }
        
        return false;
    }

    /**
     * Export report to CSV
     *
     * @since    1.0.0
     * @param    string   $report_type    Report type.
     * @param    array    $report_data    Report data.
     * @return   string|false             Download URL or false on failure.
     */
    private function export_to_csv( $report_type, $report_data ) {
        $upload_dir = wp_upload_dir();
        $filename = $report_type . '_report_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen( $filepath, 'w' );
        if ( ! $file ) {
            return false;
        }

        // Write CSV content based on report type
        switch ( $report_type ) {
            case 'bookings':
                fputcsv( $file, array( 'ID', 'Service', 'Mentor', 'Mentee', 'Date', 'Amount', 'Status', 'Payment Status' ) );
                foreach ( $report_data['bookings'] as $booking ) {
                    fputcsv( $file, array(
                        $booking->id,
                        $booking->service_name,
                        $booking->mentor_name,
                        $booking->mentee_name,
                        $booking->booking_date,
                        $booking->total_amount,
                        $booking->status,
                        $booking->payment_status
                    ) );
                }
                break;
            
            case 'revenue':
                fputcsv( $file, array( 'Period', 'Revenue', 'Bookings' ) );
                foreach ( $report_data['monthly_revenue'] as $revenue ) {
                    fputcsv( $file, array(
                        $revenue->month,
                        $revenue->revenue,
                        $revenue->bookings
                    ) );
                }
                break;
        }

        fclose( $file );
        
        return $upload_dir['url'] . '/' . $filename;
    }

    /**
     * Export report to PDF (placeholder)
     *
     * @since    1.0.0
     * @param    string   $report_type    Report type.
     * @param    array    $report_data    Report data.
     * @return   string|false             Download URL or false on failure.
     */
    private function export_to_pdf( $report_type, $report_data ) {
        // PDF export functionality would require a library like TCPDF or FPDF
        // This is a placeholder implementation
        return false;
    }

    /**
     * Get date range for period
     *
     * @since    1.0.0
     * @param    string   $period    Period.
     * @return   array               Start and end dates.
     */
    private function get_date_range_for_period( $period ) {
        switch ( $period ) {
            case 'today':
                return array(
                    'start' => date( 'Y-m-d 00:00:00' ),
                    'end' => date( 'Y-m-d 23:59:59' ),
                );
            case 'week':
                return array(
                    'start' => date( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) ),
                    'end' => date( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) ),
                );
            case 'month':
                return array(
                    'start' => date( 'Y-m-01 00:00:00' ),
                    'end' => date( 'Y-m-t 23:59:59' ),
                );
            case 'year':
                return array(
                    'start' => date( 'Y-01-01 00:00:00' ),
                    'end' => date( 'Y-12-31 23:59:59' ),
                );
            default:
                return array(
                    'start' => date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) ),
                    'end' => date( 'Y-m-d 23:59:59' ),
                );
        }
    }

    // Helper methods for statistics
    private function get_booking_stats( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $where_clause = "WHERE booking_date BETWEEN %s AND %s";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND mentor_id = %d";
            $params[] = $mentor_id;
        }

        return array(
            'total' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table $where_clause",
                $params
            ) ),
            'confirmed' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table $where_clause AND status = 'confirmed'",
                $params
            ) ),
            'completed' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table $where_clause AND status = 'completed'",
                $params
            ) ),
            'cancelled' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table $where_clause AND status = 'cancelled'",
                $params
            ) ),
        );
    }

    private function get_revenue_stats( $start_date, $end_date, $mentor_id = null ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $where_clause = "WHERE booking_date BETWEEN %s AND %s AND payment_status = 'paid'";
        $params = array( $start_date, $end_date );
        
        if ( $mentor_id ) {
            $where_clause .= " AND mentor_id = %d";
            $params[] = $mentor_id;
        }

        return array(
            'total' => $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_amount) FROM $bookings_table $where_clause",
                $params
            ) ) ?: 0,
            'average' => $wpdb->get_var( $wpdb->prepare(
                "SELECT AVG(total_amount) FROM $bookings_table $where_clause",
                $params
            ) ) ?: 0,
        );
    }

    private function get_user_stats( $start_date, $end_date ) {
        global $wpdb;
        
        return array(
            'new_mentors' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                 WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                 AND um.meta_value LIKE '%mentor%' 
                 AND u.user_registered BETWEEN %s AND %s",
                $start_date,
                $end_date
            ) ),
            'new_mentees' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                 WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                 AND um.meta_value LIKE '%mentee%' 
                 AND u.user_registered BETWEEN %s AND %s",
                $start_date,
                $end_date
            ) ),
        );
    }

    private function get_popular_services( $start_date, $end_date ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                s.service_name,
                COUNT(b.id) as booking_count,
                SUM(b.total_amount) as revenue
             FROM {$wpdb->prefix}bm_services s
             LEFT JOIN {$wpdb->prefix}bm_bookings b ON s.id = b.service_id 
                 AND b.booking_date BETWEEN %s AND %s
             GROUP BY s.id
             ORDER BY booking_count DESC
             LIMIT 5",
            $start_date,
            $end_date
        ) );
    }

    private function get_mentor_service_stats( $mentor_id, $start_date, $end_date ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                s.service_name,
                s.price,
                COUNT(b.id) as bookings,
                SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END) as revenue
             FROM {$wpdb->prefix}bm_services s
             LEFT JOIN {$wpdb->prefix}bm_bookings b ON s.id = b.service_id 
                 AND b.booking_date BETWEEN %s AND %s
             WHERE s.mentor_id = %d
             GROUP BY s.id
             ORDER BY bookings DESC",
            $start_date,
            $end_date,
            $mentor_id
        ) );
    }

    private function get_mentor_availability_stats( $mentor_id, $start_date, $end_date ) {
        // This would integrate with the availability management system
        return array(
            'total_slots' => 0,
            'booked_slots' => 0,
            'blocked_slots' => 0,
            'utilization_rate' => 0,
        );
    }
}