<?php
/**
 * Payments & Transactions Page
 *
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get current page
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'transactions';

// Get database instance
global $wpdb;
$transactions_table = $wpdb->prefix . 'bm_payment_transactions';
$bookings_table = $wpdb->prefix . 'bm_bookings';
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=booking-master-payments&tab=transactions" class="nav-tab <?php echo $current_tab === 'transactions' ? 'nav-tab-active' : ''; ?>">
            Transactions
        </a>
        <a href="?page=booking-master-payments&tab=refunds" class="nav-tab <?php echo $current_tab === 'refunds' ? 'nav-tab-active' : ''; ?>">
            Refunds
        </a>
        <a href="?page=booking-master-payments&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            Payment Settings
        </a>
    </nav>

    <div class="tab-content">
        <?php if ( $current_tab === 'transactions' ) : ?>
            <div class="bm-transactions-tab">
                <h2>Payment Transactions</h2>
                
                <!-- Transaction Filters -->
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="payment_method" id="payment-method-filter">
                            <option value="">All Payment Methods</option>
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                        </select>
                        
                        <select name="payment_status" id="payment-status-filter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                        
                        <input type="date" name="start_date" id="start-date-filter" placeholder="Start Date">
                        <input type="date" name="end_date" id="end-date-filter" placeholder="End Date">
                        
                        <button type="button" id="filter-transactions" class="button">Filter</button>
                        <button type="button" id="export-transactions" class="button">Export CSV</button>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <table class="wp-list-table widefat fixed striped" id="transactions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-tbody">
                        <!-- Transactions will be loaded here via AJAX -->
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="transaction-count">0 items</span>
                        <span class="pagination-links">
                            <button class="button" id="prev-page" disabled>‹</button>
                            <span class="current-page">1</span>
                            <button class="button" id="next-page">›</button>
                        </span>
                    </div>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'refunds' ) : ?>
            <div class="bm-refunds-tab">
                <h2>Refunds Management</h2>
                
                <!-- Refunds Table -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Original Amount</th>
                            <th>Refund Amount</th>
                            <th>Refund Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get refunded bookings
                        $refunded_bookings = $wpdb->get_results(
                            "SELECT b.*, u.display_name as customer_name 
                             FROM $bookings_table b 
                             LEFT JOIN {$wpdb->users} u ON b.mentee_id = u.ID 
                             WHERE b.payment_status = 'refunded' 
                             ORDER BY b.refund_date DESC 
                             LIMIT 50"
                        );
                        
                        if ( $refunded_bookings ) {
                            foreach ( $refunded_bookings as $booking ) {
                                echo '<tr>';
                                echo '<td>' . $booking->id . '</td>';
                                echo '<td>' . esc_html( $booking->customer_name ) . '</td>';
                                echo '<td>$' . number_format( $booking->total_amount, 2 ) . '</td>';
                                echo '<td>$' . number_format( $booking->refund_amount, 2 ) . '</td>';
                                echo '<td>' . date( 'Y-m-d H:i', strtotime( $booking->refund_date ) ) . '</td>';
                                echo '<td><span class="status-refunded">Refunded</span></td>';
                                echo '<td>';
                                echo '<a href="#" class="button button-small view-details" data-booking-id="' . $booking->id . '">View Details</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7">No refunds found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ( $current_tab === 'settings' ) : ?>
            <div class="bm-payment-settings-tab">
                <h2>Payment Gateway Settings</h2>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'booking_master_payment_settings' );
                    $settings = get_option( 'booking_master_settings', array() );
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Payments</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[payment_enabled]" value="1" <?php checked( $settings['payment_enabled'] ?? false, true ); ?> />
                                    Enable payment processing for bookings
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Require Payment</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[require_payment_for_booking]" value="1" <?php checked( $settings['require_payment_for_booking'] ?? false, true ); ?> />
                                    Require payment before booking confirmation
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Stripe Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Stripe</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[stripe_enabled]" value="1" <?php checked( $settings['stripe_enabled'] ?? false, true ); ?> />
                                    Enable Stripe payment processing
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Stripe Publishable Key</th>
                            <td>
                                <input type="text" name="booking_master_settings[stripe_publishable_key]" value="<?php echo esc_attr( $settings['stripe_publishable_key'] ?? '' ); ?>" class="regular-text" />
                                <p class="description">Your Stripe publishable key (starts with pk_)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Stripe Secret Key</th>
                            <td>
                                <input type="password" name="booking_master_settings[stripe_secret_key]" value="<?php echo esc_attr( $settings['stripe_secret_key'] ?? '' ); ?>" class="regular-text" />
                                <p class="description">Your Stripe secret key (starts with sk_)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>PayPal Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable PayPal</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[paypal_enabled]" value="1" <?php checked( $settings['paypal_enabled'] ?? false, true ); ?> />
                                    Enable PayPal payment processing
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">PayPal Client ID</th>
                            <td>
                                <input type="text" name="booking_master_settings[paypal_client_id]" value="<?php echo esc_attr( $settings['paypal_client_id'] ?? '' ); ?>" class="regular-text" />
                                <p class="description">Your PayPal application client ID</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">PayPal Client Secret</th>
                            <td>
                                <input type="password" name="booking_master_settings[paypal_client_secret]" value="<?php echo esc_attr( $settings['paypal_client_secret'] ?? '' ); ?>" class="regular-text" />
                                <p class="description">Your PayPal application client secret</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Sandbox Mode</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[paypal_sandbox]" value="1" <?php checked( $settings['paypal_sandbox'] ?? true, true ); ?> />
                                    Enable sandbox mode for testing
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Refund Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Refund Policy</th>
                            <td>
                                <select name="booking_master_settings[refund_policy]">
                                    <option value="full" <?php selected( $settings['refund_policy'] ?? 'full', 'full' ); ?>>Full Refund</option>
                                    <option value="partial" <?php selected( $settings['refund_policy'] ?? 'full', 'partial' ); ?>>Partial Refund</option>
                                    <option value="no_refund" <?php selected( $settings['refund_policy'] ?? 'full', 'no_refund' ); ?>>No Refund</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Cancellation Hours</th>
                            <td>
                                <input type="number" name="booking_master_settings[cancellation_hours]" value="<?php echo esc_attr( $settings['cancellation_hours'] ?? 24 ); ?>" class="small-text" />
                                <p class="description">Hours before booking when cancellation is allowed</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tablenav {
    margin-bottom: 20px;
}

.tablenav .actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.tablenav .actions input,
.tablenav .actions select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.status-completed {
    background: #46b450;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-pending {
    background: #ffb900;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-failed {
    background: #dc3232;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-refunded {
    background: #666;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.pagination-links {
    display: flex;
    gap: 5px;
    align-items: center;
}

.current-page {
    padding: 5px 10px;
    background: #0073aa;
    color: white;
    border-radius: 3px;
}

@media (max-width: 768px) {
    .tablenav .actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tablenav .actions input,
    .tablenav .actions select,
    .tablenav .actions button {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    loadTransactions();
    
    // Filter transactions
    $('#filter-transactions').on('click', function() {
        loadTransactions();
    });
    
    // Export transactions
    $('#export-transactions').on('click', function() {
        exportTransactions();
    });
    
    // Pagination
    $('#prev-page').on('click', function() {
        if (!$(this).is(':disabled')) {
            // Implement pagination
        }
    });
    
    $('#next-page').on('click', function() {
        if (!$(this).is(':disabled')) {
            // Implement pagination
        }
    });
    
    function loadTransactions() {
        const filters = {
            action: 'bm_get_payment_transactions',
            payment_method: $('#payment-method-filter').val(),
            payment_status: $('#payment-status-filter').val(),
            start_date: $('#start-date-filter').val(),
            end_date: $('#end-date-filter').val(),
            nonce: '<?php echo wp_create_nonce( 'bm_payments_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success) {
                    displayTransactions(response.data.transactions);
                    $('#transaction-count').text(response.data.total + ' items');
                } else {
                    $('#transactions-tbody').html('<tr><td colspan="9">Error loading transactions</td></tr>');
                }
            },
            error: function() {
                $('#transactions-tbody').html('<tr><td colspan="9">Error loading transactions</td></tr>');
            }
        });
    }
    
    function displayTransactions(transactions) {
        let html = '';
        
        if (transactions.length === 0) {
            html = '<tr><td colspan="9">No transactions found</td></tr>';
        } else {
            transactions.forEach(function(transaction) {
                const statusClass = 'status-' + transaction.status;
                html += '<tr>';
                html += '<td>' + transaction.id + '</td>';
                html += '<td>' + transaction.booking_id + '</td>';
                html += '<td>' + (transaction.customer_name || 'N/A') + '</td>';
                html += '<td>$' + parseFloat(transaction.amount).toFixed(2) + '</td>';
                html += '<td>' + transaction.payment_method.toUpperCase() + '</td>';
                html += '<td><span class="' + statusClass + '">' + transaction.status.toUpperCase() + '</span></td>';
                html += '<td>' + transaction.transaction_id + '</td>';
                html += '<td>' + transaction.created_at + '</td>';
                html += '<td>';
                html += '<button class="button button-small view-transaction" data-id="' + transaction.id + '">View</button>';
                if (transaction.status === 'completed') {
                    html += ' <button class="button button-small process-refund" data-id="' + transaction.id + '">Refund</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#transactions-tbody').html(html);
    }
    
    function exportTransactions() {
        const filters = {
            action: 'bm_export_payment_transactions',
            payment_method: $('#payment-method-filter').val(),
            payment_status: $('#payment-status-filter').val(),
            start_date: $('#start-date-filter').val(),
            end_date: $('#end-date-filter').val(),
            nonce: '<?php echo wp_create_nonce( 'bm_payments_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success) {
                    window.open(response.data.download_url, '_blank');
                } else {
                    alert('Error exporting transactions');
                }
            }
        });
    }
    
    // View transaction details
    $(document).on('click', '.view-transaction', function() {
        const transactionId = $(this).data('id');
        // Implement transaction details modal
        alert('Transaction details for ID: ' + transactionId);
    });
    
    // Process refund
    $(document).on('click', '.process-refund', function() {
        const transactionId = $(this).data('id');
        if (confirm('Are you sure you want to process a refund for this transaction?')) {
            processRefund(transactionId);
        }
    });
    
    function processRefund(transactionId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_process_refund',
                transaction_id: transactionId,
                nonce: '<?php echo wp_create_nonce( 'bm_payments_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Refund processed successfully');
                    loadTransactions();
                } else {
                    alert('Error processing refund: ' + response.data.message);
                }
            }
        });
    }
});
</script>