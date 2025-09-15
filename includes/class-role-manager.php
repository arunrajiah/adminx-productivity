<?php
/**
 * AdminX Role Manager
 * 
 * Handles user role and capability management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AdminX_Role_Manager {
    
    public function __construct() {
        add_action('admin_init', array($this, 'init'));
        add_action('wp_ajax_adminx_update_role_capabilities', array($this, 'update_role_capabilities'));
        add_action('wp_ajax_adminx_create_custom_role', array($this, 'create_custom_role'));
        add_action('wp_ajax_adminx_delete_custom_role', array($this, 'delete_custom_role'));
    }
    
    public function init() {
        // Initialize role management features
    }
    
    /**
     * Get all WordPress roles
     */
    public function get_all_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }
    
    /**
     * Get role capabilities
     */
    public function get_role_capabilities($role_name) {
        $role = get_role($role_name);
        
        if (!$role) {
            return false;
        }
        
        return $role->capabilities;
    }
    
    /**
     * Get all available capabilities
     */
    public function get_all_capabilities() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $capabilities = array();
        
        foreach ($wp_roles->roles as $role) {
            if (isset($role['capabilities'])) {
                $capabilities = array_merge($capabilities, array_keys($role['capabilities']));
            }
        }
        
        // Add common capabilities that might not be assigned to any role
        $common_caps = array(
            'read', 'edit_posts', 'edit_others_posts', 'edit_published_posts',
            'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts',
            'edit_pages', 'edit_others_pages', 'edit_published_pages',
            'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages',
            'manage_categories', 'manage_links', 'moderate_comments',
            'upload_files', 'import', 'unfiltered_html',
            'edit_themes', 'install_themes', 'switch_themes', 'edit_theme_options',
            'edit_plugins', 'install_plugins', 'activate_plugins',
            'manage_options', 'manage_users', 'create_users', 'edit_users', 'delete_users',
            'list_users', 'promote_users', 'remove_users'
        );
        
        $capabilities = array_unique(array_merge($capabilities, $common_caps));
        sort($capabilities);
        
        return $capabilities;
    }
    
    /**
     * Update role capabilities via AJAX
     */
    public function update_role_capabilities() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $role_name = sanitize_text_field($_POST['role_name']);
        $capabilities = isset($_POST['capabilities']) ? array_map('sanitize_text_field', $_POST['capabilities']) : array();
        
        $role = get_role($role_name);
        
        if (!$role) {
            wp_send_json_error(array('message' => __('Role not found', 'adminx-productivity')));
        }
        
        // Get all possible capabilities
        $all_capabilities = $this->get_all_capabilities();
        
        // Remove all capabilities first
        foreach ($all_capabilities as $cap) {
            $role->remove_cap($cap);
        }
        
        // Add selected capabilities
        foreach ($capabilities as $cap) {
            if (in_array($cap, $all_capabilities)) {
                $role->add_cap($cap);
            }
        }
        
        wp_send_json_success(array('message' => __('Role capabilities updated successfully', 'adminx-productivity')));
    }
    
    /**
     * Create custom role via AJAX
     */
    public function create_custom_role() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $role_name = sanitize_text_field($_POST['role_name']);
        $display_name = sanitize_text_field($_POST['display_name']);
        $capabilities = isset($_POST['capabilities']) ? array_map('sanitize_text_field', $_POST['capabilities']) : array();
        
        // Validate role name
        if (empty($role_name) || get_role($role_name)) {
            wp_send_json_error(array('message' => __('Role name already exists or is invalid', 'adminx-productivity')));
        }
        
        // Prepare capabilities array
        $caps_array = array();
        foreach ($capabilities as $cap) {
            $caps_array[$cap] = true;
        }
        
        // Create the role
        $result = add_role($role_name, $display_name, $caps_array);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Custom role created successfully', 'adminx-productivity')));
        } else {
            wp_send_json_error(array('message' => __('Failed to create custom role', 'adminx-productivity')));
        }
    }
    
    /**
     * Delete custom role via AJAX
     */
    public function delete_custom_role() {
        check_ajax_referer('adminx_productivity_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'adminx-productivity'));
        }
        
        $role_name = sanitize_text_field($_POST['role_name']);
        
        // Prevent deletion of default WordPress roles
        $default_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        
        if (in_array($role_name, $default_roles)) {
            wp_send_json_error(array('message' => __('Cannot delete default WordPress roles', 'adminx-productivity')));
        }
        
        // Check if role exists
        if (!get_role($role_name)) {
            wp_send_json_error(array('message' => __('Role not found', 'adminx-productivity')));
        }
        
        // Check if any users have this role
        $users_with_role = get_users(array('role' => $role_name));
        
        if (!empty($users_with_role)) {
            wp_send_json_error(array('message' => __('Cannot delete role that is assigned to users', 'adminx-productivity')));
        }
        
        // Delete the role
        remove_role($role_name);
        
        wp_send_json_success(array('message' => __('Custom role deleted successfully', 'adminx-productivity')));
    }
    
    /**
     * Get users by role
     */
    public function get_users_by_role($role_name) {
        return get_users(array('role' => $role_name));
    }
    
    /**
     * Assign role to user
     */
    public function assign_role_to_user($user_id, $role_name) {
        $user = new WP_User($user_id);
        
        if (!$user->exists()) {
            return false;
        }
        
        $user->set_role($role_name);
        return true;
    }
    
    /**
     * Add capability to user
     */
    public function add_capability_to_user($user_id, $capability) {
        $user = new WP_User($user_id);
        
        if (!$user->exists()) {
            return false;
        }
        
        $user->add_cap($capability);
        return true;
    }
    
    /**
     * Remove capability from user
     */
    public function remove_capability_from_user($user_id, $capability) {
        $user = new WP_User($user_id);
        
        if (!$user->exists()) {
            return false;
        }
        
        $user->remove_cap($capability);
        return true;
    }
}