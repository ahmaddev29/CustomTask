<?php

/**
 * Advanced Availability Management
 *
 * @since      1.0.0
 */

/**
 * Availability Management.
 *
 * This class defines all availability and time slot management functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Availability_Management {

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
        add_action( 'wp_ajax_bm_get_mentor_availability', array( $this, 'ajax_get_mentor_availability' ) );
        add_action( 'wp_ajax_bm_update_time_slot', array( $this, 'ajax_update_time_slot' ) );
        add_action( 'wp_ajax_bm_bulk_update_availability', array( $this, 'ajax_bulk_update_availability' ) );
        add_action( 'wp_ajax_bm_set_working_hours', array( $this, 'ajax_set_working_hours' ) );
        add_action( 'wp_ajax_bm_set_time_off', array( $this, 'ajax_set_time_off' ) );
        add_action( 'wp_ajax_bm_get_availability_calendar', array( $this, 'ajax_get_availability_calendar' ) );
        add_action( 'wp_ajax_bm_copy_availability', array( $this, 'ajax_copy_availability' ) );
    }

    /**
     * Get mentor availability for a specific date range (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_mentor_availability() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $availability = $this->get_mentor_availability( $mentor_id, $start_date, $end_date );
        
        wp_send_json_success( array( 'availability' => $availability ) );
    }

    /**
     * Update a specific time slot (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_update_time_slot() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $date = sanitize_text_field( $_POST['date'] );
        $time_slot = sanitize_text_field( $_POST['time_slot'] );
        $status = sanitize_text_field( $_POST['status'] ); // 'available', 'blocked', 'booked'

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $result = $this->update_time_slot( $mentor_id, $date, $time_slot, $status );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Time slot updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update time slot' ) );
        }
    }

    /**
     * Bulk update availability (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_bulk_update_availability() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $updates = json_decode( stripslashes( $_POST['updates'] ), true );

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $success_count = 0;
        $error_count = 0;

        foreach ( $updates as $update ) {
            $result = $this->update_time_slot( 
                $mentor_id, 
                $update['date'], 
                $update['time_slot'], 
                $update['status'] 
            );
            
            if ( $result ) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        wp_send_json_success( array( 
            'message' => "Updated {$success_count} time slots successfully",
            'success_count' => $success_count,
            'error_count' => $error_count
        ) );
    }

    /**
     * Set working hours for mentor (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_set_working_hours() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $working_hours = json_decode( stripslashes( $_POST['working_hours'] ), true );

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $result = $this->set_working_hours( $mentor_id, $working_hours );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Working hours updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update working hours' ) );
        }
    }

    /**
     * Set time off for mentor (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_set_time_off() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );
        $reason = sanitize_text_field( $_POST['reason'] );

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $result = $this->set_time_off( $mentor_id, $start_date, $end_date, $reason );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Time off scheduled successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to schedule time off' ) );
        }
    }

    /**
     * Get availability calendar data (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_availability_calendar() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $month = intval( $_POST['month'] );
        $year = intval( $_POST['year'] );

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $calendar_data = $this->get_availability_calendar( $mentor_id, $month, $year );
        
        wp_send_json_success( array( 'calendar' => $calendar_data ) );
    }

    /**
     * Copy availability from one date/week to another (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_copy_availability() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_availability_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = intval( $_POST['mentor_id'] );
        $source_date = sanitize_text_field( $_POST['source_date'] );
        $target_dates = json_decode( stripslashes( $_POST['target_dates'] ), true );
        $copy_type = sanitize_text_field( $_POST['copy_type'] ); // 'day' or 'week'

        // Verify mentor ownership
        if ( $mentor_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ) );
        }

        $result = $this->copy_availability( $mentor_id, $source_date, $target_dates, $copy_type );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Availability copied successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to copy availability' ) );
        }
    }

    /**
     * Get mentor availability for a date range
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $start_date   Start date (Y-m-d).
     * @param    string   $end_date     End date (Y-m-d).
     * @return   array                  Availability data.
     */
    public function get_mentor_availability( $mentor_id, $start_date, $end_date ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bm_mentor_availability';
        
        $availability = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE mentor_id = %d 
                 AND date BETWEEN %s AND %s
                 ORDER BY date, time_slot",
                $mentor_id,
                $start_date,
                $end_date
            )
        );

        // Group by date
        $grouped_availability = array();
        foreach ( $availability as $slot ) {
            if ( ! isset( $grouped_availability[ $slot->date ] ) ) {
                $grouped_availability[ $slot->date ] = array();
            }
            $grouped_availability[ $slot->date ][ $slot->time_slot ] = $slot->status;
        }

        // Fill in missing dates with default availability
        $current_date = new DateTime( $start_date );
        $end_date_obj = new DateTime( $end_date );
        
        while ( $current_date <= $end_date_obj ) {
            $date_str = $current_date->format( 'Y-m-d' );
            
            if ( ! isset( $grouped_availability[ $date_str ] ) ) {
                $grouped_availability[ $date_str ] = $this->get_default_availability_for_date( $mentor_id, $date_str );
            }
            
            $current_date->add( new DateInterval( 'P1D' ) );
        }

        return $grouped_availability;
    }

    /**
     * Update a specific time slot
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $date         Date (Y-m-d).
     * @param    string   $time_slot    Time slot (H:i).
     * @param    string   $status       Status (available, blocked, booked).
     * @return   bool                   True on success, false on failure.
     */
    public function update_time_slot( $mentor_id, $date, $time_slot, $status ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bm_mentor_availability';
        
        // Check if the time slot already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE mentor_id = %d AND date = %s AND time_slot = %s",
                $mentor_id,
                $date,
                $time_slot
            )
        );

        if ( $existing ) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                array( 
                    'status' => $status,
                    'updated_at' => current_time( 'mysql' )
                ),
                array( 
                    'mentor_id' => $mentor_id,
                    'date' => $date,
                    'time_slot' => $time_slot
                )
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'mentor_id' => $mentor_id,
                    'date' => $date,
                    'time_slot' => $time_slot,
                    'status' => $status,
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' )
                )
            );
        }

        return $result !== false;
    }

    /**
     * Set working hours for a mentor
     *
     * @since    1.0.0
     * @param    int      $mentor_id      Mentor ID.
     * @param    array    $working_hours  Working hours configuration.
     * @return   bool                     True on success, false on failure.
     */
    public function set_working_hours( $mentor_id, $working_hours ) {
        $settings = get_user_meta( $mentor_id, 'bm_working_hours', true );
        if ( ! $settings ) {
            $settings = array();
        }

        $settings = array_merge( $settings, $working_hours );
        
        return update_user_meta( $mentor_id, 'bm_working_hours', $settings );
    }

    /**
     * Set time off for a mentor
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $start_date   Start date.
     * @param    string   $end_date     End date.
     * @param    string   $reason       Reason for time off.
     * @return   bool                   True on success, false on failure.
     */
    public function set_time_off( $mentor_id, $start_date, $end_date, $reason ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bm_mentor_time_off';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'mentor_id' => $mentor_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
                'created_at' => current_time( 'mysql' )
            )
        );

        if ( $result ) {
            // Block all time slots for the time off period
            $this->block_time_slots_for_period( $mentor_id, $start_date, $end_date );
        }

        return $result !== false;
    }

    /**
     * Get availability calendar for a specific month
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    int      $month        Month (1-12).
     * @param    int      $year         Year.
     * @return   array                  Calendar data.
     */
    public function get_availability_calendar( $mentor_id, $month, $year ) {
        $start_date = sprintf( '%04d-%02d-01', $year, $month );
        $end_date = date( 'Y-m-t', strtotime( $start_date ) );
        
        $availability = $this->get_mentor_availability( $mentor_id, $start_date, $end_date );
        $bookings = $this->get_bookings_for_period( $mentor_id, $start_date, $end_date );
        
        $calendar = array();
        $current_date = new DateTime( $start_date );
        $end_date_obj = new DateTime( $end_date );
        
        while ( $current_date <= $end_date_obj ) {
            $date_str = $current_date->format( 'Y-m-d' );
            $day_availability = isset( $availability[ $date_str ] ) ? $availability[ $date_str ] : array();
            $day_bookings = isset( $bookings[ $date_str ] ) ? $bookings[ $date_str ] : array();
            
            $available_slots = 0;
            $blocked_slots = 0;
            $booked_slots = count( $day_bookings );
            
            foreach ( $day_availability as $slot => $status ) {
                if ( $status === 'available' ) {
                    $available_slots++;
                } elseif ( $status === 'blocked' ) {
                    $blocked_slots++;
                }
            }
            
            $calendar[ $date_str ] = array(
                'date' => $date_str,
                'day' => $current_date->format( 'j' ),
                'day_name' => $current_date->format( 'D' ),
                'available_slots' => $available_slots,
                'blocked_slots' => $blocked_slots,
                'booked_slots' => $booked_slots,
                'total_slots' => count( $day_availability ),
                'status' => $this->get_day_status( $available_slots, $blocked_slots, $booked_slots )
            );
            
            $current_date->add( new DateInterval( 'P1D' ) );
        }
        
        return $calendar;
    }

    /**
     * Copy availability from one period to another
     *
     * @since    1.0.0
     * @param    int      $mentor_id      Mentor ID.
     * @param    string   $source_date    Source date.
     * @param    array    $target_dates   Target dates.
     * @param    string   $copy_type      Copy type ('day' or 'week').
     * @return   bool                     True on success, false on failure.
     */
    public function copy_availability( $mentor_id, $source_date, $target_dates, $copy_type ) {
        if ( $copy_type === 'week' ) {
            return $this->copy_week_availability( $mentor_id, $source_date, $target_dates );
        } else {
            return $this->copy_day_availability( $mentor_id, $source_date, $target_dates );
        }
    }

    /**
     * Copy day availability
     *
     * @since    1.0.0
     * @param    int      $mentor_id      Mentor ID.
     * @param    string   $source_date    Source date.
     * @param    array    $target_dates   Target dates.
     * @return   bool                     True on success, false on failure.
     */
    private function copy_day_availability( $mentor_id, $source_date, $target_dates ) {
        $source_availability = $this->get_mentor_availability( $mentor_id, $source_date, $source_date );
        
        if ( ! isset( $source_availability[ $source_date ] ) ) {
            return false;
        }

        $success_count = 0;
        foreach ( $target_dates as $target_date ) {
            foreach ( $source_availability[ $source_date ] as $time_slot => $status ) {
                if ( $this->update_time_slot( $mentor_id, $target_date, $time_slot, $status ) ) {
                    $success_count++;
                }
            }
        }

        return $success_count > 0;
    }

    /**
     * Copy week availability
     *
     * @since    1.0.0
     * @param    int      $mentor_id      Mentor ID.
     * @param    string   $source_date    Source week start date.
     * @param    array    $target_dates   Target week start dates.
     * @return   bool                     True on success, false on failure.
     */
    private function copy_week_availability( $mentor_id, $source_date, $target_dates ) {
        $source_start = new DateTime( $source_date );
        $source_start->modify( 'monday this week' );
        
        $source_end = clone $source_start;
        $source_end->add( new DateInterval( 'P6D' ) );
        
        $source_availability = $this->get_mentor_availability( 
            $mentor_id, 
            $source_start->format( 'Y-m-d' ), 
            $source_end->format( 'Y-m-d' ) 
        );

        $success_count = 0;
        foreach ( $target_dates as $target_week_start ) {
            $target_start = new DateTime( $target_week_start );
            $target_start->modify( 'monday this week' );
            
            for ( $i = 0; $i < 7; $i++ ) {
                $source_day = clone $source_start;
                $source_day->add( new DateInterval( 'P' . $i . 'D' ) );
                
                $target_day = clone $target_start;
                $target_day->add( new DateInterval( 'P' . $i . 'D' ) );
                
                $source_day_str = $source_day->format( 'Y-m-d' );
                $target_day_str = $target_day->format( 'Y-m-d' );
                
                if ( isset( $source_availability[ $source_day_str ] ) ) {
                    foreach ( $source_availability[ $source_day_str ] as $time_slot => $status ) {
                        if ( $this->update_time_slot( $mentor_id, $target_day_str, $time_slot, $status ) ) {
                            $success_count++;
                        }
                    }
                }
            }
        }

        return $success_count > 0;
    }

    /**
     * Get default availability for a date based on working hours
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $date         Date (Y-m-d).
     * @return   array                  Default availability.
     */
    private function get_default_availability_for_date( $mentor_id, $date ) {
        $working_hours = get_user_meta( $mentor_id, 'bm_working_hours', true );
        $day_of_week = strtolower( date( 'l', strtotime( $date ) ) );
        
        $default_availability = array();
        
        if ( ! empty( $working_hours[ $day_of_week ] ) && $working_hours[ $day_of_week ]['enabled'] ) {
            $start_time = $working_hours[ $day_of_week ]['start'];
            $end_time = $working_hours[ $day_of_week ]['end'];
            $slot_duration = 30; // minutes
            
            $current_time = new DateTime( $date . ' ' . $start_time );
            $end_datetime = new DateTime( $date . ' ' . $end_time );
            
            while ( $current_time < $end_datetime ) {
                $time_slot = $current_time->format( 'H:i' );
                $default_availability[ $time_slot ] = 'available';
                $current_time->add( new DateInterval( 'PT' . $slot_duration . 'M' ) );
            }
        }
        
        return $default_availability;
    }

    /**
     * Get bookings for a specific period
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $start_date   Start date.
     * @param    string   $end_date     End date.
     * @return   array                  Bookings grouped by date.
     */
    private function get_bookings_for_period( $mentor_id, $start_date, $end_date ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $bookings_table 
                 WHERE mentor_id = %d 
                 AND DATE(booking_date) BETWEEN %s AND %s
                 AND status NOT IN ('cancelled')
                 ORDER BY booking_date",
                $mentor_id,
                $start_date,
                $end_date
            )
        );

        $grouped_bookings = array();
        foreach ( $bookings as $booking ) {
            $date = date( 'Y-m-d', strtotime( $booking->booking_date ) );
            if ( ! isset( $grouped_bookings[ $date ] ) ) {
                $grouped_bookings[ $date ] = array();
            }
            $grouped_bookings[ $date ][] = $booking;
        }

        return $grouped_bookings;
    }

    /**
     * Block time slots for a period (time off)
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $start_date   Start date.
     * @param    string   $end_date     End date.
     */
    private function block_time_slots_for_period( $mentor_id, $start_date, $end_date ) {
        $current_date = new DateTime( $start_date );
        $end_date_obj = new DateTime( $end_date );
        
        while ( $current_date <= $end_date_obj ) {
            $date_str = $current_date->format( 'Y-m-d' );
            $day_availability = $this->get_default_availability_for_date( $mentor_id, $date_str );
            
            foreach ( $day_availability as $time_slot => $status ) {
                $this->update_time_slot( $mentor_id, $date_str, $time_slot, 'blocked' );
            }
            
            $current_date->add( new DateInterval( 'P1D' ) );
        }
    }

    /**
     * Get day status based on slot counts
     *
     * @since    1.0.0
     * @param    int      $available    Available slots count.
     * @param    int      $blocked      Blocked slots count.
     * @param    int      $booked       Booked slots count.
     * @return   string                 Day status.
     */
    private function get_day_status( $available, $blocked, $booked ) {
        if ( $booked > 0 && $available === 0 ) {
            return 'fully-booked';
        } elseif ( $booked > 0 && $available > 0 ) {
            return 'partially-booked';
        } elseif ( $available > 0 ) {
            return 'available';
        } elseif ( $blocked > 0 ) {
            return 'blocked';
        } else {
            return 'unavailable';
        }
    }

    /**
     * Get time slots for a specific date
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $date         Date (Y-m-d).
     * @return   array                  Time slots with status.
     */
    public function get_time_slots_for_date( $mentor_id, $date ) {
        $availability = $this->get_mentor_availability( $mentor_id, $date, $date );
        return isset( $availability[ $date ] ) ? $availability[ $date ] : array();
    }

    /**
     * Check if a specific time slot is available
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $date         Date (Y-m-d).
     * @param    string   $time_slot    Time slot (H:i).
     * @return   bool                   True if available, false otherwise.
     */
    public function is_time_slot_available( $mentor_id, $date, $time_slot ) {
        $time_slots = $this->get_time_slots_for_date( $mentor_id, $date );
        return isset( $time_slots[ $time_slot ] ) && $time_slots[ $time_slot ] === 'available';
    }
}