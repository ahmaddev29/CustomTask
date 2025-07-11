<?php
/**
 * Plugin Name: Booking Master
 * Plugin URI: https://your-domain.com/booking-master
 * Description: A comprehensive multivendor booking plugin similar to Amelia. Allows mentors to create services and mentees to book appointments with Zoom integration.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-domain.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: booking-master
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 */
define( 'BOOKING_MASTER_VERSION', '1.0.0' );

/**
 * Plugin directory path
 */
define( 'BOOKING_MASTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 */
define( 'BOOKING_MASTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename
 */
define( 'BOOKING_MASTER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_booking_master() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
    Booking_Master_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_booking_master() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
    Booking_Master_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_booking_master' );
register_deactivation_hook( __FILE__, 'deactivate_booking_master' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-booking-master.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_booking_master() {
    $plugin = new Booking_Master();
    $plugin->run();
}
run_booking_master();