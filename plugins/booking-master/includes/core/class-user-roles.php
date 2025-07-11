<?php

/**
 * User roles and capabilities management
 *
 * @since      1.0.0
 */

/**
 * User roles and capabilities management.
 *
 * This class defines all user role related functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_User_Roles {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_bm_assign_mentor_role', array( $this, 'assign_mentor_role' ) );
        add_action( 'wp_ajax_bm_assign_mentee_role', array( $this, 'assign_mentee_role' ) );
        add_action( 'user_register', array( $this, 'set_default_mentee_role' ) );
        add_filter( 'user_has_cap', array( $this, 'check_user_capabilities' ), 10, 3 );
    }

    /**
     * Set default role to mentee for new registrations
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID.
     */
    public function set_default_mentee_role( $user_id ) {
        $user = new WP_User( $user_id );
        
        // Only set role if user doesn't have one already
        if ( empty( $user->roles ) ) {
            $user->set_role( 'mentee' );
        }
    }

    /**
     * Assign mentor role to user (AJAX handler)
     *
     * @since    1.0.0
     */
    public function assign_mentor_role() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_assign_role' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = intval( $_POST['user_id'] );
        $user = new WP_User( $user_id );

        if ( $user->exists() ) {
            $user->set_role( 'mentor' );
            wp_send_json_success( array( 'message' => 'User role updated to Mentor successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'User not found.' ) );
        }
    }

    /**
     * Assign mentee role to user (AJAX handler)
     *
     * @since    1.0.0
     */
    public function assign_mentee_role() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_assign_role' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = intval( $_POST['user_id'] );
        $user = new WP_User( $user_id );

        if ( $user->exists() ) {
            $user->set_role( 'mentee' );
            wp_send_json_success( array( 'message' => 'User role updated to Mentee successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'User not found.' ) );
        }
    }

    /**
     * Check user capabilities for custom actions
     *
     * @since    1.0.0
     * @param    array    $allcaps    All capabilities.
     * @param    array    $caps       Required capabilities.
     * @param    array    $args       Arguments.
     * @return   array                Modified capabilities.
     */
    public function check_user_capabilities( $allcaps, $caps, $args ) {
        // Check if we're dealing with our custom capabilities
        if ( isset( $caps[0] ) ) {
            switch ( $caps[0] ) {
                case 'bm_manage_services':
                    // Mentors can manage their own services
                    if ( in_array( 'mentor', wp_get_current_user()->roles ) ) {
                        $allcaps['bm_manage_services'] = true;
                    }
                    break;

                case 'bm_view_bookings':
                    // Mentors can view their own bookings
                    if ( in_array( 'mentor', wp_get_current_user()->roles ) ) {
                        $allcaps['bm_view_bookings'] = true;
                    }
                    break;

                case 'bm_book_services':
                    // Mentees can book services
                    if ( in_array( 'mentee', wp_get_current_user()->roles ) ) {
                        $allcaps['bm_book_services'] = true;
                    }
                    break;

                case 'bm_manage_zoom':
                    // Mentors can manage their zoom settings
                    if ( in_array( 'mentor', wp_get_current_user()->roles ) ) {
                        $allcaps['bm_manage_zoom'] = true;
                    }
                    break;
            }
        }

        return $allcaps;
    }

    /**
     * Get all mentors
     *
     * @since    1.0.0
     * @return   array    Array of mentor user objects.
     */
    public function get_all_mentors() {
        $mentors = get_users( array( 'role' => 'mentor' ) );
        return $mentors;
    }

    /**
     * Get all mentees
     *
     * @since    1.0.0
     * @return   array    Array of mentee user objects.
     */
    public function get_all_mentees() {
        $mentees = get_users( array( 'role' => 'mentee' ) );
        return $mentees;
    }

    /**
     * Check if user is mentor
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   bool                 True if user is mentor, false otherwise.
     */
    public function is_mentor( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        $user = new WP_User( $user_id );
        return in_array( 'mentor', $user->roles );
    }

    /**
     * Check if user is mentee
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   bool                 True if user is mentee, false otherwise.
     */
    public function is_mentee( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        $user = new WP_User( $user_id );
        return in_array( 'mentee', $user->roles );
    }

    /**
     * Get user role display name
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID.
     * @return   string               Role display name.
     */
    public function get_user_role_display( $user_id ) {
        $user = new WP_User( $user_id );
        
        if ( in_array( 'mentor', $user->roles ) ) {
            return 'Mentor';
        } elseif ( in_array( 'mentee', $user->roles ) ) {
            return 'Mentee';
        } elseif ( in_array( 'administrator', $user->roles ) ) {
            return 'Administrator';
        } else {
            return 'User';
        }
    }

    /**
     * Create mentor application form
     *
     * @since    1.0.0
     * @return   string    HTML for mentor application form.
     */
    public function get_mentor_application_form() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to apply as a mentor.</p>';
        }

        $current_user = wp_get_current_user();
        
        if ( $this->is_mentor( $current_user->ID ) ) {
            return '<p>You are already registered as a mentor.</p>';
        }

        ob_start();
        ?>
        <div class="bm-mentor-application">
            <h3>Apply to Become a Mentor</h3>
            <form id="bm-mentor-application-form" method="post">
                <div class="form-group">
                    <label for="mentor-bio">Tell us about yourself:</label>
                    <textarea id="mentor-bio" name="mentor_bio" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="mentor-expertise">Your areas of expertise:</label>
                    <input type="text" id="mentor-expertise" name="mentor_expertise" required>
                </div>
                <div class="form-group">
                    <label for="mentor-experience">Years of experience:</label>
                    <select id="mentor-experience" name="mentor_experience" required>
                        <option value="">Select experience level</option>
                        <option value="1-2">1-2 years</option>
                        <option value="3-5">3-5 years</option>
                        <option value="6-10">6-10 years</option>
                        <option value="10+">10+ years</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Submit Application</button>
                <?php wp_nonce_field( 'bm_mentor_application', 'mentor_application_nonce' ); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}