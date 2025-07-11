<?php

/**
 * Zoom integration
 *
 * @since      1.0.0
 */

/**
 * Zoom integration.
 *
 * This class defines all Zoom integration functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Zoom_Integration {

    /**
     * Database instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Booking_Master_Database    $db    Database operations.
     */
    private $db;

    /**
     * Zoom API base URL
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_base_url    Zoom API base URL.
     */
    private $api_base_url = 'https://api.zoom.us/v2/';

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
        add_action( 'wp_ajax_bm_connect_zoom', array( $this, 'ajax_connect_zoom' ) );
        add_action( 'wp_ajax_bm_disconnect_zoom', array( $this, 'ajax_disconnect_zoom' ) );
        add_action( 'wp_ajax_bm_test_zoom_connection', array( $this, 'ajax_test_zoom_connection' ) );
        add_action( 'init', array( $this, 'handle_zoom_oauth_callback' ) );
    }

    /**
     * Connect to Zoom (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_connect_zoom() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_zoom_nonce' ) || ! current_user_can( 'bm_manage_zoom' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $settings = get_option( 'booking_master_settings', array() );
        
        if ( empty( $settings['zoom_api_key'] ) || empty( $settings['zoom_api_secret'] ) ) {
            wp_send_json_error( array( 'message' => 'Zoom API credentials not configured. Please contact administrator.' ) );
        }

        // Generate OAuth URL
        $oauth_url = $this->get_zoom_oauth_url();
        
        wp_send_json_success( array( 
            'message' => 'Redirecting to Zoom authorization...',
            'redirect_url' => $oauth_url
        ) );
    }

    /**
     * Disconnect from Zoom (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_disconnect_zoom() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_zoom_nonce' ) || ! current_user_can( 'bm_manage_zoom' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = get_current_user_id();
        
        // Clear Zoom tokens
        $this->db->save_mentor_settings( $mentor_id, array(
            'zoom_access_token' => '',
            'zoom_refresh_token' => '',
            'zoom_expires_at' => null,
        ) );

        wp_send_json_success( array( 'message' => 'Zoom account disconnected successfully!' ) );
    }

    /**
     * Test Zoom connection (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_test_zoom_connection() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_zoom_nonce' ) || ! current_user_can( 'bm_manage_zoom' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $mentor_id = get_current_user_id();
        $user_info = $this->get_zoom_user_info( $mentor_id );

        if ( $user_info ) {
            wp_send_json_success( array( 
                'message' => 'Zoom connection is working!',
                'user_info' => $user_info
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to connect to Zoom. Please reconnect your account.' ) );
        }
    }

    /**
     * Handle Zoom OAuth callback
     *
     * @since    1.0.0
     */
    public function handle_zoom_oauth_callback() {
        if ( isset( $_GET['code'] ) && isset( $_GET['state'] ) && $_GET['state'] === 'booking_master_zoom' ) {
            $code = sanitize_text_field( $_GET['code'] );
            $mentor_id = get_current_user_id();

            if ( ! $mentor_id ) {
                wp_die( 'You must be logged in to connect Zoom.' );
            }

            $tokens = $this->exchange_code_for_tokens( $code );
            
            if ( $tokens ) {
                // Save tokens to database
                $this->db->save_mentor_settings( $mentor_id, array(
                    'zoom_access_token' => $tokens['access_token'],
                    'zoom_refresh_token' => $tokens['refresh_token'],
                    'zoom_expires_at' => date( 'Y-m-d H:i:s', time() + $tokens['expires_in'] ),
                ) );

                // Redirect to dashboard with success message
                wp_redirect( admin_url( 'admin.php?page=booking-master-mentor&zoom_connected=1' ) );
                exit;
            } else {
                wp_die( 'Failed to connect to Zoom. Please try again.' );
            }
        }
    }

    /**
     * Get Zoom OAuth URL
     *
     * @since    1.0.0
     * @return   string    OAuth URL.
     */
    private function get_zoom_oauth_url() {
        $settings = get_option( 'booking_master_settings', array() );
        $client_id = $settings['zoom_api_key'];
        $redirect_uri = site_url( '/' );
        $state = 'booking_master_zoom';

        return "https://zoom.us/oauth/authorize?" . http_build_query( array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
        ) );
    }

    /**
     * Exchange authorization code for access tokens
     *
     * @since    1.0.0
     * @param    string   $code    Authorization code.
     * @return   array|false       Tokens array or false on failure.
     */
    private function exchange_code_for_tokens( $code ) {
        $settings = get_option( 'booking_master_settings', array() );
        
        $client_id = $settings['zoom_api_key'];
        $client_secret = $settings['zoom_api_secret'];
        $redirect_uri = site_url( '/' );

        $response = wp_remote_post( 'https://zoom.us/oauth/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirect_uri,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['access_token'] ) ) {
            return $data;
        }

        return false;
    }

    /**
     * Get access token for mentor
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   string|false           Access token or false if not available.
     */
    private function get_access_token( $mentor_id ) {
        $settings = $this->db->get_mentor_settings( $mentor_id );
        
        if ( ! $settings || empty( $settings->zoom_access_token ) ) {
            return false;
        }

        // Check if token is expired
        $expires_at = strtotime( $settings->zoom_expires_at );
        if ( $expires_at <= time() + 300 ) { // Refresh if expires within 5 minutes
            $new_token = $this->refresh_access_token( $mentor_id );
            return $new_token ? $new_token : false;
        }

        return $settings->zoom_access_token;
    }

    /**
     * Refresh access token
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   string|false           New access token or false on failure.
     */
    private function refresh_access_token( $mentor_id ) {
        $settings = $this->db->get_mentor_settings( $mentor_id );
        
        if ( ! $settings || empty( $settings->zoom_refresh_token ) ) {
            return false;
        }

        $plugin_settings = get_option( 'booking_master_settings', array() );
        $client_id = $plugin_settings['zoom_api_key'];
        $client_secret = $plugin_settings['zoom_api_secret'];

        $response = wp_remote_post( 'https://zoom.us/oauth/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $settings->zoom_refresh_token,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['access_token'] ) ) {
            // Update stored tokens
            $this->db->save_mentor_settings( $mentor_id, array(
                'zoom_access_token' => $data['access_token'],
                'zoom_refresh_token' => $data['refresh_token'],
                'zoom_expires_at' => date( 'Y-m-d H:i:s', time() + $data['expires_in'] ),
            ) );

            return $data['access_token'];
        }

        return false;
    }

    /**
     * Make API request to Zoom
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @param    string   $endpoint     API endpoint.
     * @param    string   $method       HTTP method.
     * @param    array    $data         Request data.
     * @return   array|false            Response data or false on failure.
     */
    private function make_api_request( $mentor_id, $endpoint, $method = 'GET', $data = array() ) {
        $access_token = $this->get_access_token( $mentor_id );
        
        if ( ! $access_token ) {
            return false;
        }

        $url = $this->api_base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
        );

        if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
            $args['body'] = json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code >= 200 && $status_code < 300 ) {
            return json_decode( $body, true );
        }

        return false;
    }

    /**
     * Get Zoom user info
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array|false            User info or false on failure.
     */
    public function get_zoom_user_info( $mentor_id ) {
        return $this->make_api_request( $mentor_id, 'users/me' );
    }

    /**
     * Create Zoom meeting for booking
     *
     * @since    1.0.0
     * @param    object   $booking    Booking object.
     * @return   array|false          Meeting data or false on failure.
     */
    public function create_meeting( $booking ) {
        $service = $this->db->get_service( $booking->service_id );
        
        if ( ! $service || ! $service->zoom_enabled ) {
            return false;
        }

        $mentor = get_user_by( 'ID', $booking->mentor_id );
        $mentee = get_user_by( 'ID', $booking->mentee_id );

        $meeting_data = array(
            'topic' => $service->service_name . ' - ' . $mentee->display_name,
            'type' => 2, // Scheduled meeting
            'start_time' => date( 'c', strtotime( $booking->booking_date ) ),
            'duration' => $service->duration,
            'timezone' => wp_timezone_string(),
            'settings' => array(
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => false,
                'audio' => 'both',
                'auto_recording' => 'none',
            ),
        );

        $response = $this->make_api_request( $booking->mentor_id, 'users/me/meetings', 'POST', $meeting_data );

        if ( $response && isset( $response['id'] ) ) {
            return array(
                'id' => $response['id'],
                'join_url' => $response['join_url'],
                'start_url' => $response['start_url'],
                'password' => isset( $response['password'] ) ? $response['password'] : '',
            );
        }

        return false;
    }

    /**
     * Delete Zoom meeting
     *
     * @since    1.0.0
     * @param    int      $mentor_id     Mentor ID.
     * @param    string   $meeting_id    Zoom meeting ID.
     * @return   bool                    True on success, false on failure.
     */
    public function delete_meeting( $mentor_id, $meeting_id ) {
        $response = $this->make_api_request( $mentor_id, "meetings/{$meeting_id}", 'DELETE' );
        return $response !== false;
    }

    /**
     * Check if mentor has Zoom connected
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   bool                   True if connected, false otherwise.
     */
    public function is_zoom_connected( $mentor_id ) {
        $access_token = $this->get_access_token( $mentor_id );
        return ! empty( $access_token );
    }

    /**
     * Get Zoom connection status for mentor
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array                  Connection status information.
     */
    public function get_connection_status( $mentor_id ) {
        $settings = $this->db->get_mentor_settings( $mentor_id );
        
        if ( ! $settings || empty( $settings->zoom_access_token ) ) {
            return array(
                'connected' => false,
                'message' => 'Not connected to Zoom',
            );
        }

        $user_info = $this->get_zoom_user_info( $mentor_id );
        
        if ( $user_info ) {
            return array(
                'connected' => true,
                'message' => 'Connected to Zoom',
                'user_email' => $user_info['email'],
                'display_name' => $user_info['first_name'] . ' ' . $user_info['last_name'],
            );
        } else {
            return array(
                'connected' => false,
                'message' => 'Zoom connection expired. Please reconnect.',
            );
        }
    }
}