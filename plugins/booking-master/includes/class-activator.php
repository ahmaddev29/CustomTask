<?php

/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Booking_Master_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Add custom user roles
        self::add_user_roles();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Services table
        $table_services = $wpdb->prefix . 'bm_services';
        $sql_services = "CREATE TABLE $table_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mentor_id bigint(20) NOT NULL,
            service_name tinytext NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT '0.00',
            duration int(11) NOT NULL DEFAULT '30',
            zoom_enabled tinyint(1) NOT NULL DEFAULT '0',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mentor_id (mentor_id)
        ) $charset_collate;";
        
        // Bookings table
        $table_bookings = $wpdb->prefix . 'bm_bookings';
        $sql_bookings = "CREATE TABLE $table_bookings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            mentee_id bigint(20) NOT NULL,
            mentor_id bigint(20) NOT NULL,
            booking_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            zoom_meeting_id varchar(255),
            zoom_join_url text,
            zoom_start_url text,
            total_amount decimal(10,2) NOT NULL DEFAULT '0.00',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY mentee_id (mentee_id),
            KEY mentor_id (mentor_id)
        ) $charset_collate;";
        
        // Mentor settings table
        $table_mentor_settings = $wpdb->prefix . 'bm_mentor_settings';
        $sql_mentor_settings = "CREATE TABLE $table_mentor_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mentor_id bigint(20) NOT NULL,
            zoom_access_token text,
            zoom_refresh_token text,
            zoom_expires_at datetime,
            availability text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY mentor_id (mentor_id)
        ) $charset_collate;";
        
        dbDelta( $sql_services );
        dbDelta( $sql_bookings );
        dbDelta( $sql_mentor_settings );
    }
    
    /**
     * Add custom user roles
     *
     * @since    1.0.0
     */
    private static function add_user_roles() {
        // Add Mentor role
        add_role(
            'mentor',
            'Mentor',
            array(
                'read' => true,
                'bm_manage_services' => true,
                'bm_view_bookings' => true,
                'bm_manage_zoom' => true,
            )
        );
        
        // Add Mentee role (basically subscriber with booking capabilities)
        add_role(
            'mentee',
            'Mentee',
            array(
                'read' => true,
                'bm_book_services' => true,
            )
        );
        
        // Add capabilities to administrator
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'bm_manage_all_services' );
            $admin_role->add_cap( 'bm_manage_all_bookings' );
            $admin_role->add_cap( 'bm_manage_zoom_settings' );
            $admin_role->add_cap( 'bm_view_reports' );
        }
    }
    
    /**
     * Set default options
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // Default plugin settings
        $default_options = array(
            'currency' => 'USD',
            'currency_symbol' => '$',
            'time_slot_duration' => 30,
            'booking_buffer_time' => 15,
            'zoom_enabled' => false,
            'zoom_api_key' => '',
            'zoom_api_secret' => '',
            'auto_approve_bookings' => false,
            'email_notifications' => true,
        );
        
        add_option( 'booking_master_settings', $default_options );
        
        // Set plugin version
        add_option( 'booking_master_version', BOOKING_MASTER_VERSION );
    }
}