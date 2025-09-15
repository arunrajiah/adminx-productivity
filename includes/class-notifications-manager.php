<?php
/**
 * AdminX Notifications Manager
 * 
 * Handles centralized admin notifications panel
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AdminX_Notifications_Manager {
    
    public function __construct() {
        add_action('admin_init', array($this, 'init'));
        add_action('wp_ajax_adminx_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_adminx_dismiss_notification', array($this, 'dismiss_notification'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_notification'), 999);
    }
    
    public function init() {
        // Check for system notifications
        $this->check_system_notifications();
    }
    
    /**
     * Add notification to admin bar
     */
    public function add_admin_bar_notification($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $unread_count = $this->get_unread_count();
        
        $wp_admin_bar->add_node(array(
            'id' => 'adminx-notifications',
            'title' => sprintf(
                '<span class="ab-icon dashicons dashicons-bell"></span><span class="ab-label">%d</span>',
                $unread_count
            ),
            'href' => admin_url('admin.php?page=adminx-notifications'),
            'meta' => array(
                'class' => $unread_count > 0 ? 'adminx-has-notifications' : ''
            )
        ));
    }
    
    /**
     * Get unread notifications count
     */
    public function get_unread_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_notifications';
        $user_id = get_current_user_id();
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE (user_id = %d OR user_id IS NULL) AND is_read = 0",
            $user_id
        ));
        
        return intval($count);
    }
    
    /**
     * Get notifications for current user
     */
    public function get_notifications($limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_notifications';
        $user_id = get_current_user_id();
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE (user_id = %d OR user_id IS NULL) 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
        
        return $notifications;
    }
    
    /**
     * Add new notification
     */
    public function add_notification($title, $message, $type = 'info', $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_notifications';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => sanitize_text_field($title),
                'message' => wp_kses_post($message),
                'type' => sanitize_text_field($type),
                'user_id' => $user_id,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $notification_id = intval($_POST['notification_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'adminx_notifications';
        
        $result = $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success(array('marked_read' => $result !== false));
    }
    
    /**
     * Dismiss notification
     */
    public function dismiss_notification() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $notification_id = intval($_POST['notification_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'adminx_notifications';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $notification_id),
            array('%d')
        );
        
        wp_send_json_success(array('dismissed' => $result !== false));
    }
    
    /**
     * Check for system notifications
     */
    private function check_system_notifications() {
        // Check for WordPress updates
        $updates = get_core_updates();
        if (!empty($updates) && $updates[0]->response === 'upgrade') {
            $this->add_notification(
                __('WordPress Update Available', 'adminx-productivity'),
                sprintf(__('WordPress %s is available. Please update for security and performance improvements.', 'adminx-productivity'), $updates[0]->version),
                'warning'
            );
        }
        
        // Check for plugin updates
        $plugin_updates = get_plugin_updates();
        if (!empty($plugin_updates)) {
            $this->add_notification(
                __('Plugin Updates Available', 'adminx-productivity'),
                sprintf(__('%d plugin(s) have updates available.', 'adminx-productivity'), count($plugin_updates)),
                'info'
            );
        }
        
        // Check for theme updates
        $theme_updates = get_theme_updates();
        if (!empty($theme_updates)) {
            $this->add_notification(
                __('Theme Updates Available', 'adminx-productivity'),
                sprintf(__('%d theme(s) have updates available.', 'adminx-productivity'), count($theme_updates)),
                'info'
            );
        }
    }
}