# Booking Master - WordPress Multivendor Booking Plugin

A comprehensive WordPress booking plugin similar to Amelia, designed for multivendor environments where mentors can create services and mentees can book appointments with integrated Zoom functionality.

## Features

### 🎯 Core Functionality
- **Multivendor System**: Separate roles for mentors and mentees
- **Service Management**: Mentors can create and manage their own services
- **Booking System**: Complete booking workflow with status management
- **Zoom Integration**: Automated Zoom meeting creation for online sessions
- **Role-based Access Control**: Granular permissions for different user types

### 🔧 Technical Features
- **Object-Oriented Design**: Clean, maintainable PHP code structure
- **WordPress Best Practices**: Follows WordPress coding standards and security practices
- **AJAX-Powered Interface**: Smooth user experience without page reloads
- **Responsive Design**: Mobile-friendly frontend and admin interfaces
- **Database Optimization**: Efficient database structure with proper indexing

### 📊 Admin Features
- **Comprehensive Dashboard**: Overview of bookings, services, and revenue
- **User Management**: Assign roles and manage mentor/mentee accounts
- **Booking Management**: View and manage all bookings across the platform
- **Settings Panel**: Configure currencies, time slots, and Zoom integration
- **Reporting**: Built-in analytics and reporting features

### 👥 User Roles

#### Mentors
- Create and manage services (name, pricing, duration)
- View and manage their bookings
- Connect individual Zoom accounts
- Set availability schedules
- Approve or reject booking requests

#### Mentees
- Browse available services
- Book appointments with mentors
- View their booking history
- Join Zoom meetings for confirmed bookings
- Cancel bookings (with restrictions)

#### Administrators
- Full access to all plugin features
- Configure global settings
- Manage user roles
- View system-wide analytics
- Configure Zoom marketplace integration

## Installation

1. **Upload the Plugin**
   ```
   Upload the `booking-master` folder to `/wp-content/plugins/`
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Booking Master" and click "Activate"

3. **Initial Setup**
   - Navigate to Booking Master → Settings
   - Configure your currency and basic settings
   - Set up Zoom integration (optional)

## Configuration

### Basic Settings
1. **Currency**: Set your preferred currency (USD, EUR, GBP, etc.)
2. **Time Slots**: Configure time slot duration (15, 30, or 60 minutes)
3. **Buffer Time**: Set minimum booking advance time
4. **Auto-approval**: Choose whether bookings need manual approval

### Zoom Integration
1. **Create Zoom App**: Visit [Zoom Marketplace](https://marketplace.zoom.us/)
2. **Get Credentials**: Obtain your API Key and Secret
3. **Configure Plugin**: Enter credentials in Booking Master → Settings
4. **Mentor Setup**: Each mentor connects their individual Zoom account

## Usage

### For Administrators

#### Setting Up User Roles
```php
// Assign mentor role
$user = new WP_User($user_id);
$user->set_role('mentor');

// Assign mentee role  
$user = new WP_User($user_id);
$user->set_role('mentee');
```

#### Managing Services
- Go to Booking Master → All Services
- View, edit, or delete services created by mentors
- Monitor service performance and popularity

#### Managing Bookings
- Access Booking Master → All Bookings
- Update booking statuses
- View booking details and communication

### For Mentors

#### Creating Services
1. Navigate to Booking Master → Mentor Dashboard
2. Click "Add New Service"
3. Fill in service details:
   - Service name and description
   - Price and duration
   - Enable Zoom if needed
4. Save the service

#### Managing Bookings
1. View pending bookings in your dashboard
2. Approve or reject booking requests
3. Zoom meetings are automatically created upon approval
4. Communicate with mentees through the system

### For Mentees

#### Booking Services
1. Browse available services
2. Select a service and click "Book Now"
3. Choose date and time slot
4. Add any special notes
5. Submit booking request

#### Managing Bookings
1. View your bookings in the user dashboard
2. Join Zoom meetings when available
3. Cancel bookings (if allowed)
4. View booking history

## Shortcodes

### Service Display
```php
// Display all services
[booking_master_services]

// Display services by specific mentor
[booking_master_services mentor_id="123"]

// Limit number of services shown
[booking_master_services limit="6"]
```

### Booking Forms
```php
// Booking form for specific service
[booking_master_booking_form service_id="123"]
```

### User Dashboards
```php
// Universal user dashboard (shows appropriate content based on role)
[booking_master_user_dashboard]

// Mentor application form
[booking_master_mentor_application]
```

## Database Schema

### Services Table (`wp_bm_services`)
- `id`: Primary key
- `mentor_id`: WordPress user ID of the mentor
- `service_name`: Name of the service
- `description`: Service description
- `price`: Service price
- `duration`: Duration in minutes
- `zoom_enabled`: Whether Zoom is enabled for this service
- `status`: Service status (active/inactive)
- `created_at`, `updated_at`: Timestamps

### Bookings Table (`wp_bm_bookings`)
- `id`: Primary key
- `service_id`: Foreign key to services table
- `mentee_id`: WordPress user ID of the mentee
- `mentor_id`: WordPress user ID of the mentor
- `booking_date`: Date and time of the appointment
- `status`: Booking status (pending/confirmed/cancelled/completed)
- `zoom_meeting_id`: Zoom meeting ID
- `zoom_join_url`: Zoom join URL
- `zoom_start_url`: Zoom start URL (for mentors)
- `total_amount`: Total booking amount
- `notes`: Additional notes
- `created_at`, `updated_at`: Timestamps

### Mentor Settings Table (`wp_bm_mentor_settings`)
- `id`: Primary key
- `mentor_id`: WordPress user ID
- `zoom_access_token`: Zoom OAuth access token
- `zoom_refresh_token`: Zoom OAuth refresh token
- `zoom_expires_at`: Token expiration time
- `availability`: JSON-encoded availability schedule
- `created_at`, `updated_at`: Timestamps

## API Reference

### Core Classes

#### `Booking_Master_Services`
```php
// Create a service
$services = new Booking_Master_Services();
$service_id = $services->create_service($service_data);

// Get services by mentor
$services = $services->get_services_by_mentor($mentor_id);

// Update service
$services->update_service($service_id, $update_data);
```

#### `Booking_Master_Bookings`
```php
// Create a booking
$bookings = new Booking_Master_Bookings();
$booking_id = $bookings->create_booking($booking_data);

// Check slot availability
$available = $bookings->is_slot_available($service_id, $datetime);

// Get available slots
$slots = $bookings->get_available_slots($service_id, $date);
```

#### `Booking_Master_Zoom_Integration`
```php
// Create Zoom meeting
$zoom = new Booking_Master_Zoom_Integration();
$meeting = $zoom->create_meeting($booking_object);

// Check connection status
$status = $zoom->get_connection_status($mentor_id);
```

### AJAX Actions

#### Frontend Actions
- `bm_create_booking`: Create a new booking
- `bm_get_available_slots`: Get available time slots
- `bm_cancel_booking`: Cancel a booking
- `bm_connect_zoom`: Connect Zoom account
- `bm_disconnect_zoom`: Disconnect Zoom account

#### Admin Actions
- `bm_create_service`: Create a new service
- `bm_update_service`: Update an existing service
- `bm_delete_service`: Delete a service
- `bm_update_booking_status`: Update booking status
- `bm_assign_mentor_role`: Assign mentor role to user
- `bm_assign_mentee_role`: Assign mentee role to user

## Customization

### Hooks and Filters

#### Action Hooks
```php
// After booking creation
do_action('booking_master_booking_created', $booking_id, $booking_data);

// After booking status change
do_action('booking_master_booking_status_changed', $booking_id, $old_status, $new_status);

// After service creation
do_action('booking_master_service_created', $service_id, $service_data);
```

#### Filter Hooks
```php
// Modify available time slots
$slots = apply_filters('booking_master_available_slots', $slots, $service_id, $date);

// Modify booking confirmation email
$message = apply_filters('booking_master_booking_email', $message, $booking_id);

// Modify service display
$html = apply_filters('booking_master_service_html', $html, $service);
```

### Custom Styling
Add custom CSS to override default styles:

```css
/* Custom service card styling */
.bm-services-list .service-card {
    background: #your-color;
    border: 2px solid #your-border-color;
}

/* Custom button styling */
.btn.btn-primary {
    background: #your-primary-color;
}
```

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **jQuery**: Included with WordPress

### Recommended
- **SSL Certificate**: Required for Zoom integration
- **Modern Browser**: For optimal admin experience
- **Caching Plugin**: For better performance

## Support

### Documentation
- Plugin settings include built-in help text
- Shortcode reference available in admin
- Database schema documented in code

### Common Issues

#### Zoom Integration Not Working
1. Verify API credentials are correct
2. Ensure SSL is enabled on your site
3. Check Zoom app permissions

#### Bookings Not Saving
1. Check database table creation
2. Verify user permissions
3. Check for JavaScript errors

#### Time Slots Not Loading
1. Ensure AJAX is working properly
2. Check service availability settings
3. Verify mentor has connected Zoom (if required)

## Changelog

### Version 1.0.0
- Initial release
- Core booking functionality
- Zoom integration
- User role management
- Admin dashboard
- Frontend shortcodes

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with ❤️ using WordPress best practices and modern PHP techniques.

---

**Note**: This plugin is designed to be a comprehensive booking solution. For specific customization needs, consider hiring a WordPress developer familiar with the plugin's architecture.