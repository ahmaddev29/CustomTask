<?php

/**
 * Admin Notices Handler
 *
 * @since      1.0.0
 */

/**
 * Handle admin notices for the plugin
 *
 * @since      1.0.0
 */
class Booking_Master_Admin_Notices {

    /**
     * Initialize the admin notices
     *
     * @since    1.0.0
     */
    public function init() {
        add_action( 'admin_notices', array( $this, 'show_role_update_notice' ) );
        add_action( 'wp_ajax_bm_update_roles', array( $this, 'ajax_update_roles' ) );
        add_action( 'wp_ajax_bm_dismiss_roles_notice', array( $this, 'ajax_dismiss_notice' ) );
    }

    /**
     * Show role update notice if needed
     *
     * @since    1.0.0
     */
    public function show_role_update_notice() {
        // Only show to administrators
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if roles need updating
        if ( ! Booking_Master_Roles_Updater::needs_update() ) {
            return;
        }

        // Check if notice was dismissed
        if ( get_option( 'bm_roles_update_notice_dismissed' ) ) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible" id="bm-roles-update-notice">
            <p>
                <strong>Booking Master:</strong> User roles need to be updated to ensure proper access to dashboards. 
                <a href="#" id="bm-update-roles-btn" class="button button-primary">Update Roles Now</a>
                <a href="#" id="bm-dismiss-notice" class="button button-secondary">Dismiss</a>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#bm-update-roles-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true).text('Updating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bm_update_roles',
                        nonce: '<?php echo wp_create_nonce( 'bm_update_roles' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#bm-roles-update-notice').removeClass('notice-info').addClass('notice-success');
                            $('#bm-roles-update-notice p').html('<strong>Success!</strong> User roles have been updated. Mentors and mentees can now access their dashboards.');
                            setTimeout(function() {
                                $('#bm-roles-update-notice').fadeOut();
                            }, 3000);
                        } else {
                            $btn.prop('disabled', false).text('Update Roles Now');
                            alert('Error updating roles: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Update Roles Now');
                        alert('Error updating roles. Please try again.');
                    }
                });
            });
            
            $('#bm-dismiss-notice').on('click', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'bm_dismiss_roles_notice',
                    nonce: '<?php echo wp_create_nonce( 'bm_dismiss_roles_notice' ); ?>'
                });
                $('#bm-roles-update-notice').fadeOut();
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to update roles
     *
     * @since    1.0.0
     */
    public function ajax_update_roles() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_update_roles' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }

        try {
            // Update roles
            Booking_Master_Roles_Updater::update_roles();
            
            // Mark notice as dismissed
            update_option( 'bm_roles_update_notice_dismissed', true );
            
            wp_send_json_success( array( 'message' => 'Roles updated successfully' ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * AJAX handler to dismiss roles notice
     *
     * @since    1.0.0
     */
    public function ajax_dismiss_notice() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_dismiss_roles_notice' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }

        // Mark notice as dismissed
        update_option( 'bm_roles_update_notice_dismissed', true );
        
        wp_send_json_success( array( 'message' => 'Notice dismissed' ) );
    }
}