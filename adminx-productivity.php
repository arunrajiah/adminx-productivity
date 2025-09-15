<?php
/**
 * Plugin Name: AdminX Productivity
 * Plugin URI: https://github.com/arunrajiah/adminx-plugins/adminx-productivity
 * Description: Centralized admin notifications, user role management, activity logging, and custom dashboard widgets for WordPress administrators.
 * Version: 1.0.0
 * Author: AdminX Team
 * Author URI: https://adminx.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adminx-productivity
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADMINX_PRODUCTIVITY_VERSION', '1.0.0');
define('ADMINX_PRODUCTIVITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADMINX_PRODUCTIVITY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADMINX_PRODUCTIVITY_PLUGIN_FILE', __FILE__);

/**
 * Main AdminX Productivity Plugin Class
 */
class AdminX_Productivity {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('adminx-productivity', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Add admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'includes/class-notifications-manager.php';
        require_once ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'includes/class-role-manager.php';
        require_once ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'includes/class-activity-logger.php';
        require_once ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'includes/class-dashboard-widgets.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        new AdminX_Notifications_Manager();
        new AdminX_Role_Manager();
        new AdminX_Activity_Logger();
        new AdminX_Dashboard_Widgets();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AdminX Productivity', 'adminx-productivity'),
            __('AdminX Productivity', 'adminx-productivity'),
            'manage_options',
            'adminx-productivity',
            array($this, 'admin_page'),
            'dashicons-performance',
            30
        );
        
        add_submenu_page(
            'adminx-productivity',
            __('Notifications', 'adminx-productivity'),
            __('Notifications', 'adminx-productivity'),
            'manage_options',
            'adminx-notifications',
            array($this, 'notifications_page')
        );
        
        add_submenu_page(
            'adminx-productivity',
            __('User Roles', 'adminx-productivity'),
            __('User Roles', 'adminx-productivity'),
            'manage_options',
            'adminx-roles',
            array($this, 'roles_page')
        );
        
        add_submenu_page(
            'adminx-productivity',
            __('Activity Log', 'adminx-productivity'),
            __('Activity Log', 'adminx-productivity'),
            'manage_options',
            'adminx-activity',
            array($this, 'activity_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'adminx-productivity') !== false || strpos($hook, 'adminx-') !== false) {
            wp_enqueue_style(
                'adminx-productivity-admin',
                ADMINX_PRODUCTIVITY_PLUGIN_URL . 'assets/admin.css',
                array(),
                ADMINX_PRODUCTIVITY_VERSION
            );
            
            wp_enqueue_script(
                'adminx-productivity-admin',
                ADMINX_PRODUCTIVITY_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                ADMINX_PRODUCTIVITY_VERSION,
                true
            );
            
            wp_localize_script('adminx-productivity-admin', 'adminx_productivity_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('adminx_productivity_nonce')
            ));
        }
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        include ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'templates/admin-main.php';
    }
    
    /**
     * Notifications page
     */
    public function notifications_page() {
        include ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'templates/admin-notifications.php';
    }
    
    /**
     * Roles page
     */
    public function roles_page() {
        include ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'templates/admin-roles.php';
    }
    
    /**
     * Activity page
     */
    public function activity_page() {
        include ADMINX_PRODUCTIVITY_PLUGIN_DIR . 'templates/admin-activity.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        add_option('adminx_productivity_version', ADMINX_PRODUCTIVITY_VERSION);
        add_option('adminx_productivity_settings', array(
            'notifications_enabled' => true,
            'activity_log_retention' => 30,
            'dashboard_widgets_enabled' => true
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('adminx_productivity_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Activity log table
        $table_name = $wpdb->prefix . 'adminx_activity_log';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) DEFAULT NULL,
            description text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Notifications table
        $notifications_table = $wpdb->prefix . 'adminx_notifications';
        $notifications_sql = "CREATE TABLE $notifications_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(20) DEFAULT 'info',
            user_id bigint(20) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($notifications_sql);
    }
}

// Initialize the plugin
AdminX_Productivity::get_instance();