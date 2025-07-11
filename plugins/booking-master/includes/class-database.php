<?php

/**
 * Database operations for the plugin
 *
 * @since      1.0.0
 */

/**
 * Database operations for the plugin.
 *
 * This class defines all database operations used throughout the plugin.
 *
 * @since      1.0.0
 */
class Booking_Master_Database {

    /**
     * Global wpdb object
     *
     * @since    1.0.0
     * @access   private
     * @var      wpdb    $wpdb    The WordPress database object.
     */
    private $wpdb;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get table name with prefix
     *
     * @since    1.0.0
     * @param    string    $table    Table name without prefix.
     * @return   string             Table name with prefix.
     */
    private function get_table_name( $table ) {
        return $this->wpdb->prefix . 'bm_' . $table;
    }

    /**
     * Service related operations
     */

    /**
     * Create a new service
     *
     * @since    1.0.0
     * @param    array    $data    Service data.
     * @return   int|false         Service ID on success, false on failure.
     */
    public function create_service( $data ) {
        $table_name = $this->get_table_name( 'services' );
        
        $result = $this->wpdb->insert(
            $table_name,
            array(
                'mentor_id' => $data['mentor_id'],
                'service_name' => $data['service_name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'duration' => $data['duration'],
                'zoom_enabled' => $data['zoom_enabled'],
                'status' => isset( $data['status'] ) ? $data['status'] : 'active',
            ),
            array( '%d', '%s', '%s', '%f', '%d', '%d', '%s' )
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Get service by ID
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @return   object|null             Service object or null if not found.
     */
    public function get_service( $service_id ) {
        $table_name = $this->get_table_name( 'services' );
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $service_id
            )
        );
    }

    /**
     * Get services by mentor ID
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array                  Array of service objects.
     */
    public function get_services_by_mentor( $mentor_id ) {
        $table_name = $this->get_table_name( 'services' );
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $table_name WHERE mentor_id = %d AND status = 'active' ORDER BY created_at DESC",
                $mentor_id
            )
        );
    }

    /**
     * Get all active services
     *
     * @since    1.0.0
     * @return   array    Array of service objects.
     */
    public function get_all_active_services() {
        $table_name = $this->get_table_name( 'services' );
        
        return $this->wpdb->get_results(
            "SELECT s.*, u.display_name as mentor_name 
             FROM $table_name s 
             LEFT JOIN {$this->wpdb->users} u ON s.mentor_id = u.ID 
             WHERE s.status = 'active' 
             ORDER BY s.created_at DESC"
        );
    }

    /**
     * Update service
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @param    array    $data          Updated data.
     * @return   bool                    True on success, false on failure.
     */
    public function update_service( $service_id, $data ) {
        $table_name = $this->get_table_name( 'services' );
        
        $update_data = array();
        $format = array();
        
        if ( isset( $data['service_name'] ) ) {
            $update_data['service_name'] = $data['service_name'];
            $format[] = '%s';
        }
        if ( isset( $data['description'] ) ) {
            $update_data['description'] = $data['description'];
            $format[] = '%s';
        }
        if ( isset( $data['price'] ) ) {
            $update_data['price'] = $data['price'];
            $format[] = '%f';
        }
        if ( isset( $data['duration'] ) ) {
            $update_data['duration'] = $data['duration'];
            $format[] = '%d';
        }
        if ( isset( $data['zoom_enabled'] ) ) {
            $update_data['zoom_enabled'] = $data['zoom_enabled'];
            $format[] = '%d';
        }
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }

        return $this->wpdb->update(
            $table_name,
            $update_data,
            array( 'id' => $service_id ),
            $format,
            array( '%d' )
        ) !== false;
    }

    /**
     * Delete service
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @return   bool                    True on success, false on failure.
     */
    public function delete_service( $service_id ) {
        $table_name = $this->get_table_name( 'services' );
        
        return $this->wpdb->delete(
            $table_name,
            array( 'id' => $service_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Booking related operations
     */

    /**
     * Create a new booking
     *
     * @since    1.0.0
     * @param    array    $data    Booking data.
     * @return   int|false         Booking ID on success, false on failure.
     */
    public function create_booking( $data ) {
        $table_name = $this->get_table_name( 'bookings' );
        
        $result = $this->wpdb->insert(
            $table_name,
            array(
                'service_id' => $data['service_id'],
                'mentee_id' => $data['mentee_id'],
                'mentor_id' => $data['mentor_id'],
                'booking_date' => $data['booking_date'],
                'status' => isset( $data['status'] ) ? $data['status'] : 'pending',
                'total_amount' => $data['total_amount'],
                'notes' => isset( $data['notes'] ) ? $data['notes'] : '',
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%f', '%s' )
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Get booking by ID
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @return   object|null             Booking object or null if not found.
     */
    public function get_booking( $booking_id ) {
        $table_name = $this->get_table_name( 'bookings' );
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT b.*, s.service_name, s.duration, s.zoom_enabled,
                        mentor.display_name as mentor_name, mentee.display_name as mentee_name
                 FROM $table_name b
                 LEFT JOIN {$this->get_table_name('services')} s ON b.service_id = s.id
                 LEFT JOIN {$this->wpdb->users} mentor ON b.mentor_id = mentor.ID
                 LEFT JOIN {$this->wpdb->users} mentee ON b.mentee_id = mentee.ID
                 WHERE b.id = %d",
                $booking_id
            )
        );
    }

    /**
     * Get bookings by mentor ID
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array                  Array of booking objects.
     */
    public function get_bookings_by_mentor( $mentor_id ) {
        $table_name = $this->get_table_name( 'bookings' );
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT b.*, s.service_name, mentee.display_name as mentee_name
                 FROM $table_name b
                 LEFT JOIN {$this->get_table_name('services')} s ON b.service_id = s.id
                 LEFT JOIN {$this->wpdb->users} mentee ON b.mentee_id = mentee.ID
                 WHERE b.mentor_id = %d
                 ORDER BY b.booking_date DESC",
                $mentor_id
            )
        );
    }

    /**
     * Get bookings by mentee ID
     *
     * @since    1.0.0
     * @param    int      $mentee_id    Mentee ID.
     * @return   array                  Array of booking objects.
     */
    public function get_bookings_by_mentee( $mentee_id ) {
        $table_name = $this->get_table_name( 'bookings' );
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT b.*, s.service_name, mentor.display_name as mentor_name
                 FROM $table_name b
                 LEFT JOIN {$this->get_table_name('services')} s ON b.service_id = s.id
                 LEFT JOIN {$this->wpdb->users} mentor ON b.mentor_id = mentor.ID
                 WHERE b.mentee_id = %d
                 ORDER BY b.booking_date DESC",
                $mentee_id
            )
        );
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
        $table_name = $this->get_table_name( 'bookings' );
        
        $update_data = array();
        $format = array();
        
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }
        if ( isset( $data['zoom_meeting_id'] ) ) {
            $update_data['zoom_meeting_id'] = $data['zoom_meeting_id'];
            $format[] = '%s';
        }
        if ( isset( $data['zoom_join_url'] ) ) {
            $update_data['zoom_join_url'] = $data['zoom_join_url'];
            $format[] = '%s';
        }
        if ( isset( $data['zoom_start_url'] ) ) {
            $update_data['zoom_start_url'] = $data['zoom_start_url'];
            $format[] = '%s';
        }
        if ( isset( $data['notes'] ) ) {
            $update_data['notes'] = $data['notes'];
            $format[] = '%s';
        }

        return $this->wpdb->update(
            $table_name,
            $update_data,
            array( 'id' => $booking_id ),
            $format,
            array( '%d' )
        ) !== false;
    }

    /**
     * Mentor settings operations
     */

    /**
     * Save mentor settings
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    array    $settings     Settings data.
     * @return   bool                   True on success, false on failure.
     */
    public function save_mentor_settings( $mentor_id, $settings ) {
        $table_name = $this->get_table_name( 'mentor_settings' );
        
        // Check if settings exist
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM $table_name WHERE mentor_id = %d",
                $mentor_id
            )
        );

        if ( $existing ) {
            // Update existing settings
            return $this->wpdb->update(
                $table_name,
                $settings,
                array( 'mentor_id' => $mentor_id ),
                array( '%s', '%s', '%s', '%s' ),
                array( '%d' )
            ) !== false;
        } else {
            // Insert new settings
            $settings['mentor_id'] = $mentor_id;
            return $this->wpdb->insert(
                $table_name,
                $settings,
                array( '%d', '%s', '%s', '%s', '%s' )
            ) !== false;
        }
    }

    /**
     * Get mentor settings
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   object|null             Settings object or null if not found.
     */
    public function get_mentor_settings( $mentor_id ) {
        $table_name = $this->get_table_name( 'mentor_settings' );
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $table_name WHERE mentor_id = %d",
                $mentor_id
            )
        );
    }
}