<?php
/**
 * Pro Tables Uninstall Script
 * Runs automatically when plugin is deleted via WordPress admin.
 * 
 * @package ProTables
 * @author  Adrian Lock
 */

// Exit if not triggered by WordPress uninstall process
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security: Only allow admins to execute
if (!current_user_can('activate_plugins')) {
    exit;
}

// 1. Delete all plugin options
delete_option('prosheets_tables');
delete_option('prosheets_defaults');
delete_option('prosheets_version');
delete_option('prosheets_installed');
delete_option('prosheets_encrypted_api_key'); // Added missing option

// 2. Clear Google Sheets cache transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_prosheets_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_prosheets_%'");

// 3. Remove scheduled cache refresh events
wp_clear_scheduled_hook('prosheets_refresh_cache');
