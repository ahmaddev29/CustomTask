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
        // Implementation for mentor dashboard
        return '<div class="bm-mentor-dashboard"><p>Welcome to your mentor dashboard! <a href="' . admin_url( 'admin.php?page=booking-master-mentor' ) . '">Visit full dashboard</a></p></div>';
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
        );
        
        // Get recent bookings
        $recent_bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.service_name, u.display_name as mentor_name 
             FROM $bookings_table b 
             LEFT JOIN $services_table s ON b.service_id = s.id 
             LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID 
             WHERE b.mentee_id = %d 
             ORDER BY b.booking_date DESC 
             LIMIT 5",
            $mentee_id
        ) );
        
        ob_start();
        ?>
        <div class="bm-mentee-dashboard">
            <div class="bm-dashboard-stats">
                <div class="bm-stat-item">
                    <h4><?php echo esc_html( $stats['total_bookings'] ); ?></h4>
                    <p>Total Sessions</p>
                </div>
                <div class="bm-stat-item">
                    <h4><?php echo esc_html( $stats['upcoming_bookings'] ); ?></h4>
                    <p>Upcoming</p>
                </div>
                <div class="bm-stat-item">
                    <h4><?php echo esc_html( $stats['completed_sessions'] ); ?></h4>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="bm-dashboard-actions">
                <a href="<?php echo admin_url( 'admin.php?page=booking-master-mentee' ); ?>" class="bm-button bm-button-primary">
                    Visit Full Dashboard
                </a>
                <a href="<?php echo home_url( '/services' ); ?>" class="bm-button bm-button-secondary">
                    Browse Services
                </a>
            </div>
            
            <?php if ( $recent_bookings ) : ?>
            <div class="bm-recent-bookings">
                <h4>Recent Bookings</h4>
                <div class="bm-bookings-list">
                    <?php foreach ( $recent_bookings as $booking ) : ?>
                    <div class="bm-booking-item">
                        <div class="bm-booking-info">
                            <h5><?php echo esc_html( $booking->service_name ); ?></h5>
                            <p>with <?php echo esc_html( $booking->mentor_name ); ?></p>
                            <p><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date ) ) ); ?></p>
                        </div>
                        <div class="bm-booking-status">
                            <span class="bm-status <?php echo esc_attr( $booking->status ); ?>">
                                <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else : ?>
            <div class="bm-no-bookings">
                <p>You haven't booked any sessions yet. <a href="<?php echo home_url( '/services' ); ?>">Browse available services</a> to get started!</p>
            </div>
            <?php endif; ?>
        </div>
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