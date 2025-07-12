<?php
/**
 * Provide a admin settings view for the plugin
 *
 * This file is used to markup the admin settings page.
 *
 * @since      1.0.0
 */

$settings = get_option('booking_master_settings', array());
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

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
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=booking-master-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            General
        </a>
        <a href="?page=booking-master-settings&tab=fees" class="nav-tab <?php echo $current_tab === 'fees' ? 'nav-tab-active' : ''; ?>">
            Fees & Taxes
        </a>
        <a href="?page=booking-master-settings&tab=calendar" class="nav-tab <?php echo $current_tab === 'calendar' ? 'nav-tab-active' : ''; ?>">
            Calendar
        </a>
        <a href="?page=booking-master-settings&tab=zoom" class="nav-tab <?php echo $current_tab === 'zoom' ? 'nav-tab-active' : ''; ?>">
            Zoom
        </a>
        <a href="?page=booking-master-settings&tab=marketplace" class="nav-tab <?php echo $current_tab === 'marketplace' ? 'nav-tab-active' : ''; ?>">
            Marketplace
        </a>
    </nav>

    <div class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field( 'bm_settings', 'settings_nonce' ); ?>
            
            <?php if ( $current_tab === 'general' ) : ?>
                <h2><?php esc_html_e( 'General Settings', 'booking-master' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency_symbol"><?php esc_html_e( 'Currency Symbol', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="currency_symbol" name="currency_symbol" 
                                   value="<?php echo esc_attr( $settings['currency_symbol'] ?? '$' ); ?>" 
                                   class="regular-text" maxlength="3" />
                            <p class="description"><?php esc_html_e( 'The currency symbol to display with prices.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="currency_position"><?php esc_html_e( 'Currency Position', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <select id="currency_position" name="currency_position">
                                <option value="before" <?php selected( $settings['currency_position'] ?? 'before', 'before' ); ?>>
                                    <?php esc_html_e( 'Before amount ($100)', 'booking-master' ); ?>
                                </option>
                                <option value="after" <?php selected( $settings['currency_position'] ?? 'before', 'after' ); ?>>
                                    <?php esc_html_e( 'After amount (100$)', 'booking-master' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="timezone"><?php esc_html_e( 'Timezone', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <select id="timezone" name="timezone">
                                <?php
                                $selected_timezone = $settings['timezone'] ?? get_option( 'timezone_string' );
                                $timezone_identifiers = timezone_identifiers_list();
                                foreach ( $timezone_identifiers as $timezone ) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr( $timezone ),
                                        selected( $selected_timezone, $timezone, false ),
                                        esc_html( $timezone )
                                    );
                                }
                                ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Default timezone for bookings and availability.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="booking_buffer_time"><?php esc_html_e( 'Booking Buffer Time', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="booking_buffer_time" name="booking_buffer_time" 
                                   value="<?php echo esc_attr( $settings['booking_buffer_time'] ?? 15 ); ?>" 
                                   class="small-text" min="0" max="120" />
                            <span><?php esc_html_e( 'minutes', 'booking-master' ); ?></span>
                            <p class="description"><?php esc_html_e( 'Buffer time between bookings to prevent overlapping.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="advance_booking_days"><?php esc_html_e( 'Advance Booking Days', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="advance_booking_days" name="advance_booking_days" 
                                   value="<?php echo esc_attr( $settings['advance_booking_days'] ?? 30 ); ?>" 
                                   class="small-text" min="1" max="365" />
                            <span><?php esc_html_e( 'days', 'booking-master' ); ?></span>
                            <p class="description"><?php esc_html_e( 'How many days in advance can customers book services.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auto_approve_bookings"><?php esc_html_e( 'Auto-approve Bookings', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_approve_bookings" name="auto_approve_bookings" 
                                       value="1" <?php checked( $settings['auto_approve_bookings'] ?? false, true ); ?> />
                                <?php esc_html_e( 'Automatically approve new bookings', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cancellation_policy"><?php esc_html_e( 'Cancellation Policy', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <textarea id="cancellation_policy" name="cancellation_policy" 
                                      rows="4" cols="50"><?php echo esc_textarea( $settings['cancellation_policy'] ?? '' ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Cancellation policy to display to customers.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="terms_of_service"><?php esc_html_e( 'Terms of Service URL', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="terms_of_service" name="terms_of_service" 
                                   value="<?php echo esc_attr( $settings['terms_of_service'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'URL to your terms of service page.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="privacy_policy"><?php esc_html_e( 'Privacy Policy URL', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="privacy_policy" name="privacy_policy" 
                                   value="<?php echo esc_attr( $settings['privacy_policy'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'URL to your privacy policy page.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php elseif ( $current_tab === 'fees' ) : ?>
                <h2><?php esc_html_e( 'Fees & Taxes', 'booking-master' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="management_fee_enabled"><?php esc_html_e( 'Enable Management Fee', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="management_fee_enabled" name="management_fee_enabled" 
                                       value="1" <?php checked( $settings['management_fee_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Add management fee to all bookings', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="management_fee_rate"><?php esc_html_e( 'Management Fee Rate', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="management_fee_rate" name="management_fee_rate" 
                                   value="<?php echo esc_attr( $settings['management_fee_rate'] ?? 10 ); ?>" 
                                   class="small-text" min="0" max="100" step="0.1" />
                            <span>%</span>
                            <p class="description"><?php esc_html_e( 'Percentage of the service price to charge as management fee.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="management_fee_description"><?php esc_html_e( 'Management Fee Description', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="management_fee_description" name="management_fee_description" 
                                   value="<?php echo esc_attr( $settings['management_fee_description'] ?? 'Platform Fee' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Description shown to customers for the management fee.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax_enabled"><?php esc_html_e( 'Enable Tax', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="tax_enabled" name="tax_enabled" 
                                       value="1" <?php checked( $settings['tax_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Add tax to all bookings', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax_rate"><?php esc_html_e( 'Tax Rate', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="tax_rate" name="tax_rate" 
                                   value="<?php echo esc_attr( $settings['tax_rate'] ?? 0 ); ?>" 
                                   class="small-text" min="0" max="100" step="0.1" />
                            <span>%</span>
                            <p class="description"><?php esc_html_e( 'Tax rate to apply to bookings.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax_description"><?php esc_html_e( 'Tax Description', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="tax_description" name="tax_description" 
                                   value="<?php echo esc_attr( $settings['tax_description'] ?? 'Tax' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Description shown to customers for tax.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax_handling"><?php esc_html_e( 'Tax Handling', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <select id="tax_handling" name="tax_handling">
                                <option value="admin" <?php selected( $settings['tax_handling'] ?? 'admin', 'admin' ); ?>>
                                    <?php esc_html_e( 'Admin keeps tax', 'booking-master' ); ?>
                                </option>
                                <option value="mentor" <?php selected( $settings['tax_handling'] ?? 'admin', 'mentor' ); ?>>
                                    <?php esc_html_e( 'Mentor receives tax', 'booking-master' ); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Who should receive the tax amount from bookings.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pricing_display"><?php esc_html_e( 'Pricing Display', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <select id="pricing_display" name="pricing_display">
                                <option value="exclusive" <?php selected( $settings['pricing_display'] ?? 'exclusive', 'exclusive' ); ?>>
                                    <?php esc_html_e( 'Exclusive (fees added at checkout)', 'booking-master' ); ?>
                                </option>
                                <option value="inclusive" <?php selected( $settings['pricing_display'] ?? 'exclusive', 'inclusive' ); ?>>
                                    <?php esc_html_e( 'Inclusive (fees included in price)', 'booking-master' ); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e( 'How to display prices to customers.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php elseif ( $current_tab === 'calendar' ) : ?>
                <h2><?php esc_html_e( 'Calendar Sync Settings', 'booking-master' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="calendar_sync_enabled"><?php esc_html_e( 'Enable Calendar Sync', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="calendar_sync_enabled" name="calendar_sync_enabled" 
                                       value="1" <?php checked( $settings['calendar_sync_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Enable calendar synchronization', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="google_calendar_enabled"><?php esc_html_e( 'Google Calendar', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="google_calendar_enabled" name="google_calendar_enabled" 
                                       value="1" <?php checked( $settings['google_calendar_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Enable Google Calendar integration', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="google_client_id"><?php esc_html_e( 'Google Client ID', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="google_client_id" name="google_client_id" 
                                   value="<?php echo esc_attr( $settings['google_client_id'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Google OAuth Client ID for calendar integration.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="google_client_secret"><?php esc_html_e( 'Google Client Secret', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="google_client_secret" name="google_client_secret" 
                                   value="<?php echo esc_attr( $settings['google_client_secret'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Google OAuth Client Secret for calendar integration.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="outlook_calendar_enabled"><?php esc_html_e( 'Outlook Calendar', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="outlook_calendar_enabled" name="outlook_calendar_enabled" 
                                       value="1" <?php checked( $settings['outlook_calendar_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Enable Outlook Calendar integration', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="outlook_client_id"><?php esc_html_e( 'Outlook Client ID', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="outlook_client_id" name="outlook_client_id" 
                                   value="<?php echo esc_attr( $settings['outlook_client_id'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Microsoft Azure Client ID for Outlook integration.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="outlook_client_secret"><?php esc_html_e( 'Outlook Client Secret', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="outlook_client_secret" name="outlook_client_secret" 
                                   value="<?php echo esc_attr( $settings['outlook_client_secret'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Microsoft Azure Client Secret for Outlook integration.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php elseif ( $current_tab === 'zoom' ) : ?>
                <h2><?php esc_html_e( 'Zoom Integration Settings', 'booking-master' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="zoom_enabled"><?php esc_html_e( 'Enable Zoom', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="zoom_enabled" name="zoom_enabled" 
                                       value="1" <?php checked( $settings['zoom_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Enable Zoom integration for online sessions', 'booking-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_api_key"><?php esc_html_e( 'Zoom API Key', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="zoom_api_key" name="zoom_api_key" 
                                   value="<?php echo esc_attr( $settings['zoom_api_key'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Your Zoom API Key.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_api_secret"><?php esc_html_e( 'Zoom API Secret', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="zoom_api_secret" name="zoom_api_secret" 
                                   value="<?php echo esc_attr( $settings['zoom_api_secret'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Your Zoom API Secret.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_oauth_client_id"><?php esc_html_e( 'Zoom OAuth Client ID', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="zoom_oauth_client_id" name="zoom_oauth_client_id" 
                                   value="<?php echo esc_attr( $settings['zoom_oauth_client_id'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Your Zoom OAuth Client ID.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="zoom_oauth_client_secret"><?php esc_html_e( 'Zoom OAuth Client Secret', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="zoom_oauth_client_secret" name="zoom_oauth_client_secret" 
                                   value="<?php echo esc_attr( $settings['zoom_oauth_client_secret'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Your Zoom OAuth Client Secret.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php elseif ( $current_tab === 'marketplace' ) : ?>
                <h2><?php esc_html_e( 'Stripe Marketplace', 'booking-master' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="marketplace_enabled"><?php esc_html_e( 'Enable Marketplace', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="marketplace_enabled" name="marketplace_enabled" 
                                       value="1" <?php checked( $settings['marketplace_enabled'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Enable Stripe marketplace functionality', 'booking-master' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Allows mentors to connect their Stripe accounts for automatic payouts.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marketplace_application_fee"><?php esc_html_e( 'Application Fee', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="marketplace_application_fee" name="marketplace_application_fee" 
                                   value="<?php echo esc_attr( $settings['marketplace_application_fee'] ?? 10 ); ?>" 
                                   class="small-text" min="0" max="100" step="0.1" />
                            <span>%</span>
                            <p class="description"><?php esc_html_e( 'Percentage of each booking to keep as platform fee.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marketplace_payout_schedule"><?php esc_html_e( 'Payout Schedule', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <select id="marketplace_payout_schedule" name="marketplace_payout_schedule">
                                <option value="immediate" <?php selected( $settings['marketplace_payout_schedule'] ?? 'immediate', 'immediate' ); ?>>
                                    <?php esc_html_e( 'Immediate (after session completion)', 'booking-master' ); ?>
                                </option>
                                <option value="weekly" <?php selected( $settings['marketplace_payout_schedule'] ?? 'immediate', 'weekly' ); ?>>
                                    <?php esc_html_e( 'Weekly', 'booking-master' ); ?>
                                </option>
                                <option value="monthly" <?php selected( $settings['marketplace_payout_schedule'] ?? 'immediate', 'monthly' ); ?>>
                                    <?php esc_html_e( 'Monthly', 'booking-master' ); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e( 'When to transfer earnings to mentor accounts.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marketplace_onboarding_redirect"><?php esc_html_e( 'Onboarding Redirect URL', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="marketplace_onboarding_redirect" name="marketplace_onboarding_redirect" 
                                   value="<?php echo esc_attr( $settings['marketplace_onboarding_redirect'] ?? '' ); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'URL to redirect mentors after completing Stripe onboarding.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marketplace_account_requirements"><?php esc_html_e( 'Account Requirements', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <textarea id="marketplace_account_requirements" name="marketplace_account_requirements" 
                                      rows="4" cols="50"><?php echo esc_textarea( $settings['marketplace_account_requirements'] ?? '' ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Requirements for mentor accounts (shown during onboarding).', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marketplace_auto_accept"><?php esc_html_e( 'Auto-Accept Applications', 'booking-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="marketplace_auto_accept" name="marketplace_auto_accept" 
                                       value="1" <?php checked( $settings['marketplace_auto_accept'] ?? 0, 1 ); ?> />
                                <?php esc_html_e( 'Automatically accept mentor applications', 'booking-master' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'If disabled, admin must manually approve mentor applications.', 'booking-master' ); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'booking-master' ); ?>" />
            </p>
        </form>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="email"],
.form-table input[type="url"],
.form-table input[type="password"],
.form-table textarea,
.form-table select {
    width: 100%;
    max-width: 400px;
}

.form-table input[type="number"] {
    width: 80px;
}

.form-table textarea {
    height: 100px;
    resize: vertical;
}

.description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

@media (max-width: 782px) {
    .form-table th,
    .form-table td {
        display: block;
        width: 100%;
        padding: 10px 0;
    }
    
    .form-table th {
        border-bottom: 0;
        padding-bottom: 5px;
    }
}</style>