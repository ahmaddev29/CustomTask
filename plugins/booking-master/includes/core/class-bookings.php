<?php

/**
 * Bookings management
 *
 * @since      1.0.0
 */

/**
 * Bookings management.
 *
 * This class defines all booking related functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Bookings {

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
        add_action( 'wp_ajax_bm_create_booking', array( $this, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_bm_update_booking_status', array( $this, 'ajax_update_booking_status' ) );
        add_action( 'wp_ajax_bm_get_bookings', array( $this, 'ajax_get_bookings' ) );
        add_action( 'wp_ajax_bm_get_available_slots', array( $this, 'ajax_get_available_slots' ) );
        add_action( 'wp_ajax_nopriv_bm_get_available_slots', array( $this, 'ajax_get_available_slots' ) );
        add_action( 'wp_ajax_bm_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
        
        // Scheduled events
        add_action( 'booking_master_send_reminder_emails', array( $this, 'send_reminder_emails' ) );
        
        // Schedule events if not already scheduled
        if ( ! wp_next_scheduled( 'booking_master_send_reminder_emails' ) ) {
            wp_schedule_event( time(), 'hourly', 'booking_master_send_reminder_emails' );
        }
    }

    /**
     * Create a new booking (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_booking() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_booking_nonce' ) || ! current_user_can( 'bm_book_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $booking_date = sanitize_text_field( $_POST['booking_date'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        // Get service details
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => 'Service not found.' ) );
        }

        // Validate booking date
        $booking_datetime = DateTime::createFromFormat( 'Y-m-d H:i', $booking_date );
        if ( ! $booking_datetime || $booking_datetime <= new DateTime() ) {
            wp_send_json_error( array( 'message' => 'Invalid booking date. Please select a future date and time.' ) );
        }

        // Check if slot is available
        if ( ! $this->is_slot_available( $service_id, $booking_date ) ) {
            wp_send_json_error( array( 'message' => 'Selected time slot is not available. Please choose another time.' ) );
        }

        $booking_data = array(
            'service_id' => $service_id,
            'mentee_id' => get_current_user_id(),
            'mentor_id' => $service->mentor_id,
            'booking_date' => $booking_datetime->format( 'Y-m-d H:i:s' ),
            'total_amount' => $service->price,
            'notes' => $notes,
            'status' => 'pending', // Will be auto-approved based on settings
        );

        $booking_id = $this->create_booking( $booking_data );

        if ( $booking_id ) {
            // Send confirmation emails
            $this->send_booking_confirmation_email( $booking_id );
            
            wp_send_json_success( array( 
                'message' => 'Booking created successfully! You will receive a confirmation email shortly.',
                'booking_id' => $booking_id 
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create booking. Please try again.' ) );
        }
    }

    /**
     * Update booking status (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_update_booking_status() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_booking_nonce' ) || ! current_user_can( 'bm_view_bookings' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $new_status = sanitize_text_field( $_POST['status'] );

        // Validate status
        $valid_statuses = array( 'pending', 'confirmed', 'cancelled', 'completed' );
        if ( ! in_array( $new_status, $valid_statuses ) ) {
            wp_send_json_error( array( 'message' => 'Invalid status.' ) );
        }

        $booking = $this->get_booking( $booking_id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => 'Booking not found.' ) );
        }

        // Check permissions
        if ( $booking->mentor_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to update this booking.' ) );
        }

        $update_data = array( 'status' => $new_status );
        
        // If confirming and zoom is enabled, create zoom meeting
        if ( $new_status === 'confirmed' && $booking->zoom_enabled ) {
            $zoom_integration = new Booking_Master_Zoom_Integration();
            $zoom_meeting = $zoom_integration->create_meeting( $booking );
            
            if ( $zoom_meeting ) {
                $update_data['zoom_meeting_id'] = $zoom_meeting['id'];
                $update_data['zoom_join_url'] = $zoom_meeting['join_url'];
                $update_data['zoom_start_url'] = $zoom_meeting['start_url'];
            }
        }

        $updated = $this->update_booking( $booking_id, $update_data );

        if ( $updated ) {
            // Send status update email
            $this->send_booking_status_email( $booking_id, $new_status );
            
            wp_send_json_success( array( 'message' => 'Booking status updated successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update booking status. Please try again.' ) );
        }
    }

    /**
     * Get bookings (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_bookings() {
        $user_id = get_current_user_id();
        $user_roles = wp_get_current_user()->roles;
        
        if ( in_array( 'mentor', $user_roles ) ) {
            $bookings = $this->get_bookings_by_mentor( $user_id );
        } elseif ( in_array( 'mentee', $user_roles ) ) {
            $bookings = $this->get_bookings_by_mentee( $user_id );
        } else {
            $bookings = array();
        }

        wp_send_json_success( array( 'bookings' => $bookings ) );
    }

    /**
     * Get available time slots (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_available_slots() {
        $service_id = intval( $_POST['service_id'] );
        $date = sanitize_text_field( $_POST['date'] );

        $slots = $this->get_available_slots( $service_id, $date );
        wp_send_json_success( array( 'slots' => $slots ) );
    }

    /**
     * Cancel booking (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_cancel_booking() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_booking_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $booking = $this->get_booking( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => 'Booking not found.' ) );
        }

        // Check permissions
        $user_id = get_current_user_id();
        if ( $booking->mentee_id != $user_id && $booking->mentor_id != $user_id && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to cancel this booking.' ) );
        }

        $updated = $this->update_booking( $booking_id, array( 'status' => 'cancelled' ) );

        if ( $updated ) {
            // Send cancellation emails
            $this->send_booking_status_email( $booking_id, 'cancelled' );
            
            wp_send_json_success( array( 'message' => 'Booking cancelled successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to cancel booking. Please try again.' ) );
        }
    }

    /**
     * Create a new booking
     *
     * @since    1.0.0
     * @param    array    $booking_data    Booking data.
     * @return   int|false                 Booking ID on success, false on failure.
     */
    public function create_booking( $booking_data ) {
        return $this->db->create_booking( $booking_data );
    }

    /**
     * Get booking by ID
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @return   object|null             Booking object or null if not found.
     */
    public function get_booking( $booking_id ) {
        return $this->db->get_booking( $booking_id );
    }

    /**
     * Get bookings by mentor ID
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array                  Array of booking objects.
     */
    public function get_bookings_by_mentor( $mentor_id ) {
        return $this->db->get_bookings_by_mentor( $mentor_id );
    }

    /**
     * Get bookings by mentee ID
     *
     * @since    1.0.0
     * @param    int      $mentee_id    Mentee ID.
     * @return   array                  Array of booking objects.
     */
    public function get_bookings_by_mentee( $mentee_id ) {
        return $this->db->get_bookings_by_mentee( $mentee_id );
    }

    /**
     * Update booking
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @param    array    $data          Updated data.
     * @return   bool                    True on success, false on failure.
     */
    public function update_booking( $booking_id, $data ) {
        return $this->db->update_booking( $booking_id, $data );
    }

    /**
     * Check if a time slot is available
     *
     * @since    1.0.0
     * @param    int      $service_id      Service ID.
     * @param    string   $booking_date    Booking date and time.
     * @return   bool                      True if available, false otherwise.
     */
    public function is_slot_available( $service_id, $booking_date ) {
        global $wpdb;
        
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            return false;
        }

        $table_name = $wpdb->prefix . 'bm_bookings';
        
        $booking_datetime = DateTime::createFromFormat( 'Y-m-d H:i', $booking_date );
        $end_datetime = clone $booking_datetime;
        $end_datetime->add( new DateInterval( 'PT' . $service->duration . 'M' ) );

        // Check for overlapping bookings
        $overlapping = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                WHERE service_id = %d 
                AND status NOT IN ('cancelled', 'completed')
                AND (
                    (booking_date <= %s AND DATE_ADD(booking_date, INTERVAL %d MINUTE) > %s) OR
                    (booking_date < %s AND DATE_ADD(booking_date, INTERVAL %d MINUTE) >= %s)
                )",
                $service_id,
                $booking_datetime->format( 'Y-m-d H:i:s' ),
                $service->duration,
                $booking_datetime->format( 'Y-m-d H:i:s' ),
                $end_datetime->format( 'Y-m-d H:i:s' ),
                $service->duration,
                $end_datetime->format( 'Y-m-d H:i:s' )
            )
        );

        return $overlapping == 0;
    }

    /**
     * Get available time slots for a service on a specific date
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @param    string   $date          Date in Y-m-d format.
     * @return   array                   Array of available time slots.
     */
    public function get_available_slots( $service_id, $date ) {
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            return array();
        }

        // Get mentor availability (simplified - assuming 9 AM to 5 PM for now)
        $start_time = new DateTime( $date . ' 09:00' );
        $end_time = new DateTime( $date . ' 17:00' );
        $slot_duration = 30; // minutes
        $service_duration = $service->duration;

        $slots = array();
        $current_time = clone $start_time;

        while ( $current_time < $end_time ) {
            // Check if there's enough time for the service
            $slot_end = clone $current_time;
            $slot_end->add( new DateInterval( 'PT' . $service_duration . 'M' ) );
            
            if ( $slot_end <= $end_time ) {
                $slot_time = $current_time->format( 'Y-m-d H:i' );
                
                // Check if slot is available
                if ( $this->is_slot_available( $service_id, $slot_time ) ) {
                    $slots[] = array(
                        'time' => $current_time->format( 'H:i' ),
                        'datetime' => $slot_time,
                        'display' => $current_time->format( 'g:i A' )
                    );
                }
            }

            $current_time->add( new DateInterval( 'PT' . $slot_duration . 'M' ) );
        }

        return $slots;
    }

    /**
     * Send booking confirmation email
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function send_booking_confirmation_email( $booking_id ) {
        $booking = $this->get_booking( $booking_id );
        if ( ! $booking ) {
            return;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );

        $subject = 'Booking Confirmation - ' . $booking->service_name;
        
        $message = "Hello {$mentee->display_name},\n\n";
        $message .= "Your booking has been confirmed!\n\n";
        $message .= "Service: {$booking->service_name}\n";
        $message .= "Mentor: {$mentor->display_name}\n";
        $message .= "Date: " . date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ) . "\n";
        $message .= "Duration: {$booking->duration} minutes\n";
        $message .= "Amount: $" . number_format( $booking->total_amount, 2 ) . "\n\n";
        
        if ( $booking->zoom_enabled ) {
            $message .= "This session will be conducted via Zoom. Meeting details will be provided once the mentor confirms your booking.\n\n";
        }
        
        $message .= "Thank you for using our booking system!";

        wp_mail( $mentee->user_email, $subject, $message );

        // Send notification to mentor
        $mentor_subject = 'New Booking Request - ' . $booking->service_name;
        $mentor_message = "Hello {$mentor->display_name},\n\n";
        $mentor_message .= "You have received a new booking request!\n\n";
        $mentor_message .= "Service: {$booking->service_name}\n";
        $mentor_message .= "Mentee: {$mentee->display_name}\n";
        $mentor_message .= "Date: " . date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ) . "\n";
        $mentor_message .= "Duration: {$booking->duration} minutes\n";
        $mentor_message .= "Amount: $" . number_format( $booking->total_amount, 2 ) . "\n\n";
        
        if ( $booking->notes ) {
            $mentor_message .= "Notes from mentee: {$booking->notes}\n\n";
        }
        
        $mentor_message .= "Please log in to your dashboard to confirm or manage this booking.";

        wp_mail( $mentor->user_email, $mentor_subject, $mentor_message );
    }

    /**
     * Send booking status update email
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @param    string   $status        New status.
     */
    public function send_booking_status_email( $booking_id, $status ) {
        $booking = $this->get_booking( $booking_id );
        if ( ! $booking ) {
            return;
        }

        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );

        $status_text = ucfirst( $status );
        $subject = "Booking {$status_text} - {$booking->service_name}";
        
        $message = "Hello {$mentee->display_name},\n\n";
        $message .= "Your booking status has been updated to: {$status_text}\n\n";
        $message .= "Service: {$booking->service_name}\n";
        $message .= "Mentor: {$mentor->display_name}\n";
        $message .= "Date: " . date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ) . "\n\n";

        if ( $status === 'confirmed' && $booking->zoom_join_url ) {
            $message .= "Zoom Meeting Details:\n";
            $message .= "Join URL: {$booking->zoom_join_url}\n\n";
        }

        wp_mail( $mentee->user_email, $subject, $message );
    }

    /**
     * Send reminder emails for upcoming bookings
     *
     * @since    1.0.0
     */
    public function send_reminder_emails() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bm_bookings';
        
        // Get bookings that are 24 hours away
        $reminder_time = date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );
        $reminder_end = date( 'Y-m-d H:i:s', strtotime( '+25 hours' ) );
        
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, s.service_name, s.duration, s.zoom_enabled
                 FROM $table_name b
                 LEFT JOIN {$wpdb->prefix}bm_services s ON b.service_id = s.id
                 WHERE b.status = 'confirmed'
                 AND b.booking_date BETWEEN %s AND %s",
                $reminder_time,
                $reminder_end
            )
        );

        foreach ( $bookings as $booking ) {
            $this->send_reminder_email( $booking );
        }
    }

    /**
     * Send reminder email for a specific booking
     *
     * @since    1.0.0
     * @param    object   $booking    Booking object.
     */
    private function send_reminder_email( $booking ) {
        $mentee = get_user_by( 'ID', $booking->mentee_id );
        $mentor = get_user_by( 'ID', $booking->mentor_id );

        $subject = 'Booking Reminder - ' . $booking->service_name;
        
        $message = "Hello {$mentee->display_name},\n\n";
        $message .= "This is a reminder about your upcoming booking:\n\n";
        $message .= "Service: {$booking->service_name}\n";
        $message .= "Mentor: {$mentor->display_name}\n";
        $message .= "Date: " . date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ) . "\n";
        $message .= "Duration: {$booking->duration} minutes\n\n";

        if ( $booking->zoom_enabled && $booking->zoom_join_url ) {
            $message .= "Zoom Meeting Details:\n";
            $message .= "Join URL: {$booking->zoom_join_url}\n\n";
        }

        $message .= "We look forward to your session!";

        wp_mail( $mentee->user_email, $subject, $message );
    }
}