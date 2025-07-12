<?php

/**
 * Roles and Capabilities Updater
 *
 * @since      1.0.0
 */

/**
 * Handle roles and capabilities updates for existing installations
 *
 * @since      1.0.0
 */
class Booking_Master_Roles_Updater {

    /**
     * Update user roles and capabilities
     *
     * @since    1.0.0
     */
    public static function update_roles() {
        // Update mentor role capabilities
        $mentor_role = get_role( 'mentor' );
        if ( $mentor_role ) {
            $mentor_role->add_cap( 'edit_dashboard' );
            $mentor_role->add_cap( 'read' );
            $mentor_role->add_cap( 'bm_manage_services' );
            $mentor_role->add_cap( 'bm_view_bookings' );
            $mentor_role->add_cap( 'bm_manage_zoom' );
        } else {
            // Create mentor role if it doesn't exist
            add_role(
                'mentor',
                'Mentor',
                array(
                    'read' => true,
                    'edit_dashboard' => true,
                    'bm_manage_services' => true,
                    'bm_view_bookings' => true,
                    'bm_manage_zoom' => true,
                )
            );
        }
        
        // Update mentee role capabilities
        $mentee_role = get_role( 'mentee' );
        if ( $mentee_role ) {
            $mentee_role->add_cap( 'edit_dashboard' );
            $mentee_role->add_cap( 'read' );
            $mentee_role->add_cap( 'bm_book_services' );
        } else {
            // Create mentee role if it doesn't exist
            add_role(
                'mentee',
                'Mentee',
                array(
                    'read' => true,
                    'edit_dashboard' => true,
                    'bm_book_services' => true,
                )
            );
        }
        
        // Update administrator capabilities
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'bm_manage_all_services' );
            $admin_role->add_cap( 'bm_manage_all_bookings' );
            $admin_role->add_cap( 'bm_manage_zoom_settings' );
            $admin_role->add_cap( 'bm_view_reports' );
        }
    }
    
    /**
     * Check if roles update is needed
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function needs_update() {
        $mentor_role = get_role( 'mentor' );
        if ( ! $mentor_role || ! $mentor_role->has_cap( 'edit_dashboard' ) ) {
            return true;
        }
        
        $mentee_role = get_role( 'mentee' );
        if ( ! $mentee_role || ! $mentee_role->has_cap( 'edit_dashboard' ) ) {
            return true;
        }
        
        return false;
    }
}