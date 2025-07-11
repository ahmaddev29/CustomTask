<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 */
class Booking_Master_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( 
            $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'css/admin.css', 
            array(), 
            $this->version, 
            'all' 
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 
            $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'js/admin.js', 
            array( 'jquery' ), 
            $this->version, 
            false 
        );

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'bm_admin_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bm_admin_nonce' ),
        ) );
    }

    /**
     * Add admin menu pages
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Booking Master',
            'Booking Master',
            'manage_options',
            'booking-master',
            array( $this, 'display_admin_dashboard' ),
            'dashicons-calendar-alt',
            6
        );

        // Dashboard submenu
        add_submenu_page(
            'booking-master',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'booking-master',
            array( $this, 'display_admin_dashboard' )
        );

        // Services submenu
        add_submenu_page(
            'booking-master',
            'All Services',
            'All Services',
            'manage_options',
            'booking-master-services',
            array( $this, 'display_services_page' )
        );

        // Bookings submenu
        add_submenu_page(
            'booking-master',
            'All Bookings',
            'All Bookings',
            'manage_options',
            'booking-master-bookings',
            array( $this, 'display_bookings_page' )
        );

        // Users submenu
        add_submenu_page(
            'booking-master',
            'Users & Roles',
            'Users & Roles',
            'manage_options',
            'booking-master-users',
            array( $this, 'display_users_page' )
        );

        // Settings submenu
        add_submenu_page(
            'booking-master',
            'Settings',
            'Settings',
            'manage_options',
            'booking-master-settings',
            array( $this, 'display_settings_page' )
        );

        // Mentor dashboard (for mentors only)
        if ( current_user_can( 'bm_manage_services' ) ) {
            add_submenu_page(
                'booking-master',
                'Mentor Dashboard',
                'Mentor Dashboard',
                'bm_manage_services',
                'booking-master-mentor',
                array( $this, 'display_mentor_dashboard' )
            );
        }

        // Mentee dashboard (for mentees only)
        if ( current_user_can( 'bm_book_services' ) ) {
            add_submenu_page(
                'booking-master',
                'My Bookings',
                'My Bookings',
                'bm_book_services',
                'booking-master-mentee',
                array( $this, 'display_mentee_dashboard' )
            );
        }
    }

    /**
     * Display admin dashboard
     *
     * @since    1.0.0
     */
    public function display_admin_dashboard() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/admin-dashboard.php';
    }

    /**
     * Display services page
     *
     * @since    1.0.0
     */
    public function display_services_page() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/services-page.php';
    }

    /**
     * Display bookings page
     *
     * @since    1.0.0
     */
    public function display_bookings_page() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/bookings-page.php';
    }

    /**
     * Display users page
     *
     * @since    1.0.0
     */
    public function display_users_page() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/users-page.php';
    }

    /**
     * Display settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Handle form submission
        if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['settings_nonce'], 'bm_settings' ) ) {
            $this->save_settings();
        }
        
        include_once plugin_dir_path( __FILE__ ) . 'partials/settings-page.php';
    }

    /**
     * Display mentor dashboard
     *
     * @since    1.0.0
     */
    public function display_mentor_dashboard() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/mentor-dashboard.php';
    }

    /**
     * Display mentee dashboard
     *
     * @since    1.0.0
     */
    public function display_mentee_dashboard() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/mentee-dashboard.php';
    }

    /**
     * Save plugin settings
     *
     * @since    1.0.0
     */
    private function save_settings() {
        $settings = get_option( 'booking_master_settings', array() );
        
        // Update settings with form data
        $settings['currency'] = sanitize_text_field( $_POST['currency'] );
        $settings['currency_symbol'] = sanitize_text_field( $_POST['currency_symbol'] );
        $settings['time_slot_duration'] = intval( $_POST['time_slot_duration'] );
        $settings['booking_buffer_time'] = intval( $_POST['booking_buffer_time'] );
        $settings['zoom_enabled'] = isset( $_POST['zoom_enabled'] ) ? true : false;
        $settings['zoom_api_key'] = sanitize_text_field( $_POST['zoom_api_key'] );
        $settings['zoom_api_secret'] = sanitize_text_field( $_POST['zoom_api_secret'] );
        $settings['auto_approve_bookings'] = isset( $_POST['auto_approve_bookings'] ) ? true : false;
        $settings['email_notifications'] = isset( $_POST['email_notifications'] ) ? true : false;

        update_option( 'booking_master_settings', $settings );
        
        // Add admin notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        } );
    }

    /**
     * Get statistics for dashboard
     *
     * @since    1.0.0
     * @return   array    Statistics data.
     */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total services
        $services_table = $wpdb->prefix . 'bm_services';
        $stats['total_services'] = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table WHERE status = 'active'" );
        
        // Total bookings
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $stats['total_bookings'] = $wpdb->get_var( "SELECT COUNT(*) FROM $bookings_table" );
        
        // Pending bookings
        $stats['pending_bookings'] = $wpdb->get_var( "SELECT COUNT(*) FROM $bookings_table WHERE status = 'pending'" );
        
        // Total mentors
        $stats['total_mentors'] = count( get_users( array( 'role' => 'mentor' ) ) );
        
        // Total mentees
        $stats['total_mentees'] = count( get_users( array( 'role' => 'mentee' ) ) );
        
        // Revenue this month
        $stats['monthly_revenue'] = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM $bookings_table 
             WHERE status IN ('confirmed', 'completed') 
             AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
             AND YEAR(created_at) = YEAR(CURRENT_DATE())"
        );
        
        if ( ! $stats['monthly_revenue'] ) {
            $stats['monthly_revenue'] = 0;
        }
        
        return $stats;
    }

    /**
     * Get recent bookings for dashboard
     *
     * @since    1.0.0
     * @param    int      $limit    Number of bookings to retrieve.
     * @return   array              Recent bookings data.
     */
    public function get_recent_bookings( $limit = 10 ) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, s.service_name, 
                        mentor.display_name as mentor_name, 
                        mentee.display_name as mentee_name
                 FROM $bookings_table b
                 LEFT JOIN $services_table s ON b.service_id = s.id
                 LEFT JOIN {$wpdb->users} mentor ON b.mentor_id = mentor.ID
                 LEFT JOIN {$wpdb->users} mentee ON b.mentee_id = mentee.ID
                 ORDER BY b.created_at DESC
                 LIMIT %d",
                $limit
            )
        );
    }
}