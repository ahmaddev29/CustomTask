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
     * Database instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Booking_Master_Database    $db    Database operations.
     */
    private $db;

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
        $this->db = new Booking_Master_Database();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/public.js', array( 'jquery' ), $this->version, false );
        
        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'bm_public_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bm_public_nonce' ),
            'currency_symbol' => get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$',
            'stripe_publishable_key' => get_option( 'booking_master_settings', array() )['stripe_publishable_key'] ?? '',
        ) );
    }

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode( 'booking_master_services', array( $this, 'services_shortcode' ) );
        add_shortcode( 'booking_master_user_dashboard', array( $this, 'user_dashboard_shortcode' ) );
        add_shortcode( 'booking_master_mentor_application', array( $this, 'mentor_application_shortcode' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_bm_get_service_availability', array( $this, 'ajax_get_service_availability' ) );
        add_action( 'wp_ajax_nopriv_bm_get_service_availability', array( $this, 'ajax_get_service_availability' ) );
        add_action( 'wp_ajax_bm_create_booking', array( $this, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_nopriv_bm_create_booking', array( $this, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_bm_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
        add_action( 'wp_ajax_nopriv_bm_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
        add_action( 'wp_ajax_bm_calculate_booking_total', array( $this, 'ajax_calculate_booking_total' ) );
        add_action( 'wp_ajax_nopriv_bm_calculate_booking_total', array( $this, 'ajax_calculate_booking_total' ) );
        
        // Initialize booking modal
        add_action( 'wp_footer', array( $this, 'add_booking_modal' ) );
    }

    /**
     * Services shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function services_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'mentor_id' => '',
            'limit' => -1,
            'category' => '',
            'layout' => 'grid', // grid or list
        ), $atts );

        global $wpdb;
        $services_table = $wpdb->prefix . 'bm_services';
        
        $where_conditions = array( "s.status = 'active'" );
        $params = array();
        
        if ( ! empty( $atts['mentor_id'] ) ) {
            $where_conditions[] = "s.mentor_id = %d";
            $params[] = intval( $atts['mentor_id'] );
        }
        
        $where_clause = implode( ' AND ', $where_conditions );
        $limit_clause = $atts['limit'] > 0 ? "LIMIT " . intval( $atts['limit'] ) : "";
        
        $query = "
            SELECT s.*, u.display_name as mentor_name, u.user_email as mentor_email
            FROM $services_table s
            LEFT JOIN {$wpdb->users} u ON s.mentor_id = u.ID
            WHERE $where_clause
            ORDER BY s.created_at DESC
            $limit_clause
        ";
        
        $services = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
        
        ob_start();
        ?>
        <div class="bm-services-container" data-layout="<?php echo esc_attr( $atts['layout'] ); ?>">
            <?php if ( empty( $services ) ) : ?>
                <div class="bm-no-services">
                    <h3>No services available</h3>
                    <p>Please check back later for available services.</p>
                </div>
            <?php else : ?>
                <div class="bm-services-grid <?php echo esc_attr( $atts['layout'] ); ?>">
                    <?php foreach ( $services as $service ) : ?>
                        <div class="bm-service-card" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                            <div class="bm-service-header">
                                <?php if ( $service->zoom_enabled ) : ?>
                                    <div class="bm-service-badge zoom-enabled">
                                        <i class="bm-icon-video"></i> Online Session
                                    </div>
                                <?php endif; ?>
                                <h3 class="bm-service-title"><?php echo esc_html( $service->service_name ); ?></h3>
                                <div class="bm-service-mentor">
                                    <span class="bm-mentor-label">with</span>
                                    <span class="bm-mentor-name"><?php echo esc_html( $service->mentor_name ); ?></span>
                                </div>
                            </div>
                            
                            <div class="bm-service-content">
                                <?php if ( $service->description ) : ?>
                                    <p class="bm-service-description"><?php echo esc_html( wp_trim_words( $service->description, 25 ) ); ?></p>
                                <?php endif; ?>
                                
                                <div class="bm-service-meta">
                                    <div class="bm-service-price">
                                        <span class="bm-price-amount"><?php echo esc_html( get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$' ); ?><?php echo esc_html( number_format( $service->price, 2 ) ); ?></span>
                                        <span class="bm-price-label">per session</span>
                                    </div>
                                    <div class="bm-service-duration">
                                        <i class="bm-icon-clock"></i>
                                        <span><?php echo esc_html( $service->duration ); ?> minutes</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bm-service-footer">
                                <?php if ( is_user_logged_in() && current_user_can( 'bm_book_services' ) ) : ?>
                                    <button class="bm-book-button" onclick="bmOpenBookingModal(<?php echo esc_attr( $service->id ); ?>)">
                                        <i class="bm-icon-calendar"></i>
                                        <span>Book Now</span>
                                    </button>
                                <?php else : ?>
                                    <button class="bm-book-button" onclick="bmShowLoginRequired()">
                                        <i class="bm-icon-lock"></i>
                                        <span>Login to Book</span>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="bm-view-details-button" onclick="bmViewServiceDetails(<?php echo esc_attr( $service->id ); ?>)">
                                    <span>View Details</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * User dashboard shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function user_dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<div class="bm-login-required"><p>Please <a href="' . wp_login_url( get_permalink() ) . '">login</a> to access your dashboard.</p></div>';
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        ob_start();
        ?>
        <div class="bm-user-dashboard">
            <div class="bm-dashboard-header">
                <h2>Welcome, <?php echo esc_html( $user->display_name ); ?>!</h2>
            </div>
            
            <?php if ( in_array( 'mentor', $user_roles ) ) : ?>
                <?php echo $this->render_mentor_dashboard(); ?>
            <?php elseif ( in_array( 'mentee', $user_roles ) ) : ?>
                <?php echo $this->render_mentee_dashboard(); ?>
            <?php else : ?>
                <div class="bm-role-selection">
                    <h3>Choose Your Role</h3>
                    <p>To get started, please select your role:</p>
                    <div class="bm-role-buttons">
                        <a href="<?php echo esc_url( add_query_arg( 'action', 'become_mentor' ) ); ?>" class="bm-role-button mentor">
                            <i class="bm-icon-user-tie"></i>
                            <h4>Become a Mentor</h4>
                            <p>Offer your expertise and create services</p>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'action', 'become_mentee' ) ); ?>" class="bm-role-button mentee">
                            <i class="bm-icon-user"></i>
                            <h4>Find a Mentor</h4>
                            <p>Book sessions with expert mentors</p>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Mentor application shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function mentor_application_shortcode( $atts ) {
        ob_start();
        ?>
        <div class="bm-mentor-application">
            <div class="bm-application-header">
                <h2>Apply to Become a Mentor</h2>
                <p>Share your expertise and help others achieve their goals. Fill out the application below to get started.</p>
            </div>
            
            <form id="bm-mentor-application-form" class="bm-application-form">
                <div class="bm-form-section">
                    <h3>Personal Information</h3>
                    <div class="bm-form-row">
                        <div class="bm-form-group">
                            <label for="mentor-expertise">Area of Expertise *</label>
                            <input type="text" id="mentor-expertise" name="expertise" required>
                        </div>
                        <div class="bm-form-group">
                            <label for="mentor-experience">Years of Experience *</label>
                            <select id="mentor-experience" name="experience" required>
                                <option value="">Select experience</option>
                                <option value="1-2">1-2 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="6-10">6-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="bm-form-group">
                        <label for="mentor-bio">Professional Bio *</label>
                        <textarea id="mentor-bio" name="bio" rows="5" placeholder="Tell us about your background, achievements, and what makes you a great mentor..." required></textarea>
                    </div>
                </div>
                
                <div class="bm-form-section">
                    <h3>Services You Plan to Offer</h3>
                    <div class="bm-form-group">
                        <label for="mentor-services">Describe the services you'd like to offer *</label>
                        <textarea id="mentor-services" name="planned_services" rows="4" placeholder="What type of mentoring sessions would you provide? What topics would you cover?" required></textarea>
                    </div>
                    
                    <div class="bm-form-row">
                        <div class="bm-form-group">
                            <label for="mentor-rate">Preferred Hourly Rate *</label>
                            <div class="bm-input-with-symbol">
                                <span class="bm-currency-symbol">$</span>
                                <input type="number" id="mentor-rate" name="hourly_rate" min="10" step="5" required>
                            </div>
                        </div>
                        <div class="bm-form-group">
                            <label for="mentor-availability">Weekly Availability *</label>
                            <select id="mentor-availability" name="availability" required>
                                <option value="">Select availability</option>
                                <option value="5-10">5-10 hours per week</option>
                                <option value="10-20">10-20 hours per week</option>
                                <option value="20-30">20-30 hours per week</option>
                                <option value="30+">30+ hours per week</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bm-form-section">
                    <h3>Additional Information</h3>
                    <div class="bm-form-group">
                        <label>
                            <input type="checkbox" name="zoom_comfortable" value="1">
                            I am comfortable conducting sessions via video calls (Zoom)
                        </label>
                    </div>
                    
                    <div class="bm-form-group">
                        <label>
                            <input type="checkbox" name="terms_accepted" value="1" required>
                            I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Mentor Guidelines</a> *
                        </label>
                    </div>
                </div>
                
                <div class="bm-form-footer">
                    <button type="submit" class="bm-submit-button">
                        <span>Submit Application</span>
                        <i class="bm-icon-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get service availability (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_service_availability() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $date = sanitize_text_field( $_POST['date'] );
        
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => 'Service not found' ) );
        }

        // Get available time slots for the date
        $availability_manager = new Booking_Master_Availability_Management();
        $available_slots = $availability_manager->get_time_slots_for_date( $service->mentor_id, $date );
        
        // Filter out booked slots
        $booked_slots = $this->get_booked_slots( $service_id, $date );
        $available_slots = array_diff( $available_slots, $booked_slots );
        
        wp_send_json_success( array( 'slots' => array_values( $available_slots ) ) );
    }

    /**
     * Create booking (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_booking() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please login to create a booking' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $booking_date = sanitize_text_field( $_POST['booking_date'] ) . ' ' . sanitize_text_field( $_POST['booking_time'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );
        $payment_method = sanitize_text_field( $_POST['payment_method'] );
        
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => 'Service not found' ) );
        }

        // Calculate total amount including taxes and fees
        $booking_total = $this->calculate_booking_total( $service->price );
        
        $booking_data = array(
            'service_id' => $service_id,
            'mentee_id' => get_current_user_id(),
            'mentor_id' => $service->mentor_id,
            'booking_date' => $booking_date,
            'total_amount' => $booking_total['total'],
            'notes' => $notes,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $payment_method,
        );

        $booking_id = $this->db->create_booking( $booking_data );

        if ( $booking_id ) {
            // Process payment if payment method is provided
            if ( $payment_method === 'stripe' ) {
                $this->process_stripe_payment( $booking_id, $booking_total );
            } elseif ( $payment_method === 'paypal' ) {
                $this->process_paypal_payment( $booking_id, $booking_total );
            }
            
            wp_send_json_success( array( 
                'message' => 'Booking created successfully!',
                'booking_id' => $booking_id,
                'total' => $booking_total
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create booking' ) );
        }
    }

    /**
     * Calculate booking total (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_calculate_booking_total() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_public_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $service = $this->db->get_service( $service_id );
        
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => 'Service not found' ) );
        }

        $total_breakdown = $this->calculate_booking_total( $service->price );
        wp_send_json_success( $total_breakdown );
    }

    /**
     * Calculate booking total including taxes and fees
     *
     * @since    1.0.0
     * @param    float    $base_price    Base service price.
     * @return   array                   Total breakdown.
     */
    private function calculate_booking_total( $base_price ) {
        $settings = get_option( 'booking_master_settings', array() );
        
        $tax_rate = floatval( $settings['tax_rate'] ?? 0 ) / 100;
        $management_fee_rate = floatval( $settings['management_fee_rate'] ?? 0 ) / 100;
        
        $subtotal = $base_price;
        $management_fee = $subtotal * $management_fee_rate;
        $tax_amount = ( $subtotal + $management_fee ) * $tax_rate;
        $total = $subtotal + $management_fee + $tax_amount;
        
        return array(
            'subtotal' => round( $subtotal, 2 ),
            'management_fee' => round( $management_fee, 2 ),
            'management_fee_rate' => $management_fee_rate * 100,
            'tax_amount' => round( $tax_amount, 2 ),
            'tax_rate' => $tax_rate * 100,
            'total' => round( $total, 2 ),
        );
    }

    /**
     * Add booking modal to footer
     *
     * @since    1.0.0
     */
    public function add_booking_modal() {
        if ( ! $this->should_load_booking_modal() ) {
            return;
        }
        ?>
        <!-- Booking Modal -->
        <div id="bm-booking-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content bm-booking-modal-content">
                <div class="bm-modal-header">
                    <h3 id="bm-booking-modal-title">Book Your Session</h3>
                    <button class="bm-modal-close" onclick="bmCloseBookingModal()">
                        <i class="bm-icon-close"></i>
                    </button>
                </div>
                
                <div class="bm-booking-steps">
                    <div class="bm-step" data-step="1">
                        <div class="bm-step-number">1</div>
                        <div class="bm-step-label">Select Date</div>
                    </div>
                    <div class="bm-step" data-step="2">
                        <div class="bm-step-number">2</div>
                        <div class="bm-step-label">Choose Time</div>
                    </div>
                    <div class="bm-step" data-step="3">
                        <div class="bm-step-number">3</div>
                        <div class="bm-step-label">Your Details</div>
                    </div>
                    <div class="bm-step" data-step="4">
                        <div class="bm-step-number">4</div>
                        <div class="bm-step-label">Payment</div>
                    </div>
                    <div class="bm-step" data-step="5">
                        <div class="bm-step-number">5</div>
                        <div class="bm-step-label">Confirmation</div>
                    </div>
                </div>
                
                <div class="bm-modal-body">
                    <!-- Step 1: Date Selection -->
                    <div class="bm-booking-step" id="bm-step-1" data-step="1">
                        <h4>Select Your Preferred Date</h4>
                        <div id="bm-booking-calendar" class="bm-booking-calendar">
                            <!-- Calendar will be generated here -->
                        </div>
                    </div>
                    
                    <!-- Step 2: Time Selection -->
                    <div class="bm-booking-step" id="bm-step-2" data-step="2" style="display: none;">
                        <h4>Choose Your Time Slot</h4>
                        <p>Selected date: <span id="bm-selected-date"></span></p>
                        <div id="bm-time-slots" class="bm-time-slots">
                            <!-- Time slots will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Step 3: Personal Information -->
                    <div class="bm-booking-step" id="bm-step-3" data-step="3" style="display: none;">
                        <h4>Confirm Your Details</h4>
                        <form id="bm-booking-details-form">
                            <div class="bm-form-row">
                                <div class="bm-form-group">
                                    <label for="bm-first-name">First Name *</label>
                                    <input type="text" id="bm-first-name" name="first_name" required>
                                </div>
                                <div class="bm-form-group">
                                    <label for="bm-last-name">Last Name *</label>
                                    <input type="text" id="bm-last-name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="bm-form-group">
                                <label for="bm-email">Email Address *</label>
                                <input type="email" id="bm-email" name="email" required>
                            </div>
                            
                            <div class="bm-form-group">
                                <label for="bm-phone">Phone Number</label>
                                <input type="tel" id="bm-phone" name="phone">
                            </div>
                            
                            <div class="bm-form-group">
                                <label for="bm-notes">Additional Notes (Optional)</label>
                                <textarea id="bm-notes" name="notes" rows="3" placeholder="Any specific topics you'd like to discuss or questions you have..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Step 4: Payment -->
                    <div class="bm-booking-step" id="bm-step-4" data-step="4" style="display: none;">
                        <h4>Payment Details</h4>
                        
                        <div class="bm-booking-summary">
                            <h5>Booking Summary</h5>
                            <div class="bm-summary-item">
                                <span>Service:</span>
                                <span id="bm-summary-service"></span>
                            </div>
                            <div class="bm-summary-item">
                                <span>Date & Time:</span>
                                <span id="bm-summary-datetime"></span>
                            </div>
                            <div class="bm-summary-item">
                                <span>Duration:</span>
                                <span id="bm-summary-duration"></span>
                            </div>
                        </div>
                        
                        <div class="bm-payment-breakdown">
                            <div class="bm-breakdown-item">
                                <span>Subtotal:</span>
                                <span id="bm-breakdown-subtotal"></span>
                            </div>
                            <div class="bm-breakdown-item">
                                <span>Management Fee (<span id="bm-management-rate"></span>%):</span>
                                <span id="bm-breakdown-management"></span>
                            </div>
                            <div class="bm-breakdown-item">
                                <span>Tax (<span id="bm-tax-rate"></span>%):</span>
                                <span id="bm-breakdown-tax"></span>
                            </div>
                            <div class="bm-breakdown-item bm-total">
                                <span>Total:</span>
                                <span id="bm-breakdown-total"></span>
                            </div>
                        </div>
                        
                        <div class="bm-payment-methods">
                            <h5>Select Payment Method</h5>
                            <div class="bm-payment-options">
                                <?php
                                $settings = get_option( 'booking_master_settings', array() );
                                if ( ! empty( $settings['stripe_enabled'] ) ) :
                                ?>
                                <label class="bm-payment-option">
                                    <input type="radio" name="payment_method" value="stripe" checked>
                                    <div class="bm-payment-option-content">
                                        <i class="bm-icon-credit-card"></i>
                                        <span>Credit/Debit Card</span>
                                    </div>
                                </label>
                                <?php endif; ?>
                                
                                <?php if ( ! empty( $settings['paypal_enabled'] ) ) : ?>
                                <label class="bm-payment-option">
                                    <input type="radio" name="payment_method" value="paypal">
                                    <div class="bm-payment-option-content">
                                        <i class="bm-icon-paypal"></i>
                                        <span>PayPal</span>
                                    </div>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div id="bm-stripe-payment" class="bm-payment-form">
                            <div id="bm-card-element">
                                <!-- Stripe card element will be mounted here -->
                            </div>
                            <div id="bm-card-errors" role="alert"></div>
                        </div>
                        
                        <div id="bm-paypal-payment" class="bm-payment-form" style="display: none;">
                            <div id="bm-paypal-button-container">
                                <!-- PayPal button will be rendered here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 5: Confirmation -->
                    <div class="bm-booking-step" id="bm-step-5" data-step="5" style="display: none;">
                        <div class="bm-success-message">
                            <div class="bm-success-icon">
                                <i class="bm-icon-check-circle"></i>
                            </div>
                            <h4>Congratulations!</h4>
                            <p>Your booking has been confirmed successfully.</p>
                        </div>
                        
                        <div class="bm-booking-confirmation">
                            <h5>Booking Details</h5>
                            <div id="bm-confirmation-details">
                                <!-- Booking details will be populated here -->
                            </div>
                        </div>
                        
                        <div class="bm-next-steps">
                            <h5>What's Next?</h5>
                            <ul>
                                <li>You'll receive a confirmation email shortly</li>
                                <li>Your mentor will be notified about the booking</li>
                                <li>If this is a video session, Zoom details will be provided</li>
                                <li>You can manage your bookings from your dashboard</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bm-modal-footer">
                    <button id="bm-booking-back" class="bm-button bm-button-secondary" onclick="bmBookingPrevStep()" style="display: none;">
                        <i class="bm-icon-arrow-left"></i>
                        <span>Back</span>
                    </button>
                    
                    <button id="bm-booking-next" class="bm-button bm-button-primary" onclick="bmBookingNextStep()">
                        <span>Continue</span>
                        <i class="bm-icon-arrow-right"></i>
                    </button>
                    
                    <button id="bm-booking-finish" class="bm-button bm-button-primary" onclick="bmCloseBookingModal()" style="display: none;">
                        <span>Finish</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Login Required Modal -->
        <div id="bm-login-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content">
                <div class="bm-modal-header">
                    <h3>Login Required</h3>
                    <button class="bm-modal-close" onclick="bmCloseLoginModal()">
                        <i class="bm-icon-close"></i>
                    </button>
                </div>
                <div class="bm-modal-body">
                    <p>Please login or create an account to book a session.</p>
                    <div class="bm-login-buttons">
                        <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="bm-button bm-button-primary">Login</a>
                        <a href="<?php echo wp_registration_url(); ?>" class="bm-button bm-button-secondary">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Check if booking modal should be loaded
     *
     * @since    1.0.0
     * @return   bool
     */
    private function should_load_booking_modal() {
        global $post;
        
        if ( ! $post ) {
            return false;
        }
        
        // Check if the current page contains the services shortcode
        return has_shortcode( $post->post_content, 'booking_master_services' );
    }

    /**
     * Render mentor dashboard
     *
     * @since    1.0.0
     * @return   string
     */
    private function render_mentor_dashboard() {
        global $wpdb;
        $mentor_id = get_current_user_id();
        
        // Get mentor statistics
        $services_table = $wpdb->prefix . 'bm_services';
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $stats = array(
            'total_services' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $services_table WHERE mentor_id = %d AND status = 'active'",
                $mentor_id
            ) ),
            'total_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d",
                $mentor_id
            ) ),
            'upcoming_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d AND status = 'confirmed' AND booking_date > NOW()",
                $mentor_id
            ) ),
            'completed_sessions' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d AND status = 'completed'",
                $mentor_id
            ) ),
            'pending_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d AND status = 'pending'",
                $mentor_id
            ) ),
            'monthly_earnings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_amount) FROM $bookings_table 
                 WHERE mentor_id = %d AND status IN ('confirmed', 'completed') 
                 AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                 AND YEAR(created_at) = YEAR(CURRENT_DATE())",
                $mentor_id
            ) ) ?? 0,
        );
        
        // Get recent bookings
        $recent_bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.service_name, u.display_name as mentee_name 
             FROM $bookings_table b 
             LEFT JOIN $services_table s ON b.service_id = s.id 
             LEFT JOIN {$wpdb->users} u ON b.mentee_id = u.ID 
             WHERE b.mentor_id = %d 
             ORDER BY b.booking_date DESC 
             LIMIT 8",
            $mentor_id
        ) );
        
        // Get mentor services
        $services = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE mentor_id = %d ORDER BY created_at DESC",
            $mentor_id
        ) );
        
        ob_start();
        ?>
        <div class="bm-mentor-dashboard">
            <!-- Dashboard Header -->
            <div class="bm-dashboard-header">
                <div class="bm-dashboard-title">
                    <h3>Mentor Dashboard</h3>
                    <p>Manage your services and bookings</p>
                </div>
                <div class="bm-dashboard-actions">
                    <button class="bm-button bm-button-primary" onclick="bmShowCreateService()">
                        <i class="bm-icon-plus"></i>
                        Create Service
                    </button>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="bm-dashboard-stats">
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-service"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['total_services'] ); ?></h4>
                        <p>Active Services</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-calendar"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['upcoming_bookings'] ); ?></h4>
                        <p>Upcoming Sessions</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-clock"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['pending_bookings'] ); ?></h4>
                        <p>Pending Approval</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-dollar"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$' ); ?><?php echo esc_html( number_format( $stats['monthly_earnings'], 2 ) ); ?></h4>
                        <p>This Month</p>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Navigation -->
            <div class="bm-dashboard-nav">
                <button class="bm-nav-tab active" data-tab="overview">Overview</button>
                <button class="bm-nav-tab" data-tab="services">My Services</button>
                <button class="bm-nav-tab" data-tab="bookings">Bookings</button>
                <button class="bm-nav-tab" data-tab="availability">Availability</button>
                <button class="bm-nav-tab" data-tab="earnings">Earnings</button>
                <button class="bm-nav-tab" data-tab="profile">Profile</button>
            </div>
            
            <!-- Overview Tab -->
            <div class="bm-dashboard-tab active" id="bm-tab-overview">
                <div class="bm-dashboard-grid">
                    <div class="bm-dashboard-section">
                        <h4>Recent Bookings</h4>
                        <?php if ( $recent_bookings ) : ?>
                        <div class="bm-bookings-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Mentee</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $recent_bookings as $booking ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $booking->service_name ); ?></td>
                                        <td><?php echo esc_html( $booking->mentee_name ); ?></td>
                                        <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date ) ) ); ?></td>
                                        <td>
                                            <span class="bm-status <?php echo esc_attr( $booking->status ); ?>">
                                                <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="bm-action-buttons">
                                                <?php if ( $booking->status === 'pending' ) : ?>
                                                <button class="bm-button bm-button-small bm-button-success" onclick="bmApproveBooking(<?php echo $booking->id; ?>)">
                                                    Approve
                                                </button>
                                                <button class="bm-button bm-button-small bm-button-danger" onclick="bmRejectBooking(<?php echo $booking->id; ?>)">
                                                    Reject
                                                </button>
                                                <?php else : ?>
                                                <button class="bm-button bm-button-small" onclick="bmViewBooking(<?php echo $booking->id; ?>)">
                                                    View
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else : ?>
                        <p>No bookings yet. <a href="#" onclick="bmShowCreateService()">Create your first service</a> to start receiving bookings!</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bm-dashboard-section">
                        <h4>Quick Actions</h4>
                        <div class="bm-quick-actions">
                            <button class="bm-action-item" onclick="bmShowCreateService()">
                                <i class="bm-icon-plus"></i>
                                <span>Create New Service</span>
                            </button>
                            <button class="bm-action-item" onclick="bmShowTab('availability')">
                                <i class="bm-icon-calendar"></i>
                                <span>Set Availability</span>
                            </button>
                            <button class="bm-action-item" onclick="bmShowTab('earnings')">
                                <i class="bm-icon-chart"></i>
                                <span>View Earnings</span>
                            </button>
                            <button class="bm-action-item" onclick="bmShowTab('profile')">
                                <i class="bm-icon-user"></i>
                                <span>Update Profile</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-services">
                <div class="bm-section-header">
                    <h4>My Services</h4>
                    <button class="bm-button bm-button-primary" onclick="bmShowCreateService()">
                        <i class="bm-icon-plus"></i>
                        Add Service
                    </button>
                </div>
                
                <?php if ( $services ) : ?>
                <div class="bm-services-grid">
                    <?php foreach ( $services as $service ) : ?>
                    <div class="bm-service-card">
                        <div class="bm-service-header">
                            <h5><?php echo esc_html( $service->service_name ); ?></h5>
                            <div class="bm-service-status">
                                <span class="bm-status <?php echo esc_attr( $service->status ); ?>">
                                    <?php echo esc_html( ucfirst( $service->status ) ); ?>
                                </span>
                            </div>
                        </div>
                        <div class="bm-service-details">
                            <p class="bm-service-description"><?php echo esc_html( wp_trim_words( $service->description, 15 ) ); ?></p>
                            <div class="bm-service-meta">
                                <span class="bm-price">
                                    <?php echo esc_html( get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$' ); ?><?php echo esc_html( number_format( $service->price, 2 ) ); ?>
                                </span>
                                <span class="bm-duration"><?php echo esc_html( $service->duration ); ?> min</span>
                            </div>
                        </div>
                        <div class="bm-service-actions">
                            <button class="bm-button bm-button-small" onclick="bmEditService(<?php echo $service->id; ?>)">
                                Edit
                            </button>
                            <button class="bm-button bm-button-small bm-button-secondary" onclick="bmDuplicateService(<?php echo $service->id; ?>)">
                                Duplicate
                            </button>
                            <button class="bm-button bm-button-small bm-button-danger" onclick="bmDeleteService(<?php echo $service->id; ?>)">
                                Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="bm-empty-state">
                    <div class="bm-empty-icon">
                        <i class="bm-icon-service"></i>
                    </div>
                    <h5>No Services Created Yet</h5>
                    <p>Create your first service to start offering mentoring sessions.</p>
                    <button class="bm-button bm-button-primary" onclick="bmShowCreateService()">
                        Create Your First Service
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Bookings Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-bookings">
                <div class="bm-section-header">
                    <h4>All Bookings</h4>
                    <div class="bm-filters">
                        <select id="bm-booking-status-filter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" id="bm-booking-date-filter" placeholder="Filter by date">
                    </div>
                </div>
                
                <div id="bm-bookings-container">
                    <!-- Bookings will be loaded here via AJAX -->
                </div>
            </div>
            
            <!-- Availability Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-availability">
                <div class="bm-section-header">
                    <h4>Set Your Availability</h4>
                    <p>Define when you're available for mentoring sessions</p>
                </div>
                
                <div id="bm-availability-manager">
                    <!-- Availability manager will be loaded here -->
                </div>
            </div>
            
            <!-- Earnings Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-earnings">
                <div class="bm-section-header">
                    <h4>Earnings & Payouts</h4>
                </div>
                
                <div id="bm-earnings-dashboard">
                    <!-- Earnings dashboard will be loaded here -->
                </div>
            </div>
            
            <!-- Profile Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-profile">
                <div class="bm-section-header">
                    <h4>Mentor Profile</h4>
                    <p>Update your profile information and preferences</p>
                </div>
                
                <div id="bm-profile-form">
                    <!-- Profile form will be loaded here -->
                </div>
            </div>
        </div>
        
        <!-- Create/Edit Service Modal -->
        <div id="bm-service-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content">
                <div class="bm-modal-header">
                    <h3 id="bm-service-modal-title">Create New Service</h3>
                    <button class="bm-modal-close" onclick="bmCloseServiceModal()">
                        <i class="bm-icon-close"></i>
                    </button>
                </div>
                <div class="bm-modal-body">
                    <form id="bm-service-form">
                        <input type="hidden" id="bm-service-id" name="service_id">
                        
                        <div class="bm-form-group">
                            <label for="bm-service-name">Service Name *</label>
                            <input type="text" id="bm-service-name" name="service_name" required>
                        </div>
                        
                        <div class="bm-form-group">
                            <label for="bm-service-description">Description *</label>
                            <textarea id="bm-service-description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="bm-form-row">
                            <div class="bm-form-group">
                                <label for="bm-service-price">Price *</label>
                                <div class="bm-input-with-symbol">
                                    <span class="bm-currency-symbol"><?php echo esc_html( get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$' ); ?></span>
                                    <input type="number" id="bm-service-price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="bm-form-group">
                                <label for="bm-service-duration">Duration (minutes) *</label>
                                <select id="bm-service-duration" name="duration" required>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                    <option value="90">90 minutes</option>
                                    <option value="120">120 minutes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="bm-form-group">
                            <label for="bm-service-category">Category</label>
                            <select id="bm-service-category" name="category">
                                <option value="">Select Category</option>
                                <option value="business">Business</option>
                                <option value="technology">Technology</option>
                                <option value="marketing">Marketing</option>
                                <option value="design">Design</option>
                                <option value="personal-development">Personal Development</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="bm-form-group">
                            <label>
                                <input type="checkbox" id="bm-service-zoom" name="zoom_enabled" value="1">
                                Enable Zoom for this service
                            </label>
                        </div>
                        
                        <div class="bm-form-group">
                            <label for="bm-service-status">Status</label>
                            <select id="bm-service-status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="bm-modal-footer">
                    <button class="bm-button bm-button-secondary" onclick="bmCloseServiceModal()">Cancel</button>
                    <button class="bm-button bm-button-primary" onclick="bmSaveService()">Save Service</button>
                </div>
            </div>
        </div>
        
        <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            bmInitializeMentorDashboard();
        });
        
        function bmInitializeMentorDashboard() {
            // Tab navigation
            const tabs = document.querySelectorAll('.bm-nav-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    bmShowTab(tabId);
                });
            });
            
            // Load initial content
            bmLoadBookings();
        }
        
        function bmShowTab(tabId) {
            // Remove active class from all tabs and tab contents
            document.querySelectorAll('.bm-nav-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.bm-dashboard-tab').forEach(tab => tab.classList.remove('active'));
            
            // Add active class to selected tab and content
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            document.getElementById(`bm-tab-${tabId}`).classList.add('active');
            
            // Load content based on tab
            switch(tabId) {
                case 'bookings':
                    bmLoadBookings();
                    break;
                case 'availability':
                    bmLoadAvailability();
                    break;
                case 'earnings':
                    bmLoadEarnings();
                    break;
                case 'profile':
                    bmLoadProfile();
                    break;
            }
        }
        
        function bmShowCreateService() {
            document.getElementById('bm-service-modal-title').textContent = 'Create New Service';
            document.getElementById('bm-service-form').reset();
            document.getElementById('bm-service-id').value = '';
            document.getElementById('bm-service-modal').style.display = 'flex';
        }
        
        function bmCloseServiceModal() {
            document.getElementById('bm-service-modal').style.display = 'none';
        }
        
        function bmSaveService() {
            const form = document.getElementById('bm-service-form');
            const formData = new FormData(form);
            formData.append('action', 'bm_save_service');
            formData.append('nonce', '<?php echo wp_create_nonce( 'bm_mentor_nonce' ); ?>');
            
            fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bmCloseServiceModal();
                    location.reload(); // Reload to show updated services
                } else {
                    alert('Error: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the service.');
            });
        }
        
        function bmLoadBookings() {
            // Load bookings via AJAX
            // Implementation will be added
        }
        
        function bmLoadAvailability() {
            // Load availability manager
            // Implementation will be added
        }
        
        function bmLoadEarnings() {
            // Load earnings dashboard
            // Implementation will be added
        }
        
        function bmLoadProfile() {
            // Load profile form
            // Implementation will be added
        }
        
        function bmApproveBooking(bookingId) {
            if (confirm('Are you sure you want to approve this booking?')) {
                // AJAX call to approve booking
                // Implementation will be added
            }
        }
        
        function bmRejectBooking(bookingId) {
            if (confirm('Are you sure you want to reject this booking?')) {
                // AJAX call to reject booking
                // Implementation will be added
            }
        }
        
        function bmViewBooking(bookingId) {
            // Show booking details modal
            // Implementation will be added
        }
        
        function bmEditService(serviceId) {
            // Load service data and show edit modal
            // Implementation will be added
        }
        
        function bmDuplicateService(serviceId) {
            // Duplicate service
            // Implementation will be added
        }
        
        function bmDeleteService(serviceId) {
            if (confirm('Are you sure you want to delete this service?')) {
                // AJAX call to delete service
                // Implementation will be added
            }
        }
        </script>
        
        <style>
        .bm-mentor-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .bm-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .bm-dashboard-title h3 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }
        
        .bm-dashboard-title p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .bm-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .bm-stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .bm-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #0073aa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .bm-stat-content h4 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .bm-stat-content p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .bm-dashboard-nav {
            display: flex;
            gap: 2px;
            margin-bottom: 30px;
            background: #f5f5f5;
            padding: 4px;
            border-radius: 8px;
        }
        
        .bm-nav-tab {
            background: transparent;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #666;
        }
        
        .bm-nav-tab:hover {
            background: #e0e0e0;
        }
        
        .bm-nav-tab.active {
            background: #0073aa;
            color: white;
        }
        
        .bm-dashboard-tab {
            display: none;
        }
        
        .bm-dashboard-tab.active {
            display: block;
        }
        
        .bm-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .bm-dashboard-section {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .bm-dashboard-section h4 {
            margin: 0 0 20px;
            color: #333;
        }
        
        .bm-bookings-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bm-bookings-table th,
        .bm-bookings-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .bm-bookings-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .bm-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .bm-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .bm-status.confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .bm-status.completed {
            background: #cce5ff;
            color: #004085;
        }
        
        .bm-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .bm-action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .bm-quick-actions {
            display: grid;
            gap: 15px;
        }
        
        .bm-action-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .bm-action-item:hover {
            background: #e9ecef;
            border-color: #0073aa;
        }
        
        .bm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .bm-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .bm-service-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .bm-service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .bm-service-header h5 {
            margin: 0;
            color: #333;
        }
        
        .bm-service-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .bm-service-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .bm-price {
            font-size: 18px;
            font-weight: 600;
            color: #0073aa;
        }
        
        .bm-duration {
            color: #666;
            font-size: 14px;
        }
        
        .bm-service-actions {
            display: flex;
            gap: 10px;
        }
        
        .bm-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .bm-empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .bm-empty-state h5 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .bm-filters {
            display: flex;
            gap: 10px;
        }
        
        .bm-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .bm-button:hover {
            background: #005a87;
        }
        
        .bm-button-secondary {
            background: #6c757d;
        }
        
        .bm-button-secondary:hover {
            background: #5a6268;
        }
        
        .bm-button-success {
            background: #28a745;
        }
        
        .bm-button-success:hover {
            background: #218838;
        }
        
        .bm-button-danger {
            background: #dc3545;
        }
        
        .bm-button-danger:hover {
            background: #c82333;
        }
        
        .bm-button-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .bm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .bm-modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .bm-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .bm-modal-header h3 {
            margin: 0;
        }
        
        .bm-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }
        
        .bm-modal-body {
            padding: 20px;
        }
        
        .bm-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .bm-form-group {
            margin-bottom: 20px;
        }
        
        .bm-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .bm-form-group input,
        .bm-form-group select,
        .bm-form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .bm-form-group textarea {
            resize: vertical;
        }
        
        .bm-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .bm-input-with-symbol {
            position: relative;
        }
        
        .bm-currency-symbol {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .bm-input-with-symbol input {
            padding-left: 30px;
        }
        
        @media (max-width: 768px) {
            .bm-dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .bm-dashboard-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .bm-dashboard-nav {
                flex-wrap: wrap;
            }
            
            .bm-nav-tab {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .bm-services-grid {
                grid-template-columns: 1fr;
            }
            
            .bm-form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Render mentee dashboard
     *
     * @since    1.0.0
     * @return   string
     */
    private function render_mentee_dashboard() {
        global $wpdb;
        $mentee_id = get_current_user_id();
        
        // Get mentee statistics
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $services_table = $wpdb->prefix . 'bm_services';
        
        $stats = array(
            'total_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d",
                $mentee_id
            ) ),
            'upcoming_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d AND status = 'confirmed' AND booking_date > NOW()",
                $mentee_id
            ) ),
            'completed_sessions' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d AND status = 'completed'",
                $mentee_id
            ) ),
            'total_spent' => $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_amount) FROM $bookings_table WHERE mentee_id = %d AND status IN ('confirmed', 'completed')",
                $mentee_id
            ) ) ?? 0,
        );
        
        // Get recent bookings
        $recent_bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.service_name, u.display_name as mentor_name, u.user_email as mentor_email
             FROM $bookings_table b 
             LEFT JOIN $services_table s ON b.service_id = s.id 
             LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID 
             WHERE b.mentee_id = %d 
             ORDER BY b.booking_date DESC 
             LIMIT 10",
            $mentee_id
        ) );
        
        // Get upcoming sessions
        $upcoming_sessions = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.service_name, u.display_name as mentor_name, u.user_email as mentor_email
             FROM $bookings_table b 
             LEFT JOIN $services_table s ON b.service_id = s.id 
             LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID 
             WHERE b.mentee_id = %d AND b.status = 'confirmed' AND b.booking_date > NOW() 
             ORDER BY b.booking_date ASC 
             LIMIT 5",
            $mentee_id
        ) );
        
        ob_start();
        ?>
        <div class="bm-mentee-dashboard">
            <!-- Dashboard Header -->
            <div class="bm-dashboard-header">
                <div class="bm-dashboard-title">
                    <h3>My Learning Dashboard</h3>
                    <p>Track your mentoring sessions and progress</p>
                </div>
                <div class="bm-dashboard-actions">
                    <a href="<?php echo home_url( '/services' ); ?>" class="bm-button bm-button-primary">
                        <i class="bm-icon-search"></i>
                        Browse Services
                    </a>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="bm-dashboard-stats">
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-calendar"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['total_bookings'] ); ?></h4>
                        <p>Total Sessions</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-clock"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['upcoming_bookings'] ); ?></h4>
                        <p>Upcoming Sessions</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-check-circle"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( $stats['completed_sessions'] ); ?></h4>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="bm-stat-card">
                    <div class="bm-stat-icon">
                        <i class="bm-icon-dollar"></i>
                    </div>
                    <div class="bm-stat-content">
                        <h4><?php echo esc_html( get_option( 'booking_master_settings', array() )['currency_symbol'] ?? '$' ); ?><?php echo esc_html( number_format( $stats['total_spent'], 2 ) ); ?></h4>
                        <p>Total Invested</p>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Navigation -->
            <div class="bm-dashboard-nav">
                <button class="bm-nav-tab active" data-tab="overview">Overview</button>
                <button class="bm-nav-tab" data-tab="sessions">My Sessions</button>
                <button class="bm-nav-tab" data-tab="history">History</button>
                <button class="bm-nav-tab" data-tab="favorites">Favorites</button>
                <button class="bm-nav-tab" data-tab="profile">Profile</button>
            </div>
            
            <!-- Overview Tab -->
            <div class="bm-dashboard-tab active" id="bm-tab-overview">
                <div class="bm-dashboard-grid">
                    <div class="bm-dashboard-section">
                        <h4>Upcoming Sessions</h4>
                        <?php if ( $upcoming_sessions ) : ?>
                        <div class="bm-upcoming-sessions">
                            <?php foreach ( $upcoming_sessions as $session ) : ?>
                            <div class="bm-session-card">
                                <div class="bm-session-info">
                                    <h5><?php echo esc_html( $session->service_name ); ?></h5>
                                    <p class="bm-session-mentor">with <?php echo esc_html( $session->mentor_name ); ?></p>
                                    <p class="bm-session-date">
                                        <i class="bm-icon-calendar"></i>
                                        <?php echo esc_html( date( 'M j, Y', strtotime( $session->booking_date ) ) ); ?>
                                    </p>
                                    <p class="bm-session-time">
                                        <i class="bm-icon-clock"></i>
                                        <?php echo esc_html( date( 'g:i A', strtotime( $session->booking_date ) ) ); ?>
                                    </p>
                                </div>
                                <div class="bm-session-actions">
                                    <button class="bm-button bm-button-small" onclick="bmViewSession(<?php echo $session->id; ?>)">
                                        View Details
                                    </button>
                                    <?php if ( $session->zoom_meeting_url ) : ?>
                                    <a href="<?php echo esc_url( $session->zoom_meeting_url ); ?>" class="bm-button bm-button-small bm-button-success" target="_blank">
                                        Join Session
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else : ?>
                        <div class="bm-empty-state-small">
                            <p>No upcoming sessions. <a href="<?php echo home_url( '/services' ); ?>">Book your next session</a>!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bm-dashboard-section">
                        <h4>Quick Actions</h4>
                        <div class="bm-quick-actions">
                            <a href="<?php echo home_url( '/services' ); ?>" class="bm-action-item">
                                <i class="bm-icon-search"></i>
                                <span>Browse Services</span>
                            </a>
                            <button class="bm-action-item" onclick="bmShowTab('sessions')">
                                <i class="bm-icon-calendar"></i>
                                <span>View All Sessions</span>
                            </button>
                            <button class="bm-action-item" onclick="bmShowTab('history')">
                                <i class="bm-icon-history"></i>
                                <span>Session History</span>
                            </button>
                            <button class="bm-action-item" onclick="bmShowTab('profile')">
                                <i class="bm-icon-user"></i>
                                <span>Update Profile</span>
                            </button>
                        </div>
                        
                        <div class="bm-progress-section">
                            <h5>Learning Progress</h5>
                            <div class="bm-progress-item">
                                <span>Sessions Completed</span>
                                <div class="bm-progress-bar">
                                    <div class="bm-progress-fill" style="width: <?php echo $stats['total_bookings'] > 0 ? ($stats['completed_sessions'] / $stats['total_bookings'] * 100) : 0; ?>%"></div>
                                </div>
                                <span><?php echo esc_html( $stats['completed_sessions'] ); ?> / <?php echo esc_html( $stats['total_bookings'] ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sessions Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-sessions">
                <div class="bm-section-header">
                    <h4>My Sessions</h4>
                    <div class="bm-filters">
                        <select id="bm-session-status-filter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="month" id="bm-session-month-filter" placeholder="Filter by month">
                    </div>
                </div>
                
                <div class="bm-sessions-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Mentor</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $recent_bookings as $booking ) : ?>
                            <tr>
                                <td><?php echo esc_html( $booking->service_name ); ?></td>
                                <td><?php echo esc_html( $booking->mentor_name ); ?></td>
                                <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date ) ) ); ?></td>
                                <td>
                                    <span class="bm-status <?php echo esc_attr( $booking->status ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="bm-action-buttons">
                                        <button class="bm-button bm-button-small" onclick="bmViewSession(<?php echo $booking->id; ?>)">
                                            View
                                        </button>
                                        <?php if ( $booking->status === 'confirmed' && strtotime( $booking->booking_date ) > time() ) : ?>
                                        <button class="bm-button bm-button-small bm-button-danger" onclick="bmCancelBooking(<?php echo $booking->id; ?>)">
                                            Cancel
                                        </button>
                                        <?php endif; ?>
                                        <?php if ( $booking->status === 'completed' ) : ?>
                                        <button class="bm-button bm-button-small bm-button-secondary" onclick="bmRateSession(<?php echo $booking->id; ?>)">
                                            Rate
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- History Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-history">
                <div class="bm-section-header">
                    <h4>Session History</h4>
                    <p>Review your past mentoring sessions</p>
                </div>
                
                <div id="bm-history-container">
                    <!-- History will be loaded here -->
                </div>
            </div>
            
            <!-- Favorites Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-favorites">
                <div class="bm-section-header">
                    <h4>Favorite Mentors & Services</h4>
                    <p>Quick access to your preferred mentors and services</p>
                </div>
                
                <div id="bm-favorites-container">
                    <!-- Favorites will be loaded here -->
                </div>
            </div>
            
            <!-- Profile Tab -->
            <div class="bm-dashboard-tab" id="bm-tab-profile">
                <div class="bm-section-header">
                    <h4>My Profile</h4>
                    <p>Update your profile information and preferences</p>
                </div>
                
                <div id="bm-mentee-profile-form">
                    <!-- Profile form will be loaded here -->
                </div>
            </div>
        </div>
        
        <!-- Session Details Modal -->
        <div id="bm-session-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content">
                <div class="bm-modal-header">
                    <h3>Session Details</h3>
                    <button class="bm-modal-close" onclick="bmCloseSessionModal()">
                        <i class="bm-icon-close"></i>
                    </button>
                </div>
                <div class="bm-modal-body">
                    <div id="bm-session-details">
                        <!-- Session details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rating Modal -->
        <div id="bm-rating-modal" class="bm-modal" style="display: none;">
            <div class="bm-modal-content">
                <div class="bm-modal-header">
                    <h3>Rate Your Session</h3>
                    <button class="bm-modal-close" onclick="bmCloseRatingModal()">
                        <i class="bm-icon-close"></i>
                    </button>
                </div>
                <div class="bm-modal-body">
                    <form id="bm-rating-form">
                        <input type="hidden" id="bm-rating-booking-id" name="booking_id">
                        
                        <div class="bm-form-group">
                            <label>How was your session?</label>
                            <div class="bm-rating-stars">
                                <span class="bm-star" data-rating="1">☆</span>
                                <span class="bm-star" data-rating="2">☆</span>
                                <span class="bm-star" data-rating="3">☆</span>
                                <span class="bm-star" data-rating="4">☆</span>
                                <span class="bm-star" data-rating="5">☆</span>
                            </div>
                            <input type="hidden" id="bm-rating-value" name="rating" value="5">
                        </div>
                        
                        <div class="bm-form-group">
                            <label for="bm-rating-comment">Share your feedback (optional)</label>
                            <textarea id="bm-rating-comment" name="comment" rows="4" placeholder="What did you learn? How was the mentor?"></textarea>
                        </div>
                        
                        <div class="bm-form-group">
                            <label>
                                <input type="checkbox" id="bm-rating-recommend" name="recommend" value="1" checked>
                                I would recommend this mentor to others
                            </label>
                        </div>
                    </form>
                </div>
                <div class="bm-modal-footer">
                    <button class="bm-button bm-button-secondary" onclick="bmCloseRatingModal()">Cancel</button>
                    <button class="bm-button bm-button-primary" onclick="bmSubmitRating()">Submit Rating</button>
                </div>
            </div>
        </div>
        
        <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            bmInitializeMenteeDashboard();
        });
        
        function bmInitializeMenteeDashboard() {
            // Tab navigation
            const tabs = document.querySelectorAll('.bm-nav-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    bmShowTab(tabId);
                });
            });
            
            // Rating stars
            const stars = document.querySelectorAll('.bm-star');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    bmSetRating(rating);
                });
            });
        }
        
        function bmShowTab(tabId) {
            // Remove active class from all tabs and tab contents
            document.querySelectorAll('.bm-nav-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.bm-dashboard-tab').forEach(tab => tab.classList.remove('active'));
            
            // Add active class to selected tab and content
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            document.getElementById(`bm-tab-${tabId}`).classList.add('active');
            
            // Load content based on tab
            switch(tabId) {
                case 'history':
                    bmLoadHistory();
                    break;
                case 'favorites':
                    bmLoadFavorites();
                    break;
                case 'profile':
                    bmLoadMenteeProfile();
                    break;
            }
        }
        
        function bmViewSession(bookingId) {
            // Show session details modal
            document.getElementById('bm-session-modal').style.display = 'flex';
            bmLoadSessionDetails(bookingId);
        }
        
        function bmCloseSessionModal() {
            document.getElementById('bm-session-modal').style.display = 'none';
        }
        
        function bmCancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                // AJAX call to cancel booking
                fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=bm_cancel_booking&booking_id=${bookingId}&nonce=<?php echo wp_create_nonce( 'bm_mentee_nonce' ); ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking.');
                });
            }
        }
        
        function bmRateSession(bookingId) {
            document.getElementById('bm-rating-booking-id').value = bookingId;
            document.getElementById('bm-rating-modal').style.display = 'flex';
        }
        
        function bmCloseRatingModal() {
            document.getElementById('bm-rating-modal').style.display = 'none';
        }
        
        function bmSetRating(rating) {
            document.getElementById('bm-rating-value').value = rating;
            const stars = document.querySelectorAll('.bm-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '★';
                    star.classList.add('active');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('active');
                }
            });
        }
        
        function bmSubmitRating() {
            const form = document.getElementById('bm-rating-form');
            const formData = new FormData(form);
            formData.append('action', 'bm_submit_rating');
            formData.append('nonce', '<?php echo wp_create_nonce( 'bm_mentee_nonce' ); ?>');
            
            fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your feedback!');
                    bmCloseRatingModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your rating.');
            });
        }
        
        function bmLoadSessionDetails(bookingId) {
            // Load session details via AJAX
            // Implementation will be added
        }
        
        function bmLoadHistory() {
            // Load session history
            // Implementation will be added
        }
        
        function bmLoadFavorites() {
            // Load favorites
            // Implementation will be added
        }
        
        function bmLoadMenteeProfile() {
            // Load mentee profile form
            // Implementation will be added
        }
        </script>
        
        <style>
        .bm-mentee-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .bm-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .bm-dashboard-title h3 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }
        
        .bm-dashboard-title p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .bm-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .bm-stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .bm-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #0073aa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .bm-stat-content h4 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .bm-stat-content p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .bm-dashboard-nav {
            display: flex;
            gap: 2px;
            margin-bottom: 30px;
            background: #f5f5f5;
            padding: 4px;
            border-radius: 8px;
        }
        
        .bm-nav-tab {
            background: transparent;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #666;
        }
        
        .bm-nav-tab:hover {
            background: #e0e0e0;
        }
        
        .bm-nav-tab.active {
            background: #0073aa;
            color: white;
        }
        
        .bm-dashboard-tab {
            display: none;
        }
        
        .bm-dashboard-tab.active {
            display: block;
        }
        
        .bm-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .bm-dashboard-section {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .bm-dashboard-section h4 {
            margin: 0 0 20px;
            color: #333;
        }
        
        .bm-upcoming-sessions {
            display: grid;
            gap: 15px;
        }
        
        .bm-session-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bm-session-info h5 {
            margin: 0 0 8px;
            color: #333;
        }
        
        .bm-session-mentor {
            margin: 0 0 8px;
            color: #666;
            font-size: 14px;
        }
        
        .bm-session-date,
        .bm-session-time {
            margin: 0 0 4px;
            color: #666;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .bm-session-actions {
            display: flex;
            gap: 8px;
        }
        
        .bm-empty-state-small {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .bm-quick-actions {
            display: grid;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .bm-action-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        
        .bm-action-item:hover {
            background: #e9ecef;
            border-color: #0073aa;
        }
        
        .bm-progress-section h5 {
            margin: 0 0 15px;
            color: #333;
        }
        
        .bm-progress-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .bm-progress-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bm-progress-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease;
        }
        
        .bm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .bm-filters {
            display: flex;
            gap: 10px;
        }
        
        .bm-sessions-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bm-sessions-table th,
        .bm-sessions-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .bm-sessions-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .bm-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .bm-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .bm-status.confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .bm-status.completed {
            background: #cce5ff;
            color: #004085;
        }
        
        .bm-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .bm-action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .bm-rating-stars {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .bm-star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .bm-star:hover,
        .bm-star.active {
            color: #ffc107;
        }
        
        .bm-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .bm-button:hover {
            background: #005a87;
        }
        
        .bm-button-secondary {
            background: #6c757d;
        }
        
        .bm-button-secondary:hover {
            background: #5a6268;
        }
        
        .bm-button-success {
            background: #28a745;
        }
        
        .bm-button-success:hover {
            background: #218838;
        }
        
        .bm-button-danger {
            background: #dc3545;
        }
        
        .bm-button-danger:hover {
            background: #c82333;
        }
        
        .bm-button-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .bm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .bm-modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .bm-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .bm-modal-header h3 {
            margin: 0;
        }
        
        .bm-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }
        
        .bm-modal-body {
            padding: 20px;
        }
        
        .bm-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .bm-form-group {
            margin-bottom: 20px;
        }
        
        .bm-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .bm-form-group input,
        .bm-form-group select,
        .bm-form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .bm-form-group textarea {
            resize: vertical;
        }
        
        @media (max-width: 768px) {
            .bm-dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .bm-dashboard-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .bm-dashboard-nav {
                flex-wrap: wrap;
            }
            
            .bm-nav-tab {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .bm-session-card {
                flex-direction: column;
                gap: 15px;
            }
            
            .bm-sessions-table {
                overflow-x: auto;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Get booked slots for a service on a specific date
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @param    string   $date          Date (Y-m-d).
     * @return   array                   Booked time slots.
     */
    private function get_booked_slots( $service_id, $date ) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $booked_slots = $wpdb->get_col( $wpdb->prepare(
            "SELECT TIME(booking_date) as time_slot
             FROM $bookings_table
             WHERE service_id = %d
             AND DATE(booking_date) = %s
             AND status IN ('confirmed', 'pending')
             ORDER BY booking_date",
            $service_id,
            $date
        ) );
        
        return $booked_slots;
    }

    /**
     * Process Stripe payment
     *
     * @since    1.0.0
     * @param    int      $booking_id      Booking ID.
     * @param    array    $booking_total   Booking total breakdown.
     */
    private function process_stripe_payment( $booking_id, $booking_total ) {
        // Implementation for Stripe payment processing
        // This will be handled by the payment gateways class
    }

    /**
     * Process PayPal payment
     *
     * @since    1.0.0
     * @param    int      $booking_id      Booking ID.
     * @param    array    $booking_total   Booking total breakdown.
     */
    private function process_paypal_payment( $booking_id, $booking_total ) {
        // Implementation for PayPal payment processing
        // This will be handled by the payment gateways class
    }
}