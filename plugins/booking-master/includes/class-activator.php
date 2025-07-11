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
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(20),
            payment_intent_id varchar(255),
            paypal_order_id varchar(255),
            refund_amount decimal(10,2) DEFAULT '0.00',
            refund_date datetime,
            google_event_id varchar(255),
            outlook_event_id varchar(255),
            reminder_24h_sent tinyint(1) NOT NULL DEFAULT '0',
            reminder_1h_sent tinyint(1) NOT NULL DEFAULT '0',
            follow_up_sent tinyint(1) NOT NULL DEFAULT '0',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY mentee_id (mentee_id),
            KEY mentor_id (mentor_id),
            KEY booking_date (booking_date),
            KEY status (status)
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

        // Mentor availability table
        $table_mentor_availability = $wpdb->prefix . 'bm_mentor_availability';
        $sql_mentor_availability = "CREATE TABLE $table_mentor_availability (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mentor_id bigint(20) NOT NULL,
            date date NOT NULL,
            time_slot time NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'available',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY mentor_date_time (mentor_id, date, time_slot),
            KEY mentor_id (mentor_id),
            KEY date (date)
        ) $charset_collate;";

        // Mentor time off table
        $table_mentor_time_off = $wpdb->prefix . 'bm_mentor_time_off';
        $sql_mentor_time_off = "CREATE TABLE $table_mentor_time_off (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mentor_id bigint(20) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            reason text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mentor_id (mentor_id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";

        // Calendar sync events table
        $table_calendar_events = $wpdb->prefix . 'bm_calendar_events';
        $sql_calendar_events = "CREATE TABLE $table_calendar_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mentor_id bigint(20) NOT NULL,
            external_event_id varchar(255) NOT NULL,
            provider varchar(20) NOT NULL,
            event_title text,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            all_day tinyint(1) NOT NULL DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mentor_id (mentor_id),
            KEY provider (provider),
            KEY start_time (start_time),
            KEY end_time (end_time)
        ) $charset_collate;";

        // Email notifications log table
        $table_email_log = $wpdb->prefix . 'bm_email_log';
        $sql_email_log = "CREATE TABLE $table_email_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recipient_email varchar(255) NOT NULL,
            subject text NOT NULL,
            template_name varchar(100),
            booking_id mediumint(9),
            status varchar(20) NOT NULL DEFAULT 'sent',
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipient_email (recipient_email),
            KEY booking_id (booking_id),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        // Payment transactions table
        $table_payment_transactions = $wpdb->prefix . 'bm_payment_transactions';
        $sql_payment_transactions = "CREATE TABLE $table_payment_transactions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_id mediumint(9) NOT NULL,
            transaction_id varchar(255) NOT NULL,
            payment_method varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            status varchar(20) NOT NULL DEFAULT 'pending',
            gateway_response text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY transaction_id (transaction_id),
            KEY payment_method (payment_method),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta( $sql_services );
        dbDelta( $sql_bookings );
        dbDelta( $sql_mentor_settings );
        dbDelta( $sql_mentor_availability );
        dbDelta( $sql_mentor_time_off );
        dbDelta( $sql_calendar_events );
        dbDelta( $sql_email_log );
        dbDelta( $sql_payment_transactions );
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
            'booking_lead_time' => 60, // minutes
            'max_advance_booking' => 90, // days
            
            // Zoom settings
            'zoom_enabled' => false,
            'zoom_api_key' => '',
            'zoom_api_secret' => '',
            'zoom_oauth_client_id' => '',
            'zoom_oauth_client_secret' => '',
            
            // Payment gateway settings
            'payment_enabled' => true,
            'stripe_enabled' => false,
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
            'paypal_enabled' => false,
            'paypal_client_id' => '',
            'paypal_client_secret' => '',
            'paypal_sandbox' => true,
            
            // Email notification settings
            'email_notifications' => true,
            'booking_confirmation_email' => true,
            'booking_reminder_email' => true,
            'booking_cancellation_email' => true,
            'payment_confirmation_email' => true,
            'reminder_24h_enabled' => true,
            'reminder_1h_enabled' => true,
            'follow_up_email_enabled' => true,
            
            // Calendar sync settings
            'calendar_sync_enabled' => false,
            'google_calendar_enabled' => false,
            'google_client_id' => '',
            'google_client_secret' => '',
            'outlook_calendar_enabled' => false,
            'outlook_client_id' => '',
            'outlook_client_secret' => '',
            
            // Availability settings
            'default_working_hours' => array(
                'monday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'tuesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'wednesday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'thursday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'friday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => true),
                'saturday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => false),
                'sunday' => array('start' => '09:00', 'end' => '17:00', 'enabled' => false),
            ),
            
            // Booking settings
            'auto_approve_bookings' => false,
            'require_payment_for_booking' => false,
            'cancellation_policy' => 'flexible', // flexible, moderate, strict
            'cancellation_hours' => 24,
            'refund_policy' => 'full', // full, partial, no_refund
            
            // General settings
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => 'UTC',
        );
        
        add_option( 'booking_master_settings', $default_options );
        
        // Set plugin version
        add_option( 'booking_master_version', BOOKING_MASTER_VERSION );
    }
}