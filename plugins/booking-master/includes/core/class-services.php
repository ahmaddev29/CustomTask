<?php

/**
 * Services management
 *
 * @since      1.0.0
 */

/**
 * Services management.
 *
 * This class defines all service related functionality.
 *
 * @since      1.0.0
 */
class Booking_Master_Services {

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
        add_action( 'wp_ajax_bm_create_service', array( $this, 'ajax_create_service' ) );
        add_action( 'wp_ajax_bm_update_service', array( $this, 'ajax_update_service' ) );
        add_action( 'wp_ajax_bm_delete_service', array( $this, 'ajax_delete_service' ) );
        add_action( 'wp_ajax_bm_get_services', array( $this, 'ajax_get_services' ) );
        add_action( 'wp_ajax_nopriv_bm_get_services', array( $this, 'ajax_get_services' ) );
    }

    /**
     * Create a new service (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_create_service() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_service_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_data = array(
            'mentor_id' => get_current_user_id(),
            'service_name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['description'] ),
            'price' => floatval( $_POST['price'] ),
            'duration' => intval( $_POST['duration'] ),
            'zoom_enabled' => isset( $_POST['zoom_enabled'] ) ? 1 : 0,
        );

        // Validate required fields
        if ( empty( $service_data['service_name'] ) || $service_data['price'] < 0 || $service_data['duration'] <= 0 ) {
            wp_send_json_error( array( 'message' => 'Please fill in all required fields correctly.' ) );
        }

        $service_id = $this->create_service( $service_data );

        if ( $service_id ) {
            wp_send_json_success( array( 
                'message' => 'Service created successfully!',
                'service_id' => $service_id 
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create service. Please try again.' ) );
        }
    }

    /**
     * Update service (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_update_service() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_service_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $service = $this->get_service( $service_id );

        // Check if service exists and user owns it
        if ( ! $service || ( $service->mentor_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( array( 'message' => 'Service not found or you do not have permission to edit it.' ) );
        }

        $update_data = array(
            'service_name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['description'] ),
            'price' => floatval( $_POST['price'] ),
            'duration' => intval( $_POST['duration'] ),
            'zoom_enabled' => isset( $_POST['zoom_enabled'] ) ? 1 : 0,
        );

        // Validate required fields
        if ( empty( $update_data['service_name'] ) || $update_data['price'] < 0 || $update_data['duration'] <= 0 ) {
            wp_send_json_error( array( 'message' => 'Please fill in all required fields correctly.' ) );
        }

        $updated = $this->update_service( $service_id, $update_data );

        if ( $updated ) {
            wp_send_json_success( array( 'message' => 'Service updated successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update service. Please try again.' ) );
        }
    }

    /**
     * Delete service (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_delete_service() {
        // Check nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bm_service_nonce' ) || ! current_user_can( 'bm_manage_services' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $service_id = intval( $_POST['service_id'] );
        $service = $this->get_service( $service_id );

        // Check if service exists and user owns it
        if ( ! $service || ( $service->mentor_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( array( 'message' => 'Service not found or you do not have permission to delete it.' ) );
        }

        $deleted = $this->delete_service( $service_id );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => 'Service deleted successfully!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete service. Please try again.' ) );
        }
    }

    /**
     * Get services (AJAX handler)
     *
     * @since    1.0.0
     */
    public function ajax_get_services() {
        $mentor_id = isset( $_POST['mentor_id'] ) ? intval( $_POST['mentor_id'] ) : null;
        
        if ( $mentor_id ) {
            $services = $this->get_services_by_mentor( $mentor_id );
        } else {
            $services = $this->get_all_active_services();
        }

        wp_send_json_success( array( 'services' => $services ) );
    }

    /**
     * Create a new service
     *
     * @since    1.0.0
     * @param    array    $service_data    Service data.
     * @return   int|false                 Service ID on success, false on failure.
     */
    public function create_service( $service_data ) {
        return $this->db->create_service( $service_data );
    }

    /**
     * Get service by ID
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @return   object|null             Service object or null if not found.
     */
    public function get_service( $service_id ) {
        return $this->db->get_service( $service_id );
    }

    /**
     * Get services by mentor ID
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID.
     * @return   array                  Array of service objects.
     */
    public function get_services_by_mentor( $mentor_id ) {
        return $this->db->get_services_by_mentor( $mentor_id );
    }

    /**
     * Get all active services
     *
     * @since    1.0.0
     * @return   array    Array of service objects.
     */
    public function get_all_active_services() {
        return $this->db->get_all_active_services();
    }

    /**
     * Update service
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @param    array    $data          Updated data.
     * @return   bool                    True on success, false on failure.
     */
    public function update_service( $service_id, $data ) {
        return $this->db->update_service( $service_id, $data );
    }

    /**
     * Delete service
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @return   bool                    True on success, false on failure.
     */
    public function delete_service( $service_id ) {
        return $this->db->delete_service( $service_id );
    }

    /**
     * Get service form HTML
     *
     * @since    1.0.0
     * @param    object   $service    Service object (for editing) or null (for creating).
     * @return   string               HTML form.
     */
    public function get_service_form( $service = null ) {
        $is_edit = ! is_null( $service );
        $form_title = $is_edit ? 'Edit Service' : 'Create New Service';
        $submit_text = $is_edit ? 'Update Service' : 'Create Service';
        
        ob_start();
        ?>
        <div class="bm-service-form">
            <h3><?php echo esc_html( $form_title ); ?></h3>
            <form id="bm-service-form" method="post">
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>">
                    <input type="hidden" name="action" value="bm_update_service">
                <?php else : ?>
                    <input type="hidden" name="action" value="bm_create_service">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="service-name">Service Name: <span class="required">*</span></label>
                    <input type="text" id="service-name" name="service_name" 
                           value="<?php echo $is_edit ? esc_attr( $service->service_name ) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="service-description">Description:</label>
                    <textarea id="service-description" name="description" rows="4"><?php echo $is_edit ? esc_textarea( $service->description ) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="service-price">Price ($): <span class="required">*</span></label>
                        <input type="number" id="service-price" name="price" step="0.01" min="0"
                               value="<?php echo $is_edit ? esc_attr( $service->price ) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="service-duration">Duration (minutes): <span class="required">*</span></label>
                        <select id="service-duration" name="duration" required>
                            <option value="">Select duration</option>
                            <option value="15" <?php echo ( $is_edit && $service->duration == 15 ) ? 'selected' : ''; ?>>15 minutes</option>
                            <option value="30" <?php echo ( $is_edit && $service->duration == 30 ) ? 'selected' : ''; ?>>30 minutes</option>
                            <option value="45" <?php echo ( $is_edit && $service->duration == 45 ) ? 'selected' : ''; ?>>45 minutes</option>
                            <option value="60" <?php echo ( $is_edit && $service->duration == 60 ) ? 'selected' : ''; ?>>60 minutes</option>
                            <option value="90" <?php echo ( $is_edit && $service->duration == 90 ) ? 'selected' : ''; ?>>90 minutes</option>
                            <option value="120" <?php echo ( $is_edit && $service->duration == 120 ) ? 'selected' : ''; ?>>120 minutes</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="zoom_enabled" value="1" 
                               <?php echo ( $is_edit && $service->zoom_enabled ) ? 'checked' : ''; ?>>
                        Enable Zoom Integration
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo esc_html( $submit_text ); ?></button>
                    <button type="button" class="btn btn-secondary" onclick="closeServiceForm()">Cancel</button>
                </div>
                
                <?php wp_nonce_field( 'bm_service_nonce', 'nonce' ); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get services list HTML
     *
     * @since    1.0.0
     * @param    int      $mentor_id    Mentor ID (optional).
     * @return   string                 HTML services list.
     */
    public function get_services_list( $mentor_id = null ) {
        if ( $mentor_id ) {
            $services = $this->get_services_by_mentor( $mentor_id );
        } else {
            $services = $this->get_all_active_services();
        }

        ob_start();
        ?>
        <div class="bm-services-list">
            <?php if ( empty( $services ) ) : ?>
                <div class="no-services">
                    <p>No services found.</p>
                    <?php if ( current_user_can( 'bm_manage_services' ) ) : ?>
                        <button class="btn btn-primary" onclick="showServiceForm()">Create Your First Service</button>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="services-grid">
                    <?php foreach ( $services as $service ) : ?>
                        <div class="service-card" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                            <div class="service-header">
                                <h4><?php echo esc_html( $service->service_name ); ?></h4>
                                <?php if ( isset( $service->mentor_name ) ) : ?>
                                    <span class="mentor-name">by <?php echo esc_html( $service->mentor_name ); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-content">
                                <?php if ( $service->description ) : ?>
                                    <p class="service-description"><?php echo esc_html( wp_trim_words( $service->description, 20 ) ); ?></p>
                                <?php endif; ?>
                                
                                <div class="service-details">
                                    <span class="price">$<?php echo esc_html( number_format( $service->price, 2 ) ); ?></span>
                                    <span class="duration"><?php echo esc_html( $service->duration ); ?> min</span>
                                    <?php if ( $service->zoom_enabled ) : ?>
                                        <span class="zoom-enabled">📹 Zoom Enabled</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="service-actions">
                                <?php if ( current_user_can( 'bm_book_services' ) && ! $mentor_id ) : ?>
                                    <button class="btn btn-primary btn-sm" onclick="bookService(<?php echo esc_attr( $service->id ); ?>)">Book Now</button>
                                <?php endif; ?>
                                
                                <?php if ( current_user_can( 'bm_manage_services' ) && ( ! $mentor_id || $service->mentor_id == get_current_user_id() ) ) : ?>
                                    <button class="btn btn-secondary btn-sm" onclick="editService(<?php echo esc_attr( $service->id ); ?>)">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteService(<?php echo esc_attr( $service->id ); ?>)">Delete</button>
                                <?php endif; ?>
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
     * Get service details HTML
     *
     * @since    1.0.0
     * @param    int      $service_id    Service ID.
     * @return   string                  HTML service details.
     */
    public function get_service_details( $service_id ) {
        $service = $this->get_service( $service_id );
        
        if ( ! $service ) {
            return '<p>Service not found.</p>';
        }

        ob_start();
        ?>
        <div class="bm-service-details">
            <h3><?php echo esc_html( $service->service_name ); ?></h3>
            
            <?php if ( $service->description ) : ?>
                <div class="service-description">
                    <h4>Description</h4>
                    <p><?php echo esc_html( $service->description ); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="service-info">
                <div class="info-item">
                    <strong>Price:</strong> $<?php echo esc_html( number_format( $service->price, 2 ) ); ?>
                </div>
                <div class="info-item">
                    <strong>Duration:</strong> <?php echo esc_html( $service->duration ); ?> minutes
                </div>
                <div class="info-item">
                    <strong>Zoom Integration:</strong> <?php echo $service->zoom_enabled ? 'Enabled' : 'Disabled'; ?>
                </div>
            </div>
            
            <?php if ( current_user_can( 'bm_book_services' ) ) : ?>
                <div class="booking-section">
                    <button class="btn btn-primary btn-lg" onclick="openBookingModal(<?php echo esc_attr( $service->id ); ?>)">
                        Book This Service
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}