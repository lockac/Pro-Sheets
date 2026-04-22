<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'prosheets_process_admin_actions');
function prosheets_process_admin_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'prosheets') return;
    $tables = get_option('prosheets_tables', array());

    if (isset($_GET['refresh_all'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_prosheets_%' OR option_name LIKE '_transient_timeout_prosheets_%'");
        wp_safe_redirect(admin_url('admin.php?page=prosheets&all_cleared=1'));
        exit;
    }
    if (isset($_GET['clear_cache']) && isset($_GET['id']) && current_user_can('manage_options')) {
        if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'prosheets_clear_cache')) {
            $tid = intval($_GET['id']);
            if (isset($tables[$tid])) {
                $t_key = 'prosheets_' . md5($tables[$tid]['sheet_id'] . $tables[$tid]['range']);
                delete_transient($t_key); delete_transient($t_key . '_etag');
                wp_safe_redirect(admin_url('admin.php?page=prosheets&cache_cleared=1'));
                exit;
            }
        }
    }
    if (isset($_GET['reset_defaults']) && isset($_GET['id']) && current_user_can('manage_options')) {
        if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'prosheets_reset_defaults')) {
            $tid = intval($_GET['id']);
            if (isset($tables[$tid])) {
                $mandatory = array('name', 'sheet_id', 'range', 'cache_time', 'cache_unit');
                $cleaned = array_intersect_key($tables[$tid], array_flip($mandatory));
                $tables[$tid] = $cleaned;
                update_option('prosheets_tables', $tables);
                wp_safe_redirect(admin_url('admin.php?page=prosheets&defaults_reset=1&t=' . time()));
                exit;
            }
        }
    }
    if (isset($_GET['clear_all_cache'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_prosheets_%' OR option_name LIKE '_transient_timeout_prosheets_%'");
        wp_safe_redirect(admin_url('admin.php?page=prosheets&all_cleared=1'));
        exit;
    }
    if (isset($_GET['delete_table'])) {
        unset($tables[$_GET['delete_table']]);
        update_option('prosheets_tables', $tables);
        wp_safe_redirect(admin_url('admin.php?page=prosheets'));
        exit;
    }
    if (isset($_POST['save_table_entry'])) {
        $id_to_save = !empty($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
        if (!$id_to_save) {
            $numeric_keys = array_filter(array_keys($tables), 'is_numeric');
            $id_to_save = !empty($numeric_keys) ? max($numeric_keys) + 1 : 1;
        }
        $defaults = get_option('prosheets_defaults', array());
        $posted = array(
            'name' => sanitize_text_field($_POST['table_name']), 'sheet_id' => sanitize_text_field($_POST['sheet_id']), 'range' => sanitize_text_field($_POST['range']),
            'cache_time' => intval($_POST['cache_time']), 'cache_unit' => sanitize_text_field($_POST['cache_unit']),
            'g_bg' => sanitize_hex_color($_POST['g_bg']), 'g_font' => intval($_POST['g_font']), 'g_txt' => sanitize_hex_color($_POST['g_txt']), 'g_align' => sanitize_text_field($_POST['g_align']), 'g_valign' => sanitize_text_field($_POST['g_valign']), 'g_b_clr' => sanitize_hex_color($_POST['g_b_clr']), 'g_b_thk' => intval($_POST['g_b_thk']),
            'g_bold' => isset($_POST['g_bold']) ? 1 : 0, 'g_italic' => isset($_POST['g_italic']) ? 1 : 0, 'g_underline' => isset($_POST['g_underline']) ? 1 : 0,
            'b_bg' => sanitize_hex_color($_POST['b_bg']), 'b_font' => intval($_POST['b_font']), 'b_txt' => sanitize_hex_color($_POST['b_txt']), 'b_align' => sanitize_text_field($_POST['b_align']), 'b_valign' => sanitize_text_field($_POST['b_valign']), 'b_b_clr' => sanitize_hex_color($_POST['b_b_clr']), 'b_b_thk' => intval($_POST['b_b_thk']),
            'b_bold' => isset($_POST['b_bold']) ? 1 : 0, 'b_italic' => isset($_POST['b_italic']) ? 1 : 0, 'b_underline' => isset($_POST['b_underline']) ? 1 : 0,
            'hi_en' => isset($_POST['hi_en']) ? 1 : 0, 'hi_bg' => sanitize_hex_color($_POST['hi_bg']), 'hi_txt' => sanitize_hex_color($_POST['hi_txt']), 'hi_opacity' => max(0, min(100, intval($_POST['hi_opacity']))),
            'hi_bold' => isset($_POST['hi_bold']) ? 1 : 0, 'hi_italic' => isset($_POST['hi_italic']) ? 1 : 0, 'hi_underline' => isset($_POST['hi_underline']) ? 1 : 0,
            't_b_en' => isset($_POST['t_b_en']) ? 1 : 0, 't_b_thk' => intval($_POST['t_b_thk']), 't_b_clr' => sanitize_hex_color($_POST['t_b_clr']), 't_b_rad' => intval($_POST['t_b_rad']), 't_b_shd' => sanitize_text_field($_POST['t_b_shd']), 't_b_pad_b' => sanitize_text_field($_POST['t_b_pad_b']),
            'h_en' => isset($_POST['h_en']) ? 1 : 0, 'h_rows' => intval($_POST['h_rows']), 'h_bg' => sanitize_hex_color($_POST['h_bg']), 'h_txt' => sanitize_hex_color($_POST['h_txt']), 'h_font' => intval($_POST['h_font']), 'h_align' => sanitize_text_field($_POST['h_align']), 'h_valign' => sanitize_text_field($_POST['h_valign']), 'h_b_clr' => sanitize_hex_color($_POST['h_b_clr']), 'h_b_thk' => intval($_POST['h_b_thk']),
            'h_bold' => isset($_POST['h_bold']) ? 1 : 0, 'h_italic' => isset($_POST['h_italic']) ? 1 : 0, 'h_underline' => isset($_POST['h_underline']) ? 1 : 0, 'h_case' => sanitize_text_field($_POST['h_case']),
            'f_en' => isset($_POST['f_en']) ? 1 : 0, 'f_rows' => intval($_POST['f_rows']), 'f_bg' => sanitize_hex_color($_POST['f_bg']), 'f_txt' => sanitize_hex_color($_POST['f_txt']), 'f_font' => intval($_POST['f_font']), 'f_align' => sanitize_text_field($_POST['f_align']), 'f_valign' => sanitize_text_field($_POST['f_valign']), 'f_b_clr' => sanitize_hex_color($_POST['f_b_clr']), 'f_b_thk' => intval($_POST['f_b_thk']),
            'f_bold' => isset($_POST['f_bold']) ? 1 : 0, 'f_italic' => isset($_POST['f_italic']) ? 1 : 0, 'f_underline' => isset($_POST['f_underline']) ? 1 : 0, 'f_case' => sanitize_text_field($_POST['f_case']),
            'l_en' => isset($_POST['l_en']) ? 1 : 0, 'l_cols' => intval($_POST['l_cols']), 'l_bg' => sanitize_hex_color($_POST['l_bg']), 'l_txt' => sanitize_hex_color($_POST['l_txt']), 'l_font' => intval($_POST['l_font']), 'l_align' => sanitize_text_field($_POST['l_align']), 'l_valign' => sanitize_text_field($_POST['l_valign']), 'l_b_clr' => sanitize_hex_color($_POST['l_b_clr']), 'l_b_thk' => intval($_POST['l_b_thk']),
            'l_bold' => isset($_POST['l_bold']) ? 1 : 0, 'l_italic' => isset($_POST['l_italic']) ? 1 : 0, 'l_underline' => isset($_POST['l_underline']) ? 1 : 0, 'l_case' => sanitize_text_field($_POST['l_case']),
            'r_en' => isset($_POST['r_en']) ? 1 : 0, 'r_cols' => intval($_POST['r_cols']), 'r_bg' => sanitize_hex_color($_POST['r_bg']), 'r_txt' => sanitize_hex_color($_POST['r_txt']), 'r_font' => intval($_POST['r_font']), 'r_align' => sanitize_text_field($_POST['r_align']), 'r_valign' => sanitize_text_field($_POST['r_valign']), 'r_b_clr' => sanitize_hex_color($_POST['r_b_clr']), 'r_b_thk' => intval($_POST['r_b_thk']),
            'r_bold' => isset($_POST['r_bold']) ? 1 : 0, 'r_italic' => isset($_POST['r_italic']) ? 1 : 0, 'r_underline' => isset($_POST['r_underline']) ? 1 : 0, 'r_case' => sanitize_text_field($_POST['r_case']),
            'l_align_to_col' => isset($_POST['l_align_to_col']) ? 1 : 0,
            'r_align_to_col' => isset($_POST['r_align_to_col']) ? 1 : 0,
            );

        $mandatory = ['name', 'sheet_id', 'range', 'cache_time', 'cache_unit'];
        $clean_overrides = [];
        foreach ($mandatory as $key) $clean_overrides[$key] = $posted[$key];
        foreach ($posted as $key => $value) {
            if (in_array($key, $mandatory)) continue;
            $default_val = isset($defaults[$key]) ? $defaults[$key] : '';
            if (!prosheets_values_match($value, $default_val)) $clean_overrides[$key] = $value;
        }
        $tables[$id_to_save] = $clean_overrides;
        update_option('prosheets_tables', $tables);
        wp_safe_redirect(admin_url('admin.php?page=prosheets'));
        exit;
    }
}