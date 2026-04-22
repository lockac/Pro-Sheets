<?php
if (!defined('ABSPATH')) exit;

function prosheets_render_settings_form($data, $is_defaults = false) {
    $save_name = $is_defaults ? 'save_defaults_entry' : 'save_table_entry';
    $save_label = $is_defaults ? 'Save Default Settings' : 'Update Settings';
    $cancel_url = $is_defaults ? admin_url('admin.php?page=prosheets') : '?page=prosheets';
    $edit_id = $is_defaults ? '' : (isset($_GET['id']) ? intval($_GET['id']) : '');
    
    ob_start(); ?>
    <form method="post" action="<?php echo admin_url('admin.php?page=' . ($is_defaults ? 'prosheets-defaults' : 'prosheets')); ?>">
        <?php if (!$is_defaults): ?><input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>"><?php endif; ?>
        <div class="ps-editor-layout">
            <div class="ps-tabs-sidebar">
                <div class="ps-tab active" data-target="tab-conn">Sheet</div>
                <div class="ps-tab" data-target="tab-body">Body</div>
                <div class="ps-tab" data-target="tab-highlight">Highlight</div>
                <div class="ps-tab" data-target="tab-freeze">Freeze Panes</div>
                <div class="ps-tab" data-target="tab-border">Table Border</div>
            </div>
            <div class="ps-editor-content">
                
                <!-- SHEET TAB -->
                <div id="tab-conn" class="ps-tab-content active">
                    <table class="ps-sheet-table">
                        <?php if (!$is_defaults): ?>
                        <tr><th>Table Name</th><td><input type="text" name="table_name" class="regular-text" value="<?php echo ps_v($data, 'name'); ?>" placeholder="Price list 2024" required></td></tr>
                        <tr><th>Sheet ID</th><td><input type="text" name="sheet_id" class="regular-text" value="<?php echo ps_v($data, 'sheet_id'); ?>" required><span class="ps-help-text">Found in your Google Sheet URL between /d/ and /edit</span></td></tr>
                        <tr><th>Range</th><td><input type="text" name="range" class="regular-text" value="<?php echo ps_v($data, 'range'); ?>" placeholder="sheet1!A1:E50" required></td></tr>
                        <?php endif; ?>
                        <tr>
                            <th>Refresh Rate</th>
                            <td>
                                <div class="refresh-flex">
                                    <input type="number" name="cache_time" class="ps-size-refresh" value="<?php echo ps_v($data, 'cache_time', 12); ?>" min="1">
                                    <select name="cache_unit" style="width: 100px;">
                                        <option value="minutes" <?php selected(ps_v($data,'cache_unit'), 'minutes'); ?>>Minutes</option>
                                        <option value="hours" <?php selected(ps_v($data,'cache_unit','hours'), 'hours'); ?>>Hours</option>
                                        <option value="days" <?php selected(ps_v($data,'cache_unit'), 'days'); ?>>Days</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- BODY TAB -->
                <div id="tab-body" class="ps-tab-content">
                    <div class="freeze-flex-container">
                        <div class="freeze-section-col">
                            <h3>Body</h3>
                            <div class="freeze-field-row"><label>Background Color</label><input type="color" name="b_bg" value="<?php echo ps_v($data, 'b_bg', '#ffffff'); ?>"></div>
                            <div class="freeze-field-row"><label>Font Size (px)</label><input type="number" name="b_font" class="ps-size-50" value="<?php echo ps_v($data, 'b_font', 14); ?>" min="8"></div>
                            <div class="freeze-field-row"><label>Font Color</label><input type="color" name="b_txt" value="<?php echo ps_v($data, 'b_txt', '#333333'); ?>"></div>
                            <div class="freeze-field-row"><label>Font Style</label>
                                <div class="ps-style-group">
                                    <label class="ps-style-btn is-bold <?php echo ps_v($data,'b_bold') ? 'active' : ''; ?>"><input type="checkbox" name="b_bold" value="1" <?php checked(ps_v($data,'b_bold'),1); ?>>B</label>
                                    <label class="ps-style-btn is-italic <?php echo ps_v($data,'b_italic') ? 'active' : ''; ?>"><input type="checkbox" name="b_italic" value="1" <?php checked(ps_v($data,'b_italic'),1); ?>>I</label>
                                    <label class="ps-style-btn is-underline <?php echo ps_v($data,'b_underline') ? 'active' : ''; ?>"><input type="checkbox" name="b_underline" value="1" <?php checked(ps_v($data,'b_underline'),1); ?>>U</label>
                                </div>
                            </div>
                            <div class="freeze-field-row"><label>Align Horizontal</label><select name="b_align"><option value="left" <?php selected(ps_v($data,'b_align'),'left');?>>Left</option><option value="center" <?php selected(ps_v($data,'b_align'),'center');?>>Center</option><option value="right" <?php selected(ps_v($data,'b_align'),'right');?>>Right</option></select></div>
                            <div class="freeze-field-row"><label>Align Vertical</label><select name="b_valign"><option value="top" <?php selected(ps_v($data,'b_valign'),'top');?>>Top</option><option value="middle" <?php selected(ps_v($data,'b_valign','middle'),'middle');?>>Middle</option><option value="bottom" <?php selected(ps_v($data,'b_valign'),'bottom');?>>Bottom</option></select></div>
                            <div class="freeze-field-row"><label>Border Color</label><input type="color" name="b_b_clr" value="<?php echo ps_v($data, 'b_b_clr', '#dddddd'); ?>"></div>
                            <div class="freeze-field-row"><label>Thickness</label><input type="number" name="b_b_thk" class="ps-size-50" value="<?php echo ps_v($data, 'b_b_thk', 1); ?>" min="0"></div>
                        </div>
                    </div>
                </div>

                <!-- HIGHLIGHT TAB (With Working Transparency) -->
                <div id="tab-highlight" class="ps-tab-content">
                    <div class="freeze-section-col <?php echo ps_v($data, 'hi_en', 0) ? '' : 'section-disabled'; ?>">
                        <h3>Highlight <input type="checkbox" name="hi_en" class="ps-section-toggle" value="1" <?php checked(ps_v($data, 'hi_en', 0), 1); ?>></h3>
                        <div class="freeze-field-row"><label>Background</label><input type="text" id="protable-highlight-bg" name="hi_bg" class="my-color-picker" value="<?php echo ps_v($data, 'hi_bg', '#ffff00'); ?>"></div>
                        <div class="freeze-field-row"><label>Transparency</label>
                            <div class="ps-trans-slider-container">
                                <input type="range" id="protable-highlight-slider" name="hi_opacity" min="0" max="100" value="<?php echo esc_attr(ps_v($data, 'hi_opacity', 100)); ?>">
                                <span class="ps-trans-percent" id="hi-transparency-display"><?php echo esc_attr(ps_v($data, 'hi_opacity', 100)); ?>%</span>
                            </div>
                        </div>
                        <div class="freeze-field-row"><label>Font Color</label><input type="text" id="protable-highlight-text" name="hi_txt" class="my-color-picker" value="<?php echo ps_v($data, 'hi_txt', '#333333'); ?>"></div>
                        <div class="freeze-field-row"><label>Font Style</label>
                            <div class="ps-style-group">
                                <label class="ps-style-btn is-bold <?php echo ps_v($data,'hi_bold') ? 'active' : ''; ?>"><input type="checkbox" name="hi_bold" value="1" <?php checked(ps_v($data,'hi_bold'),1); ?>>B</label>
                                <label class="ps-style-btn is-italic <?php echo ps_v($data,'hi_italic') ? 'active' : ''; ?>"><input type="checkbox" name="hi_italic" value="1" <?php checked(ps_v($data,'hi_italic'),1); ?>>I</label>
                                <label class="ps-style-btn is-underline <?php echo ps_v($data,'hi_underline') ? 'active' : ''; ?>"><input type="checkbox" name="hi_underline" value="1" <?php checked(ps_v($data,'hi_underline'),1); ?>>U</label>
                            </div>
                        </div>
                    </div>
                </div>

              <!-- FREEZE PANES TAB -->
                <div id="tab-freeze" class="ps-tab-content">
                    <div class="freeze-flex-container">
                    <?php 
                    $groups = array(
                        'h'=>array('title'=>'Header (Top)','unit'=>'Rows','val'=>'h_rows'),
                        'f'=>array('title'=>'Footer (Bottom)','unit'=>'Rows','val'=>'f_rows'),
                        'l'=>array('title'=>'Left Sidebar','unit'=>'Columns','val'=>'l_cols'),
                        'r'=>array('title'=>'Right Sidebar','unit'=>'Columns','val'=>'r_cols')
                    );
                    foreach($groups as $p => $info): ?>
                    <div class="freeze-section-col <?php echo ps_v($data, $p.'_en', 0) ? '' : 'section-disabled'; ?>">
                        <h3><?php echo $info['title']; ?> <input type="checkbox" name="<?php echo $p; ?>_en" class="ps-section-toggle" value="1" <?php checked(ps_v($data, $p.'_en', 0), 1); ?>></h3>
                        <div class="freeze-field-row"><label><?php echo $info['unit']; ?></label><input type="number" name="<?php echo $info['val']; ?>" class="ps-size-50" value="<?php echo ps_v($data, $info['val'], ($p==='h'?1:0)); ?>" min="0"></div>
                        <div class="freeze-field-row"><label>Background</label><input type="color" name="<?php echo $p; ?>_bg" value="<?php echo ps_v($data, $p.'_bg', '#f9f9f9'); ?>"></div>
                        <div class="freeze-field-row"><label>Font Size</label><input type="number" name="<?php echo $p; ?>_font" class="ps-size-50" value="<?php echo ps_v($data, $p.'_font', 14); ?>" min="8"></div>
                        <div class="freeze-field-row"><label>Font Color</label><input type="color" name="<?php echo $p; ?>_txt" value="<?php echo ps_v($data, $p.'_txt', '#333333'); ?>"></div>
                        
                        <div class="freeze-field-row"><label>Font Style</label>
                            <div class="ps-style-group">
                                <label class="ps-style-btn is-bold <?php echo ps_v($data,$p.'_bold') ? 'active' : ''; ?>"><input type="checkbox" name="<?php echo $p; ?>_bold" value="1" <?php checked(ps_v($data,$p.'_bold'),1); ?>>B</label>
                                <label class="ps-style-btn is-italic <?php echo ps_v($data,$p.'_italic') ? 'active' : ''; ?>"><input type="checkbox" name="<?php echo $p; ?>_italic" value="1" <?php checked(ps_v($data,$p.'_italic'),1); ?>>I</label>
                                <label class="ps-style-btn is-underline <?php echo ps_v($data,$p.'_underline') ? 'active' : ''; ?>"><input type="checkbox" name="<?php echo $p; ?>_underline" value="1" <?php checked(ps_v($data,$p.'_underline'),1); ?>>U</label>
                            </div>
                        </div>

                        <div class="freeze-field-row"><label>Text Case</label>
                            <div class="ps-case-group">
                                <label class="ps-case-btn is-lower <?php echo ps_v($data,$p.'_case')==='lower' ? 'active' : ''; ?>" title="Convert to lowercase (abc)">
                                    <input type="radio" name="<?php echo $p; ?>_case" value="lower" <?php checked(ps_v($data,$p.'_case'),'lower'); ?>>ab
                                </label>
                                <label class="ps-case-btn is-upper <?php echo ps_v($data,$p.'_case')==='upper' ? 'active' : ''; ?>" title="Convert to UPPERCASE (ABC)">
                                    <input type="radio" name="<?php echo $p; ?>_case" value="upper" <?php checked(ps_v($data,$p.'_case'),'upper'); ?>>AB
                                </label>
                                <label class="ps-case-btn is-proper <?php echo ps_v($data,$p.'_case')==='proper' ? 'active' : ''; ?>" title="Convert to Proper Case (Abc)">
                                    <input type="radio" name="<?php echo $p; ?>_case" value="proper" <?php checked(ps_v($data,$p.'_case'),'proper'); ?>>Ab
                                </label>
                            </div>
                        </div>

                        <div class="freeze-field-row"><label>Align Horizontal</label><select name="<?php echo $p; ?>_align"><option value="left" <?php selected(ps_v($data,$p.'_align'),'left');?>>Left</option><option value="center" <?php selected(ps_v($data,$p.'_align','center'),'center');?>>Center</option><option value="right" <?php selected(ps_v($data,$p.'_align'),'right');?>>Right</option></select></div>
                        <div class="freeze-field-row"><label>Align Vertical</label><select name="<?php echo $p; ?>_valign"><option value="top" <?php selected(ps_v($data,$p.'_valign'),'top');?>>Top</option><option value="middle" <?php selected(ps_v($data,$p.'_valign','middle'),'middle');?>>Middle</option><option value="bottom" <?php selected(ps_v($data,$p.'_valign'),'bottom');?>>Bottom</option></select></div>
                        
                        <!-- NEW: Align to Column Checkbox (Only for Left/Right) -->
                        <?php if ($p === 'l' || $p === 'r'): ?>
                        <div class="freeze-field-row"><label>Align to Column</label><input type="checkbox" name="<?php echo $p; ?>_align_to_col" value="1" <?php checked(ps_v($data, $p.'_align_to_col', 0), 1); ?>></div>
                        <?php endif; ?>

                        <div class="freeze-field-row"><label>Border Color</label><input type="color" name="<?php echo $p; ?>_b_clr" value="<?php echo ps_v($data, $p.'_b_clr', '#dddddd'); ?>"></div>
                        <div class="freeze-field-row"><label>Thickness</label><input type="number" name="<?php echo $p; ?>_b_thk" class="ps-size-50" value="<?php echo ps_v($data, $p.'_b_thk', 1); ?>" min="0"></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <!-- TABLE BORDER TAB -->
                <div id="tab-border" class="ps-tab-content">
                    <div class="freeze-flex-container">
                        <div class="freeze-section-col <?php echo ps_v($data, 't_b_en', 0) ? '' : 'section-disabled'; ?>">
                            <h3>Table Border <input type="checkbox" name="t_b_en" class="ps-section-toggle" value="1" <?php checked(ps_v($data,'t_b_en',0),1); ?>></h3>
                            <div class="freeze-field-row"><label>Thickness</label><input type="number" name="t_b_thk" class="ps-size-50" value="<?php echo ps_v($data, 't_b_thk', 0); ?>" min="0"></div>
                            <div class="freeze-field-row"><label>Color</label><input type="color" name="t_b_clr" value="<?php echo ps_v($data, 't_b_clr', '#dddddd'); ?>"></div>
                            <div class="freeze-field-row"><label>Radius</label><input type="number" name="t_b_rad" class="ps-size-50" value="<?php echo ps_v($data, 't_b_rad', 0); ?>" min="0"></div>
                            <div class="freeze-field-row"><label>Box Shadow</label><input type="text" name="t_b_shd" class="ps-box-shadow-input" placeholder="0px 4px 10px rgba(0,0,0,0.1)" value="<?php echo ps_v($data, 't_b_shd', ''); ?>"></div>
                            <div class="freeze-field-row"><label>Padding Bottom</label><input type="text" name="t_b_pad_b" class="ps-padding-input" placeholder="e.g. 10px, 1em" value="<?php echo ps_v($data, 't_b_pad_b', ''); ?>"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div style="margin-top: 20px;"><input type="submit" name="<?php echo $save_name; ?>" class="button button-primary button-large" value="<?php echo $save_label; ?>"><a href="<?php echo $cancel_url; ?>" class="button button-large" style="margin-left:10px;">Cancel</a></div>
    </form>
    <?php
    return ob_get_clean();
}

function prosheets_defaults_page_html() {
    $defaults = get_option('prosheets_defaults', array());
    ?>
    <div class="wrap prosheets-admin-wrap">
        <h1>Default Settings</h1>
        <p class="description">These settings apply to all new tables. Individual table settings override these defaults.</p>
        <?php if (isset($_GET['defaults_saved'])): ?><div class="updated notice is-dismissible"><p>Default settings saved.</p></div><?php endif; ?>
        <?php echo prosheets_render_settings_form($defaults, true); ?>
    </div>
    <?php
}

add_action('admin_init', 'prosheets_process_defaults_save', 15);
function prosheets_process_defaults_save() {
    if (!isset($_POST['save_defaults_entry'])) return;
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $defaults = array(
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

    update_option('prosheets_defaults', $defaults);
    wp_safe_redirect(admin_url('admin.php?page=prosheets-defaults&defaults_saved=1'));
    exit;
}