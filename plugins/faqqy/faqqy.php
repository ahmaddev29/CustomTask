<?php

/**
 * Plugin Name: Faqqy
 * Plugin URI: https://www.wordpress.org/
 * Description: Best plugin to show faqs
 * Version: 1.0
 * Requires at least: 5.6
 * Author: Muhammad Ahmad
 * Author URI: https://www.wordpress.org/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: faqqy
 * Domain Path: /languages
 */

 /*
Faqqy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
Faqqy is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with Faqqy. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if( ! defined( 'ABSPATH') ){
    exit;
}

if( ! class_exists( 'Faqqy' ) ){
    class Faqqy{
        function __construct(){
            $this->define_constants();

            require_once( FAQQY_PATH . 'post-types/class.faqqy-cpt.php' );
            $faqqy_post_type = new Faqqy_Post_Type();

            require_once( FAQQY_PATH . 'shortcodes/class.faqqy-shortcode.php' );
            $faqqy_Shortcode = new Faqqy_Shortcode();

            add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 999 );
        }

        public function define_constants(){
            define( 'FAQQY_PATH', plugin_dir_path( __FILE__ ) );
            define( 'FAQQY_URL', plugin_dir_url( __FILE__ ) );
            define( 'FAQQY_VERSION', '1.0.0' );
        }

        public static function activate(){
            update_option( 'rewrite_rules', '' );
        }

        public static function deactivate(){
            flush_rewrite_rules();
            unregister_post_type( 'faqqy' );
        }

        public static function uninstall(){
            $posts = get_posts(
                array(
                    'post_type' => 'faqqy',
                    'number_posts'  => -1,
                    'post_status'   => 'any'
                )
            );

            foreach( $posts as $post ){
                wp_delete_post( $post->ID, true );
            }

        }

        public function register_scripts(){
            wp_enqueue_style( 'faqqy-main-css', FAQQY_URL . 'assets/css/main.css', array(), FAQQY_VERSION, 'all' );
        }

    }
}

if( class_exists( 'Faqqy' ) ){
    register_activation_hook( __FILE__, array( 'Faqqy', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'Faqqy', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'Faqqy', 'uninstall' ) );

    $faqqy = new Faqqy();
} 
