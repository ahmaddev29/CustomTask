<?php

/**
 * Stripe Marketplace Integration
 *
 * @since      1.0.0
 */

/**
 * Stripe Marketplace Integration class.
 *
 * This class handles all Stripe marketplace functionality including:
 * - Mentor account onboarding
 * - Automatic payouts
 * - Transfer management
 * - Webhook handling
 *
 * @since      1.0.0
 */
class Booking_Master_Stripe_Marketplace {

    /**
     * Stripe API instance
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $stripe    Stripe API instance.
     */
    private $stripe;

    /**
     * Plugin settings
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Plugin settings.
     */
    private $settings;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->settings = get_option( 'booking_master_settings', array() );
        $this->init_stripe();
    }

    /**
     * Initialize Stripe API
     *
     * @since    1.0.0
     */
    private function init_stripe() {
        if ( empty( $this->settings['stripe_secret_key'] ) ) {
            return;
        }

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

        \Stripe\Stripe::setApiKey( $this->settings['stripe_secret_key'] );
        $this->stripe = new \Stripe\StripeClient( $this->settings['stripe_secret_key'] );
    }

    /**
     * Initialize hooks
     *
     * @since    1.0.0
     */
    public function init() {
        // AJAX handlers
        add_action( 'wp_ajax_bm_create_connect_account', array( $this, 'ajax_create_connect_account' ) );
        add_action( 'wp_ajax_bm_get_connect_account_status', array( $this, 'ajax_get_connect_account_status' ) );
        add_action( 'wp_ajax_bm_create_onboarding_link', array( $this, 'ajax_create_onboarding_link' ) );
        add_action( 'wp_ajax_bm_create_dashboard_link', array( $this, 'ajax_create_dashboard_link' ) );
        
        // Webhook handler
        add_action( 'wp_ajax_nopriv_bm_stripe_webhook', array( $this, 'handle_webhook' ) );
        add_action( 'wp_ajax_bm_stripe_webhook', array( $this, 'handle_webhook' ) );
        
        // REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Cron jobs
        add_action( 'bm_process_scheduled_payouts', array( $this, 'process_scheduled_payouts' ) );
        
        // Schedule cron events
        if ( ! wp_next_scheduled( 'bm_process_scheduled_payouts' ) ) {
            wp_schedule_event( time(), 'daily', 'bm_process_scheduled_payouts' );
        }
    }

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'booking-master/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Create Stripe Connect account for mentor (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_connect_account() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        if ( ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $mentor_id = get_current_user_id();
        
        // Check if mentor already has a connected account
        $existing_account = get_user_meta( $mentor_id, 'stripe_connect_account_id', true );
        if ( $existing_account ) {
            wp_send_json_error( array( 'message' => 'Account already connected' ) );
        }

        $account = $this->create_connect_account( $mentor_id );
        
        if ( $account ) {
            wp_send_json_success( array( 
                'account_id' => $account->id,
                'message' => 'Connect account created successfully'
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create connect account' ) );
        }
    }

    /**
     * Get connect account status (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_connect_account_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        if ( ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $mentor_id = get_current_user_id();
        $status = $this->get_connect_account_status( $mentor_id );
        
        wp_send_json_success( $status );
    }

    /**
     * Create onboarding link (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_onboarding_link() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        if ( ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $mentor_id = get_current_user_id();
        $account_id = get_user_meta( $mentor_id, 'stripe_connect_account_id', true );
        
        if ( ! $account_id ) {
            wp_send_json_error( array( 'message' => 'No connect account found' ) );
        }

        $link = $this->create_onboarding_link( $account_id );
        
        if ( $link ) {
            wp_send_json_success( array( 'url' => $link->url ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create onboarding link' ) );
        }
    }

    /**
     * Create dashboard link (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_dashboard_link() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        if ( ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $mentor_id = get_current_user_id();
        $account_id = get_user_meta( $mentor_id, 'stripe_connect_account_id', true );
        
        if ( ! $account_id ) {
            wp_send_json_error( array( 'message' => 'No connect account found' ) );
        }

        $link = $this->create_dashboard_link( $account_id );
        
        if ( $link ) {
            wp_send_json_success( array( 'url' => $link->url ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create dashboard link' ) );
        }
    }

    /**
     * Create Stripe Connect account for mentor
     *
     * @since    1.0.0
     * @param    int    $mentor_id    Mentor user ID.
     * @return   object|false         Stripe account object or false on failure.
     */
    public function create_connect_account( $mentor_id ) {
        if ( ! $this->stripe ) {
            return false;
        }

        try {
            $user = get_user_by( 'ID', $mentor_id );
            if ( ! $user ) {
                return false;
            }

            $account = $this->stripe->accounts->create( array(
                'type' => 'standard',
                'email' => $user->user_email,
                'capabilities' => array(
                    'card_payments' => array( 'requested' => true ),
                    'transfers' => array( 'requested' => true ),
                ),
                'business_type' => 'individual',
                'metadata' => array(
                    'mentor_id' => $mentor_id,
                    'platform' => 'booking-master',
                ),
            ) );

            // Store account ID in user meta
            update_user_meta( $mentor_id, 'stripe_connect_account_id', $account->id );
            update_user_meta( $mentor_id, 'stripe_connect_status', 'created' );
            update_user_meta( $mentor_id, 'stripe_connect_created_at', current_time( 'mysql' ) );

            return $account;

        } catch ( Exception $e ) {
            error_log( 'Stripe Connect Account Creation Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Get connect account status
     *
     * @since    1.0.0
     * @param    int    $mentor_id    Mentor user ID.
     * @return   array                Account status information.
     */
    public function get_connect_account_status( $mentor_id ) {
        $account_id = get_user_meta( $mentor_id, 'stripe_connect_account_id', true );
        
        if ( ! $account_id ) {
            return array(
                'connected' => false,
                'status' => 'not_connected',
                'message' => 'No Stripe account connected',
            );
        }

        if ( ! $this->stripe ) {
            return array(
                'connected' => false,
                'status' => 'error',
                'message' => 'Stripe not configured',
            );
        }

        try {
            $account = $this->stripe->accounts->retrieve( $account_id );
            
            $charges_enabled = $account->charges_enabled;
            $details_submitted = $account->details_submitted;
            $payouts_enabled = $account->payouts_enabled;
            
            $status = 'incomplete';
            $message = 'Account setup incomplete';
            
            if ( $charges_enabled && $details_submitted && $payouts_enabled ) {
                $status = 'complete';
                $message = 'Account fully set up and ready to receive payments';
            } elseif ( $details_submitted ) {
                $status = 'pending';
                $message = 'Account under review';
            } elseif ( $account->requirements->currently_due ) {
                $status = 'requirements_due';
                $message = 'Additional information required';
            }

            return array(
                'connected' => true,
                'status' => $status,
                'message' => $message,
                'account_id' => $account_id,
                'charges_enabled' => $charges_enabled,
                'details_submitted' => $details_submitted,
                'payouts_enabled' => $payouts_enabled,
                'requirements' => $account->requirements,
            );

        } catch ( Exception $e ) {
            error_log( 'Stripe Account Status Error: ' . $e->getMessage() );
            return array(
                'connected' => false,
                'status' => 'error',
                'message' => 'Error retrieving account status',
            );
        }
    }

    /**
     * Create onboarding link
     *
     * @since    1.0.0
     * @param    string    $account_id    Stripe account ID.
     * @return   object|false             Onboarding link object or false on failure.
     */
    public function create_onboarding_link( $account_id ) {
        if ( ! $this->stripe ) {
            return false;
        }

        try {
            $link = $this->stripe->accountLinks->create( array(
                'account' => $account_id,
                'refresh_url' => admin_url( 'admin.php?page=booking-master-mentor&tab=payments&action=refresh' ),
                'return_url' => admin_url( 'admin.php?page=booking-master-mentor&tab=payments&action=return' ),
                'type' => 'account_onboarding',
            ) );

            return $link;

        } catch ( Exception $e ) {
            error_log( 'Stripe Onboarding Link Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create dashboard link
     *
     * @since    1.0.0
     * @param    string    $account_id    Stripe account ID.
     * @return   object|false             Dashboard link object or false on failure.
     */
    public function create_dashboard_link( $account_id ) {
        if ( ! $this->stripe ) {
            return false;
        }

        try {
            $link = $this->stripe->accounts->createLoginLink( $account_id );
            return $link;

        } catch ( Exception $e ) {
            error_log( 'Stripe Dashboard Link Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Process payment with marketplace functionality
     *
     * @since    1.0.0
     * @param    int      $booking_id     Booking ID.
     * @param    array    $payment_data   Payment data.
     * @return   array                    Payment result.
     */
    public function process_marketplace_payment( $booking_id, $payment_data ) {
        if ( ! $this->stripe ) {
            return array(
                'success' => false,
                'message' => 'Stripe not configured',
            );
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Get booking details
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ) );

        if ( ! $booking ) {
            return array(
                'success' => false,
                'message' => 'Booking not found',
            );
        }

        // Get mentor's connect account
        $mentor_account_id = get_user_meta( $booking->mentor_id, 'stripe_connect_account_id', true );
        
        // Calculate fees
        $total_amount = $booking->total_amount * 100; // Convert to cents
        $application_fee = $this->calculate_application_fee( $booking->total_amount );
        
        try {
            // Create payment intent with marketplace setup
            $intent_data = array(
                'amount' => $total_amount,
                'currency' => 'usd',
                'payment_method' => $payment_data['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => array(
                    'booking_id' => $booking_id,
                    'mentor_id' => $booking->mentor_id,
                    'platform' => 'booking-master',
                ),
            );

            // Add marketplace data if mentor has connected account
            if ( $mentor_account_id && $this->settings['marketplace_enabled'] ) {
                $intent_data['application_fee_amount'] = $application_fee * 100;
                $intent_data['transfer_data'] = array(
                    'destination' => $mentor_account_id,
                );
            }

            $intent = $this->stripe->paymentIntents->create( $intent_data );

            if ( $intent->status === 'succeeded' ) {
                // Update booking status
                $wpdb->update(
                    $bookings_table,
                    array(
                        'payment_status' => 'paid',
                        'payment_intent_id' => $intent->id,
                        'stripe_transfer_id' => $intent->transfer_data->destination ?? '',
                    ),
                    array( 'id' => $booking_id )
                );

                // Log payment transaction
                $this->log_payment_transaction( $booking_id, $intent );

                return array(
                    'success' => true,
                    'payment_intent' => $intent,
                    'message' => 'Payment processed successfully',
                );

            } elseif ( $intent->status === 'requires_action' ) {
                return array(
                    'success' => false,
                    'requires_action' => true,
                    'payment_intent' => $intent,
                    'message' => 'Payment requires additional authentication',
                );

            } else {
                return array(
                    'success' => false,
                    'message' => 'Payment failed',
                );
            }

        } catch ( Exception $e ) {
            error_log( 'Stripe Marketplace Payment Error: ' . $e->getMessage() );
            return array(
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Calculate application fee
     *
     * @since    1.0.0
     * @param    float    $amount    Booking amount.
     * @return   float               Application fee amount.
     */
    private function calculate_application_fee( $amount ) {
        $fee_rate = floatval( $this->settings['marketplace_application_fee'] ?? 10 ) / 100;
        return round( $amount * $fee_rate, 2 );
    }

    /**
     * Process scheduled payouts
     *
     * @since    1.0.0
     */
    public function process_scheduled_payouts() {
        if ( ! $this->settings['marketplace_enabled'] ) {
            return;
        }

        $schedule = $this->settings['marketplace_payout_schedule'] ?? 'immediate';
        
        if ( $schedule === 'immediate' ) {
            return; // Immediate payouts are handled during payment processing
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Get completed bookings that need payout
        $where_clause = "status = 'completed' AND payment_status = 'paid' AND payout_status != 'paid'";
        
        if ( $schedule === 'weekly' ) {
            $where_clause .= " AND DATE(updated_at) <= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ( $schedule === 'monthly' ) {
            $where_clause .= " AND DATE(updated_at) <= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }

        $bookings = $wpdb->get_results( "SELECT * FROM $bookings_table WHERE $where_clause" );

        foreach ( $bookings as $booking ) {
            $this->process_payout( $booking );
        }
    }

    /**
     * Process payout for a booking
     *
     * @since    1.0.0
     * @param    object    $booking    Booking object.
     */
    private function process_payout( $booking ) {
        if ( ! $this->stripe ) {
            return;
        }

        $mentor_account_id = get_user_meta( $booking->mentor_id, 'stripe_connect_account_id', true );
        
        if ( ! $mentor_account_id ) {
            return;
        }

        try {
            // Calculate payout amount (total - application fee)
            $total_amount = $booking->total_amount;
            $application_fee = $this->calculate_application_fee( $total_amount );
            $payout_amount = $total_amount - $application_fee;

            // Create transfer
            $transfer = $this->stripe->transfers->create( array(
                'amount' => $payout_amount * 100, // Convert to cents
                'currency' => 'usd',
                'destination' => $mentor_account_id,
                'metadata' => array(
                    'booking_id' => $booking->id,
                    'mentor_id' => $booking->mentor_id,
                    'type' => 'scheduled_payout',
                ),
            ) );

            // Update booking payout status
            global $wpdb;
            $bookings_table = $wpdb->prefix . 'bm_bookings';
            
            $wpdb->update(
                $bookings_table,
                array(
                    'payout_status' => 'paid',
                    'payout_transfer_id' => $transfer->id,
                    'payout_amount' => $payout_amount,
                ),
                array( 'id' => $booking->id )
            );

            // Log payout transaction
            $this->log_payout_transaction( $booking->id, $transfer );

        } catch ( Exception $e ) {
            error_log( 'Stripe Payout Error: ' . $e->getMessage() );
        }
    }

    /**
     * Handle Stripe webhooks
     *
     * @since    1.0.0
     */
    public function handle_webhook() {
        $payload = @file_get_contents( 'php://input' );
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $this->settings['stripe_webhook_secret'];

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );

            // Handle different event types
            switch ( $event->type ) {
                case 'payment_intent.succeeded':
                    $this->handle_payment_succeeded( $event->data->object );
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handle_payment_failed( $event->data->object );
                    break;
                    
                case 'account.updated':
                    $this->handle_account_updated( $event->data->object );
                    break;
                    
                case 'transfer.created':
                    $this->handle_transfer_created( $event->data->object );
                    break;
                    
                default:
                    // Unhandled event type
                    break;
            }

            http_response_code( 200 );
            echo 'OK';

        } catch ( Exception $e ) {
            error_log( 'Stripe Webhook Error: ' . $e->getMessage() );
            http_response_code( 400 );
            echo 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Handle payment succeeded webhook
     *
     * @since    1.0.0
     * @param    object    $payment_intent    Stripe PaymentIntent object.
     */
    private function handle_payment_succeeded( $payment_intent ) {
        $booking_id = $payment_intent->metadata->booking_id ?? null;
        
        if ( ! $booking_id ) {
            return;
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Update booking status
        $wpdb->update(
            $bookings_table,
            array(
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ),
            array( 'id' => $booking_id )
        );

        // Send confirmation emails
        $this->send_booking_confirmation_emails( $booking_id );
    }

    /**
     * Handle payment failed webhook
     *
     * @since    1.0.0
     * @param    object    $payment_intent    Stripe PaymentIntent object.
     */
    private function handle_payment_failed( $payment_intent ) {
        $booking_id = $payment_intent->metadata->booking_id ?? null;
        
        if ( ! $booking_id ) {
            return;
        }

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        // Update booking status
        $wpdb->update(
            $bookings_table,
            array(
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ),
            array( 'id' => $booking_id )
        );

        // Send failure notification
        $this->send_payment_failure_notification( $booking_id );
    }

    /**
     * Handle account updated webhook
     *
     * @since    1.0.0
     * @param    object    $account    Stripe Account object.
     */
    private function handle_account_updated( $account ) {
        $mentor_id = $account->metadata->mentor_id ?? null;
        
        if ( ! $mentor_id ) {
            return;
        }

        // Update mentor's connect status
        if ( $account->charges_enabled && $account->details_submitted && $account->payouts_enabled ) {
            update_user_meta( $mentor_id, 'stripe_connect_status', 'complete' );
        } else {
            update_user_meta( $mentor_id, 'stripe_connect_status', 'incomplete' );
        }
    }

    /**
     * Handle transfer created webhook
     *
     * @since    1.0.0
     * @param    object    $transfer    Stripe Transfer object.
     */
    private function handle_transfer_created( $transfer ) {
        $booking_id = $transfer->metadata->booking_id ?? null;
        
        if ( ! $booking_id ) {
            return;
        }

        // Log transfer
        $this->log_transfer_transaction( $booking_id, $transfer );
    }

    /**
     * Log payment transaction
     *
     * @since    1.0.0
     * @param    int       $booking_id    Booking ID.
     * @param    object    $intent        Stripe PaymentIntent object.
     */
    private function log_payment_transaction( $booking_id, $intent ) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'bm_payment_transactions';
        
        $wpdb->insert(
            $transactions_table,
            array(
                'booking_id' => $booking_id,
                'transaction_type' => 'payment',
                'stripe_transaction_id' => $intent->id,
                'amount' => $intent->amount / 100,
                'currency' => $intent->currency,
                'status' => $intent->status,
                'metadata' => json_encode( $intent->metadata ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Log payout transaction
     *
     * @since    1.0.0
     * @param    int       $booking_id    Booking ID.
     * @param    object    $transfer      Stripe Transfer object.
     */
    private function log_payout_transaction( $booking_id, $transfer ) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'bm_payment_transactions';
        
        $wpdb->insert(
            $transactions_table,
            array(
                'booking_id' => $booking_id,
                'transaction_type' => 'payout',
                'stripe_transaction_id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'status' => 'succeeded',
                'metadata' => json_encode( $transfer->metadata ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Log transfer transaction
     *
     * @since    1.0.0
     * @param    int       $booking_id    Booking ID.
     * @param    object    $transfer      Stripe Transfer object.
     */
    private function log_transfer_transaction( $booking_id, $transfer ) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'bm_payment_transactions';
        
        $wpdb->insert(
            $transactions_table,
            array(
                'booking_id' => $booking_id,
                'transaction_type' => 'transfer',
                'stripe_transaction_id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'status' => 'succeeded',
                'metadata' => json_encode( $transfer->metadata ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Send booking confirmation emails
     *
     * @since    1.0.0
     * @param    int    $booking_id    Booking ID.
     */
    private function send_booking_confirmation_emails( $booking_id ) {
        // Implementation would integrate with email notifications class
        if ( class_exists( 'Booking_Master_Email_Notifications' ) ) {
            $email_notifications = new Booking_Master_Email_Notifications();
            $email_notifications->send_booking_confirmation( $booking_id );
        }
    }

    /**
     * Send payment failure notification
     *
     * @since    1.0.0
     * @param    int    $booking_id    Booking ID.
     */
    private function send_payment_failure_notification( $booking_id ) {
        // Implementation would integrate with email notifications class
        if ( class_exists( 'Booking_Master_Email_Notifications' ) ) {
            $email_notifications = new Booking_Master_Email_Notifications();
            $email_notifications->send_payment_failure_notification( $booking_id );
        }
    }

    /**
     * Get mentor earnings summary
     *
     * @since    1.0.0
     * @param    int    $mentor_id    Mentor ID.
     * @return   array               Earnings summary.
     */
    public function get_mentor_earnings_summary( $mentor_id ) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        $transactions_table = $wpdb->prefix . 'bm_payment_transactions';
        
        // Get total earnings
        $total_earnings = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_amount) FROM $bookings_table 
             WHERE mentor_id = %d AND payment_status = 'paid'",
            $mentor_id
        ) );

        // Get application fees
        $total_fees = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_amount * %f) FROM $bookings_table 
             WHERE mentor_id = %d AND payment_status = 'paid'",
            $mentor_id,
            floatval( $this->settings['marketplace_application_fee'] ?? 10 ) / 100
        ) );

        // Get paid payouts
        $paid_payouts = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(payout_amount) FROM $bookings_table 
             WHERE mentor_id = %d AND payout_status = 'paid'",
            $mentor_id
        ) );

        // Get pending payouts
        $pending_payouts = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_amount - (total_amount * %f)) FROM $bookings_table 
             WHERE mentor_id = %d AND payment_status = 'paid' AND payout_status != 'paid'",
            $mentor_id,
            floatval( $this->settings['marketplace_application_fee'] ?? 10 ) / 100
        ) );

        return array(
            'total_earnings' => floatval( $total_earnings ) ?: 0,
            'platform_fees' => floatval( $total_fees ) ?: 0,
            'net_earnings' => floatval( $total_earnings - $total_fees ) ?: 0,
            'paid_payouts' => floatval( $paid_payouts ) ?: 0,
            'pending_payouts' => floatval( $pending_payouts ) ?: 0,
        );
    }

    /**
     * Get mentor payout history
     *
     * @since    1.0.0
     * @param    int    $mentor_id    Mentor ID.
     * @param    int    $limit        Number of records to return.
     * @return   array               Payout history.
     */
    public function get_mentor_payout_history( $mentor_id, $limit = 10 ) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'bm_payment_transactions';
        $bookings_table = $wpdb->prefix . 'bm_bookings';
        
        $payouts = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, b.service_id, b.booking_date
             FROM $transactions_table t
             LEFT JOIN $bookings_table b ON t.booking_id = b.id
             WHERE t.transaction_type IN ('payout', 'transfer') 
             AND b.mentor_id = %d
             ORDER BY t.created_at DESC
             LIMIT %d",
            $mentor_id,
            $limit
        ) );

        return $payouts;
    }
}