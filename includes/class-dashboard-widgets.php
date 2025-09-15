<?php
/**
 * AdminX Dashboard Widgets
 * 
 * Manages custom dashboard widgets
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AdminX_Dashboard_Widgets {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('wp_ajax_adminx_save_widget_settings', array($this, 'save_widget_settings'));
    }
    
    /**
     * Add custom dashboard widgets
     */
    public function add_dashboard_widgets() {
        $settings = get_option('adminx_productivity_settings', array());
        
        if (!isset($settings['dashboard_widgets_enabled']) || $settings['dashboard_widgets_enabled']) {
            // System overview widget
            wp_add_dashboard_widget(
                'adminx_system_overview',
                __('AdminX System Overview', 'adminx-productivity'),
                array($this, 'system_overview_widget')
            );
            
            // Recent activity widget
            wp_add_dashboard_widget(
                'adminx_recent_activity',
                __('AdminX Recent Activity', 'adminx-productivity'),
                array($this, 'recent_activity_widget')
            );
            
            // Quick stats widget
            wp_add_dashboard_widget(
                'adminx_quick_stats',
                __('AdminX Quick Stats', 'adminx-productivity'),
                array($this, 'quick_stats_widget')
            );
            
            // Notifications widget
            wp_add_dashboard_widget(
                'adminx_notifications_widget',
                __('AdminX Notifications', 'adminx-productivity'),
                array($this, 'notifications_widget')
            );
        }
    }
    
    /**
     * System overview widget
     */
    public function system_overview_widget() {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        $mysql_version = $this->get_mysql_version();
        $theme = wp_get_theme();
        $plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        echo '<div class="adminx-widget-content">';
        echo '<h4>' . __('System Information', 'adminx-productivity') . '</h4>';
        echo '<ul>';
        echo '<li><strong>' . __('WordPress:', 'adminx-productivity') . '</strong> ' . esc_html($wp_version) . '</li>';
        echo '<li><strong>' . __('PHP:', 'adminx-productivity') . '</strong> ' . esc_html($php_version) . '</li>';
        echo '<li><strong>' . __('MySQL:', 'adminx-productivity') . '</strong> ' . esc_html($mysql_version) . '</li>';
        echo '<li><strong>' . __('Active Theme:', 'adminx-productivity') . '</strong> ' . esc_html($theme->get('Name')) . ' v' . esc_html($theme->get('Version')) . '</li>';
        echo '<li><strong>' . __('Active Plugins:', 'adminx-productivity') . '</strong> ' . count($active_plugins) . '/' . count($plugins) . '</li>';
        echo '</ul>';
        
        // Check for updates
        $updates = get_core_updates();
        if (!empty($updates) && $updates[0]->response === 'upgrade') {
            echo '<div class="notice notice-warning inline"><p>';
            echo sprintf(__('WordPress %s is available!', 'adminx-productivity'), $updates[0]->version);
            echo '</p></div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Recent activity widget
     */
    public function recent_activity_widget() {
        $activity_logger = new AdminX_Activity_Logger();
        $recent_activities = $activity_logger->get_activity_log(10);
        
        echo '<div class="adminx-widget-content">';
        
        if (empty($recent_activities)) {
            echo '<p>' . __('No recent activity found.', 'adminx-productivity') . '</p>';
        } else {
            echo '<ul class="adminx-activity-list">';
            
            foreach ($recent_activities as $activity) {
                $time_diff = human_time_diff(strtotime($activity->created_at), current_time('timestamp'));
                $user_name = $activity->display_name ?: $activity->user_login ?: __('Unknown User', 'adminx-productivity');
                
                echo '<li>';
                echo '<div class="activity-description">' . esc_html($activity->description) . '</div>';
                echo '<div class="activity-meta">';
                echo '<span class="activity-user">' . esc_html($user_name) . '</span>';
                echo ' • ';
                echo '<span class="activity-time">' . sprintf(__('%s ago', 'adminx-productivity'), $time_diff) . '</span>';
                echo '</div>';
                echo '</li>';
            }
            
            echo '</ul>';
            
            echo '<p><a href="' . admin_url('admin.php?page=adminx-activity') . '" class="button">';
            echo __('View All Activity', 'adminx-productivity');
            echo '</a></p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Quick stats widget
     */
    public function quick_stats_widget() {
        $post_counts = wp_count_posts();
        $page_counts = wp_count_posts('page');
        $user_count = count_users();
        $comment_counts = wp_count_comments();
        
        echo '<div class="adminx-widget-content">';
        echo '<div class="adminx-stats-grid">';
        
        // Posts
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . intval($post_counts->publish) . '</div>';
        echo '<div class="stat-label">' . __('Published Posts', 'adminx-productivity') . '</div>';
        echo '</div>';
        
        // Pages
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . intval($page_counts->publish) . '</div>';
        echo '<div class="stat-label">' . __('Published Pages', 'adminx-productivity') . '</div>';
        echo '</div>';
        
        // Users
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . intval($user_count['total_users']) . '</div>';
        echo '<div class="stat-label">' . __('Total Users', 'adminx-productivity') . '</div>';
        echo '</div>';
        
        // Comments
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . intval($comment_counts->approved) . '</div>';
        echo '<div class="stat-label">' . __('Approved Comments', 'adminx-productivity') . '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Notifications widget
     */
    public function notifications_widget() {
        $notifications_manager = new AdminX_Notifications_Manager();
        $notifications = $notifications_manager->get_notifications(5);
        
        echo '<div class="adminx-widget-content">';
        
        if (empty($notifications)) {
            echo '<p>' . __('No notifications at this time.', 'adminx-productivity') . '</p>';
        } else {
            echo '<ul class="adminx-notifications-list">';
            
            foreach ($notifications as $notification) {
                $time_diff = human_time_diff(strtotime($notification->created_at), current_time('timestamp'));
                $type_class = 'notification-' . esc_attr($notification->type);
                
                echo '<li class="' . $type_class . '">';
                echo '<div class="notification-title">' . esc_html($notification->title) . '</div>';
                echo '<div class="notification-message">' . wp_kses_post($notification->message) . '</div>';
                echo '<div class="notification-time">' . sprintf(__('%s ago', 'adminx-productivity'), $time_diff) . '</div>';
                echo '</li>';
            }
            
            echo '</ul>';
            
            echo '<p><a href="' . admin_url('admin.php?page=adminx-notifications') . '" class="button">';
            echo __('View All Notifications', 'adminx-productivity');
            echo '</a></p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Get MySQL version
     */
    private function get_mysql_version() {
        global $wpdb;
        
        $version = $wpdb->get_var('SELECT VERSION()');
        
        if ($version) {
            return $version;
        }
        
        return __('Unknown', 'adminx-productivity');
    }
    
    /**
     * Save widget settings via AJAX
     */
    public function save_widget_settings() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $settings = get_option('adminx_productivity_settings', array());
        $settings['dashboard_widgets_enabled'] = isset($_POST['widgets_enabled']) && $_POST['widgets_enabled'] === 'true';
        
        update_option('adminx_productivity_settings', $settings);
        
        wp_send_json_success(array('message' => __('Widget settings saved successfully', 'adminx-productivity')));
    }
    
    /**
     * Remove default WordPress widgets (optional)
     */
    public function remove_default_widgets() {
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    }
}