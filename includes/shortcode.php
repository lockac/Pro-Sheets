<?php
if (!defined('ABSPATH')) exit;

function ps_font_style($c, $prefix) {
    $css = '';
    if (!empty($c[$prefix.'_bold'])) $css .= 'font-weight:bold;'; else $css .= 'font-weight:normal;';
    if (!empty($c[$prefix.'_italic'])) $css .= 'font-style:italic;';
    if (!empty($c[$prefix.'_underline'])) $css .= 'text-decoration:underline;';
    return $css;
}

function ps_text_case($c, $prefix) {
    $case = isset($c[$prefix.'_case']) ? $c[$prefix.'_case'] : '';
    if ($case === 'lower') return 'text-transform:lowercase;';
    if ($case === 'upper') return 'text-transform:uppercase;';
    if ($case === 'proper') return 'text-transform:capitalize;';
    return '';
}

add_shortcode('prosheets', 'prosheets_shortcode');
function prosheets_shortcode($atts) {
    // 1. Shortcode Attributes & Config
    $a = shortcode_atts(array('id' => '', 'height' => 'auto'), $atts);
    
    $tables = get_option('prosheets_tables', array());
    if (!isset($tables[$a['id']])) return '';
    
    $c = prosheets_get_table_config($a['id']);
    
    // 2. Fetch Data
    $data = get_prosheets_data($c['sheet_id'], $c['range'], $a['id']);
    if (isset($data['error'])) return '<p style="color:#dc3232; padding:10px; background:#ffe0e0; border:1px solid #dc3232;">' . esc_html($data['error']) . '</p>';
    
    // 3. Variable Extraction
    $values     = isset($data['values']) ? $data['values'] : [];
    $merges     = isset($data['merges']) ? $data['merges'] : [];
    $col_widths = isset($data['col_widths']) ? $data['col_widths'] : [];
    $colors     = isset($data['colors']) ? $data['colors'] : [];
    $bounds     = isset($data['range_bounds']) ? $data['range_bounds'] : [];
    $font_colors = isset($data['font_colors']) ? $data['font_colors'] : [];
    
    $start_row = isset($bounds['start_row']) ? intval($bounds['start_row']) : 0;
    $start_col = isset($bounds['start_col']) ? intval($bounds['start_col']) : 0;
    
    $h_count = ps_v($c, 'h_en') ? intval(ps_v($c, 'h_rows', 1)) : 0;
    $f_count = ps_v($c, 'f_en') ? intval(ps_v($c, 'f_rows', 0)) : 0;
    $l_count = ps_v($c, 'l_en') ? intval(ps_v($c, 'l_cols', 0)) : 0;
    $r_count = ps_v($c, 'r_en') ? intval(ps_v($c, 'r_cols', 0)) : 0;
    $total   = count($values);
    
    $border_style  = ps_v($c,'t_b_en') ? ps_v($c,'t_b_thk',0).'px solid '.ps_v($c,'t_b_clr','#ddd') : 'none';
    $margin_bottom = (!empty($c['t_b_pad_b']) && trim($c['t_b_pad_b']) !== '') ? $c['t_b_pad_b'] : '0';
    $table_height  = (!empty($c['t_b_hght']) && trim($c['t_b_hght']) !== '') ? $c['t_b_hght'] : $a['height'];
    $br            = ps_v($c,'t_b_rad',0).'px';

    // 4. Merge Map Logic
    $merge_map = array();
    foreach ($merges as $m) {
        $abs_r0 = intval($m['startRowIndex']); $abs_r1 = intval($m['endRowIndex']);
        $abs_c0 = intval($m['startColumnIndex']); $abs_c1 = intval($m['endColumnIndex']);
        $r0 = $abs_r0 - $start_row; $r1 = $abs_r1 - $start_row;
        $c0 = $abs_c0 - $start_col; $c1 = $abs_c1 - $start_col;
        $merge_map[$r0][$c0] = array('colspan' => $c1 - $c0, 'rowspan' => $r1 - $r0);
        for ($ri = $r0; $ri < $r1; $ri++) {
            for ($ci = $c0; $ci < $c1; $ci++) {
                if ($ri === $r0 && $ci === $c0) continue;
                $merge_map[$ri][$ci] = 'skip';
            }
        }
    }

    // 5. CSS Output (Shrink-wrap + Scrollbars + Sticky Panes)
    $out = "<style>
    .ps-c-{$a['id']} {display: block; width: fit-content; max-width: 100%; margin: 0 auto; margin-bottom: {$margin_bottom}; overflow:hidden; border:{$border_style}; border-radius:{$br}; box-shadow:".ps_v($c,'t_b_shd','none')."; background:".ps_v($c,'g_bg','#fff')."; position:relative; }
    .ps-c-{$a['id']}-scroll { width:auto !important; max-width:100%; max-height:{$table_height}; overflow:auto; overflow-y:auto;-webkit-overflow-scrolling:touch;border-radius:{$br}; display:block;}    
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar { width:12px; height:12px; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-track { background:".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb { background:#888; border-radius:6px; border:3px solid ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb:hover { background:#555; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-corner { background:".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll { scrollbar-width:thin; scrollbar-color:#888 ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll table { width:fit-content; min-width:0; border-collapse:separate; border-spacing:0; table-layout:fixed; font-family:sans-serif; font-size:".ps_v($c,'g_font',14)."px; }
    .ps-c-{$a['id']}-scroll th, .ps-c-{$a['id']}-scroll td { box-sizing:border-box; padding:5px; border:".ps_v($c,'g_b_thk',1)."px solid ".ps_v($c,'g_b_clr','#ddd')."; white-space:pre-wrap; word-wrap:break-word; vertical-align:".ps_v($c,'g_valign','top')."; color:".ps_v($c,'g_txt','#333')."; text-align:".ps_v($c,'g_align','left')."; ".ps_font_style($c,'g')." }
    ".(!empty($c['row_hght']) ? ".ps-c-{$a['id']}-scroll th, .ps-c-{$a['id']}-scroll td { height:".esc_attr($c['row_hght'])."; min-height:".esc_attr($c['row_hght'])."; }" : "")."
    .ps-c-{$a['id']}-scroll tbody td { font-size:".ps_v($c,'b_font',14)."px; color:".ps_v($c,'b_txt','#333')."; text-align:".ps_v($c,'b_align','left')."; vertical-align:".ps_v($c,'b_valign','top')."; border:".ps_v($c,'b_b_thk',1)."px solid ".ps_v($c,'b_b_clr','#ddd')."; ".ps_font_style($c,'b')." }";
  
    //2. Sticky Panes CSS (Fixed Stacking Context + Align + Padding)

    if ($h_count > 0) {
        $h_ov = !empty($c['h_override_colors']);
        $h_bg = $h_ov ? 'background:'.ps_v($c,'h_bg','#f9f9f9').' !important;' : '';
        $h_tx = $h_ov ? 'color:'.ps_v($c,'h_txt','#333').' !important;' : '';
        $out .= ".ps-c-{$a['id']}-scroll thead { position:sticky; top:0; display:table-header-group; z-index:20;}";
        $out .= ".ps-c-{$a['id']}-scroll thead th { position:sticky; top:0; z-index:20 !important; line-height:1.3; {$h_bg} {$h_tx} text-align:".ps_v($c,'h_align','center')." !important; vertical-align:".ps_v($c,'h_valign','top')." !important; border:".ps_v($c,'h_b_thk',1)."px solid ".ps_v($c,'h_b_clr','#ddd')." !important; font-size:".ps_v($c,'h_font',14)."px !important; ".ps_font_style($c,'h')." ".ps_text_case($c,'h')." }";
    }
    if ($f_count > 0) {
        $f_ov = !empty($c['f_override_colors']);
        $f_bg = $f_ov ? 'background:'.ps_v($c,'f_bg','#f9f9f9').' !important;' : '';
        $f_tx = $f_ov ? 'color:'.ps_v($c,'f_txt','#333').' !important;' : '';
        $out .= ".ps-c-{$a['id']}-scroll tfoot { position:sticky; bottom:0; display:table-footer-group; }";
        $out .= ".ps-c-{$a['id']}-scroll tfoot td { position:sticky; bottom:0; z-index:20 !important; {$f_bg} {$f_tx} text-align:".ps_v($c,'f_align','center')." !important; vertical-align:".ps_v($c,'f_valign','top')." !important; border:".ps_v($c,'f_b_thk',1)."px solid ".ps_v($c,'f_b_clr','#ddd')." !important; font-size:".ps_v($c,'f_font',14)."px !important; ".ps_font_style($c,'f')." ".ps_text_case($c,'f')." }";
    }
    if ($l_count > 0) {
        $l_ov = !empty($c['l_override_colors']);
        $l_bg = $l_ov ? 'background:'.ps_v($c,'l_bg','#f9f9f9').' !important;' : '';
        $l_tx = $l_ov ? 'color:'.ps_v($c,'l_txt','#333').' !important;' : '';
        // Strictly body sidebar only (z-index 10)
        $out .= ".ps-c-{$a['id']}-scroll tbody tr td:nth-child(-n+{$l_count}) { position:sticky; left:0; z-index:10 !important; {$l_bg} {$l_tx} text-align:".ps_v($c,'l_align','left')." !important; vertical-align:".ps_v($c,'l_valign','top')." !important; border:".ps_v($c,'l_b_thk',1)."px solid ".ps_v($c,'l_b_clr','#ddd')." !important; font-size:".ps_v($c,'l_font',14)."px !important; ".ps_font_style($c,'l')." ".ps_text_case($c,'l')." }";
    }
    if ($r_count > 0) {
        $r_ov = !empty($c['r_override_colors']);
        $r_bg = $r_ov ? 'background:'.ps_v($c,'r_bg','#f9f9f9').' !important;' : '';
        $r_tx = $r_ov ? 'color:'.ps_v($c,'r_txt','#333').' !important;' : '';
        $out .= ".ps-c-{$a['id']}-scroll tbody tr td:nth-last-child(-n+{$r_count}) { position:sticky; right:0; z-index:10 !important; {$r_bg} {$r_tx} text-align:".ps_v($c,'r_align','left')." !important; vertical-align:".ps_v($c,'r_valign','top')." !important; border:".ps_v($c,'r_b_thk',1)."px solid ".ps_v($c,'r_b_clr','#ddd')." !important; font-size:".ps_v($c,'r_font',14)."px !important; ".ps_font_style($c,'r')." ".ps_text_case($c,'r')." }";
    }
    
    // Corner stacking (30 explicitly beats header 20 & sidebar 10)
    if ($h_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead th:nth-child(-n+{$l_count}) { position: sticky; left: 0; z-index:30 !important; }";
    if ($h_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead th:nth-last-child(-n+{$r_count}) { position: sticky; right: 0; z-index:30 !important; }";
    if ($f_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot td:nth-child(-n+{$l_count}) { position: sticky; left: 0; z-index:30 !important; }";
    if ($f_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot td:nth-last-child(-n+{$r_count}) { position: sticky; right: 0; z-index:30 !important; }";

    // Align to Column (Scoped across all sections)
    if ($l_count > 0 && !empty($c['l_align_to_col'])) {
        $l_align = ps_v($c, 'l_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead th:nth-child(-n+{$l_count}), .ps-c-{$a['id']}-scroll tbody td:nth-child(-n+{$l_count}), .ps-c-{$a['id']}-scroll tfoot td:nth-child(-n+{$l_count}) { text-align: {$l_align} !important; }";
    }
    if ($r_count > 0 && !empty($c['r_align_to_col'])) {
        $r_align = ps_v($c, 'r_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead th:nth-last-child(-n+{$r_count}), .ps-c-{$a['id']}-scroll tbody td:nth-last-child(-n+{$r_count}), .ps-c-{$a['id']}-scroll tfoot td:nth-last-child(-n+{$r_count}) { text-align: {$r_align} !important; }";
    }

    // Multi-row Header Padding Compression
    if ($h_count > 1) {
        $out .= ".ps-c-{$a['id']}-scroll thead tr:first-child th { padding-top: 5px !important; padding-bottom: 1px !important; }";
        $out .= ".ps-c-{$a['id']}-scroll thead tr:not(:first-child):not(:last-child) th { padding-top: 1px !important; padding-bottom: 1px !important; }";
        $out .= ".ps-c-{$a['id']}-scroll thead tr:last-child th { padding-top: 1px !important; padding-bottom: 5px !important; }";
    }
    $out .= "</style>";

    // 6. Colgroup (Exact Widths) & Render Row Function
    // FIX: Use exact column count from widths to prevent phantom columns
    $max_cols = count($col_widths);
    $colgroup = '<colgroup>';
    for ($ci = 0; $ci < $max_cols; $ci++) {
        $w = isset($col_widths[$ci]) ? $col_widths[$ci] : '100px';
        $colgroup .= "<col style='width:{$w};min-width:{$w};max-width:{$w};'>";
    }
    $colgroup .= '</colgroup>';

                $render_row = function($row_data, $row_idx, $tag) use (&$merge_map, $max_cols, $colors, $font_colors, $c, $l_count, $r_count, $h_count, $f_count, $total) {
        while (count($row_data) < $max_cols) $row_data[] = '';
        $html = '<tr>';
        foreach ($row_data as $ci => $cell) {
            if (isset($merge_map[$row_idx][$ci]) && $merge_map[$row_idx][$ci] === 'skip') continue;
            $attrs = '';
            if (isset($merge_map[$row_idx][$ci]) && is_array($merge_map[$row_idx][$ci])) {
                $cs = intval($merge_map[$row_idx][$ci]['colspan']);
                $rs = intval($merge_map[$row_idx][$ci]['rowspan']);
                if ($cs > 1) $attrs .= " colspan='{$cs}'";
                if ($rs > 1) $attrs .= " rowspan='{$rs}'";
            }
            
            // Build inline styles (NO !important so Admin Override wins when checked)
            $style = '';
            if (!empty($colors[$row_idx][$ci])) {
                $style .= "background-color:{$colors[$row_idx][$ci]};";
            }
            if (!empty($font_colors[$row_idx][$ci])) {
                $fc = str_replace(' ', '', $font_colors[$row_idx][$ci]);
                
                // Determine if cell is in a special zone (header, footer, or sidebar)
                $is_header = ($row_idx < $h_count);
                $is_footer = ($f_count > 0 && $row_idx >= $total - $f_count);
                $is_left   = ($l_count > 0 && $ci < $l_count);
                $is_right  = ($r_count > 0 && $ci >= $max_cols - $r_count);
                $is_special = $is_header || $is_footer || $is_left || $is_right;
                
                // Only filter default white/black for standard body cells.
                // Special zones get exact Sheet colors (even if white/black).
                if ($is_special || ($fc !== 'rgb(0,0,0)' && $fc !== 'rgba(0,0,0,0)' && $fc !== 'rgba(0,0,0,1)' && $fc !== 'rgb(255,255,255)' && $fc !== 'rgba(255,255,255,1)')) {
                    $style .= "color:{$font_colors[$row_idx][$ci]};";
                }
            }
            $style_attr = $style ? " style='{$style}'" : '';
            
            $html .= "<{$tag}{$attrs}{$style_attr}>" . esc_html($cell) . "</{$tag}>";
        }
        $html .= '</tr>';
        return $html;
    };

    // 7. HTML Table Output
    $out .= "<div class='prosheets-container ps-c-{$a['id']}'><div class='ps-c-{$a['id']}-scroll'><table>{$colgroup}<thead>";
    for ($i = 0; $i < $h_count && isset($values[$i]); $i++) $out .= $render_row($values[$i], $i, 'th');
    $out .= '</thead><tbody>';
    for ($i = $h_count; $i < ($total - $f_count); $i++) {
        if (!isset($values[$i])) continue;
        $out .= $render_row($values[$i], $i, 'td');
    }
    $out .= '</tbody><tfoot>';
    for ($i = max($total - $f_count, $h_count); $i < $total; $i++) {
        if (!isset($values[$i])) continue;
        $out .= $render_row($values[$i], $i, 'td');
    }
    $out .= '</tfoot></table></div></div>';

    // 8. Hover Highlight CSS
    if (!empty($c['hi_en'])) {
        $hi_bg  = trim(ps_v($c,'hi_bg','#ffff00'));
        $hi_txt = trim(ps_v($c,'hi_txt','#333333'));
        $opacity_percent = intval(ps_v($c,'hi_opacity',100));
        $hi_op = number_format($opacity_percent / 100, 2, '.', '');
        $hex = ltrim($hi_bg, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
            $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
            $out .= "<style>.ps-c-{$a['id']}-scroll tbody tr:hover td { box-shadow: inset 0 0 0 9999px rgba({$r},{$g},{$b},{$hi_op}) !important; color: {$hi_txt} !important; " . ps_font_style($c,'hi') . " transition: box-shadow 0.2s ease, color 0.2s ease; }</style>";
        }
    }
    return $out;
}
