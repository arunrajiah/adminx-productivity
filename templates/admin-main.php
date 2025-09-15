<?php
/**
 * AdminX Productivity Main Admin Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$activity_logger = new AdminX_Activity_Logger();
$notifications_manager = new AdminX_Notifications_Manager();
$stats = $activity_logger->get_activity_statistics(30);
$unread_notifications = $notifications_manager->get_unread_count();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="adminx-main-content">
        
        <!-- Overview Stats -->
        <div class="adminx-section">
            <div class="adminx-section-header">
                <h2 class="adminx-section-title"><?php _e('Overview', 'adminx-productivity'); ?></h2>
            </div>
            <div class="adminx-section-content">
                <div class="adminx-stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo intval($stats['total']); ?></div>
                        <div class="stat-label"><?php _e('Activities (30 days)', 'adminx-productivity'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $unread_notifications; ?></div>
                        <div class="stat-label"><?php _e('Unread Notifications', 'adminx-productivity'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(get_users()); ?></div>
                        <div class="stat-label"><?php _e('Total Users', 'adminx-productivity'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(get_editable_roles()); ?></div>
                        <div class="stat-label"><?php _e('User Roles', 'adminx-productivity'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="adminx-section">
            <div class="adminx-section-header">
                <h2 class="adminx-section-title"><?php _e('Recent Activity', 'adminx-productivity'); ?></h2>
            </div>
            <div class="adminx-section-content">
                <?php
                $recent_activities = $activity_logger->get_activity_log(10);
                
                if (empty($recent_activities)) {
                    echo '<p>' . __('No recent activity found.', 'adminx-productivity') . '</p>';
                } else {
                    echo '<table class="adminx-activity-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>' . __('User', 'adminx-productivity') . '</th>';
                    echo '<th>' . __('Action', 'adminx-productivity') . '</th>';
                    echo '<th>' . __('Description', 'adminx-productivity') . '</th>';
                    echo '<th>' . __('Date', 'adminx-productivity') . '</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    foreach ($recent_activities as $activity) {
                        $user_name = $activity->display_name ?: $activity->user_login ?: __('Unknown User', 'adminx-productivity');
                        $time_diff = human_time_diff(strtotime($activity->created_at), current_time('timestamp'));
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($user_name) . '</td>';
                        echo '<td>' . esc_html($activity->action) . '</td>';
                        echo '<td>' . esc_html($activity->description) . '</td>';
                        echo '<td>' . sprintf(__('%s ago', 'adminx-productivity'), $time_diff) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    
                    echo '<p><a href="' . admin_url('admin.php?page=adminx-activity') . '" class="button">';
                    echo __('View All Activity', 'adminx-productivity');
                    echo '</a></p>';
                }
                ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="adminx-section">
            <div class="adminx-section-header">
                <h2 class="adminx-section-title"><?php _e('Quick Actions', 'adminx-productivity'); ?></h2>
            </div>
            <div class="adminx-section-content">
                <div class="adminx-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=adminx-notifications'); ?>" class="button button-primary">
                        <?php _e('Manage Notifications', 'adminx-productivity'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=adminx-roles'); ?>" class="button button-secondary">
                        <?php _e('Manage User Roles', 'adminx-productivity'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=adminx-activity'); ?>" class="button button-secondary">
                        <?php _e('View Activity Log', 'adminx-productivity'); ?>
                    </a>
                    <a href="<?php echo admin_url('index.php'); ?>" class="button button-secondary">
                        <?php _e('Dashboard Widgets', 'adminx-productivity'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="adminx-section">
            <div class="adminx-section-header">
                <h2 class="adminx-section-title"><?php _e('System Information', 'adminx-productivity'); ?></h2>
            </div>
            <div class="adminx-section-content">
                <table class="adminx-form-table">
                    <tr>
                        <th><?php _e('WordPress Version:', 'adminx-productivity'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('PHP Version:', 'adminx-productivity'); ?></th>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Active Theme:', 'adminx-productivity'); ?></th>
                        <td>
                            <?php 
                            $theme = wp_get_theme();
                            echo esc_html($theme->get('Name')) . ' v' . esc_html($theme->get('Version'));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Active Plugins:', 'adminx-productivity'); ?></th>
                        <td>
                            <?php 
                            $all_plugins = get_plugins();
                            $active_plugins = get_option('active_plugins');
                            echo count($active_plugins) . ' / ' . count($all_plugins);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Memory Limit:', 'adminx-productivity'); ?></th>
                        <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Execution Time:', 'adminx-productivity'); ?></th>
                        <td><?php echo esc_html(ini_get('max_execution_time')); ?>s</td>
                    </tr>
                </table>
            </div>
        </div>
        
    </div>
</div>

<style>
.adminx-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.adminx-quick-actions .button {
    margin-right: 0;
}

@media (max-width: 768px) {
    .adminx-quick-actions {
        flex-direction: column;
    }
    
    .adminx-quick-actions .button {
        width: 100%;
        text-align: center;
    }
}
</style>