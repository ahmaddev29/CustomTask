<?php
/**
 * Mentee Dashboard
 *
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get current mentee ID
$mentee_id = get_current_user_id();
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

// Get mentee statistics
global $wpdb;
$bookings_table = $wpdb->prefix . 'bm_bookings';
$services_table = $wpdb->prefix . 'bm_services';

$mentee_stats = array(
    'total_bookings' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d",
        $mentee_id
    ) ),
    'upcoming_bookings' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d AND status = 'confirmed' AND booking_date > NOW()",
        $mentee_id
    ) ),
    'completed_sessions' => $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE mentee_id = %d AND status = 'completed'",
        $mentee_id
    ) ),
    'total_spent' => $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(total_amount) FROM $bookings_table WHERE mentee_id = %d AND payment_status = 'paid'",
        $mentee_id
    ) ) ?: 0
);
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=booking-master-mentee&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            Overview
        </a>
        <a href="?page=booking-master-mentee&tab=bookings" class="nav-tab <?php echo $current_tab === 'bookings' ? 'nav-tab-active' : ''; ?>">
            My Bookings
        </a>
        <a href="?page=booking-master-mentee&tab=history" class="nav-tab <?php echo $current_tab === 'history' ? 'nav-tab-active' : ''; ?>">
            Session History
        </a>
        <a href="?page=booking-master-mentee&tab=mentors" class="nav-tab <?php echo $current_tab === 'mentors' ? 'nav-tab-active' : ''; ?>">
            My Mentors
        </a>
        <a href="?page=booking-master-mentee&tab=browse" class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
            Browse Services
        </a>
    </nav>

    <div class="tab-content">
        <?php if ( $current_tab === 'overview' ) : ?>
            <div class="bm-mentee-overview">
                <h2>Dashboard Overview</h2>
                
                <!-- Stats Cards -->
                <div class="bm-stats-cards">
                    <div class="bm-stat-card">
                        <div class="bm-stat-icon">📚</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentee_stats['total_bookings'] ); ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card bm-upcoming">
                        <div class="bm-stat-icon">⏰</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentee_stats['upcoming_bookings'] ); ?></h3>
                            <p>Upcoming Sessions</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card bm-completed">
                        <div class="bm-stat-icon">✅</div>
                        <div class="bm-stat-content">
                            <h3><?php echo esc_html( $mentee_stats['completed_sessions'] ); ?></h3>
                            <p>Completed Sessions</p>
                        </div>
                    </div>
                    
                    <div class="bm-stat-card bm-spent">
                        <div class="bm-stat-icon">💰</div>
                        <div class="bm-stat-content">
                            <h3>$<?php echo esc_html( number_format( $mentee_stats['total_spent'], 2 ) ); ?></h3>
                            <p>Total Investment</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bm-quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="bm-action-buttons">
                        <a href="?page=booking-master-mentee&tab=browse" class="button button-primary">
                            <span class="dashicons dashicons-search"></span> Browse Services
                        </a>
                        <a href="?page=booking-master-mentee&tab=bookings" class="button button-secondary">
                            <span class="dashicons dashicons-calendar-alt"></span> View My Bookings
                        </a>
                        <a href="?page=booking-master-mentee&tab=mentors" class="button button-secondary">
                            <span class="dashicons dashicons-groups"></span> My Mentors
                        </a>
                    </div>
                </div>
                
                <!-- Upcoming Sessions -->
                <div class="bm-upcoming-sessions">
                    <h3>Upcoming Sessions</h3>
                    <?php
                    $upcoming_bookings = $wpdb->get_results( $wpdb->prepare(
                        "SELECT b.*, s.service_name, u.display_name as mentor_name 
                         FROM $bookings_table b 
                         LEFT JOIN $services_table s ON b.service_id = s.id 
                         LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID 
                         WHERE b.mentee_id = %d AND b.status = 'confirmed' AND b.booking_date > NOW()
                         ORDER BY b.booking_date ASC 
                         LIMIT 5",
                        $mentee_id
                    ) );
                    ?>
                    
                    <?php if ( $upcoming_bookings ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Mentor</th>
                                    <th>Date & Time</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $upcoming_bookings as $booking ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $booking->service_name ); ?></td>
                                        <td><?php echo esc_html( $booking->mentor_name ); ?></td>
                                        <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date ) ) ); ?></td>
                                        <td>$<?php echo esc_html( number_format( $booking->total_amount, 2 ) ); ?></td>
                                        <td>
                                            <?php if ( $booking->zoom_join_url ) : ?>
                                                <a href="<?php echo esc_url( $booking->zoom_join_url ); ?>" class="button button-small button-primary" target="_blank">Join Meeting</a>
                                            <?php endif; ?>
                                            <button class="button button-small" onclick="viewBookingDetails(<?php echo $booking->id; ?>)">Details</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="bm-no-sessions">
                            <p>No upcoming sessions. <a href="?page=booking-master-mentee&tab=browse">Browse services</a> to book your next session!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'bookings' ) : ?>
            <div class="bm-mentee-bookings">
                <div class="bm-bookings-header">
                    <h2>My Bookings</h2>
                    <a href="?page=booking-master-mentee&tab=browse" class="button button-primary">Book New Session</a>
                </div>
                
                <!-- Booking Filters -->
                <div class="bm-booking-filters">
                    <select id="booking-status-filter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    
                    <input type="month" id="booking-month-filter" placeholder="Filter by month">
                    
                    <button type="button" id="filter-bookings" class="button">Filter</button>
                </div>
                
                <div id="bookings-container">
                    <!-- Bookings will be loaded here via AJAX -->
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'history' ) : ?>
            <div class="bm-session-history">
                <h2>Session History</h2>
                
                <?php
                $completed_bookings = $wpdb->get_results( $wpdb->prepare(
                    "SELECT b.*, s.service_name, u.display_name as mentor_name 
                     FROM $bookings_table b 
                     LEFT JOIN $services_table s ON b.service_id = s.id 
                     LEFT JOIN {$wpdb->users} u ON b.mentor_id = u.ID 
                     WHERE b.mentee_id = %d AND b.status = 'completed'
                     ORDER BY b.booking_date DESC",
                    $mentee_id
                ) );
                ?>
                
                <?php if ( $completed_bookings ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Mentor</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $completed_bookings as $booking ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $booking->service_name ); ?></td>
                                    <td><?php echo esc_html( $booking->mentor_name ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></td>
                                    <td><?php echo esc_html( $booking->duration ?? 'N/A' ); ?> min</td>
                                    <td>$<?php echo esc_html( number_format( $booking->total_amount, 2 ) ); ?></td>
                                    <td>
                                        <?php if ( $booking->rating ) : ?>
                                            <div class="bm-rating">
                                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                    <span class="star <?php echo $i <= $booking->rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php else : ?>
                                            <button class="button button-small" onclick="rateSession(<?php echo $booking->id; ?>)">Rate Session</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="button button-small" onclick="viewBookingDetails(<?php echo $booking->id; ?>)">View Details</button>
                                        <button class="button button-small" onclick="bookAgain(<?php echo $booking->service_id; ?>)">Book Again</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="bm-no-history">
                        <p>No completed sessions yet. Start your learning journey by <a href="?page=booking-master-mentee&tab=browse">browsing services</a>!</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ( $current_tab === 'mentors' ) : ?>
            <div class="bm-my-mentors">
                <h2>My Mentors</h2>
                
                <?php
                $my_mentors = $wpdb->get_results( $wpdb->prepare(
                    "SELECT DISTINCT u.ID, u.display_name, u.user_email, 
                            COUNT(b.id) as session_count,
                            MAX(b.booking_date) as last_session,
                            AVG(b.rating) as avg_rating
                     FROM {$wpdb->users} u 
                     INNER JOIN $bookings_table b ON u.ID = b.mentor_id 
                     WHERE b.mentee_id = %d 
                     GROUP BY u.ID 
                     ORDER BY session_count DESC",
                    $mentee_id
                ) );
                ?>
                
                <?php if ( $my_mentors ) : ?>
                    <div class="bm-mentors-grid">
                        <?php foreach ( $my_mentors as $mentor ) : ?>
                            <div class="bm-mentor-card">
                                <div class="bm-mentor-header">
                                    <div class="bm-mentor-avatar">
                                        <?php echo get_avatar( $mentor->ID, 60 ); ?>
                                    </div>
                                    <div class="bm-mentor-info">
                                        <h4><?php echo esc_html( $mentor->display_name ); ?></h4>
                                        <?php if ( $mentor->avg_rating ) : ?>
                                            <div class="bm-mentor-rating">
                                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                    <span class="star <?php echo $i <= round( $mentor->avg_rating ) ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                                <span class="rating-text">(<?php echo number_format( $mentor->avg_rating, 1 ); ?>)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="bm-mentor-stats">
                                    <div class="stat">
                                        <span class="stat-number"><?php echo $mentor->session_count; ?></span>
                                        <span class="stat-label">Sessions</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-date"><?php echo date( 'M Y', strtotime( $mentor->last_session ) ); ?></span>
                                        <span class="stat-label">Last Session</span>
                                    </div>
                                </div>
                                
                                <div class="bm-mentor-actions">
                                    <button class="button button-primary" onclick="viewMentorServices(<?php echo $mentor->ID; ?>)">View Services</button>
                                    <button class="button button-secondary" onclick="contactMentor(<?php echo $mentor->ID; ?>)">Message</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="bm-no-mentors">
                        <p>You haven't worked with any mentors yet. <a href="?page=booking-master-mentee&tab=browse">Browse services</a> to find your first mentor!</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ( $current_tab === 'browse' ) : ?>
            <div class="bm-browse-services">
                <h2>Browse Services</h2>
                
                <div class="bm-service-filters">
                    <input type="text" id="service-search" placeholder="Search services...">
                    <select id="category-filter">
                        <option value="">All Categories</option>
                        <option value="technology">Technology</option>
                        <option value="business">Business</option>
                        <option value="design">Design</option>
                        <option value="marketing">Marketing</option>
                        <option value="personal-development">Personal Development</option>
                    </select>
                    <select id="price-range-filter">
                        <option value="">Any Price</option>
                        <option value="0-50">$0 - $50</option>
                        <option value="50-100">$50 - $100</option>
                        <option value="100-200">$100 - $200</option>
                        <option value="200+">$200+</option>
                    </select>
                    <button type="button" id="filter-services" class="button">Search</button>
                </div>
                
                <div id="services-container">
                    <!-- Services will be loaded here via AJAX -->
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

.bm-stat-card.bm-upcoming {
    border-left: 4px solid #ff9800;
}

.bm-stat-card.bm-completed {
    border-left: 4px solid #4caf50;
}

.bm-stat-card.bm-spent {
    border-left: 4px solid #2196f3;
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

.bm-upcoming-sessions,
.bm-session-history,
.bm-my-mentors,
.bm-browse-services {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin: 20px 0;
}

.bm-bookings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bm-booking-filters,
.bm-service-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.bm-mentors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.bm-mentor-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
}

.bm-mentor-header {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.bm-mentor-info h4 {
    margin: 0 0 5px 0;
    color: #1d2327;
}

.bm-mentor-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.star {
    color: #ddd;
    font-size: 14px;
}

.star.filled {
    color: #ffb900;
}

.rating-text {
    font-size: 12px;
    color: #646970;
}

.bm-mentor-stats {
    display: flex;
    justify-content: space-around;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.stat {
    text-align: center;
}

.stat-number,
.stat-date {
    display: block;
    font-weight: 600;
    color: #1d2327;
}

.stat-label {
    font-size: 12px;
    color: #646970;
}

.bm-mentor-actions {
    display: flex;
    gap: 10px;
}

.bm-no-sessions,
.bm-no-history,
.bm-no-mentors {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

@media (max-width: 768px) {
    .bm-stats-cards {
        grid-template-columns: 1fr;
    }
    
    .bm-action-buttons {
        flex-direction: column;
    }
    
    .bm-bookings-header {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .bm-booking-filters,
    .bm-service-filters {
        flex-direction: column;
    }
    
    .bm-mentors-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load initial content based on current tab
    const currentTab = '<?php echo esc_js( $current_tab ); ?>';
    
    if (currentTab === 'bookings') {
        loadMenteeBookings();
    } else if (currentTab === 'browse') {
        loadBrowseServices();
    }
    
    // Filter handlers
    $('#filter-bookings').on('click', function() {
        loadMenteeBookings();
    });
    
    $('#filter-services').on('click', function() {
        loadBrowseServices();
    });
    
    function loadMenteeBookings() {
        const status = $('#booking-status-filter').val();
        const month = $('#booking-month-filter').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_mentee_bookings',
                status: status,
                month: month,
                nonce: '<?php echo wp_create_nonce( 'bm_admin_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayBookings(response.data.bookings);
                }
            }
        });
    }
    
    function loadBrowseServices() {
        const search = $('#service-search').val();
        const category = $('#category-filter').val();
        const priceRange = $('#price-range-filter').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_browse_services',
                search: search,
                category: category,
                price_range: priceRange,
                nonce: '<?php echo wp_create_nonce( 'bm_admin_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayServices(response.data.services);
                }
            }
        });
    }
    
    function displayBookings(bookings) {
        // Implementation for displaying bookings
        console.log('Display bookings:', bookings);
    }
    
    function displayServices(services) {
        // Implementation for displaying services
        console.log('Display services:', services);
    }
    
    // Global functions
    window.viewBookingDetails = function(bookingId) {
        // Implementation for viewing booking details
        console.log('View booking details:', bookingId);
    };
    
    window.rateSession = function(bookingId) {
        // Implementation for rating session
        console.log('Rate session:', bookingId);
    };
    
    window.bookAgain = function(serviceId) {
        // Implementation for booking again
        console.log('Book again:', serviceId);
    };
    
    window.viewMentorServices = function(mentorId) {
        // Implementation for viewing mentor services
        console.log('View mentor services:', mentorId);
    };
    
    window.contactMentor = function(mentorId) {
        // Implementation for contacting mentor
        console.log('Contact mentor:', mentorId);
    };
});
</script>