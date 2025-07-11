<?php
/**
 * Mentor Dashboard
 *
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get current mentor ID
$mentor_id = get_current_user_id();
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

// Get mentor statistics
global $wpdb;
$bookings_table = $wpdb->prefix . 'bm_bookings';
$services_table = $wpdb->prefix . 'bm_services';

$mentor_stats = array(
    'total_services' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $services_table WHERE mentor_id = %d AND status = 'active'",
        $mentor_id
    ) ),
    'total_bookings' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d",
        $mentor_id
    ) ),
    'pending_bookings' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE mentor_id = %d AND status = 'pending'",
        $mentor_id
    ) ),
    'this_month_revenue' => $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(total_amount) FROM $bookings_table 
         WHERE mentor_id = %d AND payment_status = 'paid' 
         AND MONTH(booking_date) = MONTH(CURRENT_DATE()) 
         AND YEAR(booking_date) = YEAR(CURRENT_DATE())",
        $mentor_id
    ) ) ?: 0
);
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=booking-master-mentor&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            Overview
        </a>
        <a href="?page=booking-master-mentor&tab=services" class="nav-tab <?php echo $current_tab === 'services' ? 'nav-tab-active' : ''; ?>">
            My Services
        </a>
        <a href="?page=booking-master-mentor&tab=bookings" class="nav-tab <?php echo $current_tab === 'bookings' ? 'nav-tab-active' : ''; ?>">
            My Bookings
        </a>
        <a href="?page=booking-master-mentor&tab=availability" class="nav-tab <?php echo $current_tab === 'availability' ? 'nav-tab-active' : ''; ?>">
            Manage Availability
        </a>
        <a href="?page=booking-master-mentor&tab=calendar" class="nav-tab <?php echo $current_tab === 'calendar' ? 'nav-tab-active' : ''; ?>">
            Calendar Sync
        </a>
        <a href="?page=booking-master-mentor&tab=payments" class="nav-tab <?php echo $current_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
            Payments & Earnings
        </a>
        <a href="?page=booking-master-mentor&tab=zoom" class="nav-tab <?php echo $current_tab === 'zoom' ? 'nav-tab-active' : ''; ?>">
            Zoom Settings
        </a>
    </nav>

    <div class="tab-content">
        <?php if ( $current_tab === 'overview' ) : ?>
            <div class="bm-mentor-overview">
                <h2>Dashboard Overview</h2>
                
                <!-- Stats Cards -->
                <div class="bm-stats-cards">
                    <div class="bm-stat-card">
                        <div class="bm-stat-icon">📋</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentor_stats['total_services'] ); ?></h3>
                            <p>Active Services</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card">
                        <div class="bm-stat-icon">📅</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentor_stats['total_bookings'] ); ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card bm-pending">
                        <div class="bm-stat-icon">⏳</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentor_stats['pending_bookings'] ); ?></h3>
                            <p>Pending Bookings</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card bm-revenue">
                        <div class="bm-stat-icon">💰</div>
                        <div class="bm-stat-content">
                            <h3>$<?php echo esc_html( number_format( $mentor_stats['this_month_revenue'], 2 ) ); ?></h3>
                            <p>This Month Revenue</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bm-quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="bm-action-buttons">
                        <a href="?page=booking-master-mentor&tab=services" class="button button-primary">
                            <span class="dashicons dashicons-plus"></span> Create New Service
                        </a>
                        <a href="?page=booking-master-mentor&tab=availability" class="button button-secondary">
                            <span class="dashicons dashicons-calendar-alt"></span> Manage Availability
                        </a>
                        <a href="?page=booking-master-mentor&tab=bookings" class="button button-secondary">
                            <span class="dashicons dashicons-list-view"></span> View Bookings
                        </a>
                        <a href="?page=booking-master-mentor&tab=zoom" class="button button-secondary">
                            <span class="dashicons dashicons-video-alt3"></span> Setup Zoom
                        </a>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="bm-recent-bookings">
                    <h3>Recent Bookings</h3>
                    <?php
                    $recent_bookings = $wpdb->get_results( $wpdb->prepare(
                        "SELECT b.*, s.service_name, u.display_name as mentee_name 
                         FROM $bookings_table b 
                         LEFT JOIN $services_table s ON b.service_id = s.id 
                         LEFT JOIN {$wpdb->users} u ON b.mentee_id = u.ID 
                         WHERE b.mentor_id = %d 
                         ORDER BY b.created_at DESC 
                         LIMIT 5",
                        $mentor_id
                    ) );
                    ?>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Mentee</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $recent_bookings ) : ?>
                                <?php foreach ( $recent_bookings as $booking ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $booking->service_name ); ?></td>
                                        <td><?php echo esc_html( $booking->mentee_name ); ?></td>
                                        <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date ) ) ); ?></td>
                                        <td>
                                            <span class="status status-<?php echo esc_attr( $booking->status ); ?>">
                                                <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo esc_html( number_format( $booking->total_amount, 2 ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'services' ) : ?>
            <div class="bm-mentor-services">
                <div class="bm-services-header">
                    <h2>My Services</h2>
                    <button class="button button-primary" onclick="showServiceForm()">
                        <span class="dashicons dashicons-plus"></span> Add New Service
                    </button>
                </div>
                
                <div id="services-container">
                    <!-- Services will be loaded here -->
                </div>
                
                <!-- Service Form Modal -->
                <div id="service-form-modal" class="bm-modal" style="display: none;">
                    <div class="bm-modal-content">
                        <div class="bm-modal-header">
                            <h3>Service Management</h3>
                            <span class="bm-modal-close">&times;</span>
                        </div>
                        <div class="bm-modal-body">
                            <div id="service-form-content">
                                <!-- Service form will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'bookings' ) : ?>
            <div class="bm-mentor-bookings">
                <h2>My Bookings</h2>
                
                <!-- Booking Filters -->
                <div class="bm-booking-filters">
                    <select id="booking-status-filter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    
                    <input type="date" id="booking-date-filter" placeholder="Filter by date">
                    
                    <button type="button" id="filter-bookings" class="button">Filter</button>
                </div>
                
                <div id="bookings-container">
                    <!-- Bookings will be loaded here -->
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'availability' ) : ?>
            <div class="bm-mentor-availability">
                <?php include_once plugin_dir_path( __FILE__ ) . 'mentor-availability.php'; ?>
            </div>
            
        <?php elseif ( $current_tab === 'calendar' ) : ?>
            <div class="bm-mentor-calendar">
                <h2>Calendar Synchronization</h2>
                
                <div class="bm-calendar-sync-options">
                    <div class="bm-sync-option">
                        <h3>Google Calendar</h3>
                        <p>Sync your bookings with Google Calendar to avoid double bookings.</p>
                        <div id="google-calendar-status">
                            <!-- Status will be loaded via AJAX -->
                        </div>
                        <button class="button button-primary" id="connect-google-calendar">Connect Google Calendar</button>
                    </div>
                    
                    <div class="bm-sync-option">
                        <h3>Outlook Calendar</h3>
                        <p>Sync your bookings with Microsoft Outlook Calendar.</p>
                        <div id="outlook-calendar-status">
                            <!-- Status will be loaded via AJAX -->
                        </div>
                        <button class="button button-primary" id="connect-outlook-calendar">Connect Outlook Calendar</button>
                    </div>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'payments' ) : ?>
            <div class="bm-mentor-payments">
                <h2>Payments & Earnings</h2>
                
                <div class="bm-earnings-summary">
                    <div class="bm-earning-card">
                        <h3>This Month</h3>
                        <p class="amount">$<?php echo number_format( $mentor_stats['this_month_revenue'], 2 ); ?></p>
                    </div>
                    
                    <div class="bm-earning-card">
                        <h3>Total Earnings</h3>
                        <p class="amount">$<?php 
                        $total_earnings = $wpdb->get_var( $wpdb->prepare(
                            "SELECT SUM(total_amount) FROM $bookings_table WHERE mentor_id = %d AND payment_status = 'paid'",
                            $mentor_id
                        ) );
                        echo number_format( $total_earnings ?: 0, 2 );
                        ?></p>
                    </div>
                </div>
                
                <!-- Connect Stripe Account -->
                <div class="bm-stripe-connect">
                    <h3>Stripe Connect</h3>
                    <p>Connect your Stripe account to receive automatic payouts for your bookings.</p>
                    <button class="button button-primary" id="connect-stripe-account">Connect Stripe Account</button>
                </div>
                
                <!-- Payment History -->
                <div class="bm-payment-history">
                    <h3>Payment History</h3>
                    <div id="payment-history-container">
                        <!-- Payment history will be loaded here -->
                    </div>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'zoom' ) : ?>
            <div class="bm-mentor-zoom">
                <h2>Zoom Integration</h2>
                
                <div class="bm-zoom-status">
                    <div id="zoom-connection-status">
                        <!-- Zoom status will be loaded via AJAX -->
                    </div>
                </div>
                
                <div class="bm-zoom-actions">
                    <button class="button button-primary" id="connect-zoom">Connect Zoom Account</button>
                    <button class="button button-secondary" id="disconnect-zoom" style="display: none;">Disconnect Zoom</button>
                    <button class="button button-secondary" id="test-zoom">Test Zoom Connection</button>
                </div>
                
                <div class="bm-zoom-settings">
                    <h3>Zoom Settings</h3>
                    <form id="zoom-settings-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Auto-create meetings</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_create_meetings" value="1" checked>
                                        Automatically create Zoom meetings for confirmed bookings
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Meeting Duration</th>
                                <td>
                                    <select name="meeting_duration">
                                        <option value="service">Use service duration</option>
                                        <option value="30">30 minutes</option>
                                        <option value="60">60 minutes</option>
                                        <option value="90">90 minutes</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="submit" class="button button-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bm-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.bm-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.bm-stat-card.bm-pending {
    border-left: 4px solid #ffb900;
}

.bm-stat-card.bm-revenue {
    border-left: 4px solid #00a32a;
}

.bm-stat-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f1;
    border-radius: 50%;
}

.bm-stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.bm-stat-content p {
    margin: 5px 0 0 0;
    color: #646970;
    font-size: 14px;
}

.bm-quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin: 20px 0;
}

.bm-quick-actions h3 {
    margin-top: 0;
}

.bm-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.bm-action-buttons .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.bm-recent-bookings {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin: 20px 0;
}

.bm-recent-bookings h3 {
    margin-top: 0;
}

.bm-services-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bm-booking-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.bm-booking-filters select,
.bm-booking-filters input {
    padding: 5px 10px;
}

.bm-calendar-sync-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.bm-sync-option {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
}

.bm-earnings-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.bm-earning-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    text-align: center;
}

.bm-earning-card .amount {
    font-size: 24px;
    font-weight: 600;
    color: #00a32a;
    margin: 10px 0;
}

.bm-stripe-connect,
.bm-payment-history,
.bm-zoom-status,
.bm-zoom-actions,
.bm-zoom-settings {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 20px;
}

.status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.bm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bm-modal-content {
    background: #fff;
    border-radius: 5px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.bm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.bm-modal-header h3 {
    margin: 0;
}

.bm-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.bm-modal-body {
    padding: 20px;
}

@media (max-width: 768px) {
    .bm-stats-cards {
        grid-template-columns: 1fr;
    }
    
    .bm-action-buttons {
        flex-direction: column;
    }
    
    .bm-calendar-sync-options {
        grid-template-columns: 1fr;
    }
    
    .bm-services-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .bm-booking-filters {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load initial content based on current tab
    const currentTab = '<?php echo esc_js( $current_tab ); ?>';
    
    if (currentTab === 'services') {
        loadMentorServices();
    } else if (currentTab === 'bookings') {
        loadMentorBookings();
    } else if (currentTab === 'calendar') {
        loadCalendarStatus();
    } else if (currentTab === 'payments') {
        loadPaymentHistory();
    } else if (currentTab === 'zoom') {
        loadZoomStatus();
    }
    
    // Service management functions
    window.showServiceForm = function(serviceId = null) {
        const action = serviceId ? 'bm_get_service_form' : 'bm_get_service_form';
        const data = {
            action: action,
            nonce: '<?php echo wp_create_nonce( 'bm_public_nonce' ); ?>'
        };
        
        if (serviceId) {
            data.service_id = serviceId;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#service-form-content').html(response.data.html);
                    $('#service-form-modal').show();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    };
    
    window.closeServiceForm = function() {
        $('#service-form-modal').hide();
    };
    
    // Close modal when clicking the X
    $('.bm-modal-close').on('click', function() {
        $(this).closest('.bm-modal').hide();
    });
    
    // Close modal when clicking outside
    $('.bm-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Handle service form submission
    $(document).on('submit', '#bm-service-form', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    closeServiceForm();
                    loadMentorServices();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    function loadMentorServices() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_services',
                mentor_id: <?php echo get_current_user_id(); ?>,
                nonce: '<?php echo wp_create_nonce( 'bm_public_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayServices(response.data.services);
                }
            }
        });
    }
    
    function displayServices(services) {
        let html = '<div class="bm-services-grid">';
        
        if (services.length === 0) {
            html += '<div class="no-services"><p>No services found. <a href="#" onclick="showServiceForm()">Create your first service</a></p></div>';
        } else {
            services.forEach(function(service) {
                html += '<div class="bm-service-card">';
                html += '<h4>' + service.service_name + '</h4>';
                html += '<p>' + (service.description || 'No description') + '</p>';
                html += '<div class="service-meta">';
                html += '<span class="price">$' + parseFloat(service.price).toFixed(2) + '</span>';
                html += '<span class="duration">' + service.duration + ' min</span>';
                html += '</div>';
                html += '<div class="service-actions">';
                html += '<button onclick="showServiceForm(' + service.id + ')" class="button button-small">Edit</button>';
                html += '<button onclick="deleteService(' + service.id + ')" class="button button-small">Delete</button>';
                html += '</div>';
                html += '</div>';
            });
        }
        
        html += '</div>';
        $('#services-container').html(html);
    }
    
    function loadMentorBookings() {
        // Implementation for loading bookings
    }
    
    function loadCalendarStatus() {
        // Implementation for loading calendar status
    }
    
    function loadPaymentHistory() {
        // Implementation for loading payment history
    }
    
    function loadZoomStatus() {
        // Implementation for loading Zoom status
    }
});
</script>