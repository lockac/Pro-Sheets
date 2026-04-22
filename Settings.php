<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    $icon_url = plugins_url('prosheets.svg', dirname(__FILE__, 1)); 
    add_menu_page('Pro Sheets', 'Pro Sheets', 'manage_options', 'prosheets', 'prosheets_master_router', $icon_url, 80);
    add_submenu_page('prosheets', 'Default Settings', 'Default Settings', 'manage_options', 'prosheets-defaults', 'prosheets_defaults_page_html');
    add_submenu_page('prosheets', 'API Settings', 'API Settings', 'manage_options', 'prosheets-api', 'prosheets_api_page_html');
});

function prosheets_master_router() {
    $view = isset($_GET['view']) ? $_GET['view'] : 'list';
    $tables = get_option('prosheets_tables', array());
    $header_icon_url = plugins_url('prosheets.svg', dirname(__FILE__, 1));
    if (isset($_GET['defaults_reset'])) echo '<div class="updated notice is-dismissible"><p>Table reset to default settings.</p></div>';
    if (isset($_GET['cache_cleared'])) echo '<div class="updated notice is-dismissible"><p>Cache refreshed.</p></div>';
    if (isset($_GET['all_cleared'])) echo '<div class="updated notice is-dismissible"><p>All caches cleared successfully.</p></div>';
    ?>
    <div class="wrap prosheets-admin-wrap">
        <h1><img src="<?php echo $header_icon_url; ?>" class="prosheets-header-icon">Pro Sheets</h1>
        <hr class="wp-header-end">

        <?php if ($view === 'list'): ?>
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="?page=prosheets&view=edit" class="button button-primary">Add New Table</a>
            <a href="?page=prosheets&refresh_all=1" class="button" onclick="return confirm('Refresh all data from Google Sheets?')">Refresh All Cache</a>
        </div>
        <table class="prosheets-list">
            <thead>
                <tr><th class="col-id">ID</th><th>Table Name</th><th>Sheet ID</th><th>Range</th><th class="col-shortcode">Shortcode</th><th class="col-actions">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $id => $t): $sc = '[prosheets id="'.$id.'"]'; ?>
                <tr>
                    <td class="col-id"><?php echo $id; ?></td>
                    <td><strong><?php echo esc_html($t['name']); ?></strong></td>
                    <td><?php echo esc_html($t['sheet_id']); ?></td>
                    <td><?php echo esc_html($t['range']); ?></td>
                    <td class="col-shortcode">
                        <span class="ps-code-badge"><?php echo $sc; ?></span>
                        <button class="icon-btn ps-copy-btn" title="Copy" data-code='<?php echo $sc; ?>'><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></button>
                    </td>
                    <td class="col-actions">
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=prosheets&clear_cache=1&id=' . $id), 'prosheets_clear_cache')); ?>" onclick="return confirm('Refresh data from this Google Sheet?');" class="icon-btn" title="Refresh"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg></a>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=prosheets&reset_defaults=1&id=' . $id), 'prosheets_reset_defaults')); ?>" class="icon-btn" title="Reset to Defaults" onclick="return confirm('Reset this table to default settings?');"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg></a>
                        <a href="?page=prosheets&view=edit&id=<?php echo $id; ?>" class="icon-btn" title="Edit"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                        <a href="?page=prosheets&delete_table=<?php echo $id; ?>" class="icon-btn" title="Delete" onclick="return confirm('Delete permanently?')"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        jQuery(document).ready(function($) {
            $('.ps-copy-btn').click(function(e) {
                e.preventDefault();
                var text = $(this).data('code');
                var $temp = $("<input>"); $("body").append($temp);
                $temp.val(text).select(); document.execCommand("copy");
                $temp.remove(); var $btn = $(this); $btn.css('color', '#46b450');
                setTimeout(function(){ $btn.css('color', '#888'); }, 1000);
            });
        });
        </script>
        <?php endif; ?>

        <?php if ($view === 'edit'): 
            $edit_id = isset($_GET['id']) ? intval($_GET['id']) : null;
            $d = prosheets_get_table_config($edit_id);
        ?>
        <?php echo prosheets_render_settings_form($d, false); ?>
        <?php endif; ?>
    </div>
    <?php
}
    
function prosheets_api_page_html() {
    if (isset($_POST['save_api_key'])) {
        check_admin_referer('prosheets_save_api', 'prosheets_nonce');
        $raw_key = isset($_POST['prosheets_api_key']) ? sanitize_text_field($_POST['prosheets_api_key']) : '';
        update_option('prosheets_encrypted_api_key', prosheets_encrypt_key($raw_key));
        echo '<div class="updated notice is-dismissible"><p>API Key Saved Successfully.</p></div>';
    }
    $saved_enc = get_option('prosheets_encrypted_api_key', '');
    $saved_key = prosheets_decrypt_key($saved_enc);
    ?>
    <div class="wrap prosheets-admin-wrap"><h1>API Settings</h1>
        <div class="card" style="max-width: 600px; padding: 20px; margin-top:20px; background:#fff; border:1px solid #ccd0d4; border-radius:4px;">
            <form method="post"><?php wp_nonce_field('prosheets_save_api', 'prosheets_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="prosheets_api_key">Google API Key</label>
                        </th>
                        <td>
                            <input type="password" id="prosheets_api_key" name="prosheets_api_key" value="<?php echo esc_attr($saved_key); ?>" class="regular-text ps-api-input" placeholder="Google API Key"><p class="description">Found in <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>. Must have "Google Sheets API" enabled.</p>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="save_api_key" class="button button-primary" value="Save">
            </form>
        </div>
    </div>
    <?php
}