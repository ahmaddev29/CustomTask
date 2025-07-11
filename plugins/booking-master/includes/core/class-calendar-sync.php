<?php

/**
 * Calendar Synchronization
 *
 * @since      1.0.0
 */

/**
 * Calendar Synchronization.
 *
 * This class defines all calendar sync functionality for Google and Outlook.
 *
 * @since      1.0.0
 */
class Booking_Master_Calendar_Sync {

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
        add_action( 'wp_ajax_bm_google_calendar_auth', array( $this, 'ajax_google_calendar_auth' ) );
        add_action( 'wp_ajax_bm_outlook_calendar_auth', array( $this, 'ajax_outlook_calendar_auth' ) );
        add_action( 'wp_ajax_bm_sync_google_calendar', array( $this, 'ajax_sync_google_calendar' ) );
        add_action( 'wp_ajax_bm_sync_outlook_calendar', array( $this, 'ajax_sync_outlook_calendar' ) );
        add_action( 'wp_ajax_bm_disconnect_calendar', array( $this, 'ajax_disconnect_calendar' ) );
        add_action( 'wp_ajax_bm_get_calendar_status', array( $this, 'ajax_get_calendar_status' ) );
        add_action( 'wp_ajax_bm_import_calendar_events', array( $this, 'ajax_import_calendar_events' ) );
        
        // Booking hooks for calendar sync
        add_action( 'bm_booking_created', array( $this, 'create_calendar_event' ), 10, 1 );
        add_action( 'bm_booking_status_changed', array( $this, 'update_calendar_event' ), 10, 2 );
        add_action( 'bm_booking_cancelled', array( $this, 'delete_calendar_event' ), 10, 1 );
        
        // Scheduled sync
        add_action( 'bm_sync_calendars', array( $this, 'sync_all_calendars' ) );
        
        // Schedule daily sync
        if ( ! wp_next_scheduled( 'bm_sync_calendars' ) ) {
            wp_schedule_event( time(), 'daily', 'bm_sync_calendars' );
        }
    }

    /**
     * Google Calendar OAuth authentication (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_google_calendar_auth() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $auth_code = sanitize_text_field( $_POST['auth_code'] );
        $user_id = get_current_user_id();

        $result = $this->authenticate_google_calendar( $user_id, $auth_code );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Google Calendar connected successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to connect Google Calendar. Please try again.' ) );
        }
    }

    /**
     * Outlook Calendar OAuth authentication (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_outlook_calendar_auth() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $auth_code = sanitize_text_field( $_POST['auth_code'] );
        $user_id = get_current_user_id();

        $result = $this->authenticate_outlook_calendar( $user_id, $auth_code );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Outlook Calendar connected successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to connect Outlook Calendar. Please try again.' ) );
        }
    }

    /**
     * Sync Google Calendar (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_sync_google_calendar() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $user_id = get_current_user_id();
        $direction = sanitize_text_field( $_POST['direction'] ); // 'import' or 'export'

        if ( $direction === 'import' ) {
            $result = $this->import_google_calendar_events( $user_id );
        } else {
            $result = $this->export_bookings_to_google_calendar( $user_id );
        }
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Google Calendar synced successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to sync Google Calendar.' ) );
        }
    }

    /**
     * Sync Outlook Calendar (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_sync_outlook_calendar() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $user_id = get_current_user_id();
        $direction = sanitize_text_field( $_POST['direction'] ); // 'import' or 'export'

        if ( $direction === 'import' ) {
            $result = $this->import_outlook_calendar_events( $user_id );
        } else {
            $result = $this->export_bookings_to_outlook_calendar( $user_id );
        }
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Outlook Calendar synced successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to sync Outlook Calendar.' ) );
        }
    }

    /**
     * Disconnect calendar (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_disconnect_calendar() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $user_id = get_current_user_id();
        $provider = sanitize_text_field( $_POST['provider'] ); // 'google' or 'outlook'

        $result = $this->disconnect_calendar( $user_id, $provider );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => ucfirst($provider) . ' Calendar disconnected successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to disconnect calendar.' ) );
        }
    }

    /**
     * Get calendar connection status (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_calendar_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $user_id = get_current_user_id();
        $status = $this->get_calendar_connection_status( $user_id );
        
        wp_send_json_success( array( 'status' => $status ) );
    }

    /**
     * Import calendar events (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_import_calendar_events() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_calendar_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $user_id = get_current_user_id();
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );
        $provider = sanitize_text_field( $_POST['provider'] );

        if ( $provider === 'google' ) {
            $result = $this->import_google_calendar_events( $user_id, $start_date, $end_date );
        } else {
            $result = $this->import_outlook_calendar_events( $user_id, $start_date, $end_date );
        }
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Calendar events imported successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to import calendar events.' ) );
        }
    }

    /**
     * Authenticate Google Calendar
     *
     * @since    1.0.0
     * @param    int      $user_id      User ID.
     * @param    string   $auth_code    OAuth authorization code.
     * @return   bool                   True on success, false on failure.
     */
    private function authenticate_google_calendar( $user_id, $auth_code ) {
        $settings = get_option( 'booking_master_settings', array() );
        $google_client_id = isset( $settings['google_client_id'] ) ? $settings['google_client_id'] : '';
        $google_client_secret = isset( $settings['google_client_secret'] ) ? $settings['google_client_secret'] : '';
        $redirect_uri = admin_url( 'admin.php?page=booking-master-mentor&tab=calendar' );

        if ( empty( $google_client_id ) || empty( $google_client_secret ) ) {
            return false;
        }

        try {
            // Exchange authorization code for access token
            $token_url = 'https://oauth2.googleapis.com/token';
            $token_data = array(
                'code' => $auth_code,
                'client_id' => $google_client_id,
                'client_secret' => $google_client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            );

            $response = wp_remote_post( $token_url, array(
                'body' => $token_data,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $token_response = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $token_response['access_token'] ) ) {
                // Store tokens
                update_user_meta( $user_id, 'bm_google_access_token', $token_response['access_token'] );
                update_user_meta( $user_id, 'bm_google_refresh_token', $token_response['refresh_token'] ?? '' );
                update_user_meta( $user_id, 'bm_google_token_expires', time() + ( $token_response['expires_in'] ?? 3600 ) );
                update_user_meta( $user_id, 'bm_google_calendar_connected', true );

                return true;
            }

            return false;

        } catch ( Exception $e ) {
            error_log( 'Google Calendar authentication failed: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Authenticate Outlook Calendar
     *
     * @since    1.0.0
     * @param    int      $user_id      User ID.
     * @param    string   $auth_code    OAuth authorization code.
     * @return   bool                   True on success, false on failure.
     */
    private function authenticate_outlook_calendar( $user_id, $auth_code ) {
        $settings = get_option( 'booking_master_settings', array() );
        $outlook_client_id = isset( $settings['outlook_client_id'] ) ? $settings['outlook_client_id'] : '';
        $outlook_client_secret = isset( $settings['outlook_client_secret'] ) ? $settings['outlook_client_secret'] : '';
        $redirect_uri = admin_url( 'admin.php?page=booking-master-mentor&tab=calendar' );

        if ( empty( $outlook_client_id ) || empty( $outlook_client_secret ) ) {
            return false;
        }

        try {
            // Exchange authorization code for access token
            $token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
            $token_data = array(
                'code' => $auth_code,
                'client_id' => $outlook_client_id,
                'client_secret' => $outlook_client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
                'scope' => 'https://graph.microsoft.com/calendars.readwrite offline_access',
            );

            $response = wp_remote_post( $token_url, array(
                'body' => $token_data,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $token_response = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $token_response['access_token'] ) ) {
                // Store tokens
                update_user_meta( $user_id, 'bm_outlook_access_token', $token_response['access_token'] );
                update_user_meta( $user_id, 'bm_outlook_refresh_token', $token_response['refresh_token'] ?? '' );
                update_user_meta( $user_id, 'bm_outlook_token_expires', time() + ( $token_response['expires_in'] ?? 3600 ) );
                update_user_meta( $user_id, 'bm_outlook_calendar_connected', true );

                return true;
            }

            return false;

        } catch ( Exception $e ) {
            error_log( 'Outlook Calendar authentication failed: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create calendar event when booking is created
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function create_calendar_event( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentor_id = $booking->mentor_id;
        
        // Create events in connected calendars
        if ( get_user_meta( $mentor_id, 'bm_google_calendar_connected', true ) ) {
            $this->create_google_calendar_event( $mentor_id, $booking );
        }
        
        if ( get_user_meta( $mentor_id, 'bm_outlook_calendar_connected', true ) ) {
            $this->create_outlook_calendar_event( $mentor_id, $booking );
        }
    }

    /**
     * Update calendar event when booking status changes
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     * @param    string   $new_status    New booking status.
     */
    public function update_calendar_event( $booking_id, $new_status ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentor_id = $booking->mentor_id;
        
        // Update events in connected calendars
        if ( get_user_meta( $mentor_id, 'bm_google_calendar_connected', true ) ) {
            $this->update_google_calendar_event( $mentor_id, $booking, $new_status );
        }
        
        if ( get_user_meta( $mentor_id, 'bm_outlook_calendar_connected', true ) ) {
            $this->update_outlook_calendar_event( $mentor_id, $booking, $new_status );
        }
    }

    /**
     * Delete calendar event when booking is cancelled
     *
     * @since    1.0.0
     * @param    int      $booking_id    Booking ID.
     */
    public function delete_calendar_event( $booking_id ) {
        $booking = $this->db->get_booking( $booking_id );
        if ( ! $booking ) {
            return false;
        }

        $mentor_id = $booking->mentor_id;
        
        // Delete events from connected calendars
        if ( get_user_meta( $mentor_id, 'bm_google_calendar_connected', true ) ) {
            $this->delete_google_calendar_event( $mentor_id, $booking );
        }
        
        if ( get_user_meta( $mentor_id, 'bm_outlook_calendar_connected', true ) ) {
            $this->delete_outlook_calendar_event( $mentor_id, $booking );
        }
    }

    /**
     * Create Google Calendar event
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @param    object   $booking    Booking object.
     * @return   bool                 True on success, false on failure.
     */
    private function create_google_calendar_event( $user_id, $booking ) {
        $access_token = $this->get_valid_google_access_token( $user_id );
        if ( ! $access_token ) {
            return false;
        }

        $service = $this->db->get_service( $booking->service_id );
        $mentee = get_user_by( 'ID', $booking->mentee_id );

        $start_time = new DateTime( $booking->booking_date );
        $end_time = clone $start_time;
        $end_time->add( new DateInterval( 'PT' . $service->duration . 'M' ) );

        $event_data = array(
            'summary' => $service->service_name . ' - ' . $mentee->display_name,
            'description' => "Booking session with {$mentee->display_name}\n\nService: {$service->service_name}\nDuration: {$service->duration} minutes",
            'start' => array(
                'dateTime' => $start_time->format( 'c' ),
                'timeZone' => wp_timezone_string(),
            ),
            'end' => array(
                'dateTime' => $end_time->format( 'c' ),
                'timeZone' => wp_timezone_string(),
            ),
            'attendees' => array(
                array( 'email' => $mentee->user_email ),
            ),
        );

        if ( ! empty( $booking->zoom_join_url ) ) {
            $event_data['description'] .= "\n\nZoom Meeting: " . $booking->zoom_join_url;
        }

        $response = wp_remote_post( 'https://www.googleapis.com/calendar/v3/calendars/primary/events', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $event_data ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $event_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $event_response['id'] ) ) {
                // Store event ID for future updates
                $this->db->update_booking( $booking->id, array( 'google_event_id' => $event_response['id'] ) );
                return true;
            }
        }

        return false;
    }

    /**
     * Create Outlook Calendar event
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @param    object   $booking    Booking object.
     * @return   bool                 True on success, false on failure.
     */
    private function create_outlook_calendar_event( $user_id, $booking ) {
        $access_token = $this->get_valid_outlook_access_token( $user_id );
        if ( ! $access_token ) {
            return false;
        }

        $service = $this->db->get_service( $booking->service_id );
        $mentee = get_user_by( 'ID', $booking->mentee_id );

        $start_time = new DateTime( $booking->booking_date );
        $end_time = clone $start_time;
        $end_time->add( new DateInterval( 'PT' . $service->duration . 'M' ) );

        $event_data = array(
            'subject' => $service->service_name . ' - ' . $mentee->display_name,
            'body' => array(
                'contentType' => 'text',
                'content' => "Booking session with {$mentee->display_name}\n\nService: {$service->service_name}\nDuration: {$service->duration} minutes",
            ),
            'start' => array(
                'dateTime' => $start_time->format( 'c' ),
                'timeZone' => wp_timezone_string(),
            ),
            'end' => array(
                'dateTime' => $end_time->format( 'c' ),
                'timeZone' => wp_timezone_string(),
            ),
            'attendees' => array(
                array(
                    'emailAddress' => array(
                        'address' => $mentee->user_email,
                        'name' => $mentee->display_name,
                    ),
                ),
            ),
        );

        if ( ! empty( $booking->zoom_join_url ) ) {
            $event_data['body']['content'] .= "\n\nZoom Meeting: " . $booking->zoom_join_url;
        }

        $response = wp_remote_post( 'https://graph.microsoft.com/v1.0/me/events', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $event_data ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $event_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $event_response['id'] ) ) {
                // Store event ID for future updates
                $this->db->update_booking( $booking->id, array( 'outlook_event_id' => $event_response['id'] ) );
                return true;
            }
        }

        return false;
    }

    /**
     * Import Google Calendar events
     *
     * @since    1.0.0
     * @param    int      $user_id      User ID.
     * @param    string   $start_date   Start date (optional).
     * @param    string   $end_date     End date (optional).
     * @return   bool                   True on success, false on failure.
     */
    private function import_google_calendar_events( $user_id, $start_date = null, $end_date = null ) {
        $access_token = $this->get_valid_google_access_token( $user_id );
        if ( ! $access_token ) {
            return false;
        }

        $start_date = $start_date ?: date( 'c' );
        $end_date = $end_date ?: date( 'c', strtotime( '+30 days' ) );

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?' . http_build_query( array(
            'timeMin' => $start_date,
            'timeMax' => $end_date,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
        ) );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $events_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $events_response['items'] ) ) {
                $this->process_imported_events( $user_id, $events_response['items'], 'google' );
                return true;
            }
        }

        return false;
    }

    /**
     * Import Outlook Calendar events
     *
     * @since    1.0.0
     * @param    int      $user_id      User ID.
     * @param    string   $start_date   Start date (optional).
     * @param    string   $end_date     End date (optional).
     * @return   bool                   True on success, false on failure.
     */
    private function import_outlook_calendar_events( $user_id, $start_date = null, $end_date = null ) {
        $access_token = $this->get_valid_outlook_access_token( $user_id );
        if ( ! $access_token ) {
            return false;
        }

        $start_date = $start_date ?: date( 'c' );
        $end_date = $end_date ?: date( 'c', strtotime( '+30 days' ) );

        $url = 'https://graph.microsoft.com/v1.0/me/calendarview?' . http_build_query( array(
            'startDateTime' => $start_date,
            'endDateTime' => $end_date,
        ) );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $events_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $events_response['value'] ) ) {
                $this->process_imported_events( $user_id, $events_response['value'], 'outlook' );
                return true;
            }
        }

        return false;
    }

    /**
     * Process imported calendar events
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @param    array    $events     Calendar events.
     * @param    string   $provider   Calendar provider.
     */
    private function process_imported_events( $user_id, $events, $provider ) {
        $availability_manager = new Booking_Master_Availability_Management();

        foreach ( $events as $event ) {
            // Skip all-day events
            if ( $provider === 'google' && ! isset( $event['start']['dateTime'] ) ) {
                continue;
            }
            if ( $provider === 'outlook' && $event['isAllDay'] ) {
                continue;
            }

            // Get event times
            if ( $provider === 'google' ) {
                $start_time = new DateTime( $event['start']['dateTime'] );
                $end_time = new DateTime( $event['end']['dateTime'] );
            } else {
                $start_time = new DateTime( $event['start']['dateTime'] );
                $end_time = new DateTime( $event['end']['dateTime'] );
            }

            // Block time slots for this event
            $this->block_time_slots_for_event( $user_id, $start_time, $end_time, $availability_manager );
        }
    }

    /**
     * Block time slots for imported event
     *
     * @since    1.0.0
     * @param    int                                 $user_id                User ID.
     * @param    DateTime                            $start_time             Event start time.
     * @param    DateTime                            $end_time               Event end time.
     * @param    Booking_Master_Availability_Management $availability_manager   Availability manager instance.
     */
    private function block_time_slots_for_event( $user_id, $start_time, $end_time, $availability_manager ) {
        $date = $start_time->format( 'Y-m-d' );
        $slot_duration = 30; // minutes
        
        $current_time = clone $start_time;
        while ( $current_time < $end_time ) {
            $time_slot = $current_time->format( 'H:i' );
            $availability_manager->update_time_slot( $user_id, $date, $time_slot, 'blocked' );
            $current_time->add( new DateInterval( 'PT' . $slot_duration . 'M' ) );
        }
    }

    /**
     * Get valid Google access token (refresh if needed)
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   string|false         Access token or false if not available.
     */
    private function get_valid_google_access_token( $user_id ) {
        $access_token = get_user_meta( $user_id, 'bm_google_access_token', true );
        $expires = get_user_meta( $user_id, 'bm_google_token_expires', true );
        
        if ( empty( $access_token ) ) {
            return false;
        }

        // Check if token is expired
        if ( $expires && $expires < time() ) {
            // Try to refresh token
            $refresh_token = get_user_meta( $user_id, 'bm_google_refresh_token', true );
            if ( ! empty( $refresh_token ) ) {
                $access_token = $this->refresh_google_access_token( $user_id, $refresh_token );
            } else {
                return false;
            }
        }

        return $access_token;
    }

    /**
     * Get valid Outlook access token (refresh if needed)
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   string|false         Access token or false if not available.
     */
    private function get_valid_outlook_access_token( $user_id ) {
        $access_token = get_user_meta( $user_id, 'bm_outlook_access_token', true );
        $expires = get_user_meta( $user_id, 'bm_outlook_token_expires', true );
        
        if ( empty( $access_token ) ) {
            return false;
        }

        // Check if token is expired
        if ( $expires && $expires < time() ) {
            // Try to refresh token
            $refresh_token = get_user_meta( $user_id, 'bm_outlook_refresh_token', true );
            if ( ! empty( $refresh_token ) ) {
                $access_token = $this->refresh_outlook_access_token( $user_id, $refresh_token );
            } else {
                return false;
            }
        }

        return $access_token;
    }

    /**
     * Refresh Google access token
     *
     * @since    1.0.0
     * @param    int      $user_id        User ID.
     * @param    string   $refresh_token  Refresh token.
     * @return   string|false             New access token or false.
     */
    private function refresh_google_access_token( $user_id, $refresh_token ) {
        $settings = get_option( 'booking_master_settings', array() );
        $google_client_id = isset( $settings['google_client_id'] ) ? $settings['google_client_id'] : '';
        $google_client_secret = isset( $settings['google_client_secret'] ) ? $settings['google_client_secret'] : '';

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id' => $google_client_id,
                'client_secret' => $google_client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $token_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $token_response['access_token'] ) ) {
                update_user_meta( $user_id, 'bm_google_access_token', $token_response['access_token'] );
                update_user_meta( $user_id, 'bm_google_token_expires', time() + ( $token_response['expires_in'] ?? 3600 ) );
                return $token_response['access_token'];
            }
        }

        return false;
    }

    /**
     * Refresh Outlook access token
     *
     * @since    1.0.0
     * @param    int      $user_id        User ID.
     * @param    string   $refresh_token  Refresh token.
     * @return   string|false             New access token or false.
     */
    private function refresh_outlook_access_token( $user_id, $refresh_token ) {
        $settings = get_option( 'booking_master_settings', array() );
        $outlook_client_id = isset( $settings['outlook_client_id'] ) ? $settings['outlook_client_id'] : '';
        $outlook_client_secret = isset( $settings['outlook_client_secret'] ) ? $settings['outlook_client_secret'] : '';

        $response = wp_remote_post( 'https://login.microsoftonline.com/common/oauth2/v2.0/token', array(
            'body' => array(
                'client_id' => $outlook_client_id,
                'client_secret' => $outlook_client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ),
        ) );

        if ( ! is_wp_error( $response ) ) {
            $token_response = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $token_response['access_token'] ) ) {
                update_user_meta( $user_id, 'bm_outlook_access_token', $token_response['access_token'] );
                update_user_meta( $user_id, 'bm_outlook_token_expires', time() + ( $token_response['expires_in'] ?? 3600 ) );
                return $token_response['access_token'];
            }
        }

        return false;
    }

    /**
     * Disconnect calendar
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @param    string   $provider   Calendar provider.
     * @return   bool                 True on success, false on failure.
     */
    private function disconnect_calendar( $user_id, $provider ) {
        if ( $provider === 'google' ) {
            delete_user_meta( $user_id, 'bm_google_access_token' );
            delete_user_meta( $user_id, 'bm_google_refresh_token' );
            delete_user_meta( $user_id, 'bm_google_token_expires' );
            delete_user_meta( $user_id, 'bm_google_calendar_connected' );
        } elseif ( $provider === 'outlook' ) {
            delete_user_meta( $user_id, 'bm_outlook_access_token' );
            delete_user_meta( $user_id, 'bm_outlook_refresh_token' );
            delete_user_meta( $user_id, 'bm_outlook_token_expires' );
            delete_user_meta( $user_id, 'bm_outlook_calendar_connected' );
        }

        return true;
    }

    /**
     * Get calendar connection status
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   array                Connection status.
     */
    private function get_calendar_connection_status( $user_id ) {
        return array(
            'google' => array(
                'connected' => (bool) get_user_meta( $user_id, 'bm_google_calendar_connected', true ),
                'last_sync' => get_user_meta( $user_id, 'bm_google_last_sync', true ),
            ),
            'outlook' => array(
                'connected' => (bool) get_user_meta( $user_id, 'bm_outlook_calendar_connected', true ),
                'last_sync' => get_user_meta( $user_id, 'bm_outlook_last_sync', true ),
            ),
        );
    }

    /**
     * Sync all connected calendars
     *
     * @since    1.0.0
     */
    public function sync_all_calendars() {
        // Get all mentors with connected calendars
        $mentors = get_users( array( 'role' => 'mentor' ) );

        foreach ( $mentors as $mentor ) {
            if ( get_user_meta( $mentor->ID, 'bm_google_calendar_connected', true ) ) {
                $this->import_google_calendar_events( $mentor->ID );
                update_user_meta( $mentor->ID, 'bm_google_last_sync', current_time( 'mysql' ) );
            }

            if ( get_user_meta( $mentor->ID, 'bm_outlook_calendar_connected', true ) ) {
                $this->import_outlook_calendar_events( $mentor->ID );
                update_user_meta( $mentor->ID, 'bm_outlook_last_sync', current_time( 'mysql' ) );
            }
        }
    }

    // Placeholder methods for updating/deleting calendar events
    private function update_google_calendar_event( $user_id, $booking, $new_status ) {
        // Implementation for updating Google Calendar events
    }

    private function update_outlook_calendar_event( $user_id, $booking, $new_status ) {
        // Implementation for updating Outlook Calendar events
    }

    private function delete_google_calendar_event( $user_id, $booking ) {
        // Implementation for deleting Google Calendar events
    }

    private function delete_outlook_calendar_event( $user_id, $booking ) {
        // Implementation for deleting Outlook Calendar events
    }

    private function export_bookings_to_google_calendar( $user_id ) {
        // Implementation for exporting bookings to Google Calendar
        return true;
    }

    private function export_bookings_to_outlook_calendar( $user_id ) {
        // Implementation for exporting bookings to Outlook Calendar
        return true;
    }
}