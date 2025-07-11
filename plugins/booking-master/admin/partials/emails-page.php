<?php
/**
 * Email Management Page
 *
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'templates';

// Get database instance
global $wpdb;
$email_log_table = $wpdb->prefix . 'bm_email_log';
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=booking-master-emails&tab=templates" class="nav-tab <?php echo $current_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
            Email Templates
        </a>
        <a href="?page=booking-master-emails&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            Email Settings
        </a>
        <a href="?page=booking-master-emails&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
            Email Logs
        </a>
        <a href="?page=booking-master-emails&tab=test" class="nav-tab <?php echo $current_tab === 'test' ? 'nav-tab-active' : ''; ?>">
            Test Email
        </a>
    </nav>

    <div class="tab-content">
        <?php if ( $current_tab === 'templates' ) : ?>
            <div class="bm-email-templates-tab">
                <h2>Email Templates</h2>
                <p>Customize the email templates sent to mentors and mentees.</p>
                
                <div class="email-template-list">
                    <?php
                    $templates = array(
                        'booking-confirmation-mentee' => array(
                            'title' => 'Booking Confirmation (Mentee)',
                            'description' => 'Email sent to mentee when booking is confirmed',
                            'variables' => array('{user_name}', '{service_name}', '{mentor_name}', '{booking_date}', '{booking_time}', '{amount}')
                        ),
                        'booking-notification-mentor' => array(
                            'title' => 'Booking Notification (Mentor)',
                            'description' => 'Email sent to mentor when new booking is received',
                            'variables' => array('{user_name}', '{service_name}', '{mentee_name}', '{booking_date}', '{booking_time}', '{amount}')
                        ),
                        'booking-reminder-mentee' => array(
                            'title' => 'Booking Reminder (Mentee)',
                            'description' => 'Reminder email sent to mentee before session',
                            'variables' => array('{user_name}', '{service_name}', '{mentor_name}', '{booking_date}', '{booking_time}', '{zoom_join_url}')
                        ),
                        'booking-reminder-mentor' => array(
                            'title' => 'Booking Reminder (Mentor)',
                            'description' => 'Reminder email sent to mentor before session',
                            'variables' => array('{user_name}', '{service_name}', '{mentee_name}', '{booking_date}', '{booking_time}', '{zoom_start_url}')
                        ),
                        'booking-cancelled-mentee' => array(
                            'title' => 'Booking Cancelled (Mentee)',
                            'description' => 'Email sent to mentee when booking is cancelled',
                            'variables' => array('{user_name}', '{service_name}', '{mentor_name}', '{booking_date}', '{booking_time}', '{refund_info}')
                        ),
                        'booking-cancelled-mentor' => array(
                            'title' => 'Booking Cancelled (Mentor)',
                            'description' => 'Email sent to mentor when booking is cancelled',
                            'variables' => array('{user_name}', '{service_name}', '{mentee_name}', '{booking_date}', '{booking_time}')
                        ),
                        'payment-confirmation' => array(
                            'title' => 'Payment Confirmation',
                            'description' => 'Email sent when payment is successful',
                            'variables' => array('{user_name}', '{service_name}', '{amount}', '{payment_method}', '{transaction_id}')
                        ),
                        'booking-follow-up' => array(
                            'title' => 'Follow-up Email',
                            'description' => 'Email sent after completed session',
                            'variables' => array('{user_name}', '{service_name}', '{mentor_name}', '{feedback_url}', '{book_again_url}')
                        )
                    );
                    ?>
                    
                    <?php foreach ( $templates as $template_id => $template ) : ?>
                        <div class="email-template-item">
                            <div class="template-header">
                                <h3><?php echo esc_html( $template['title'] ); ?></h3>
                                <div class="template-actions">
                                    <button class="button edit-template" data-template-id="<?php echo esc_attr( $template_id ); ?>">Edit</button>
                                    <button class="button preview-template" data-template-id="<?php echo esc_attr( $template_id ); ?>">Preview</button>
                                </div>
                            </div>
                            <p class="template-description"><?php echo esc_html( $template['description'] ); ?></p>
                            <div class="template-variables">
                                <strong>Available Variables:</strong>
                                <?php echo implode( ', ', array_map( 'esc_html', $template['variables'] ) ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Template Editor Modal -->
                <div id="template-editor-modal" class="bm-modal" style="display: none;">
                    <div class="bm-modal-content">
                        <div class="bm-modal-header">
                            <h3 id="template-title">Edit Email Template</h3>
                            <button class="bm-modal-close">&times;</button>
                        </div>
                        <div class="bm-modal-body">
                            <form id="template-editor-form">
                                <input type="hidden" id="template-id" name="template_id">
                                
                                <div class="form-group">
                                    <label for="template-subject">Subject Line:</label>
                                    <input type="text" id="template-subject" name="subject" class="widefat" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="template-content">Email Content:</label>
                                    <textarea id="template-content" name="content" rows="15" class="widefat" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="template-enabled" name="enabled" value="1">
                                        Enable this email template
                                    </label>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary">Save Template</button>
                                    <button type="button" class="button button-secondary send-test-email">Send Test Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ( $current_tab === 'settings' ) : ?>
            <div class="bm-email-settings-tab">
                <h2>Email Settings</h2>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'booking_master_email_settings' );
                    $settings = get_option( 'booking_master_settings', array() );
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Email Notifications</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[email_notifications]" value="1" <?php checked( $settings['email_notifications'] ?? true, true ); ?> />
                                    Enable email notifications system
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">From Name</th>
                            <td>
                                <input type="text" name="booking_master_settings[email_from_name]" value="<?php echo esc_attr( $settings['email_from_name'] ?? get_bloginfo( 'name' ) ); ?>" class="regular-text" />
                                <p class="description">The name that appears in the "From" field of emails</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">From Email</th>
                            <td>
                                <input type="email" name="booking_master_settings[email_from_email]" value="<?php echo esc_attr( $settings['email_from_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text" />
                                <p class="description">The email address that appears in the "From" field of emails</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Reply-To Email</th>
                            <td>
                                <input type="email" name="booking_master_settings[email_reply_to]" value="<?php echo esc_attr( $settings['email_reply_to'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text" />
                                <p class="description">The email address for replies</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Email Types</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Booking Confirmation</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[booking_confirmation_email]" value="1" <?php checked( $settings['booking_confirmation_email'] ?? true, true ); ?> />
                                    Send confirmation email when booking is created
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Booking Reminders</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[booking_reminder_email]" value="1" <?php checked( $settings['booking_reminder_email'] ?? true, true ); ?> />
                                    Send reminder emails before sessions
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">24-Hour Reminder</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[reminder_24h_enabled]" value="1" <?php checked( $settings['reminder_24h_enabled'] ?? true, true ); ?> />
                                    Send reminder 24 hours before session
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">1-Hour Reminder</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[reminder_1h_enabled]" value="1" <?php checked( $settings['reminder_1h_enabled'] ?? true, true ); ?> />
                                    Send reminder 1 hour before session
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Cancellation Emails</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[booking_cancellation_email]" value="1" <?php checked( $settings['booking_cancellation_email'] ?? true, true ); ?> />
                                    Send email when booking is cancelled
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Payment Confirmation</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[payment_confirmation_email]" value="1" <?php checked( $settings['payment_confirmation_email'] ?? true, true ); ?> />
                                    Send email when payment is successful
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Follow-up Emails</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="booking_master_settings[follow_up_email_enabled]" value="1" <?php checked( $settings['follow_up_email_enabled'] ?? true, true ); ?> />
                                    Send follow-up email after completed sessions
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
        <?php elseif ( $current_tab === 'logs' ) : ?>
            <div class="bm-email-logs-tab">
                <h2>Email Logs</h2>
                
                <!-- Email Log Filters -->
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select id="email-status-filter">
                            <option value="">All Statuses</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
                        
                        <select id="email-template-filter">
                            <option value="">All Templates</option>
                            <option value="booking-confirmation-mentee">Booking Confirmation (Mentee)</option>
                            <option value="booking-notification-mentor">Booking Notification (Mentor)</option>
                            <option value="booking-reminder">Booking Reminder</option>
                            <option value="booking-cancelled">Booking Cancelled</option>
                            <option value="payment-confirmation">Payment Confirmation</option>
                        </select>
                        
                        <input type="date" id="email-date-filter" placeholder="Date">
                        
                        <button type="button" id="filter-emails" class="button">Filter</button>
                        <button type="button" id="clear-logs" class="button">Clear Logs</button>
                    </div>
                </div>
                
                <!-- Email Logs Table -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="email-logs-tbody">
                        <?php
                        // Get recent email logs
                        $email_logs = $wpdb->get_results(
                            "SELECT * FROM $email_log_table 
                             ORDER BY sent_at DESC 
                             LIMIT 50"
                        );
                        
                        if ( $email_logs ) {
                            foreach ( $email_logs as $log ) {
                                $status_class = $log->status === 'sent' ? 'status-sent' : 'status-failed';
                                echo '<tr>';
                                echo '<td>' . $log->id . '</td>';
                                echo '<td>' . esc_html( $log->recipient_email ) . '</td>';
                                echo '<td>' . esc_html( $log->subject ) . '</td>';
                                echo '<td>' . esc_html( $log->template_name ) . '</td>';
                                echo '<td><span class="' . $status_class . '">' . ucfirst( $log->status ) . '</span></td>';
                                echo '<td>' . date( 'Y-m-d H:i:s', strtotime( $log->sent_at ) ) . '</td>';
                                echo '<td>';
                                echo '<button class="button button-small view-email-log" data-log-id="' . $log->id . '">View</button>';
                                if ( $log->status === 'failed' ) {
                                    echo ' <button class="button button-small resend-email" data-log-id="' . $log->id . '">Resend</button>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7">No email logs found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ( $current_tab === 'test' ) : ?>
            <div class="bm-test-email-tab">
                <h2>Test Email</h2>
                <p>Send a test email to verify your email settings are working correctly.</p>
                
                <form id="test-email-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Test Email Address</th>
                            <td>
                                <input type="email" id="test-email-address" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text" required />
                                <p class="description">Enter the email address to send the test email to</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Template</th>
                            <td>
                                <select id="test-template" name="template" required>
                                    <option value="">Select a template</option>
                                    <option value="booking-confirmation-mentee">Booking Confirmation (Mentee)</option>
                                    <option value="booking-notification-mentor">Booking Notification (Mentor)</option>
                                    <option value="booking-reminder-mentee">Booking Reminder (Mentee)</option>
                                    <option value="booking-reminder-mentor">Booking Reminder (Mentor)</option>
                                    <option value="booking-cancelled-mentee">Booking Cancelled (Mentee)</option>
                                    <option value="payment-confirmation">Payment Confirmation</option>
                                </select>
                                <p class="description">Select the email template to test</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">Send Test Email</button>
                    </p>
                </form>
                
                <div id="test-email-result" style="display: none;"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.email-template-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 20px;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.template-header h3 {
    margin: 0;
}

.template-actions {
    display: flex;
    gap: 10px;
}

.template-description {
    color: #666;
    margin-bottom: 10px;
}

.template-variables {
    font-size: 12px;
    color: #999;
    background: #f9f9f9;
    padding: 10px;
    border-radius: 3px;
}

.bm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
}

.bm-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 5px;
    width: 90%;
    max-width: 800px;
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

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
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

.status-sent {
    background: #46b450;
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

#test-email-result {
    margin-top: 20px;
    padding: 10px;
    border-radius: 5px;
}

#test-email-result.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#test-email-result.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .template-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .template-actions {
        margin-top: 10px;
    }
    
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
    // Edit template
    $('.edit-template').on('click', function() {
        const templateId = $(this).data('template-id');
        loadTemplate(templateId);
    });
    
    // Preview template
    $('.preview-template').on('click', function() {
        const templateId = $(this).data('template-id');
        previewTemplate(templateId);
    });
    
    // Close modal
    $('.bm-modal-close').on('click', function() {
        $('#template-editor-modal').hide();
    });
    
    // Save template
    $('#template-editor-form').on('submit', function(e) {
        e.preventDefault();
        saveTemplate();
    });
    
    // Send test email from template editor
    $('.send-test-email').on('click', function() {
        sendTestEmailFromTemplate();
    });
    
    // Test email form
    $('#test-email-form').on('submit', function(e) {
        e.preventDefault();
        sendTestEmail();
    });
    
    // Filter email logs
    $('#filter-emails').on('click', function() {
        filterEmailLogs();
    });
    
    // Clear logs
    $('#clear-logs').on('click', function() {
        if (confirm('Are you sure you want to clear all email logs?')) {
            clearEmailLogs();
        }
    });
    
    // View email log
    $('.view-email-log').on('click', function() {
        const logId = $(this).data('log-id');
        viewEmailLog(logId);
    });
    
    // Resend email
    $('.resend-email').on('click', function() {
        const logId = $(this).data('log-id');
        resendEmail(logId);
    });
    
    function loadTemplate(templateId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_email_template',
                template_id: templateId,
                nonce: '<?php echo wp_create_nonce( 'bm_email_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const template = response.data.template;
                    $('#template-id').val(templateId);
                    $('#template-title').text('Edit Template: ' + template.title);
                    $('#template-subject').val(template.subject);
                    $('#template-content').val(template.content);
                    $('#template-enabled').prop('checked', template.enabled);
                    $('#template-editor-modal').show();
                } else {
                    alert('Error loading template: ' + response.data.message);
                }
            }
        });
    }
    
    function saveTemplate() {
        const formData = {
            action: 'bm_save_email_template',
            template_id: $('#template-id').val(),
            subject: $('#template-subject').val(),
            content: $('#template-content').val(),
            enabled: $('#template-enabled').is(':checked') ? 1 : 0,
            nonce: '<?php echo wp_create_nonce( 'bm_email_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Template saved successfully!');
                    $('#template-editor-modal').hide();
                    location.reload();
                } else {
                    alert('Error saving template: ' + response.data.message);
                }
            }
        });
    }
    
    function sendTestEmail() {
        const emailAddress = $('#test-email-address').val();
        const template = $('#test-template').val();
        
        if (!emailAddress || !template) {
            alert('Please fill in all fields');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_send_test_email',
                email_address: emailAddress,
                template: template,
                nonce: '<?php echo wp_create_nonce( 'bm_email_nonce' ); ?>'
            },
            success: function(response) {
                const resultDiv = $('#test-email-result');
                if (response.success) {
                    resultDiv.removeClass('error').addClass('success');
                    resultDiv.text('Test email sent successfully!');
                } else {
                    resultDiv.removeClass('success').addClass('error');
                    resultDiv.text('Error sending test email: ' + response.data.message);
                }
                resultDiv.show();
            }
        });
    }
    
    function filterEmailLogs() {
        const filters = {
            action: 'bm_filter_email_logs',
            status: $('#email-status-filter').val(),
            template: $('#email-template-filter').val(),
            date: $('#email-date-filter').val(),
            nonce: '<?php echo wp_create_nonce( 'bm_email_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success) {
                    displayEmailLogs(response.data.logs);
                } else {
                    alert('Error filtering logs: ' + response.data.message);
                }
            }
        });
    }
    
    function displayEmailLogs(logs) {
        let html = '';
        
        if (logs.length === 0) {
            html = '<tr><td colspan="7">No email logs found</td></tr>';
        } else {
            logs.forEach(function(log) {
                const statusClass = log.status === 'sent' ? 'status-sent' : 'status-failed';
                html += '<tr>';
                html += '<td>' + log.id + '</td>';
                html += '<td>' + log.recipient_email + '</td>';
                html += '<td>' + log.subject + '</td>';
                html += '<td>' + log.template_name + '</td>';
                html += '<td><span class="' + statusClass + '">' + log.status.toUpperCase() + '</span></td>';
                html += '<td>' + log.sent_at + '</td>';
                html += '<td>';
                html += '<button class="button button-small view-email-log" data-log-id="' + log.id + '">View</button>';
                if (log.status === 'failed') {
                    html += ' <button class="button button-small resend-email" data-log-id="' + log.id + '">Resend</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#email-logs-tbody').html(html);
    }
    
    function clearEmailLogs() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_clear_email_logs',
                nonce: '<?php echo wp_create_nonce( 'bm_email_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Email logs cleared successfully');
                    location.reload();
                } else {
                    alert('Error clearing logs: ' + response.data.message);
                }
            }
        });
    }
});
</script>