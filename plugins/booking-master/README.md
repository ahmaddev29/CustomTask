# Booking Master - Advanced WordPress Multivendor Booking Plugin

A comprehensive WordPress plugin for managing multivendor booking services with mentor/mentee relationships, similar to Amelia but with advanced features.

## Features

### Core Functionality
- **Multivendor System**: Complete mentor/mentee role management
- **Service Management**: Mentors can create and manage services with pricing and duration
- **Booking System**: Full booking workflow with status management
- **Zoom Integration**: Individual mentor Zoom accounts with meeting creation
- **Admin Dashboard**: Comprehensive admin interface for managing all aspects

### Payment Processing
- **Stripe Integration**: Complete payment processing with webhooks
- **PayPal Integration**: PayPal checkout and payment verification
- **Payment Management**: Transaction tracking, refunds, and payment history
- **Multiple Payment Methods**: Support for both credit cards and PayPal
- **Refund System**: Automated refund processing through payment gateways

### Email Notifications
- **Automated Emails**: Booking confirmations, reminders, and cancellations
- **Email Templates**: Customizable email templates for all notification types
- **Scheduled Emails**: 24-hour and 1-hour reminder emails
- **Follow-up Emails**: Post-session feedback requests
- **Email Logs**: Complete email delivery tracking and troubleshooting
- **Test Email System**: Test email functionality with sample data

### Advanced Availability Management
- **Time Slot Management**: Mentors can enable/disable specific time slots
- **Bulk Availability**: Mass update availability for multiple days
- **Working Hours**: Set default working hours for each day of the week
- **Time Off**: Schedule time off periods with automatic slot blocking
- **Calendar View**: Month view of availability status
- **Copy Availability**: Copy availability patterns from one day/week to another

### Calendar Synchronization
- **Google Calendar**: Two-way sync with Google Calendar
- **Outlook Calendar**: Two-way sync with Microsoft Outlook
- **Event Management**: Automatic event creation, updates, and deletions
- **Conflict Prevention**: Import external calendar events to block time slots
- **OAuth2 Integration**: Secure authentication with calendar providers

### Reporting & Analytics
- **Comprehensive Reports**: Bookings, revenue, mentors, services, and cancellations
- **Interactive Charts**: Revenue charts and booking trend analysis
- **Export Functionality**: CSV and PDF export options
- **Dashboard Statistics**: Real-time metrics and KPIs
- **Date Range Filtering**: Custom date ranges for detailed analysis
- **Mentor-specific Reports**: Individual performance tracking

### User Management
- **Role-based Access**: Custom capabilities for mentors and mentees
- **User Dashboards**: Separate dashboards for different user types
- **Registration System**: Mentor application process
- **Permission Management**: Granular access control

### Additional Features
- **Responsive Design**: Mobile-friendly interface
- **Shortcode System**: Easy frontend integration
- **Multi-language Support**: Translation-ready
- **Security Features**: Nonce verification, sanitization, and validation
- **Database Optimization**: Efficient database schema with proper indexing

## Installation

1. Upload the plugin files to `/wp-content/plugins/booking-master/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in the Booking Master admin panel

## Database Tables

The plugin creates the following custom tables:

- `wp_bm_services` - Service information
- `wp_bm_bookings` - Booking records with payment details
- `wp_bm_mentor_settings` - Mentor-specific settings
- `wp_bm_mentor_availability` - Time slot availability
- `wp_bm_mentor_time_off` - Scheduled time off periods
- `wp_bm_calendar_events` - External calendar events
- `wp_bm_email_log` - Email delivery logs
- `wp_bm_payment_transactions` - Payment transaction records

## Configuration

### Payment Gateway Setup

#### Stripe Configuration
1. Go to Booking Master > Payments > Settings
2. Enable Stripe payments
3. Add your Stripe publishable and secret keys
4. Configure webhook endpoints

#### PayPal Configuration
1. Go to Booking Master > Payments > Settings
2. Enable PayPal payments
3. Add your PayPal client ID and secret
4. Configure sandbox mode for testing

### Email Configuration
1. Go to Booking Master > Email Management > Settings
2. Configure from name and email address
3. Enable desired email types
4. Customize email templates

### Calendar Sync Setup

#### Google Calendar
1. Create a Google Cloud project
2. Enable Calendar API
3. Create OAuth2 credentials
4. Add credentials to plugin settings

#### Outlook Calendar
1. Register app in Microsoft Azure
2. Configure calendar permissions
3. Add client ID and secret to plugin settings

### Zoom Integration
1. Create a Zoom Marketplace app
2. Configure OAuth2 credentials
3. Add credentials to plugin settings
4. Mentors connect individual accounts

## Shortcodes

### Service Display
```
[booking_master_services]
```
Display all available services in a grid layout.

### Booking Form
```
[booking_master_booking_form service_id="123"]
```
Display booking form for a specific service.

### User Dashboard
```
[booking_master_user_dashboard]
```
Display role-based dashboard for logged-in users.

### Mentor Application
```
[booking_master_mentor_application]
```
Display mentor application form.

## Admin Menu Structure

- **Dashboard**: Overview statistics and recent activity
- **All Services**: Manage all services across mentors
- **All Bookings**: View and manage all bookings
- **Reports**: Analytics and reporting tools
- **Payments**: Payment transactions and refunds
- **Email Management**: Email templates and logs
- **Users & Roles**: User management and role assignment
- **Settings**: Plugin configuration options

## User Roles & Capabilities

### Mentor Role
- `bm_manage_services` - Create and manage services
- `bm_view_bookings` - View their bookings
- `bm_manage_zoom` - Manage Zoom settings
- `bm_manage_availability` - Manage availability

### Mentee Role
- `bm_book_services` - Book services
- `bm_view_own_bookings` - View their own bookings

### Administrator
- All mentor and mentee capabilities
- `bm_manage_all_services` - Manage all services
- `bm_manage_all_bookings` - Manage all bookings
- `bm_view_reports` - Access reporting features

## API Endpoints

The plugin includes AJAX endpoints for:
- Service management
- Booking operations
- Payment processing
- Availability management
- Calendar synchronization
- Email operations
- Report generation

## Security Features

- CSRF protection with nonce verification
- User capability checks
- Data sanitization and validation
- SQL injection prevention
- XSS protection
- Secure payment processing

## Performance Optimizations

- Efficient database queries with proper indexing
- Caching for frequently accessed data
- Optimized AJAX operations
- Lazy loading for large datasets
- Background processing for email sending

## Customization

### Hooks & Filters
The plugin provides numerous hooks for customization:
- `bm_booking_created` - Triggered when booking is created
- `bm_booking_status_changed` - Triggered when booking status changes
- `bm_payment_completed` - Triggered when payment is successful
- `bm_service_created` - Triggered when service is created

### CSS Classes
All frontend elements use prefixed CSS classes for easy styling:
- `.bm-service-card` - Service display cards
- `.bm-booking-form` - Booking form container
- `.bm-dashboard` - Dashboard elements
- `.bm-calendar` - Calendar components

## Troubleshooting

### Common Issues

1. **Payment Gateway Errors**
   - Check API credentials
   - Verify webhook configurations
   - Review payment gateway logs

2. **Email Delivery Issues**
   - Check SMTP settings
   - Review email logs
   - Test with different email providers

3. **Calendar Sync Problems**
   - Verify OAuth2 credentials
   - Check calendar permissions
   - Review sync logs

4. **Availability Issues**
   - Check time zone settings
   - Verify working hours configuration
   - Review availability rules

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please contact the plugin developer or submit issues through the appropriate channels.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release with complete multivendor booking system
- Payment gateway integration (Stripe, PayPal)
- Email notification system
- Advanced availability management
- Calendar synchronization
- Comprehensive reporting
- Zoom integration
- User role management

## Credits

Developed as a comprehensive booking solution for WordPress with enterprise-level features and security.