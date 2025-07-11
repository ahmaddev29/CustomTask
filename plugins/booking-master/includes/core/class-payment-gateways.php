<?php

/**
 * Payment Gateways Integration
 *
 * @since      1.0.0
 */

/**
 * Payment Gateways management.
 *
 * This class defines all payment gateway related functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Payment_Gateways {

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
        add_action( 'wp_ajax_bm_process_stripe_payment', array( $this, 'ajax_process_stripe_payment' ) );
        add_action( 'wp_ajax_bm_process_paypal_payment', array( $this, 'ajax_process_paypal_payment' ) );
        add_action( 'wp_ajax_bm_create_payment_intent', array( $this, 'ajax_create_payment_intent' ) );
        add_action( 'wp_ajax_nopriv_bm_process_stripe_payment', array( $this, 'ajax_process_stripe_payment' ) );
        add_action( 'wp_ajax_nopriv_bm_process_paypal_payment', array( $this, 'ajax_process_paypal_payment' ) );
        add_action( 'wp_ajax_nopriv_bm_create_payment_intent', array( $this, 'ajax_create_payment_intent' ) );
        
        // PayPal webhook handler
        add_action( 'init', array( $this, 'handle_paypal_webhook' ) );
    }

    /**
     * Create Stripe Payment Intent (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_payment_intent() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_payment_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $booking_date = sanitize_text_field( $_POST['booking_date'] );

        // Get service details
        $service = $this->db->get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => 'Service not found.' ) );
        }

        $settings = get_option( 'booking_master_settings', array() );
        $stripe_secret_key = isset( $settings['stripe_secret_key'] ) ? $settings['stripe_secret_key'] : '';

        if ( empty( $stripe_secret_key ) ) {
            wp_send_json_error( array( 'message' => 'Stripe not configured.' ) );
        }

        try {
            \Stripe\Stripe::setApiKey( $stripe_secret_key );

            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $service->price * 100, // Convert to cents
                'currency' => strtolower( $settings['currency'] ?? 'usd' ),
                'description' => 'Booking for ' . $service->service_name,
                'metadata' => [
                    'service_id' => $service_id,
                    'booking_date' => $booking_date,
                    'mentee_id' => get_current_user_id(),
                    'mentor_id' => $service->mentor_id,
                ],
            ]);

            wp_send_json_success( array(
                'client_secret' => $payment_intent->client_secret,
                'payment_intent_id' => $payment_intent->id
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Payment initialization failed: ' . $e->getMessage() ) );
        }
    }

    /**
     * Process Stripe payment (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_process_stripe_payment() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_payment_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $payment_intent_id = sanitize_text_field( $_POST['payment_intent_id'] );
        $service_id = intval( $_POST['service_id'] );
        $booking_date = sanitize_text_field( $_POST['booking_date'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        $settings = get_option( 'booking_master_settings', array() );
        $stripe_secret_key = isset( $settings['stripe_secret_key'] ) ? $settings['stripe_secret_key'] : '';

        try {
            \Stripe\Stripe::setApiKey( $stripe_secret_key );
            
            // Retrieve payment intent to confirm payment
            $payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
            
            if ( $payment_intent->status === 'succeeded' ) {
                // Create booking with payment confirmed
                $booking_data = array(
                    'service_id' => $service_id,
                    'mentee_id' => get_current_user_id(),
                    'mentor_id' => $payment_intent->metadata->mentor_id,
                    'booking_date' => $booking_date,
                    'total_amount' => $payment_intent->amount / 100,
                    'notes' => $notes,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_method' => 'stripe',
                    'payment_intent_id' => $payment_intent_id,
                );

                $booking_id = $this->db->create_booking( $booking_data );

                if ( $booking_id ) {
                    // Send confirmation emails
                    $bookings = new Booking_Master_Bookings();
                    $bookings->send_booking_confirmation_email( $booking_id );
                    
                    wp_send_json_success( array( 
                        'message' => 'Payment successful! Your booking is confirmed.',
                        'booking_id' => $booking_id 
                    ) );
                } else {
                    wp_send_json_error( array( 'message' => 'Payment processed but booking creation failed.' ) );
                }
            } else {
                wp_send_json_error( array( 'message' => 'Payment not completed.' ) );
            }

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Payment processing failed: ' . $e->getMessage() ) );
        }
    }

    /**
     * Process PayPal payment (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_process_paypal_payment() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_payment_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $order_id = sanitize_text_field( $_POST['order_id'] );
        $service_id = intval( $_POST['service_id'] );
        $booking_date = sanitize_text_field( $_POST['booking_date'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        // Verify PayPal payment
        $payment_verified = $this->verify_paypal_payment( $order_id );

        if ( $payment_verified ) {
            $service = $this->db->get_service( $service_id );
            
            // Create booking with payment confirmed
            $booking_data = array(
                'service_id' => $service_id,
                'mentee_id' => get_current_user_id(),
                'mentor_id' => $service->mentor_id,
                'booking_date' => $booking_date,
                'total_amount' => $service->price,
                'notes' => $notes,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'paypal',
                'paypal_order_id' => $order_id,
            );

            $booking_id = $this->db->create_booking( $booking_data );

            if ( $booking_id ) {
                // Send confirmation emails
                $bookings = new Booking_Master_Bookings();
                $bookings->send_booking_confirmation_email( $booking_id );
                
                wp_send_json_success( array( 
                    'message' => 'Payment successful! Your booking is confirmed.',
                    'booking_id' => $booking_id 
                ) );
            } else {
                wp_send_json_error( array( 'message' => 'Payment processed but booking creation failed.' ) );
            }
        } else {
            wp_send_json_error( array( 'message' => 'Payment verification failed.' ) );
        }
    }

    /**
     * Verify PayPal payment
     *
     * @since    1.0.0
     * @param    string   $order_id    PayPal order ID.
     * @return   bool                  True if payment verified, false otherwise.
     */
    private function verify_paypal_payment( $order_id ) {
        $settings = get_option( 'booking_master_settings', array() );
        $paypal_client_id = isset( $settings['paypal_client_id'] ) ? $settings['paypal_client_id'] : '';
        $paypal_client_secret = isset( $settings['paypal_client_secret'] ) ? $settings['paypal_client_secret'] : '';
        $paypal_sandbox = isset( $settings['paypal_sandbox'] ) ? $settings['paypal_sandbox'] : true;

        if ( empty( $paypal_client_id ) || empty( $paypal_client_secret ) ) {
            return false;
        }

        $base_url = $paypal_sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';

        // Get access token
        $auth = base64_encode( $paypal_client_id . ':' . $paypal_client_secret );
        
        $response = wp_remote_post( $base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $access_token = $body['access_token'];

        // Verify order
        $order_response = wp_remote_get( $base_url . '/v2/checkout/orders/' . $order_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
        ) );

        if ( is_wp_error( $order_response ) ) {
            return false;
        }

        $order_data = json_decode( wp_remote_retrieve_body( $order_response ), true );
        
        return isset( $order_data['status'] ) && $order_data['status'] === 'COMPLETED';
    }

    /**
     * Handle PayPal webhook
     *
     * @since    1.0.0
     */
    public function handle_paypal_webhook() {
        if ( isset( $_GET['bm_paypal_webhook'] ) ) {
            $input = file_get_contents( 'php://input' );
            $event = json_decode( $input, true );

            if ( $event && isset( $event['event_type'] ) ) {
                switch ( $event['event_type'] ) {
                    case 'CHECKOUT.ORDER.APPROVED':
                        $this->handle_paypal_order_approved( $event );
                        break;
                    case 'PAYMENT.CAPTURE.COMPLETED':
                        $this->handle_paypal_payment_completed( $event );
                        break;
                    case 'PAYMENT.CAPTURE.DENIED':
                        $this->handle_paypal_payment_denied( $event );
                        break;
                }
            }

            http_response_code( 200 );
            exit;
        }
    }

    /**
     * Handle PayPal order approved webhook
     *
     * @since    1.0.0
     * @param    array    $event    Webhook event data.
     */
    private function handle_paypal_order_approved( $event ) {
        // Log the event for debugging
        error_log( 'PayPal Order Approved: ' . json_encode( $event ) );
    }

    /**
     * Handle PayPal payment completed webhook
     *
     * @since    1.0.0
     * @param    array    $event    Webhook event data.
     */
    private function handle_paypal_payment_completed( $event ) {
        // Update booking status if needed
        error_log( 'PayPal Payment Completed: ' . json_encode( $event ) );
    }

    /**
     * Handle PayPal payment denied webhook
     *
     * @since    1.0.0
     * @param    array    $event    Webhook event data.
     */
    private function handle_paypal_payment_denied( $event ) {
        // Handle payment denial
        error_log( 'PayPal Payment Denied: ' . json_encode( $event ) );
    }

    /**
     * Get supported payment methods
     *
     * @since    1.0.0
     * @return   array    Array of supported payment methods.
     */
    public function get_supported_payment_methods() {
        $settings = get_option( 'booking_master_settings', array() );
        $methods = array();

        if ( ! empty( $settings['stripe_publishable_key'] ) && ! empty( $settings['stripe_secret_key'] ) ) {
            $methods['stripe'] = array(
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your credit or debit card via Stripe',
                'icon' => 'stripe',
            );
        }

        if ( ! empty( $settings['paypal_client_id'] ) && ! empty( $settings['paypal_client_secret'] ) ) {
            $methods['paypal'] = array(
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'icon' => 'paypal',
            );
        }

        return $methods;
    }

    /**
     * Process refund
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @param    float    $amount        Refund amount.
     * @return   bool                    True on success, false on failure.
     */
    public function process_refund( $booking_id, $amount = null ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking || $booking->payment_status !== 'paid' ) {
            return false;
        }

        if ( $amount === null ) {
            $amount = $booking->total_amount;
        }

        $success = false;

        if ( $booking->payment_method === 'stripe' && ! empty( $booking->payment_intent_id ) ) {
            $success = $this->process_stripe_refund( $booking->payment_intent_id, $amount );
        } elseif ( $booking->payment_method === 'paypal' && ! empty( $booking->paypal_order_id ) ) {
            $success = $this->process_paypal_refund( $booking->paypal_order_id, $amount );
        }

        if ( $success ) {
            // Update booking status
            $this->db->update_booking( $booking_id, array(
                'payment_status' => 'refunded',
                'refund_amount' => $amount,
                'refund_date' => current_time( 'mysql' ),
            ) );
        }

        return $success;
    }

    /**
     * Process Stripe refund
     *
     * @since    1.0.0
     * @param    string   $payment_intent_id    Stripe Payment Intent ID.
     * @param    float    $amount               Refund amount.
     * @return   bool                           True on success, false on failure.
     */
    private function process_stripe_refund( $payment_intent_id, $amount ) {
        $settings = get_option( 'booking_master_settings', array() );
        $stripe_secret_key = isset( $settings['stripe_secret_key'] ) ? $settings['stripe_secret_key'] : '';

        if ( empty( $stripe_secret_key ) ) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey( $stripe_secret_key );

            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment_intent_id,
                'amount' => $amount * 100, // Convert to cents
            ]);

            return $refund->status === 'succeeded';

        } catch ( Exception $e ) {
            error_log( 'Stripe refund failed: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Process PayPal refund
     *
     * @since    1.0.0
     * @param    string   $order_id    PayPal order ID.
     * @param    float    $amount      Refund amount.
     * @return   bool                  True on success, false on failure.
     */
    private function process_paypal_refund( $order_id, $amount ) {
        $settings = get_option( 'booking_master_settings', array() );
        $paypal_client_id = isset( $settings['paypal_client_id'] ) ? $settings['paypal_client_id'] : '';
        $paypal_client_secret = isset( $settings['paypal_client_secret'] ) ? $settings['paypal_client_secret'] : '';
        $paypal_sandbox = isset( $settings['paypal_sandbox'] ) ? $settings['paypal_sandbox'] : true;

        if ( empty( $paypal_client_id ) || empty( $paypal_client_secret ) ) {
            return false;
        }

        $base_url = $paypal_sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';

        try {
            // Get access token (same as verification method)
            $auth = base64_encode( $paypal_client_id . ':' . $paypal_client_secret );
            
            $response = wp_remote_post( $base_url . '/v1/oauth2/token', array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => 'grant_type=client_credentials',
            ) );

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $access_token = $body['access_token'];

            // Process refund
            $refund_data = array(
                'amount' => array(
                    'value' => number_format( $amount, 2, '.', '' ),
                    'currency_code' => 'USD', // This should be dynamic based on settings
                ),
            );

            $refund_response = wp_remote_post( $base_url . '/v2/payments/captures/' . $order_id . '/refund', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode( $refund_data ),
            ) );

            if ( is_wp_error( $refund_response ) ) {
                return false;
            }

            $refund_result = json_decode( wp_remote_retrieve_body( $refund_response ), true );
            
            return isset( $refund_result['status'] ) && $refund_result['status'] === 'COMPLETED';

        } catch ( Exception $e ) {
            error_log( 'PayPal refund failed: ' . $e->getMessage() );
            return false;
        }
    }
}