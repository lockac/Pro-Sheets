<?php
if (!defined('ABSPATH')) exit;

function protable_shortcode($atts) {
    $a = shortcode_atts(array('id' => '', 'height' => '500'), $atts);
    $tables = get_option('protable_tables', array());
    if (!isset($tables[$a['id']])) return '';
    $c = $tables[$a['id']];
    $data = get_protable_data($c['sheet_id'], $c['range']);
    if (is_string($data)) return $data;
    
    $h_count = pt_v($c, 'h_en') ? intval(pt_v($c, 'h_rows', 1)) : 0;
    $f_count = pt_v($c, 'f_en') ? intval(pt_v($c, 'f_rows', 0)) : 0;
    $l_count = pt_v($c, 'l_en') ? intval(pt_v($c, 'l_cols', 0)) : 0;
    $r_count = pt_v($c, 'r_en') ? intval(pt_v($c, 'r_cols', 0)) : 0;
    $total = count($data);
    
    // Outer Border & Shadow logic
    $border_style = pt_v($c,'t_b_en') ? pt_v($c,'t_b_thk',0).'px solid '.pt_v($c,'t_b_clr','#ddd') : 'none';

    $out = "<style>
    .pt-c-{$a['id']} { 
        width: 100%; 
        overflow-x: auto; 
        max-height: {$a['height']}px; 
        border: {$border_style}; 
        border-radius: ".pt_v($c,'t_b_rad',0)."px; 
        box-shadow: ".pt_v($c,'t_b_shd','none').";
        background: ".pt_v($c,'g_bg','#fff')."; 
    }
    .pt-c-{$a['id']} table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: auto; min-width: max-content; font-family: sans-serif; font-size: ".pt_v($c,'g_font',14)."px; }
    .pt-c-{$a['id']} th, .pt-c-{$a['id']} td { padding: 10px; border: ".pt_v($c,'g_b_thk',1)."px solid ".pt_v($c,'g_b_clr','#ddd')."; white-space: pre-wrap; word-wrap: break-word; vertical-align: ".pt_v($c,'g_valign','top')."; color: ".pt_v($c,'g_txt','#333')."; text-align: ".pt_v($c,'g_align','left')."; }
    
    /* Main Body Styling */
    .pt-c-{$a['id']} tbody td { 
        background: ".pt_v($c,'b_bg','#fff')."; 
        font-size: ".pt_v($c,'b_font',14)."px; 
        color: ".pt_v($c,'b_txt','#333')."; 
        text-align: ".pt_v($c,'b_align','left')."; 
        vertical-align: ".pt_v($c,'b_valign','top').";
        border: ".pt_v($c,'b_b_thk',1)."px solid ".pt_v($c,'b_b_clr','#ddd')."; 
    }";
    
    if ($h_count > 0) {
        $out .= ".pt-c-{$a['id']} thead tr *:nth-child(n) { position: sticky; top: 0; z-index: 10; background: ".pt_v($c,'h_bg','#f9f9f9')." !important; color: ".pt_v($c,'h_txt','#333')." !important; text-align: ".pt_v($c,'h_align','center')." !important; vertical-align: ".pt_v($c,'h_valign','top')." !important; border: ".pt_v($c,'h_b_thk',1)."px solid ".pt_v($c,'h_b_clr','#ddd')." !important; font-size: ".pt_v($c,'h_font',14)."px !important; }";
    }
    if ($f_count > 0) {
        $out .= ".pt-c-{$a['id']} tfoot tr *:nth-child(n) { position: sticky; bottom: 0; z-index: 10; background: ".pt_v($c,'f_bg','#f9f9f9')." !important; color: ".pt_v($c,'f_txt','#333')." !important; text-align: ".pt_v($c,'f_align','center')." !important; vertical-align: ".pt_v($c,'f_valign','top')." !important; border: ".pt_v($c,'f_b_thk',1)."px solid ".pt_v($c,'f_b_clr','#ddd')." !important; font-size: ".pt_v($c,'f_font',14)."px !important; }";
    }
    if ($l_count > 0) {
        $out .= ".pt-c-{$a['id']} tr *:nth-child(-n+{$l_count}) { position: sticky; left: 0; z-index: 5; background: ".pt_v($c,'l_bg','#f9f9f9')." !important; color: ".pt_v($c,'l_txt','#333')." !important; text-align: ".pt_v($c,'l_align','left')." !important; vertical-align: ".pt_v($c,'l_valign','top')." !important; border: ".pt_v($c,'l_b_thk',1)."px solid ".pt_v($c,'l_b_clr','#ddd')." !important; font-size: ".pt_v($c,'l_font',14)."px !important; }";
    }
    if ($r_count > 0) {
        $out .= ".pt-c-{$a['id']} tr *:nth-last-child(-n+{$r_count}) { position: sticky; right: 0; z-index: 5; background: ".pt_v($c,'r_bg','#f9f9f9')." !important; color: ".pt_v($c,'r_txt','#333')." !important; text-align: ".pt_v($c,'r_align','left')." !important; vertical-align: ".pt_v($c,'r_valign','top')." !important; border: ".pt_v($c,'r_b_thk',1)."px solid ".pt_v($c,'r_b_clr','#ddd')." !important; font-size: ".pt_v($c,'r_font',14)."px !important; }";
    }
    
    $out .= "</style><div class='protable-container pt-c-{$a['id']}'><table><thead>";
    
    // Header Logic
    for($i=0; $i<$h_count && isset($data[$i]); $i++) {
        $out .= "<tr>"; foreach($data[$i] as $cell) { $out .= "<th>".esc_html($cell)."</th>"; } $out .= "</tr>";
    }
    $out .= "</thead><tbody>";
    
    // Body Logic
    for($i=$h_count; $i<($total - $f_count); $i++) {
        if (!isset($data[$i])) continue;
        $out .= "<tr>"; foreach($data[$i] as $cell) { $out .= "<td>".nl2br(esc_html($cell))."</td>"; } $out .= "</tr>";
    }
    $out .= "</tbody><tfoot>";
    
    // Footer Logic
    for($i=max($total - $f_count, $h_count); $i<$total; $i++) {
        if (!isset($data[$i])) continue;
        $out .= "<tr>"; foreach($data[$i] as $cell) { $out .= "<td>".nl2br(esc_html($cell))."</td>"; } $out .= "</tr>";
    }
    
    $out .= "</tfoot></table></div>";
    return $out;
}
add_shortcode('protable', 'protable_shortcode');
