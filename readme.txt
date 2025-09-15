=== AdminX Productivity ===
Contributors: adminxteam
Donate link: https://adminx.dev/donate
Tags: admin, productivity, notifications, user roles, activity log, dashboard widgets
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Centralized admin notifications, user role management, activity logging, and custom dashboard widgets for WordPress administrators.

== Description ==

AdminX Productivity is a comprehensive WordPress plugin designed to enhance administrator productivity and streamline site management. This plugin provides essential tools for managing notifications, user roles, activity monitoring, and dashboard customization.

= Key Features =

**Centralized Notifications Panel**
* Unified notification system for all admin alerts
* Real-time notifications in admin bar
* Automatic system update notifications
* Plugin and theme update alerts
* Custom notification management

**Advanced User Role Management**
* Visual role and capability editor
* Create custom user roles
* Bulk capability assignment
* Role-based access control
* User role analytics

**Comprehensive Activity Logging**
* Track user logins and logouts
* Monitor post and page edits
* Log plugin activations/deactivations
* User registration and deletion tracking
* IP address and user agent logging
* Filterable activity reports

**Custom Dashboard Widgets**
* System overview widget
* Recent activity summary
* Quick statistics display
* Notification center widget
* Customizable widget settings

= Why Choose AdminX Productivity? =

* **All-in-One Solution**: Combines multiple admin tools in one plugin
* **Performance Optimized**: Lightweight and efficient code
* **Security Focused**: Proper sanitization and nonce verification
* **User Friendly**: Intuitive interface design
* **Extensible**: Developer-friendly with hooks and filters
* **No External Dependencies**: Works entirely within WordPress

= Perfect For =

* WordPress administrators managing multiple sites
* Agencies needing client site oversight
* Developers requiring detailed activity logs
* Site owners wanting enhanced security monitoring
* Teams needing role-based access control

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/adminx-productivity` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the AdminX Productivity menu in the WordPress admin to configure the plugin.
4. Configure notification settings and user roles as needed.
5. Check the dashboard for new widgets and activity monitoring.

== Frequently Asked Questions ==

= Does this plugin affect site performance? =

No, AdminX Productivity is designed to be lightweight and efficient. It only loads admin-specific functionality and uses optimized database queries.

= Can I customize which activities are logged? =

Yes, the plugin provides hooks and filters for developers to customize activity logging. You can also configure retention periods for log cleanup.

= Is it compatible with multisite installations? =

Currently, the plugin is designed for single-site installations. Multisite compatibility is planned for future releases.

= Can I export activity logs? =

The current version provides filtering and viewing capabilities. Export functionality is planned for a future update.

= Does it work with custom post types? =

Yes, the activity logger automatically tracks changes to all post types, including custom post types.

= Can I disable specific features? =

Yes, you can disable dashboard widgets and configure notification settings through the plugin's admin interface.

== Screenshots ==

1. Main AdminX Productivity dashboard with overview statistics
2. Centralized notifications panel with filtering options
3. User role management interface with capability editor
4. Activity log with detailed filtering and search
5. Custom dashboard widgets showing system information
6. Admin bar notification indicator

== Changelog ==

= 1.0.0 =
* Initial release
* Centralized notifications system
* User role and capability management
* Comprehensive activity logging
* Custom dashboard widgets
* Admin bar integration
* Security hardening features
* Performance optimization

== Upgrade Notice ==

= 1.0.0 =
Initial release of AdminX Productivity. Install to enhance your WordPress admin experience with powerful productivity tools.

== Developer Information ==

= Hooks and Filters =

The plugin provides several hooks for customization:

* `adminx_productivity_log_activity` - Filter activity logging
* `adminx_productivity_notification_types` - Customize notification types
* `adminx_productivity_dashboard_widgets` - Modify dashboard widgets
* `adminx_productivity_role_capabilities` - Filter available capabilities

= Database Tables =

The plugin creates two custom tables:

* `wp_adminx_activity_log` - Stores activity log entries
* `wp_adminx_notifications` - Stores notification data

= Minimum Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* 64MB memory limit (128MB recommended)

= Support =

For support and documentation, visit: https://adminx.dev/support

= Contributing =

Contribute to development on GitHub: https://github.com/adminx-team/adminx-productivity