<?php

/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 */
class Booking_Master_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'booking_master_cleanup_expired_bookings' );
        wp_clear_scheduled_hook( 'booking_master_send_reminder_emails' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't remove user roles or database tables on deactivation
        // This preserves data if the user wants to reactivate the plugin
        // Only remove data on uninstall
    }
}