<?php
if (!defined('ABSPATH')) exit;

function ps_font_style($c, $prefix) {
    $css = '';
    if (!empty($c[$prefix.'_bold'])) {
        $css .= 'font-weight:bold;';
    } else {
        $css .= 'font-weight:normal;';
    }
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
    $a = shortcode_atts(array('id' => '', 'height' => '500'), $atts);
    $tables = get_option('prosheets_tables', array());
    if (!isset($tables[$a['id']])) return '';
    
    $c = prosheets_get_table_config($a['id']);
    $data = get_prosheets_data($c['sheet_id'], $c['range']);
    if (is_string($data)) return $data;
    
    $h_count = ps_v($c, 'h_en') ? intval(ps_v($c, 'h_rows', 1)) : 0;
    $f_count = ps_v($c, 'f_en') ? intval(ps_v($c, 'f_rows', 0)) : 0;
    $l_count = ps_v($c, 'l_en') ? intval(ps_v($c, 'l_cols', 0)) : 0;
    $r_count = ps_v($c, 'r_en') ? intval(ps_v($c, 'r_cols', 0)) : 0;
    $total = count($data);
    
    $border_style = ps_v($c,'t_b_en') ? ps_v($c,'t_b_thk',0).'px solid '.ps_v($c,'t_b_clr','#ddd') : 'none';
    $margin_bottom = (!empty($c['t_b_pad_b']) && trim($c['t_b_pad_b']) !== '') ? $c['t_b_pad_b'] : '0';
    $br = ps_v($c,'t_b_rad',0).'px';

    // 1. Base CSS (container, scrollbars, table structure)
    $out = "<style>
    .ps-c-{$a['id']} { width: 100%; overflow: hidden; border: {$border_style}; border-radius: {$br}; box-shadow: ".ps_v($c,'t_b_shd','none')."; background: ".ps_v($c,'g_bg','#fff')."; margin-bottom: {$margin_bottom}; position: relative; }
    .ps-c-{$a['id']}-scroll { width: 100%; max-height: {$a['height']}px; overflow: auto; border-radius: {$br}; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar { width: 12px; height: 12px; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-track { background: ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 6px; border: 3px solid ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-thumb:hover { background: #555; }
    .ps-c-{$a['id']}-scroll::-webkit-scrollbar-corner { background: ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll { scrollbar-width: thin; scrollbar-color: #888 ".ps_v($c,'g_bg','#fff')."; }
    .ps-c-{$a['id']}-scroll table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: auto; min-width: max-content; font-family: sans-serif; font-size: ".ps_v($c,'g_font',14)."px; }
    .ps-c-{$a['id']}-scroll th, .ps-c-{$a['id']}-scroll td { padding: 10px; border: ".ps_v($c,'g_b_thk',1)."px solid ".ps_v($c,'g_b_clr','#ddd')."; white-space: pre-wrap; word-wrap: break-word; vertical-align: ".ps_v($c,'g_valign','top')."; color: ".ps_v($c,'g_txt','#333')."; text-align: ".ps_v($c,'g_align','left')."; ".ps_font_style($c, 'g')." }
    .ps-c-{$a['id']}-scroll tbody td { background: ".ps_v($c,'b_bg','#fff')."; font-size: ".ps_v($c,'b_font',14)."px; color: ".ps_v($c,'b_txt','#333')."; text-align: ".ps_v($c,'b_align','left')."; vertical-align: ".ps_v($c,'b_valign','top')."; border: ".ps_v($c,'b_b_thk',1)."px solid ".ps_v($c,'b_b_clr','#ddd')."; ".ps_font_style($c, 'b')." }";
    
    // 2. Sticky Panes CSS (Freeze Panes)
    if ($h_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(n) { position: sticky; top: 0; z-index: 20; background: ".ps_v($c,'h_bg','#f9f9f9')." !important; color: ".ps_v($c,'h_txt','#333')." !important; text-align: ".ps_v($c,'h_align','center')." !important; vertical-align: ".ps_v($c,'h_valign','top')." !important; border: ".ps_v($c,'h_b_thk',1)."px solid ".ps_v($c,'h_b_clr','#ddd')." !important; font-size: ".ps_v($c,'h_font',14)."px !important; ".ps_font_style($c, 'h')." ".ps_text_case($c, 'h')." }";
    if ($f_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(n) { position: sticky; bottom: 0; z-index: 20; background: ".ps_v($c,'f_bg','#f9f9f9')." !important; color: ".ps_v($c,'f_txt','#333')." !important; text-align: ".ps_v($c,'f_align','center')." !important; vertical-align: ".ps_v($c,'f_valign','top')." !important; border: ".ps_v($c,'f_b_thk',1)."px solid ".ps_v($c,'f_b_clr','#ddd')." !important; font-size: ".ps_v($c,'f_font',14)."px !important; ".ps_font_style($c, 'f')." ".ps_text_case($c, 'f')." }";
    if ($l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tr *:nth-child(-n+{$l_count}) { position: sticky; left: 0; z-index: 10; background: ".ps_v($c,'l_bg','#f9f9f9')." !important; color: ".ps_v($c,'l_txt','#333')." !important; text-align: ".ps_v($c,'l_align','left')." !important; vertical-align: ".ps_v($c,'l_valign','top')." !important; border: ".ps_v($c,'l_b_thk',1)."px solid ".ps_v($c,'l_b_clr','#ddd')." !important; font-size: ".ps_v($c,'l_font',14)."px !important; ".ps_font_style($c, 'l')." ".ps_text_case($c, 'l')." }";
    if ($r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tr *:nth-last-child(-n+{$r_count}) { position: sticky; right: 0; z-index: 10; background: ".ps_v($c,'r_bg','#f9f9f9')." !important; color: ".ps_v($c,'r_txt','#333')." !important; text-align: ".ps_v($c,'r_align','left')." !important; vertical-align: ".ps_v($c,'r_valign','top')." !important; border: ".ps_v($c,'r_b_thk',1)."px solid ".ps_v($c,'r_b_clr','#ddd')." !important; font-size: ".ps_v($c,'r_font',14)."px !important; ".ps_font_style($c, 'r')." ".ps_text_case($c, 'r')." }";
    if ($h_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}) { z-index: 30 !important; }";
    if ($h_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}) { z-index: 30 !important; }";
    if ($f_count > 0 && $l_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { z-index: 30 !important; }";
    if ($f_count > 0 && $r_count > 0) $out .= ".ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { z-index: 30 !important; }";
    // Corner Alignment Override (Align to Column)
    if ($l_count > 0 && !empty($c['l_align_to_col'])) {
        $l_align = ps_v($c, 'l_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-child(-n+{$l_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-child(-n+{$l_count}) { text-align: {$l_align} !important; }";
    }
    if ($r_count > 0 && !empty($c['r_align_to_col'])) {
        $r_align = ps_v($c, 'r_align', 'left');
        $out .= ".ps-c-{$a['id']}-scroll thead tr *:nth-last-child(-n+{$r_count}), .ps-c-{$a['id']}-scroll tfoot tr *:nth-last-child(-n+{$r_count}) { text-align: {$r_align} !important; }";
    }
    
    $out .= "</style>";

    // 3. HTML Table Output
    $out .= "<div class='prosheets-container ps-c-{$a['id']}'><div class='ps-c-{$a['id']}-scroll'><table><thead>";
    for($i=0; $i<$h_count && isset($data[$i]); $i++) { $out .= "<tr>"; foreach($data[$i] as $cell) $out .= "<th>".esc_html($cell)."</th>"; $out .= "</tr>"; }
    $out .= "</thead><tbody>";
    for($i=$h_count; $i<($total - $f_count); $i++) { if (!isset($data[$i])) continue; $out .= "<tr>"; foreach($data[$i] as $cell) $out .= "<td>".nl2br(esc_html($cell))."</td>"; $out .= "</tr>"; }
    $out .= "</tbody><tfoot>";
    for($i=max($total - $f_count, $h_count); $i<$total; $i++) { if (!isset($data[$i])) continue; $out .= "<tr>"; foreach($data[$i] as $cell) $out .= "<td>".nl2br(esc_html($cell))."</td>"; $out .= "</tr>"; }
    $out .= "</tfoot></table></div></div>";

    // 4. HOVER HIGHLIGHT CSS - USING BOX-SHADOW INSET FOR TRUE TRANSPARENT OVERLAY
    if (!empty($c['hi_en'])) {
        $hi_bg = trim(ps_v($c, 'hi_bg', '#ffff00'));
        $hi_txt = trim(ps_v($c, 'hi_txt', '#333333'));
        
        $opacity_percent = intval(ps_v($c, 'hi_opacity', 100));
        $hi_op = number_format($opacity_percent / 100, 2, '.', '');
        
        $hex = ltrim($hi_bg, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        
        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            
            $out .= "<style>
            .ps-c-{$a['id']}-scroll tbody tr:hover td {
                box-shadow: inset 0 0 0 9999px rgba({$r}, {$g}, {$b}, {$hi_op}) !important;
                color: {$hi_txt} !important;
                " . ps_font_style($c, 'hi') . "
                transition: box-shadow 0.2s ease, color 0.2s ease;
            }
            </style>";
        }
    }

    return $out;
}