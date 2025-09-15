<?php
/**
 * AdminX Activity Logger
 * 
 * Logs admin activities (logins, edits, plugin changes)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AdminX_Activity_Logger {
    
    public function __construct() {
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('save_post', array($this, 'log_post_save'), 10, 3);
        add_action('delete_post', array($this, 'log_post_delete'));
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
        add_action('user_register', array($this, 'log_user_registration'));
        add_action('delete_user', array($this, 'log_user_deletion'));
        add_action('wp_ajax_adminx_get_activity_log', array($this, 'get_activity_log_ajax'));
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login, $user) {
        $this->log_activity(
            $user->ID,
            'login',
            'user',
            $user->ID,
            sprintf(__('User %s logged in', 'adminx-productivity'), $user_login)
        );
    }
    
    /**
     * Log user logout
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        
        if ($user->ID) {
            $this->log_activity(
                $user->ID,
                'logout',
                'user',
                $user->ID,
                sprintf(__('User %s logged out', 'adminx-productivity'), $user->user_login)
            );
        }
    }
    
    /**
     * Log post save
     */
    public function log_post_save($post_id, $post, $update) {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $action = $update ? 'update' : 'create';
        $description = sprintf(
            __('%s %s: %s', 'adminx-productivity'),
            $update ? 'Updated' : 'Created',
            $post->post_type,
            $post->post_title
        );
        
        $this->log_activity(
            $user_id,
            $action,
            $post->post_type,
            $post_id,
            $description
        );
    }
    
    /**
     * Log post deletion
     */
    public function log_post_delete($post_id) {
        $post = get_post($post_id);
        $user_id = get_current_user_id();
        
        if (!$user_id || !$post) {
            return;
        }
        
        $description = sprintf(
            __('Deleted %s: %s', 'adminx-productivity'),
            $post->post_type,
            $post->post_title
        );
        
        $this->log_activity(
            $user_id,
            'delete',
            $post->post_type,
            $post_id,
            $description
        );
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?: $plugin;
        
        $this->log_activity(
            $user_id,
            'activate',
            'plugin',
            null,
            sprintf(__('Activated plugin: %s', 'adminx-productivity'), $plugin_name)
        );
    }
    
    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?: $plugin;
        
        $this->log_activity(
            $user_id,
            'deactivate',
            'plugin',
            null,
            sprintf(__('Deactivated plugin: %s', 'adminx-productivity'), $plugin_name)
        );
    }
    
    /**
     * Log user registration
     */
    public function log_user_registration($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return;
        }
        
        $this->log_activity(
            $user_id,
            'register',
            'user',
            $user_id,
            sprintf(__('New user registered: %s', 'adminx-productivity'), $user->user_login)
        );
    }
    
    /**
     * Log user deletion
     */
    public function log_user_deletion($user_id) {
        $user = get_user_by('id', $user_id);
        $current_user_id = get_current_user_id();
        
        if (!$user || !$current_user_id) {
            return;
        }
        
        $this->log_activity(
            $current_user_id,
            'delete',
            'user',
            $user_id,
            sprintf(__('Deleted user: %s', 'adminx-productivity'), $user->user_login)
        );
    }
    
    /**
     * Log activity to database
     */
    private function log_activity($user_id, $action, $object_type, $object_id = null, $description = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_activity_log';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'object_type' => sanitize_text_field($object_type),
                'object_id' => $object_id,
                'description' => sanitize_text_field($description),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $this->get_user_agent(),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Clean up old logs (keep only last 30 days by default)
        $this->cleanup_old_logs();
        
        return $result !== false;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Get user agent
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    
    /**
     * Get activity log
     */
    public function get_activity_log($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_activity_log';
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $where_clauses[] = 'action = %s';
            $where_values[] = sanitize_text_field($filters['action']);
        }
        
        if (!empty($filters['object_type'])) {
            $where_clauses[] = 'object_type = %s';
            $where_values[] = sanitize_text_field($filters['object_type']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        $query = "SELECT al.*, u.user_login, u.display_name 
                 FROM $table_name al 
                 LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
                 WHERE $where_clause 
                 ORDER BY al.created_at DESC 
                 LIMIT %d OFFSET %d";
        
        $where_values[] = intval($limit);
        $where_values[] = intval($offset);
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get activity log via AJAX
     */
    public function get_activity_log_ajax() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        $logs = $this->get_activity_log($limit, $offset, $filters);
        
        wp_send_json_success($logs);
    }
    
    /**
     * Clean up old logs
     */
    private function cleanup_old_logs() {
        global $wpdb;
        
        $settings = get_option('adminx_productivity_settings', array());
        $retention_days = isset($settings['activity_log_retention']) ? intval($settings['activity_log_retention']) : 30;
        
        if ($retention_days <= 0) {
            return; // Keep all logs
        }
        
        $table_name = $wpdb->prefix . 'adminx_activity_log';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Get activity statistics
     */
    public function get_activity_statistics($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'adminx_activity_log';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = array();
        
        // Total activities
        $stats['total'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            $date_from
        ));
        
        // Activities by action
        $stats['by_action'] = $wpdb->get_results($wpdb->prepare(
            "SELECT action, COUNT(*) as count FROM $table_name WHERE created_at >= %s GROUP BY action ORDER BY count DESC",
            $date_from
        ), ARRAY_A);
        
        // Activities by user
        $stats['by_user'] = $wpdb->get_results($wpdb->prepare(
            "SELECT al.user_id, u.user_login, u.display_name, COUNT(*) as count 
             FROM $table_name al 
             LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
             WHERE al.created_at >= %s 
             GROUP BY al.user_id 
             ORDER BY count DESC 
             LIMIT 10",
            $date_from
        ), ARRAY_A);
        
        // Daily activity count
        $stats['daily'] = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $table_name 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            $date_from
        ), ARRAY_A);
        
        return $stats;
    }
}