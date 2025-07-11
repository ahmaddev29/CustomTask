<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 */
class Booking_Master {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Booking_Master_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'BOOKING_MASTER_VERSION' ) ) {
            $this->version = BOOKING_MASTER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'booking-master';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_core_classes();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Booking_Master_Loader. Orchestrates the hooks of the plugin.
     * - Booking_Master_i18n. Defines internationalization functionality.
     * - Booking_Master_Admin. Defines all hooks for the admin area.
     * - Booking_Master_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-i18n.php';

        /**
         * The class responsible for database operations.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-database.php';

        /**
         * Core classes
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-user-roles.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-services.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-bookings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-zoom-integration.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-payment-gateways.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-email-notifications.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-availability-management.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-calendar-sync.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/core/class-reports.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-public.php';

        $this->loader = new Booking_Master_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Booking_Master_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Booking_Master_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Booking_Master_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Booking_Master_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
    }

    /**
     * Initialize core classes
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_core_classes() {
        // Initialize user roles
        $user_roles = new Booking_Master_User_Roles();
        $this->loader->add_action( 'init', $user_roles, 'init' );

        // Initialize services
        $services = new Booking_Master_Services();
        $this->loader->add_action( 'init', $services, 'init' );

        // Initialize bookings
        $bookings = new Booking_Master_Bookings();
        $this->loader->add_action( 'init', $bookings, 'init' );

        // Initialize Zoom integration
        $zoom = new Booking_Master_Zoom_Integration();
        $this->loader->add_action( 'init', $zoom, 'init' );

        // Initialize payment gateways
        $payment_gateways = new Booking_Master_Payment_Gateways();
        $this->loader->add_action( 'init', $payment_gateways, 'init' );

        // Initialize email notifications
        $email_notifications = new Booking_Master_Email_Notifications();
        $this->loader->add_action( 'init', $email_notifications, 'init' );

        // Initialize availability management
        $availability_management = new Booking_Master_Availability_Management();
        $this->loader->add_action( 'init', $availability_management, 'init' );

        // Initialize calendar sync
        $calendar_sync = new Booking_Master_Calendar_Sync();
        $this->loader->add_action( 'init', $calendar_sync, 'init' );

        // Initialize reports
        $reports = new Booking_Master_Reports();
        $this->loader->add_action( 'init', $reports, 'init' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Booking_Master_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}