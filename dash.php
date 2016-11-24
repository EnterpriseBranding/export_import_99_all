<?php
if (!defined('ABSPATH'))
    exit;

global $current_user;
$code = hash('md5', $current_user->ID . site_url());
$msg  = '';
$msg .= '<div class="wrap">
        <h1>' . __("Import Export Actions", "export_import_99_export_title") . '</h1>
            <div class="metabox-holder">
                <div class="postbox">
                    <h3>' . __("Export Data", "export_import_99_export_options") . '</h3>
                        <div class="inside">';
$msg .= '<form method="post" action="">';
$msg .= '<div class="e-x-99-input-field"><select name="export_import_globals" id="export_import_globals"><optgroup label="Post Types">';

$post_types = get_post_types();
$no_keys    = array(
    'attachment',
    'revision',
    'nav_menu_item',
    'acf-field-group',
    'acf-field',
    'wpcf7_contact_form'
);
$taxonomies = array();
$first      = '';
foreach ($post_types as $p) {
    if (!in_array($p, $no_keys)) {
        $det              = get_post_type_object($p);
        $taxonomy_objects = get_object_taxonomies($p, 'objects');
        $extras           = array();
        foreach ($taxonomy_objects as $k => $value) {
            $extras[] = $k;
            if (!array_key_exists($k, $taxonomies)) {
                $taxonomies[$k] = $value;
            }
        }

        $extra = !empty($extras) ? implode(',', $extras) : '';
        if ($first == '' && $extra != '') {
            $first = $extra;
        }
        $msg .= '<option data-type="post" value="' . $p . '" id="' . $p . '" data-terms="' . $extra . '">' . $det->labels->name . '</option>';
    }
}
$msg .= '</optgroup>';
if (!empty($taxonomies)) {
    $msg .= '<optgroup label="Terms">';
    foreach ($taxonomies as $k => $v) {
        $msg .= '<option id="' . $k . '" data-type="term" value="' . $k . '" data-posts="' . implode(',', $v->object_type) . '">' . $v->labels->singular_name . '</option>';
    }
    $msg .= '</optgroup>';
}
$msg .= '</select></div>';
$msg .= '<input type="hidden" name="terms" value="' . $first . '" class="fill_in">';
$msg .= '<input type="hidden" name="type" value="post" class="fill_in">';
$msg .= wp_nonce_field($code, 'export_import_99_actions');
$msg .= '<div class="e-x-99-input-field"><input type="submit" value="' . __('Export Content', 'export_import_99_export_button') . '" class="button-primary"/></div>';
$msg .= '</form>';
$msg .= '</div></div><br /><hr><br>';

$msg.='
<div class="postbox">
<h3>' . __("Import Data", "export_import_99_import_options") . '</h3>
<div class="inside">
<form method="post" enctype="multipart/form-data">
'.wp_nonce_field($code, 'export_import_99_actions');
$inputs=export_import_99_inputs();
foreach ($inputs['import_checks'] as $v)
{
    $class=$v[3] != 0 ? ' class="required" ' : '';
    $msg.='<div class="e-x-99-input-field"><'.$v[0].' name="'.$v[1].'" id="'.$v[1].'" '.$class.' />';
    $msg.=$v[2]!='' ? '<label for="'.$v[1].'">'.$v[2].'</label>' : '';
    $msg.='</div>';
}
$msg.='<div class="e-x-99-input-field"><input type="submit" value="Import Content" class="button-primary"/></div></form></div></div></div></div>';
echo $msg;





?>