<?php

/**
 * Email Notifications System
 *
 * @since      1.0.0
 */

/**
 * Email Notifications management.
 *
 * This class defines all email notification functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Email_Notifications {

    /**
     * Database instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Booking_Master_Database    $db    Database operations.
     */
    private $db;

    /**
     * Email templates directory
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $templates_dir    Email templates directory path.
     */
    private $templates_dir;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->db = new Booking_Master_Database();
        $this->templates_dir = plugin_dir_path( __FILE__ ) . '../templates/emails/';
    }

    /**
     * Initialize hooks.
     *
     * @since    1.0.0
     */
    public function init() {
        // Booking-related email triggers
        add_action( 'bm_booking_created', array( $this, 'send_booking_confirmation' ), 10, 1 );
        add_action( 'bm_booking_status_changed', array( $this, 'send_status_change_notification' ), 10, 2 );
        add_action( 'bm_booking_cancelled', array( $this, 'send_cancellation_notification' ), 10, 1 );
        add_action( 'bm_booking_reminder', array( $this, 'send_reminder_notification' ), 10, 1 );
        
        // Payment-related email triggers
        add_action( 'bm_payment_completed', array( $this, 'send_payment_confirmation' ), 10, 1 );
        add_action( 'bm_payment_failed', array( $this, 'send_payment_failure_notification' ), 10, 1 );
        add_action( 'bm_refund_processed', array( $this, 'send_refund_notification' ), 10, 1 );
        
        // Service-related email triggers
        add_action( 'bm_service_created', array( $this, 'send_service_approval_request' ), 10, 1 );
        add_action( 'bm_service_approved', array( $this, 'send_service_approval_notification' ), 10, 1 );
        
        // Admin notifications
        add_action( 'bm_new_user_registered', array( $this, 'send_new_user_notification' ), 10, 2 );
        
        // Scheduled email tasks
        add_action( 'bm_send_reminder_emails', array( $this, 'process_reminder_emails' ) );
        add_action( 'bm_send_follow_up_emails', array( $this, 'process_follow_up_emails' ) );
        
        // Schedule recurring email tasks
        if ( ! wp_next_scheduled( 'bm_send_reminder_emails' ) ) {
            wp_schedule_event( time(), 'hourly', 'bm_send_reminder_emails' );
        }
        
        if ( ! wp_next_scheduled( 'bm_send_follow_up_emails' ) ) {
            wp_schedule_event( time(), 'daily', 'bm_send_follow_up_emails' );
        }
    }

    /**
     * Send booking confirmation email
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function send_booking_confirmation( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $service = $this->db->get_service( $booking->service_id );

        // Send to mentee
        $mentee_template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'mentor_name' => $mentor->display_name,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'booking_time' => date( 'g:i A', strtotime( $booking->booking_date ) ),
            'duration' => $service->duration . ' minutes',
            'amount' => '$' . number_format( $booking->total_amount, 2 ),
            'booking_id' => $booking->id,
            'status' => ucfirst( $booking->status ),
            'zoom_enabled' => $service->zoom_enabled,
        );

        $this->send_email(
            $mentee->user_email,
            'Booking Confirmation - ' . $service->service_name,
            'booking-confirmation-mentee',
            $mentee_template_data
        );

        // Send to mentor
        $mentor_template_data = array(
            'user_name' => $mentor->display_name,
            'service_name' => $service->service_name,
            'mentee_name' => $mentee->display_name,
            'mentee_email' => $mentee->user_email,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'booking_time' => date( 'g:i A', strtotime( $booking->booking_date ) ),
            'duration' => $service->duration . ' minutes',
            'amount' => '$' . number_format( $booking->total_amount, 2 ),
            'booking_id' => $booking->id,
            'notes' => $booking->notes,
            'dashboard_url' => admin_url( 'admin.php?page=booking-master-mentor' ),
        );

        $this->send_email(
            $mentor->user_email,
            'New Booking Request - ' . $service->service_name,
            'booking-notification-mentor',
            $mentor_template_data
        );

        return true;
    }

    /**
     * Send booking status change notification
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @param    string   $new_status    New booking status.
     */
    public function send_status_change_notification( $booking_id, $new_status ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $service = $this->db->get_service( $booking->service_id );

        $template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'mentor_name' => $mentor->display_name,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'booking_time' => date( 'g:i A', strtotime( $booking->booking_date ) ),
            'status' => ucfirst( $new_status ),
            'status_label' => $this->get_status_label( $new_status ),
            'booking_id' => $booking->id,
            'zoom_join_url' => $booking->zoom_join_url,
            'zoom_enabled' => $service->zoom_enabled && ! empty( $booking->zoom_join_url ),
        );

        $subject = $this->get_status_subject( $new_status, $service->service_name );
        $template = $this->get_status_template( $new_status );

        $this->send_email(
            $mentee->user_email,
            $subject,
            $template,
            $template_data
        );

        return true;
    }

    /**
     * Send cancellation notification
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function send_cancellation_notification( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $service = $this->db->get_service( $booking->service_id );

        $template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'mentor_name' => $mentor->display_name,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'booking_time' => date( 'g:i A', strtotime( $booking->booking_date ) ),
            'booking_id' => $booking->id,
            'refund_info' => $booking->payment_status === 'paid' ? 'A refund will be processed within 3-5 business days.' : '',
        );

        // Send to mentee
        $this->send_email(
            $mentee->user_email,
            'Booking Cancelled - ' . $service->service_name,
            'booking-cancelled-mentee',
            $template_data
        );

        // Send to mentor
        $mentor_template_data = $template_data;
        $mentor_template_data['user_name'] = $mentor->display_name;
        $mentor_template_data['mentee_name'] = $mentee->display_name;

        $this->send_email(
            $mentor->user_email,
            'Booking Cancelled - ' . $service->service_name,
            'booking-cancelled-mentor',
            $mentor_template_data
        );

        return true;
    }

    /**
     * Send reminder notification
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function send_reminder_notification( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $service = $this->db->get_service( $booking->service_id );

        $template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'mentor_name' => $mentor->display_name,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'booking_time' => date( 'g:i A', strtotime( $booking->booking_date ) ),
            'duration' => $service->duration . ' minutes',
            'booking_id' => $booking->id,
            'zoom_join_url' => $booking->zoom_join_url,
            'zoom_enabled' => $service->zoom_enabled && ! empty( $booking->zoom_join_url ),
            'hours_until' => $this->get_hours_until_booking( $booking->booking_date ),
        );

        // Send to mentee
        $this->send_email(
            $mentee->user_email,
            'Reminder: Upcoming Session - ' . $service->service_name,
            'booking-reminder-mentee',
            $template_data
        );

        // Send to mentor
        $mentor_template_data = $template_data;
        $mentor_template_data['user_name'] = $mentor->display_name;
        $mentor_template_data['mentee_name'] = $mentee->display_name;
        $mentor_template_data['zoom_start_url'] = $booking->zoom_start_url;

        $this->send_email(
            $mentor->user_email,
            'Reminder: Upcoming Session - ' . $service->service_name,
            'booking-reminder-mentor',
            $mentor_template_data
        );

        return true;
    }

    /**
     * Send payment confirmation email
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function send_payment_confirmation( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $service = $this->db->get_service( $booking->service_id );

        $template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'amount' => '$' . number_format( $booking->total_amount, 2 ),
            'payment_method' => ucfirst( $booking->payment_method ),
            'booking_id' => $booking->id,
            'transaction_id' => $booking->payment_intent_id ?: $booking->paypal_order_id,
            'booking_date' => date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ),
        );

        $this->send_email(
            $mentee->user_email,
            'Payment Confirmation - ' . $service->service_name,
            'payment-confirmation',
            $template_data
        );

        return true;
    }

    /**
     * Send payment failure notification
     *
     * @since    1.0.0
     * @param    array    $payment_data    Payment failure data.
     */
    public function send_payment_failure_notification( $payment_data ) {
        if ( ! isset( $payment_data['user_email'] ) ) {
            return false;
        }

        $template_data = array(
            'user_name' => $payment_data['user_name'] ?? 'Customer',
            'service_name' => $payment_data['service_name'] ?? 'Service',
            'amount' => '$' . number_format( $payment_data['amount'], 2 ),
            'error_message' => $payment_data['error_message'] ?? 'Payment could not be processed.',
            'retry_url' => $payment_data['retry_url'] ?? '',
        );

        $this->send_email(
            $payment_data['user_email'],
            'Payment Failed - Please Try Again',
            'payment-failed',
            $template_data
        );

        return true;
    }

    /**
     * Process reminder emails for upcoming bookings
     *
     * @since    1.0.0
     */
    public function process_reminder_emails() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Get bookings that need 24-hour reminders
        $reminder_time_24h = date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );
        $reminder_end_24h = date( 'Y-m-d H:i:s', strtotime( '+25 hours' ) );
        
        $bookings_24h = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $bookings_table 
                 WHERE status = 'confirmed'
                 AND booking_date BETWEEN %s AND %s
                 AND reminder_24h_sent = 0",
                $reminder_time_24h,
                $reminder_end_24h
            )
        );

        foreach ( $bookings_24h as $booking ) {
            $this->send_reminder_notification( $booking->id );
            
            // Mark reminder as sent
            $wpdb->update(
                $bookings_table,
                array( 'reminder_24h_sent' => 1 ),
                array( 'id' => $booking->id )
            );
        }

        // Get bookings that need 1-hour reminders
        $reminder_time_1h = date( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );
        $reminder_end_1h = date( 'Y-m-d H:i:s', strtotime( '+1 hour 30 minutes' ) );
        
        $bookings_1h = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $bookings_table 
                 WHERE status = 'confirmed'
                 AND booking_date BETWEEN %s AND %s
                 AND reminder_1h_sent = 0",
                $reminder_time_1h,
                $reminder_end_1h
            )
        );

        foreach ( $bookings_1h as $booking ) {
            $this->send_reminder_notification( $booking->id );
            
            // Mark reminder as sent
            $wpdb->update(
                $bookings_table,
                array( 'reminder_1h_sent' => 1 ),
                array( 'id' => $booking->id )
            );
        }
    }

    /**
     * Process follow-up emails for completed bookings
     *
     * @since    1.0.0
     */
    public function process_follow_up_emails() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Get completed bookings from 1 day ago that haven't received follow-up
        $follow_up_time = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
        $follow_up_end = date( 'Y-m-d H:i:s', strtotime( '-23 hours' ) );
        
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $bookings_table 
                 WHERE status = 'completed'
                 AND booking_date BETWEEN %s AND %s
                 AND follow_up_sent = 0",
                $follow_up_time,
                $follow_up_end
            )
        );

        foreach ( $bookings as $booking ) {
            $this->send_follow_up_email( $booking->id );
            
            // Mark follow-up as sent
            $wpdb->update(
                $bookings_table,
                array( 'follow_up_sent' => 1 ),
                array( 'id' => $booking->id )
            );
        }
    }

    /**
     * Send follow-up email after completed booking
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    private function send_follow_up_email( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $service = $this->db->get_service( $booking->service_id );

        $template_data = array(
            'user_name' => $mentee->display_name,
            'service_name' => $service->service_name,
            'mentor_name' => $mentor->display_name,
            'booking_date' => date( 'F j, Y', strtotime( $booking->booking_date ) ),
            'feedback_url' => home_url( '/booking-feedback/?booking_id=' . $booking->id ),
            'book_again_url' => home_url( '/services/' ),
        );

        $this->send_email(
            $mentee->user_email,
            'How was your session? - ' . $service->service_name,
            'booking-follow-up',
            $template_data
        );

        return true;
    }

    /**
     * Send email using template
     *
     * @since    1.0.0
     * @param    string   $to          Recipient email address.
     * @param    string   $subject     Email subject.
     * @param    string   $template    Template name.
     * @param    array    $data        Template data.
     * @return   bool                  True on success, false on failure.
     */
    private function send_email( $to, $subject, $template, $data = array() ) {
        $settings = get_option( 'booking_master_settings', array() );
        
        // Check if email notifications are enabled
        if ( ! isset( $settings['email_notifications'] ) || ! $settings['email_notifications'] ) {
            return false;
        }

        // Get email content from template
        $content = $this->get_template_content( $template, $data );
        
        if ( ! $content ) {
            // Fallback to plain text if template not found
            $content = $this->generate_plain_text_email( $template, $data );
        }

        // Set email headers
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        
        // Set from address if configured
        if ( ! empty( $settings['from_email'] ) ) {
            $from_name = ! empty( $settings['from_name'] ) ? $settings['from_name'] : get_bloginfo( 'name' );
            $headers[] = 'From: ' . $from_name . ' <' . $settings['from_email'] . '>';
        }

        // Apply filters for customization
        $to = apply_filters( 'bm_email_to', $to, $template, $data );
        $subject = apply_filters( 'bm_email_subject', $subject, $template, $data );
        $content = apply_filters( 'bm_email_content', $content, $template, $data );
        $headers = apply_filters( 'bm_email_headers', $headers, $template, $data );

        return wp_mail( $to, $subject, $content, $headers );
    }

    /**
     * Get template content
     *
     * @since    1.0.0
     * @param    string   $template    Template name.
     * @param    array    $data        Template data.
     * @return   string|false          Template content or false if not found.
     */
    private function get_template_content( $template, $data ) {
        $template_file = $this->templates_dir . $template . '.html';
        
        if ( ! file_exists( $template_file ) ) {
            return false;
        }

        $content = file_get_contents( $template_file );
        
        // Replace template variables
        foreach ( $data as $key => $value ) {
            $content = str_replace( '{{' . $key . '}}', $value, $content );
        }

        // Replace common variables
        $content = str_replace( '{{site_name}}', get_bloginfo( 'name' ), $content );
        $content = str_replace( '{{site_url}}', home_url(), $content );
        $content = str_replace( '{{current_year}}', date( 'Y' ), $content );

        return $content;
    }

    /**
     * Generate plain text email as fallback
     *
     * @since    1.0.0
     * @param    string   $template    Template name.
     * @param    array    $data        Template data.
     * @return   string                Plain text email content.
     */
    private function generate_plain_text_email( $template, $data ) {
        $content = "Hello " . ( $data['user_name'] ?? 'User' ) . ",\n\n";
        
        switch ( $template ) {
            case 'booking-confirmation-mentee':
                $content .= "Your booking has been confirmed!\n\n";
                $content .= "Service: " . ( $data['service_name'] ?? '' ) . "\n";
                $content .= "Mentor: " . ( $data['mentor_name'] ?? '' ) . "\n";
                $content .= "Date: " . ( $data['booking_date'] ?? '' ) . " at " . ( $data['booking_time'] ?? '' ) . "\n";
                $content .= "Duration: " . ( $data['duration'] ?? '' ) . "\n";
                $content .= "Amount: " . ( $data['amount'] ?? '' ) . "\n\n";
                break;
                
            case 'booking-reminder-mentee':
                $content .= "This is a reminder about your upcoming session.\n\n";
                $content .= "Service: " . ( $data['service_name'] ?? '' ) . "\n";
                $content .= "Mentor: " . ( $data['mentor_name'] ?? '' ) . "\n";
                $content .= "Date: " . ( $data['booking_date'] ?? '' ) . " at " . ( $data['booking_time'] ?? '' ) . "\n";
                
                if ( ! empty( $data['zoom_join_url'] ) ) {
                    $content .= "\nZoom Meeting: " . $data['zoom_join_url'] . "\n";
                }
                break;
                
            default:
                $content .= "This is a notification regarding your booking.\n\n";
                break;
        }
        
        $content .= "\nThank you for using " . get_bloginfo( 'name' ) . "!\n";
        $content .= home_url();
        
        return $content;
    }

    /**
     * Get status label for display
     *
     * @since    1.0.0
     * @param    string   $status    Booking status.
     * @return   string             Status label.
     */
    private function get_status_label( $status ) {
        $labels = array(
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        );
        
        return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
    }

    /**
     * Get email subject based on status
     *
     * @since    1.0.0
     * @param    string   $status        Booking status.
     * @param    string   $service_name  Service name.
     * @return   string                  Email subject.
     */
    private function get_status_subject( $status, $service_name ) {
        switch ( $status ) {
            case 'confirmed':
                return 'Booking Confirmed - ' . $service_name;
            case 'cancelled':
                return 'Booking Cancelled - ' . $service_name;
            case 'completed':
                return 'Session Completed - ' . $service_name;
            default:
                return 'Booking Update - ' . $service_name;
        }
    }

    /**
     * Get email template based on status
     *
     * @since    1.0.0
     * @param    string   $status    Booking status.
     * @return   string             Template name.
     */
    private function get_status_template( $status ) {
        switch ( $status ) {
            case 'confirmed':
                return 'booking-confirmed';
            case 'cancelled':
                return 'booking-cancelled-mentee';
            case 'completed':
                return 'booking-completed';
            default:
                return 'booking-status-update';
        }
    }

    /**
     * Get hours until booking
     *
     * @since    1.0.0
     * @param    string   $booking_date    Booking date.
     * @return   int                       Hours until booking.
     */
    private function get_hours_until_booking( $booking_date ) {
        $now = new DateTime();
        $booking = new DateTime( $booking_date );
        $interval = $now->diff( $booking );
        
        return $interval->days * 24 + $interval->h;
    }
}