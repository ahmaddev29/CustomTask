<?php
/**
 * Plugin Name: 3D Product Configurator
 * Plugin URI: https://your-site.com/3d-configurator
 * Description: A powerful 3D product configurator for WooCommerce that allows customers to personalize products with logos, materials, colors, and export high-quality renders.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: 3d-product-configurator
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TPC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TPC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TPC_VERSION', '1.0.0');

/**
 * Main Plugin Class
 */
class ThreeDProductConfigurator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // WooCommerce hooks
        add_action('woocommerce_single_product_summary', array($this, 'add_configurator_button'), 25);
        add_action('wp_ajax_save_configuration', array($this, 'save_configuration'));
        add_action('wp_ajax_nopriv_save_configuration', array($this, 'save_configuration'));
        add_action('wp_ajax_upload_logo', array($this, 'handle_logo_upload'));
        add_action('wp_ajax_nopriv_upload_logo', array($this, 'handle_logo_upload'));
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_meta'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('3d-product-configurator', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Create uploads directory
        $this->create_uploads_directory();
    }
    
    public function enqueue_scripts() {
        if (is_product()) {
            // React and dependencies
            wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
            
            // Three.js and related libraries
            wp_enqueue_script('threejs', 'https://unpkg.com/three@0.158.0/build/three.min.js', array(), '0.158.0', true);
            wp_enqueue_script('threejs-fiber', 'https://unpkg.com/@react-three/fiber@8.15.11/dist/index.umd.js', array('react', 'threejs'), '8.15.11', true);
            wp_enqueue_script('threejs-drei', 'https://unpkg.com/@react-three/drei@9.88.17/dist/index.umd.js', array('threejs-fiber'), '9.88.17', true);
            
            // Plugin scripts
            wp_enqueue_script('tpc-configurator', TPC_PLUGIN_URL . 'assets/js/configurator.js', array('react', 'react-dom', 'threejs-fiber'), TPC_VERSION, true);
            wp_enqueue_script('tpc-main', TPC_PLUGIN_URL . 'assets/js/main.js', array('jquery', 'tpc-configurator'), TPC_VERSION, true);
            
            // Styles
            wp_enqueue_style('tailwindcss', 'https://cdn.tailwindcss.com');
            wp_enqueue_style('tpc-style', TPC_PLUGIN_URL . 'assets/css/style.css', array(), TPC_VERSION);
            
            // Localize script
            wp_localize_script('tpc-main', 'tpc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tpc_nonce'),
                'plugin_url' => TPC_PLUGIN_URL,
                'product_id' => get_the_ID()
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_style('tpc-admin-style', TPC_PLUGIN_URL . 'assets/css/admin.css', array(), TPC_VERSION);
            wp_enqueue_script('tpc-admin', TPC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), TPC_VERSION, true);
        }
    }
    
    public function add_configurator_button() {
        global $product;
        
        if (!$product || !$this->is_3d_product($product->get_id())) {
            return;
        }
        
        echo '<div id="tpc-configurator-container" class="mt-4">';
        echo '<button id="tpc-open-configurator" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">';
        echo __('Customize in 3D', '3d-product-configurator');
        echo '</button>';
        echo '</div>';
        
        // Add configurator modal
        $this->render_configurator_modal();
    }
    
    private function render_configurator_modal() {
        global $product;
        
        $model_url = get_post_meta($product->get_id(), '_tpc_model_url', true);
        $materials = get_post_meta($product->get_id(), '_tpc_materials', true);
        $logo_positions = get_post_meta($product->get_id(), '_tpc_logo_positions', true);
        
        include TPC_PLUGIN_PATH . 'templates/configurator-modal.php';
    }
    
    public function add_product_meta_boxes() {
        add_meta_box(
            'tpc-product-settings',
            __('3D Configurator Settings', '3d-product-configurator'),
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }
    
    public function render_product_meta_box($post) {
        $enable_3d = get_post_meta($post->ID, '_tpc_enable_3d', true);
        $model_url = get_post_meta($post->ID, '_tpc_model_url', true);
        $materials = get_post_meta($post->ID, '_tpc_materials', true);
        $logo_positions = get_post_meta($post->ID, '_tpc_logo_positions', true);
        
        wp_nonce_field('tpc_save_meta', 'tpc_meta_nonce');
        
        include TPC_PLUGIN_PATH . 'templates/admin-meta-box.php';
    }
    
    public function save_product_meta($post_id) {
        if (!isset($_POST['tpc_meta_nonce']) || !wp_verify_nonce($_POST['tpc_meta_nonce'], 'tpc_save_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        update_post_meta($post_id, '_tpc_enable_3d', isset($_POST['tpc_enable_3d']) ? 1 : 0);
        update_post_meta($post_id, '_tpc_model_url', sanitize_url($_POST['tpc_model_url']));
        update_post_meta($post_id, '_tpc_materials', sanitize_textarea_field($_POST['tpc_materials']));
        update_post_meta($post_id, '_tpc_logo_positions', sanitize_textarea_field($_POST['tpc_logo_positions']));
    }
    
    public function save_configuration() {
        check_ajax_referer('tpc_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $configuration = json_decode(stripslashes($_POST['configuration']), true);
        
        if (!$product_id || !$configuration) {
            wp_die('Invalid data');
        }
        
        // Save configuration to session or database
        $config_id = $this->save_user_configuration($product_id, $configuration);
        
        wp_send_json_success(array('config_id' => $config_id));
    }
    
    public function handle_logo_upload() {
        check_ajax_referer('tpc_nonce', 'nonce');
        
        if (!isset($_FILES['logo'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['logo'];
        $upload_dir = $this->get_uploads_directory();
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type');
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $filepath = $upload_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_url = TPC_PLUGIN_URL . 'uploads/' . $filename;
            wp_send_json_success(array('url' => $file_url));
        } else {
            wp_send_json_error('Upload failed');
        }
    }
    
    private function is_3d_product($product_id) {
        return get_post_meta($product_id, '_tpc_enable_3d', true) == 1;
    }
    
    private function create_uploads_directory() {
        $upload_dir = $this->get_uploads_directory();
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
    }
    
    private function get_uploads_directory() {
        return TPC_PLUGIN_PATH . 'uploads';
    }
    
    private function save_user_configuration($product_id, $configuration) {
        // For now, save to session. In production, you might want to save to database
        if (!session_id()) {
            session_start();
        }
        
        $config_id = uniqid();
        $_SESSION['tpc_configurations'][$config_id] = array(
            'product_id' => $product_id,
            'configuration' => $configuration,
            'timestamp' => time()
        );
        
        return $config_id;
    }
    
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('3D Product Configurator requires WooCommerce to be installed and active.', '3d-product-configurator');
        echo '</p></div>';
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_uploads_directory();
        
        // Set default options
        add_option('tpc_version', TPC_VERSION);
    }
}

// Initialize the plugin
new ThreeDProductConfigurator();