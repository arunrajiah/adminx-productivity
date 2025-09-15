/* AdminX Productivity Plugin Admin JavaScript */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        AdminXProductivity.init();
    });
    
    var AdminXProductivity = {
        
        init: function() {
            this.bindEvents();
            this.initNotifications();
            this.initActivityLog();
            this.initRoleManager();
            this.initDashboardWidgets();
        },
        
        bindEvents: function() {
            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, error) {
                if (xhr.status !== 200) {
                    AdminXProductivity.showMessage('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        // Notifications functionality
        initNotifications: function() {
            // Mark notification as read
            $(document).on('click', '.adminx-mark-read', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var notificationId = $button.data('notification-id');
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'adminx_mark_notification_read',
                        notification_id: notificationId,
                        nonce: adminx_productivity_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('.adminx-notification-item').removeClass('unread');
                            $button.remove();
                            AdminXProductivity.updateNotificationCount();
                        }
                    }
                });
            });
            
            // Dismiss notification
            $(document).on('click', '.adminx-dismiss', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var notificationId = $button.data('notification-id');
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'adminx_dismiss_notification',
                        notification_id: notificationId,
                        nonce: adminx_productivity_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('.adminx-notification-item').fadeOut(300, function() {
                                $(this).remove();
                            });
                            AdminXProductivity.updateNotificationCount();
                        }
                    }
                });
            });
        },
        
        // Activity log functionality
        initActivityLog: function() {
            // Activity log filters
            $('#adminx-activity-filters').on('submit', function(e) {
                e.preventDefault();
                AdminXProductivity.loadActivityLog();
            });
            
            // Load more activities
            $(document).on('click', '#adminx-load-more-activities', function(e) {
                e.preventDefault();
                AdminXProductivity.loadActivityLog(true);
            });
        },
        
        // Role manager functionality
        initRoleManager: function() {
            // Update role capabilities
            $(document).on('click', '.adminx-update-role', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $roleCard = $button.closest('.adminx-role-card');
                var roleName = $roleCard.data('role');
                var capabilities = [];
                
                $roleCard.find('input[type="checkbox"]:checked').each(function() {
                    capabilities.push($(this).val());
                });
                
                $button.prop('disabled', true).text('Updating...');
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'adminx_update_role_capabilities',
                        role_name: roleName,
                        capabilities: capabilities,
                        nonce: adminx_productivity_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AdminXProductivity.showMessage(response.data.message, 'success');
                        } else {
                            AdminXProductivity.showMessage(response.data.message, 'error');
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Update Role');
                    }
                });
            });
            
            // Create custom role
            $('#adminx-create-role-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var formData = $form.serialize();
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: formData + '&action=adminx_create_custom_role&nonce=' + adminx_productivity_ajax.nonce,
                    success: function(response) {
                        if (response.success) {
                            AdminXProductivity.showMessage(response.data.message, 'success');
                            location.reload(); // Reload to show new role
                        } else {
                            AdminXProductivity.showMessage(response.data.message, 'error');
                        }
                    }
                });
            });
            
            // Delete custom role
            $(document).on('click', '.adminx-delete-role', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this role?')) {
                    return;
                }
                
                var $button = $(this);
                var roleName = $button.data('role');
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'adminx_delete_custom_role',
                        role_name: roleName,
                        nonce: adminx_productivity_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AdminXProductivity.showMessage(response.data.message, 'success');
                            $button.closest('.adminx-role-card').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            AdminXProductivity.showMessage(response.data.message, 'error');
                        }
                    }
                });
            });
        },
        
        // Dashboard widgets functionality
        initDashboardWidgets: function() {
            // Save widget settings
            $('#adminx-widget-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var widgetsEnabled = $form.find('#widgets_enabled').is(':checked');
                
                $.ajax({
                    url: adminx_productivity_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'adminx_save_widget_settings',
                        widgets_enabled: widgetsEnabled,
                        nonce: adminx_productivity_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AdminXProductivity.showMessage(response.data.message, 'success');
                        } else {
                            AdminXProductivity.showMessage('Failed to save settings.', 'error');
                        }
                    }
                });
            });
        },
        
        // Load activity log
        loadActivityLog: function(append) {
            append = append || false;
            
            var $container = $('#adminx-activity-log');
            var $loadMoreBtn = $('#adminx-load-more-activities');
            var offset = append ? $container.find('tr').length - 1 : 0; // -1 for header row
            
            if (!append) {
                $container.html('<tr><td colspan="5" class="adminx-loading">Loading...</td></tr>');
            }
            
            var filters = {
                user_id: $('#filter_user').val(),
                action: $('#filter_action').val(),
                object_type: $('#filter_object_type').val(),
                date_from: $('#filter_date_from').val(),
                date_to: $('#filter_date_to').val()
            };
            
            $.ajax({
                url: adminx_productivity_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'adminx_get_activity_log',
                    limit: 50,
                    offset: offset,
                    filters: filters,
                    nonce: adminx_productivity_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var activities = response.data;
                        var html = '';
                        
                        if (!append) {
                            html += '<tr><th>User</th><th>Action</th><th>Object</th><th>Description</th><th>Date</th></tr>';
                        }
                        
                        if (activities.length === 0 && !append) {
                            html += '<tr><td colspan="5">No activities found.</td></tr>';
                        } else {
                            $.each(activities, function(index, activity) {
                                var userName = activity.display_name || activity.user_login || 'Unknown User';
                                var date = new Date(activity.created_at).toLocaleString();
                                
                                html += '<tr>';
                                html += '<td>' + AdminXProductivity.escapeHtml(userName) + '</td>';
                                html += '<td>' + AdminXProductivity.escapeHtml(activity.action) + '</td>';
                                html += '<td>' + AdminXProductivity.escapeHtml(activity.object_type) + '</td>';
                                html += '<td>' + AdminXProductivity.escapeHtml(activity.description) + '</td>';
                                html += '<td>' + date + '</td>';
                                html += '</tr>';
                            });
                        }
                        
                        if (append) {
                            $container.append(html);
                        } else {
                            $container.html(html);
                        }
                        
                        // Show/hide load more button
                        if (activities.length < 50) {
                            $loadMoreBtn.hide();
                        } else {
                            $loadMoreBtn.show();
                        }
                    }
                }
            });
        },
        
        // Update notification count in admin bar
        updateNotificationCount: function() {
            var $adminBarNotifications = $('#wp-admin-bar-adminx-notifications');
            var $label = $adminBarNotifications.find('.ab-label');
            var currentCount = parseInt($label.text()) || 0;
            
            if (currentCount > 0) {
                var newCount = currentCount - 1;
                $label.text(newCount);
                
                if (newCount === 0) {
                    $adminBarNotifications.removeClass('adminx-has-notifications');
                }
            }
        },
        
        // Show message
        showMessage: function(message, type) {
            type = type || 'info';
            
            var $message = $('<div class="adminx-message ' + type + '">').text(message);
            
            // Remove existing messages
            $('.adminx-message').remove();
            
            // Add new message
            $('.adminx-main-content').prepend($message);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Escape HTML
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }
    };
    
    // Make AdminXProductivity globally available
    window.AdminXProductivity = AdminXProductivity;
    
})(jQuery);