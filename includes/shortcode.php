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
    $a = shortcode_atts(array('id' => '', 'height' => '500'), $atts);
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
    
    $start_row = isset($bounds['start_row']) ? intval($bounds['start_row']) : 0;
    $start_col = isset($bounds['start_col']) ? intval($bounds['start_col']) : 0;
    
    $h_count = ps_v($c, 'h_en') ? intval(ps_v($c, 'h_rows', 1)) : 0;
    $f_count = ps_v($c, 'f_en') ? intval(ps_v($c, 'f_rows', 0)) : 0;
    $l_count = ps_v($c, 'l_en') ? intval(ps_v($c, 'l_cols', 0)) : 0;
    $r_count = ps_v($c, 'r_en') ? intval(ps_v($c, 'r_cols', 0)) : 0;
    $total   = count($values);
    
    $border_style  = ps_v($c,'t_b_en') ? ps_v($c,'t_b_thk',0).'px solid '.ps_v($c,'t_b_clr','#ddd') : 'none';
    $margin_bottom = (!empty($c['t_b_pad_b']) && trim($c['t_b_pad_b']) !== '') ? $c['t_b_pad_b'] : '0';
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
    .ps-c-{$a['id']} {display: inline-grid; overflow:hidden; border:{$border_style}; border-radius:{$br}; box-shadow:".ps_v($c,'t_b_shd','none')."; background:".ps_v($c,'g_bg','#fff')."; margin-bottom:{$margin_bottom}; position:relative; }
    .ps-c-{$a['id']}-scroll { width:auto !important; max-width:100%; max-height:{$a['height']}px; overflow:auto; overflow-y:auto;-webkit-overflow-scrolling:touch;border-radius:{$br}; display:block;}    
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar { width:12px; height:12px; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-track { background:".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb { background:#888; border-radius:6px; border:3px solid ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb:hover { background:#555; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-corner { background:".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll { scrollbar-width:thin; scrollbar-color:#888 ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll table { width:fit-content; min-width:0; border-collapse:separate; border-spacing:0; table-layout:fixed; font-family:sans-serif; font-size:".ps_v($c,'g_font',14)."px; }
    .ps-c-{$a['id']}-scroll th, .ps-c-{$a['id']}-scroll td { box-sizing:border-box; padding:10px; border:".ps_v($c,'g_b_thk',1)."px solid ".ps_v($c,'g_b_clr','#ddd')."; white-space:pre-wrap; word-wrap:break-word; vertical-align:".ps_v($c,'g_valign','top')."; color:".ps_v($c,'g_txt','#333')."; text-align:".ps_v($c,'g_align','left')."; ".ps_font_style($c,'g')." }
    .ps-c-{$a['id']}-scroll tbody td { font-size:".ps_v($c,'b_font',14)."px; color:".ps_v($c,'b_txt','#333')."; text-align:".ps_v($c,'b_align','left')."; vertical-align:".ps_v($c,'b_valign','top')."; border:".ps_v($c,'b_b_thk',1)."px solid ".ps_v($c,'b_b_clr','#ddd')."; ".ps_font_style($c,'b')." }";

    // Sticky Panes CSS
    if ($h_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(n) { position:sticky; top:0; z-index:20; background:".ps_v($c,'h_bg','#f9f9f9')." !important; color:".ps_v($c,'h_txt','#333')." !important; text-align:".ps_v($c,'h_align','center')." !important; vertical-align:".ps_v($c,'h_valign','top')." !important; border:".ps_v($c,'h_b_thk',1)."px solid ".ps_v($c,'h_b_clr','#ddd')." !important; font-size:".ps_v($c,'h_font',14)."px !important; ".ps_font_style($c,'h')." ".ps_text_case($c,'h')." }";
    if ($f_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(n) { position:sticky; bottom:0; z-index:20; background:".ps_v($c,'f_bg','#f9f9f9')." !important; color:".ps_v($c,'f_txt','#333')." !important; text-align:".ps_v($c,'f_align','center')." !important; vertical-align:".ps_v($c,'f_valign','top')." !important; border:".ps_v($c,'f_b_thk',1)."px solid ".ps_v($c,'f_b_clr','#ddd')." !important; font-size:".ps_v($c,'f_font',14)."px !important; ".ps_font_style($c,'f')." ".ps_text_case($c,'f')." }";
    if ($l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tr *:nth-child(-n+{$l_count}) { position:sticky; left:0; z-index:10; background:".ps_v($c,'l_bg','#f9f9f9')." !important; color:".ps_v($c,'l_txt','#333')." !important; text-align:".ps_v($c,'l_align','left')." !important; vertical-align:".ps_v($c,'l_valign','top')." !important; border:".ps_v($c,'l_b_thk',1)."px solid ".ps_v($c,'l_b_clr','#ddd')." !important; font-size:".ps_v($c,'l_font',14)."px !important; ".ps_font_style($c,'l')." ".ps_text_case($c,'l')." }";
    if ($r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tr *:nth-last-child(-n+{$r_count}) { position:sticky; right:0; z-index:10; background:".ps_v($c,'r_bg','#f9f9f9')." !important; color:".ps_v($c,'r_txt','#333')." !important; text-align:".ps_v($c,'r_align','left')." !important; vertical-align:".ps_v($c,'r_valign','top')." !important; border:".ps_v($c,'r_b_thk',1)."px solid ".ps_v($c,'r_b_clr','#ddd')." !important; font-size:".ps_v($c,'r_font',14)."px !important; ".ps_font_style($c,'r')." ".ps_text_case($c,'r')." }";
    if ($h_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}) { z-index:30 !important; }";
    if ($h_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}) { z-index:30 !important; }";
    if ($f_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { z-index:30 !important; }";
    if ($f_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { z-index:30 !important; }";
    if ($l_count > 0 && !empty($c['l_align_to_col'])) {
        $l_align = ps_v($c,'l_align','left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { text-align:{$l_align} !important; }";
    }
    if ($r_count > 0 && !empty($c['r_align_to_col'])) {
        $r_align = ps_v($c,'r_align','left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { text-align:{$r_align} !important; }";
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

    $render_row = function($row_data, $row_idx, $tag) use (&$merge_map, $max_cols, $colors, $c, $l_count, $r_count) {
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
            // Apply sheet background color (NO !important so Admin Settings win)
            $style = '';
            if (!empty($colors[$row_idx][$ci])) {
                $style = " style='background-color:{$colors[$row_idx][$ci]};'";
            }
            // $html .= "<{$tag}{$attrs}{$style}>" . nl2br(esc_html($cell)) . "</{$tag}>";
            $html .= "<{$tag}{$attrs}{$style}>" . esc_html($cell) . "</{$tag}>";
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