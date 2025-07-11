<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 */

// Get dashboard statistics
$stats = $this->get_dashboard_stats();
$recent_bookings = $this->get_recent_bookings(5);
?>

<div class="wrap">
    <div class="bm-admin-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Welcome to Booking Master - Your comprehensive multivendor booking solution</p>
    </div>

    <!-- Dashboard Statistics -->
    <div class="bm-dashboard-stats">
        <div class="bm-stat-card">
            <h3>Total Services</h3>
            <span class="bm-stat-number"><?php echo esc_html($stats['total_services']); ?></span>
            <div class="bm-stat-label">Active services</div>
        </div>
        
        <div class="bm-stat-card">
            <h3>Total Bookings</h3>
            <span class="bm-stat-number"><?php echo esc_html($stats['total_bookings']); ?></span>
            <div class="bm-stat-label">All time</div>
        </div>
        
        <div class="bm-stat-card">
            <h3>Pending Bookings</h3>
            <span class="bm-stat-number"><?php echo esc_html($stats['pending_bookings']); ?></span>
            <div class="bm-stat-label">Need attention</div>
        </div>
        
        <div class="bm-stat-card">
            <h3>Total Mentors</h3>
            <span class="bm-stat-number"><?php echo esc_html($stats['total_mentors']); ?></span>
            <div class="bm-stat-label">Registered mentors</div>
        </div>
        
        <div class="bm-stat-card">
            <h3>Total Mentees</h3>
            <span class="bm-stat-number"><?php echo esc_html($stats['total_mentees']); ?></span>
            <div class="bm-stat-label">Registered mentees</div>
        </div>
        
        <div class="bm-stat-card">
            <h3>Monthly Revenue</h3>
            <span class="bm-stat-number">$<?php echo esc_html(number_format($stats['monthly_revenue'], 2)); ?></span>
            <div class="bm-stat-label">This month</div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bookings -->
        <div class="bm-admin-section">
            <div class="bm-section-header">
                <h3>Recent Bookings</h3>
                <a href="<?php echo admin_url('admin.php?page=booking-master-bookings'); ?>" class="bm-btn primary">View All</a>
            </div>
            <div class="bm-section-content">
                <?php if (empty($recent_bookings)): ?>
                    <p>No recent bookings found.</p>
                <?php else: ?>
                    <table class="bm-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Mentor</th>
                                <th>Mentee</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo esc_html($booking->service_name); ?></td>
                                    <td><?php echo esc_html($booking->mentor_name); ?></td>
                                    <td><?php echo esc_html($booking->mentee_name); ?></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($booking->booking_date))); ?></td>
                                    <td>$<?php echo esc_html(number_format($booking->total_amount, 2)); ?></td>
                                    <td>
                                        <span class="bm-status <?php echo esc_attr($booking->status); ?>">
                                            <?php echo esc_html(ucfirst($booking->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking->status === 'pending'): ?>
                                            <button class="bm-btn success small update-booking-status" 
                                                    data-booking-id="<?php echo esc_attr($booking->id); ?>" 
                                                    data-status="confirmed">Confirm</button>
                                            <button class="bm-btn danger small update-booking-status" 
                                                    data-booking-id="<?php echo esc_attr($booking->id); ?>" 
                                                    data-status="cancelled">Cancel</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="bm-admin-section">
            <div class="bm-section-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="bm-section-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div style="padding: 20px; background: #f9f9f9; border-radius: 4px; text-align: center;">
                        <h4>Manage Services</h4>
                        <p>View and manage all services created by mentors.</p>
                        <a href="<?php echo admin_url('admin.php?page=booking-master-services'); ?>" class="bm-btn primary">View Services</a>
                    </div>
                    
                    <div style="padding: 20px; background: #f9f9f9; border-radius: 4px; text-align: center;">
                        <h4>User Management</h4>
                        <p>Assign roles and manage mentor/mentee accounts.</p>
                        <a href="<?php echo admin_url('admin.php?page=booking-master-users'); ?>" class="bm-btn primary">Manage Users</a>
                    </div>
                    
                    <div style="padding: 20px; background: #f9f9f9; border-radius: 4px; text-align: center;">
                        <h4>Plugin Settings</h4>
                        <p>Configure Zoom integration and other settings.</p>
                        <a href="<?php echo admin_url('admin.php?page=booking-master-settings'); ?>" class="bm-btn primary">Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="bm-admin-section">
        <div class="bm-section-header">
            <h3>System Status</h3>
        </div>
        <div class="bm-section-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4>Plugin Version</h4>
                    <p><?php echo esc_html(BOOKING_MASTER_VERSION); ?></p>
                </div>
                
                <div>
                    <h4>WordPress Version</h4>
                    <p><?php echo esc_html(get_bloginfo('version')); ?></p>
                </div>
                
                <div>
                    <h4>PHP Version</h4>
                    <p><?php echo esc_html(PHP_VERSION); ?></p>
                </div>
                
                <div>
                    <h4>Database Tables</h4>
                    <p>
                        <?php 
                        global $wpdb;
                        $tables = array('bm_services', 'bm_bookings', 'bm_mentor_settings');
                        $existing_tables = 0;
                        foreach ($tables as $table) {
                            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'") == $wpdb->prefix . $table) {
                                $existing_tables++;
                            }
                        }
                        echo esc_html($existing_tables . '/' . count($tables) . ' tables created');
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>