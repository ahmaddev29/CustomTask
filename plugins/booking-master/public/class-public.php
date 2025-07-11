<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 */
class Booking_Master_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( 
            $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'css/public.css', 
            array(), 
            $this->version, 
            'all' 
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 
            $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'js/public.js', 
            array( 'jquery' ), 
            $this->version, 
            false 
        );

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'bm_public_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bm_public_nonce' ),
        ) );
    }

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode( 'booking_master_services', array( $this, 'services_shortcode' ) );
        add_shortcode( 'booking_master_booking_form', array( $this, 'booking_form_shortcode' ) );
        add_shortcode( 'booking_master_mentor_application', array( $this, 'mentor_application_shortcode' ) );
        add_shortcode( 'booking_master_user_dashboard', array( $this, 'user_dashboard_shortcode' ) );
    }

    /**
     * Services listing shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            HTML output.
     */
    public function services_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'mentor_id' => null,
            'limit' => -1,
            'category' => null,
        ), $atts, 'booking_master_services' );

        $services_class = new Booking_Master_Services();
        
        if ( $atts['mentor_id'] ) {
            $services = $services_class->get_services_by_mentor( intval( $atts['mentor_id'] ) );
        } else {
            $services = $services_class->get_all_active_services();
        }

        // Apply limit if specified
        if ( $atts['limit'] > 0 ) {
            $services = array_slice( $services, 0, $atts['limit'] );
        }

        ob_start();
        ?>
        <div id="bm-services-container" class="bm-services-container">
            <?php echo $services_class->get_services_list(); ?>
        </div>
        
        <!-- Booking Modal -->
        <div id="bm-booking-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content">
                <span class="bm-modal-close">&times;</span>
                <div id="bm-booking-form-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Booking form shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            HTML output.
     */
    public function booking_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'service_id' => null,
        ), $atts, 'booking_master_booking_form' );

        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to book a service.</p>';
        }

        if ( ! current_user_can( 'bm_book_services' ) ) {
            return '<p>You do not have permission to book services.</p>';
        }

        if ( ! $atts['service_id'] ) {
            return '<p>Service ID is required.</p>';
        }

        $services_class = new Booking_Master_Services();
        $service = $services_class->get_service( intval( $atts['service_id'] ) );

        if ( ! $service ) {
            return '<p>Service not found.</p>';
        }

        ob_start();
        ?>
        <div class="bm-booking-form-container">
            <h3>Book: <?php echo esc_html( $service->service_name ); ?></h3>
            
            <form id="bm-booking-form" class="bm-booking-form">
                <input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>">
                
                <div class="form-group">
                    <label for="booking-date">Select Date:</label>
                    <input type="date" id="booking-date" name="booking_date" required min="<?php echo date( 'Y-m-d', strtotime( '+1 day' ) ); ?>">
                </div>
                
                <div class="form-group">
                    <label for="booking-time">Select Time:</label>
                    <select id="booking-time" name="booking_time" required>
                        <option value="">First select a date</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="booking-notes">Additional Notes (optional):</label>
                    <textarea id="booking-notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="booking-summary">
                    <h4>Booking Summary</h4>
                    <p><strong>Service:</strong> <?php echo esc_html( $service->service_name ); ?></p>
                    <p><strong>Duration:</strong> <?php echo esc_html( $service->duration ); ?> minutes</p>
                    <p><strong>Price:</strong> $<?php echo esc_html( number_format( $service->price, 2 ) ); ?></p>
                    <?php if ( $service->zoom_enabled ) : ?>
                        <p><strong>Meeting Type:</strong> Zoom (online)</p>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">Book Now</button>
                
                <?php wp_nonce_field( 'bm_booking_nonce', 'nonce' ); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Mentor application shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            HTML output.
     */
    public function mentor_application_shortcode( $atts ) {
        $user_roles = new Booking_Master_User_Roles();
        return $user_roles->get_mentor_application_form();
    }

    /**
     * User dashboard shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            HTML output.
     */
    public function user_dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to access your dashboard.</p>';
        }

        $user = wp_get_current_user();
        $user_roles = new Booking_Master_User_Roles();

        ob_start();
        ?>
        <div class="bm-user-dashboard">
            <h2>Welcome, <?php echo esc_html( $user->display_name ); ?>!</h2>
            
            <?php if ( $user_roles->is_mentor( $user->ID ) ) : ?>
                <?php echo $this->get_mentor_dashboard_content(); ?>
            <?php elseif ( $user_roles->is_mentee( $user->ID ) ) : ?>
                <?php echo $this->get_mentee_dashboard_content(); ?>
            <?php else : ?>
                <div class="dashboard-section">
                    <h3>Get Started</h3>
                    <p>Choose your role to get started:</p>
                    <div class="role-selection">
                        <a href="#" class="btn btn-primary" onclick="applyAsMentor()">Become a Mentor</a>
                        <p>or</p>
                        <p>Browse services as a mentee!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get mentor dashboard content
     *
     * @since    1.0.0
     * @return   string    HTML content.
     */
    private function get_mentor_dashboard_content() {
        $services_class = new Booking_Master_Services();
        $bookings_class = new Booking_Master_Bookings();
        $zoom_class = new Booking_Master_Zoom_Integration();
        
        $user_id = get_current_user_id();
        $services = $services_class->get_services_by_mentor( $user_id );
        $bookings = $bookings_class->get_bookings_by_mentor( $user_id );
        $zoom_status = $zoom_class->get_connection_status( $user_id );

        ob_start();
        ?>
        <div class="mentor-dashboard">
            <!-- Quick Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h4>Total Services</h4>
                    <span class="stat-number"><?php echo count( $services ); ?></span>
                </div>
                <div class="stat-card">
                    <h4>Total Bookings</h4>
                    <span class="stat-number"><?php echo count( $bookings ); ?></span>
                </div>
                <div class="stat-card">
                    <h4>Pending Bookings</h4>
                    <span class="stat-number"><?php echo count( array_filter( $bookings, function( $b ) { return $b->status === 'pending'; } ) ); ?></span>
                </div>
            </div>

            <!-- Zoom Status -->
            <div class="dashboard-section">
                <h3>Zoom Integration</h3>
                <div class="zoom-status <?php echo $zoom_status['connected'] ? 'connected' : 'disconnected'; ?>">
                    <p><?php echo esc_html( $zoom_status['message'] ); ?></p>
                    <?php if ( $zoom_status['connected'] ) : ?>
                        <button class="btn btn-secondary" onclick="disconnectZoom()">Disconnect</button>
                    <?php else : ?>
                        <button class="btn btn-primary" onclick="connectZoom()">Connect Zoom</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Services Management -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>My Services</h3>
                    <button class="btn btn-primary" onclick="showServiceForm()">Add New Service</button>
                </div>
                <div id="services-container">
                    <?php echo $services_class->get_services_list( $user_id ); ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="dashboard-section">
                <h3>Recent Bookings</h3>
                <div class="bookings-list">
                    <?php if ( empty( $bookings ) ) : ?>
                        <p>No bookings yet.</p>
                    <?php else : ?>
                        <?php foreach ( array_slice( $bookings, 0, 5 ) as $booking ) : ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo esc_html( $booking->service_name ); ?></h4>
                                    <p>Mentee: <?php echo esc_html( $booking->mentee_name ); ?></p>
                                    <p>Date: <?php echo date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="status status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span>
                                </div>
                                <div class="booking-actions">
                                    <?php if ( $booking->status === 'pending' ) : ?>
                                        <button class="btn btn-sm btn-success" onclick="updateBookingStatus(<?php echo $booking->id; ?>, 'confirmed')">Confirm</button>
                                        <button class="btn btn-sm btn-danger" onclick="updateBookingStatus(<?php echo $booking->id; ?>, 'cancelled')">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get mentee dashboard content
     *
     * @since    1.0.0
     * @return   string    HTML content.
     */
    private function get_mentee_dashboard_content() {
        $bookings_class = new Booking_Master_Bookings();
        $user_id = get_current_user_id();
        $bookings = $bookings_class->get_bookings_by_mentee( $user_id );

        ob_start();
        ?>
        <div class="mentee-dashboard">
            <!-- Quick Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h4>Total Bookings</h4>
                    <span class="stat-number"><?php echo count( $bookings ); ?></span>
                </div>
                <div class="stat-card">
                    <h4>Upcoming Sessions</h4>
                    <span class="stat-number"><?php echo count( array_filter( $bookings, function( $b ) { return $b->status === 'confirmed' && strtotime( $b->booking_date ) > time(); } ) ); ?></span>
                </div>
            </div>

            <!-- My Bookings -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>My Bookings</h3>
                    <a href="#" class="btn btn-primary">Browse Services</a>
                </div>
                <div class="bookings-list">
                    <?php if ( empty( $bookings ) ) : ?>
                        <p>No bookings yet. <a href="#">Browse available services</a> to get started!</p>
                    <?php else : ?>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo esc_html( $booking->service_name ); ?></h4>
                                    <p>Mentor: <?php echo esc_html( $booking->mentor_name ); ?></p>
                                    <p>Date: <?php echo date( 'F j, Y g:i A', strtotime( $booking->booking_date ) ); ?></p>
                                    <p>Amount: $<?php echo number_format( $booking->total_amount, 2 ); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="status status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span>
                                </div>
                                <div class="booking-actions">
                                    <?php if ( $booking->status === 'confirmed' && $booking->zoom_join_url ) : ?>
                                        <a href="<?php echo esc_url( $booking->zoom_join_url ); ?>" class="btn btn-sm btn-primary" target="_blank">Join Meeting</a>
                                    <?php endif; ?>
                                    <?php if ( in_array( $booking->status, array( 'pending', 'confirmed' ) ) && strtotime( $booking->booking_date ) > time() + 3600 ) : ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelBooking(<?php echo $booking->id; ?>)">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}