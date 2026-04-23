<?php
/**
 * Plugin Name: Pro Sheets
 * Description: Display Google Sheets as styled tables in WordPress with advanced customization options.
 * Version: 1.0.0
 * Author: Adrian Lock
 * Text Domain: Pro Sheets
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Define plugin paths
define('PROSHEETS_DIR', plugin_dir_path(__FILE__));
define('PROSHEETS_URL', plugin_dir_url(__FILE__));

// Load helpers FIRST so they are available to all other files
require_once PROSHEETS_DIR . 'includes/functions.php';

// Load core files
require_once PROSHEETS_DIR . 'includes/admin.php';
require_once PROSHEETS_DIR . 'includes/Settings.php';
require_once PROSHEETS_DIR . 'includes/Defaults-Settings.php';
require_once PROSHEETS_DIR . 'includes/shortcode.php';

// Register uninstall hook for clean removal
if (!function_exists('prosheets_register_uninstall_hook')) {
    function prosheets_register_uninstall_hook() {
        register_uninstall_hook(__FILE__, 'prosheets_uninstall_stub');
    }
    add_action('plugins_loaded', 'prosheets_register_uninstall_hook');
}

/**
 * Stub function for uninstall hook
 * WordPress calls this, but actual logic is in includes/uninstall.php
 */
function prosheets_uninstall_stub() {
    // Logic is handled by includes/uninstall.php via root uninstall.php
    // This stub satisfies WordPress's register_uninstall_hook requirement
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'prosheets_enqueue_admin_assets');
function prosheets_enqueue_admin_assets($hook) {
    // Load admin.css on ALL admin pages so sidebar icon styles work everywhere
    wp_enqueue_style('prosheets-admin', PROSHEETS_URL . 'css/admin.css', array(), '1.0.0');
    
    // Only load color picker & JS on our specific plugin pages
    if (strpos($hook, 'prosheets') === false && strpos($hook, 'protable') === false) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('prosheets-script', PROSHEETS_URL . 'js/prosheets.js', array('jquery', 'wp-color-picker'), '1.0.0', true);
}

// Enqueue frontend assets
add_action('wp_enqueue_scripts', 'prosheets_enqueue_frontend_assets');
function prosheets_enqueue_frontend_assets() {
    wp_enqueue_style('prosheets-frontend', PROSHEETS_URL . 'css/sheets.css', array(), '1.0.0');
}

/**
 * Add uninstall warning notice on plugin list page
 */
// add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'prosheets_add_uninstall_warning');
// function prosheets_add_uninstall_warning($links) {
//     $warning = '<span style="color:#dc3232;font-size:11px;" title="Deleting this plugin will permanently remove all Pro Tables settings and cached data.">⚠️ Deletes all settings</span>';
//     array_unshift($links, $warning);
//     return $links;
// }

/**
 * Optional: Add admin notice on plugin settings page
 */
// add_action('admin_notices', 'prosheets_uninstall_settings_notice');
// function prosheets_uninstall_settings_notice() {
//     $screen = get_current_screen();
//     if ($screen && strpos($screen->id, 'prosheets') !== false) {
//         echo '<div class="notice notice-warning is-dismissible"><p><strong>Heads up:</strong> Deleting the Pro Tables plugin will permanently remove all your table configurations and cached data. Export any important settings first.</p></div>';
//     }
// }

