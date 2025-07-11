<?php
/**
 * Provide a admin settings view for the plugin
 *
 * This file is used to markup the admin settings page.
 *
 * @since      1.0.0
 */

$settings = get_option('booking_master_settings', array());

// Default values
$defaults = array(
    'currency' => 'USD',
    'currency_symbol' => '$',
    'time_slot_duration' => 30,
    'booking_buffer_time' => 15,
    'zoom_enabled' => false,
    'zoom_api_key' => '',
    'zoom_api_secret' => '',
    'auto_approve_bookings' => false,
    'email_notifications' => true,
);

$settings = wp_parse_args($settings, $defaults);
?>

<div class="wrap">
    <div class="bm-admin-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Configure your booking system settings</p>
    </div>

    <form method="post" action="">
        <div class="bm-admin-section">
            <div class="bm-section-header">
                <h3>General Settings</h3>
            </div>
            <div class="bm-section-content">
                <table class="bm-form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency">Currency</label>
                        </th>
                        <td>
                            <select name="currency" id="currency">
                                <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD - US Dollar</option>
                                <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR - Euro</option>
                                <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>GBP - British Pound</option>
                                <option value="CAD" <?php selected($settings['currency'], 'CAD'); ?>>CAD - Canadian Dollar</option>
                                <option value="AUD" <?php selected($settings['currency'], 'AUD'); ?>>AUD - Australian Dollar</option>
                            </select>
                            <p class="description">Select the currency for your booking system.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="currency_symbol">Currency Symbol</label>
                        </th>
                        <td>
                            <input type="text" name="currency_symbol" id="currency_symbol" 
                                   value="<?php echo esc_attr($settings['currency_symbol']); ?>" class="small-text" />
                            <p class="description">Symbol to display with prices (e.g., $, €, £).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="time_slot_duration">Time Slot Duration</label>
                        </th>
                        <td>
                            <select name="time_slot_duration" id="time_slot_duration">
                                <option value="15" <?php selected($settings['time_slot_duration'], 15); ?>>15 minutes</option>
                                <option value="30" <?php selected($settings['time_slot_duration'], 30); ?>>30 minutes</option>
                                <option value="60" <?php selected($settings['time_slot_duration'], 60); ?>>60 minutes</option>
                            </select>
                            <p class="description">Duration of each available time slot.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="booking_buffer_time">Booking Buffer Time</label>
                        </th>
                        <td>
                            <input type="number" name="booking_buffer_time" id="booking_buffer_time" 
                                   value="<?php echo esc_attr($settings['booking_buffer_time']); ?>" 
                                   min="0" max="120" class="small-text" /> minutes
                            <p class="description">Minimum time before a booking can be made (in minutes).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Auto-approve Bookings</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_approve_bookings" value="1" 
                                       <?php checked($settings['auto_approve_bookings']); ?> />
                                Automatically approve all bookings
                            </label>
                            <p class="description">If enabled, bookings will be confirmed automatically instead of requiring mentor approval.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_notifications" value="1" 
                                       <?php checked($settings['email_notifications']); ?> />
                                Enable email notifications
                            </label>
                            <p class="description">Send email notifications for booking confirmations, cancellations, and reminders.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bm-admin-section">
            <div class="bm-section-header">
                <h3>Zoom Integration</h3>
            </div>
            <div class="bm-section-content">
                <table class="bm-form-table">
                    <tr>
                        <th scope="row">Enable Zoom Integration</th>
                        <td>
                            <label>
                                <input type="checkbox" name="zoom_enabled" value="1" 
                                       <?php checked($settings['zoom_enabled']); ?> />
                                Enable Zoom meeting integration
                            </label>
                            <p class="description">Allow mentors to create Zoom meetings for their sessions.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_api_key">Zoom API Key</label>
                        </th>
                        <td>
                            <input type="text" name="zoom_api_key" id="zoom_api_key" 
                                   value="<?php echo esc_attr($settings['zoom_api_key']); ?>" class="regular-text" />
                            <p class="description">
                                Your Zoom API Key (Client ID). 
                                <a href="https://marketplace.zoom.us/" target="_blank">Get your API credentials here</a>.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_api_secret">Zoom API Secret</label>
                        </th>
                        <td>
                            <input type="password" name="zoom_api_secret" id="zoom_api_secret" 
                                   value="<?php echo esc_attr($settings['zoom_api_secret']); ?>" class="regular-text" />
                            <p class="description">Your Zoom API Secret (Client Secret). Keep this confidential.</p>
                        </td>
                    </tr>
                </table>
                
                <?php if ($settings['zoom_enabled'] && !empty($settings['zoom_api_key']) && !empty($settings['zoom_api_secret'])): ?>
                    <div class="bm-zoom-status connected">
                        <p><strong>Zoom Integration Status:</strong> API credentials configured ✓</p>
                        <p>Mentors can now connect their individual Zoom accounts to create meetings.</p>
                    </div>
                <?php elseif ($settings['zoom_enabled']): ?>
                    <div class="bm-zoom-status disconnected">
                        <p><strong>Zoom Integration Status:</strong> Missing API credentials</p>
                        <p>Please enter your Zoom API credentials above to enable Zoom integration.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bm-admin-section">
            <div class="bm-section-header">
                <h3>Advanced Settings</h3>
            </div>
            <div class="bm-section-content">
                <table class="bm-form-table">
                    <tr>
                        <th scope="row">Database Tables</th>
                        <td>
                            <?php 
                            global $wpdb;
                            $tables = array(
                                'bm_services' => 'Services',
                                'bm_bookings' => 'Bookings', 
                                'bm_mentor_settings' => 'Mentor Settings'
                            );
                            
                            echo '<div style="display: grid; gap: 10px;">';
                            foreach ($tables as $table => $label) {
                                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'") == $wpdb->prefix . $table;
                                $status = $exists ? '✓' : '✗';
                                $class = $exists ? 'connected' : 'disconnected';
                                echo '<div class="bm-zoom-status ' . $class . '" style="padding: 5px 10px; margin: 2px 0;">';
                                echo '<span>' . $status . ' ' . esc_html($label) . ' (' . $wpdb->prefix . $table . ')</span>';
                                echo '</div>';
                            }
                            echo '</div>';
                            ?>
                            <p class="description">Status of plugin database tables.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Shortcodes</th>
                        <td>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                                <h4 style="margin-top: 0;">Available Shortcodes:</h4>
                                <ul style="margin: 0;">
                                    <li><code>[booking_master_services]</code> - Display all services</li>
                                    <li><code>[booking_master_services mentor_id="123"]</code> - Display services by specific mentor</li>
                                    <li><code>[booking_master_booking_form service_id="123"]</code> - Display booking form for specific service</li>
                                    <li><code>[booking_master_user_dashboard]</code> - Display user dashboard</li>
                                    <li><code>[booking_master_mentor_application]</code> - Display mentor application form</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button-primary" value="Save Settings" />
            <?php wp_nonce_field('bm_settings', 'settings_nonce'); ?>
        </p>
    </form>
</div>