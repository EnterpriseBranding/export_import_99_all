<?php
/*
Plugin Name: Export Import 99%
Plugin URI: https://www.motivar.io
Description: Json Export-Import great for transfers
Version: 0.2.1
Author: Anastasiou K., Giannopoulos N.
Author URI: https://motivar.io
*/





if (!defined('WPINC')) {
    die;
}

add_action('admin_menu', 'export_import_99_menu');

define('e_x_99', 'export_import_99_');

function export_import_99_menu()
{
    /*check if super admin and then display it*/
    if (is_super_admin()) {
        add_menu_page('Export / Import', __('99% Actions', 'export_import_99_title'), 'manage_options', 'export_import_99', 'export_import_99_admin_setup');

        /*load dynamic the scripts*/
        $path = plugin_dir_path(__FILE__) . '/scripts/';
        /*check which dynamic scripts should be loaded*/
        if (file_exists($path)) {
            $paths = array(
                'js',
                'css'
            );
            foreach ($paths as $kk) {
                $check = glob($path . '*.' . $kk);
                if (!empty($check)) {

                    foreach (glob($path . '*.' . $kk) as $filename) {
                        switch ($kk) {
                            case 'js':
                                wp_enqueue_script(e_x_99 . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), array(), true);
                                break;
                            default:
                                wp_enqueue_style(e_x_99 . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), '', 'all');
                                break;
                        }
                    }

                }
            }

        }
    }
}

function export_import_99_admin_setup()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    require_once('dash.php');
}

add_action('admin_init', 'export_import_99_json_export');

function export_import_99_json_export()
{

    if (isset($_POST['export_import_99_actions'])  && current_user_can('manage_options')) {
        global $current_user;
        $code = hash('md5', $current_user->ID . site_url());
        if (wp_verify_nonce($_POST['export_import_99_actions'], $code)) {
            if (isset($_POST['export_import_globals']) && !empty($_POST['export_import_globals'])) {
                $name  = '';
                $data  = array();
                $terms = $_POST['type'] == 'term' ? array(
                    $_POST['export_import_globals']
                ) : explode(',', $_POST['terms']);
                $posts = $_POST['type'] == 'post' ? array(
                    $_POST['export_import_globals']
                ) : array();
                ;
                /*get out the terms*/
                if (!empty($terms)) {
                    $data['terms'] = array();
                    foreach ($terms as $term) {
                        $data['terms'][$term]            = array();
                        $data['terms'][$term]['metas']   = generate_all_meta_keys($term, 'term');
                        $data['terms'][$term]['content'] = array();
                        $args                            = array(
                            'hide_empty' => false
                        );
                        $ters                            = get_terms($term, $args);
                        if (!empty($ters)) {
                            foreach ($ters as $ter) {
                                $data['terms'][$term]['content'][$ter->term_id]['defaults'] = $ter;
                                if (!empty($data['terms'][$term]['metas'])) {
                                    $data['terms'][$term]['content'][$ter->term_id]['metas'] = array();
                                    foreach ($data['terms'][$term]['metas'] as $k) {
                                        $data['terms'][$term]['content'][$ter->term_id]['metas'][$k] = get_term_meta($ter->term_id, $k, true);
                                    }
                                }
                            }
                        }
                    }
                    $name = implode('_', $terms);
                }
                /*get out the posts*/
                if (!empty($posts)) {
                    $data['posts'] = array();
                    foreach ($posts as $post) {
                        $data['posts'][$post]            = array();
                        $data['posts'][$post]['metas']   = generate_all_meta_keys($post, 'post');
                        $data['posts'][$post]['content'] = array();
                        $args                            = array(
                            'posts_per_page' => -1,
                            'post_type' => $post
                        );
                        $posts_array                     = get_posts($args);
                        foreach ($posts_array as $p) {
                            $data['posts'][$post]['content'][$p->ID]['defaults'] = $p;
                            /*get metas*/
                            if (!empty($data['posts'][$post]['metas'])) {
                                $data['posts'][$post]['content'][$p->ID]['metas'] = array();
                                foreach ($data['posts'][$post]['metas'] as $k) {
                                    $data['posts'][$post]['content'][$p->ID]['metas'][$k] = get_post_meta($p->ID, $k, true);
                                }

                            }
                            /*get terms*/
                            if (!empty($terms))
                            {
                            $data['posts'][$post]['content'][$p->ID]['terms'] = array();
                            $args = array('fields' => 'names');
                             foreach ($terms as $tt)
                             {
                                $data['posts'][$post]['content'][$p->ID]['terms'][$tt]=wp_get_post_terms($p->ID, $tt, $args );
                             }
                            }


                        }
                    }
                    $namep = implode('_', $posts);
                    $name .= $name != '' ? '__' . $namep : $namep;
                }

                if (!empty($data)) {
                    ob_clean();
                    $json = json_encode($data);

                    header('Content-Disposition: attachment; filename=' . $name . '__' . date('m-d-Y_H-i') . '.json');
                    header('Content-Type: application/json; charset=utf-8');
                    header('Content-Length: ' . strlen($json));
                    echo $json;
                    exit;
                    export_import_99_admin_message('updated',0);

                }
                else
                {
                    export_import_99_admin_message('error',1);
                }
            }
            else
            {global $wpdb;
                $error=0;
                $inserted=0;
                $rejected=0;
                $changed_posts=$changed_terms=0;
                $old_terms=array();
                /*file import*/
                $inputs=export_import_99_inputs();

                foreach ($inputs['import_checks'] as $v)
                    {
                        switch ($v[3]) {
                            case 1:
                                if (isset($v[4]) && $v[4]=='f')
                                {
                                $error=!isset($_FILES[$v[1]]) ? 2 : 0;
                                }
                                else
                                {
                                $error=(!isset($_POST[$v[1]]) || empty($_POST[$v[1]])) ? 2 : 0;
                                }
                                 break;
                            default:
                                break;
                        }

                    ${$v[1]}=(isset($_POST[$v[1]]) && !empty($_POST[$v[1]])) ? $_POST[$v[1]] : 0;
                    }
                if ($error!=0)
                    {
                    export_import_99_admin_message('error',$error);
                    }
                else
                {
                    $data = file_get_contents($_FILES['import_file']['tmp_name']);
                    $data= json_decode($data, true);
                    if (!(empty($data['terms']) && empty($data['posts'])))
                    {
                        $user_id = get_current_user_id();
                        if (!empty($data['terms']) && $replace_terms==1)
                        {
                            /*import terms*/
                            foreach ($data['terms'] as $a=>$term)
                            {
                                if (taxonomy_exists($a))
                                {
                                $old_terms[$a]=array();
                                $metas=$term['metas'];
                                if (!empty($term['content']))
                                {
                                    usort($term['content'], "e_x_99_cmp");
                                    foreach ($term['content'] as $tkk=>$tvv)
                                    {
                                        $parent= $tvv['defaults']['parent']!=0 ? $old_terms[$a][$tvv['defaults']['parent'][0]] : 0;
                                        $args=array(
                                                'name' => $tvv['defaults']['name'],
                                                 'slug' => $tvv['defaults']['slug'],
                                                 'description' => $tvv['defaults']['description'],
                                                 'parent' => $parent
                                                    );
                                        if (term_exists(  $tvv['defaults']['name'], $a))
                                            {
                                                $termmm=get_term_by('name', $tvv['defaults']['name'],$a);
                                                $id=$termmm->term_id;
                                                wp_update_term($id, $a, $args);
                                            }
                                        else
                                        {
                                           $id=wp_insert_term($tvv['defaults']['name'], $a,$args);
                                           $id=$id['term_id'];
                                         }
                                        if (!empty($metas))
                                        {
                                            foreach ($metas as $mm)
                                            {
                                                if (!empty($tvv['metas'][$mm]))
                                                {
                                                   update_term_meta($id,$mm,$tvv['metas'][$mm]);
                                                }

                                            }
                                        }
                                        $old_terms[$a][$tkk]=array($id,$tvv['defaults']['name']);
                                        $changed_terms++;
                                    }
                                }
                            }
                        }
                        }
                        if (!empty($data['posts']))
                        {
                            /*import posts*/
                            foreach ($data['posts'] as $k=>$v)
                            {
                                if (post_type_exists($k))
                                {
                               if (!empty($v['content']))
                               {
                                foreach($v['content'] as $kk=>$vv)
                                {   $check= $wpdb->get_results($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title=%s AND post_type=%s AND post_status=%s", $vv['defaults']['post_title'],$k,$vv['defaults']['post_status']));
                                    if (empty($check) || $replace_meta==1)
                                    {
                                    $defaults = array(
                                        'post_author' => $user_id,
                                        'post_content' => $vv['defaults']['post_content'],
                                        'post_title' => $vv['defaults']['post_title'],
                                        'post_name' => $vv['defaults']['post_name'],
                                        'post_excerpt' => $vv['defaults']['post_excerpt'],
                                        'post_status' => $vv['defaults']['post_status'],
                                        'post_type' => $vv['defaults']['post_type'],
                                        'comment_status' => $vv['defaults']['comment_status'],
                                        'post_parent' => $vv['defaults']['post_parent'],
                                        'menu_order' => $vv['defaults']['menu_order']
                                    );
                                    if (!empty($check))
                                    {
                                        $new_id=$check[0]->ID;
                                        $defaults['ID']=$new_id;
                                        wp_update_post($defaults);
                                    }
                                    else
                                    {
                                       $new_id=wp_insert_post($defaults);
                                    }

                                    if ((int)$new_id)
                                    {
                                    $changed_posts++;
                                    update_post_meta($new_id,'e_x_99_old_id',$kk);
                                    /*replace term*/
                                    if ($replace_meta!=0 && !empty($v['metas']))
                                        {
                                            foreach($v['metas'] as $m)
                                                {
                                                    if (isset($vv['metas'][$m]) && !empty($vv['metas'][$m]))
                                                    {
                                                        update_post_meta($new_id,$m,$vv['metas'][$m]);
                                                    }
                                                }
                                        }
                                    /*add terms*/
                                    if (!empty($vv['terms']))
                                        {
                                            foreach ($vv['terms'] as $tk=>$tv)
                                            {
                                                if (taxonomy_exists($tk) || isset($old_terms[$tk]))
                                                {
                                                    $ttts=array();
                                                    foreach ($tv as $tt)
                                                    {
                                                        if (isset($old_terms[$tk][$tt]) && !empty($old_terms[$tk][$tt]))
                                                        {
                                                            $tt=$old_terms[$tk][$tt];
                                                            $ttts[]=$tt;
                                                        }
                                                        else
                                                        {
                                                        if (term_exists( $tt, $tk))
                                                            {
                                                            $ttts[]=$tt;
                                                            }
                                                        }
                                                    }
                                                   wp_set_object_terms( $new_id, $ttts, $tk );
                                                }
                                            }
                                        }
                                    }
                                }
                                }
                                export_import_99_admin_message('updated',array($changed_posts,$changed_terms));
                               }
                               else
                               {
                                export_import_99_admin_message('error',4);
                               }
                               }
                               else
                               {
                                export_import_99_admin_message('error',5);
                               }
                            }
                        }
                    }
                    else
                    {
                        export_import_99_admin_message('error',3);
                    }
                }


            }
        }
    }
}

function generate_all_meta_keys($p, $type)
{
    global $wpdb;
    $post_type = $p;
    if ($type == "post") {
        $query = "
        SELECT DISTINCT($wpdb->postmeta.meta_key)
        FROM $wpdb->posts
        LEFT JOIN $wpdb->postmeta
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id
        WHERE $wpdb->posts.post_type = '%s'
        AND $wpdb->postmeta.meta_key != ''
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)'
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
    ";
    } else {
        $query = "
        SELECT DISTINCT($wpdb->termmeta.meta_key)
        FROM $wpdb->term_taxonomy
        LEFT JOIN $wpdb->termmeta
        ON $wpdb->term_taxonomy.term_id = $wpdb->termmeta.term_id
        WHERE $wpdb->term_taxonomy.taxonomy = '%s'
        AND $wpdb->termmeta.meta_key != ''
        AND $wpdb->termmeta.meta_key NOT RegExp '(^[_0-9].+$)'
        AND $wpdb->termmeta.meta_key NOT RegExp '(^[0-9]+$)'
    ";
    }
    $meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
    return $meta_keys;
}


function export_import_99_inputs()
{
    return array('import_checks'=>
        array(
        array(
        'input type="file"',
        'import_file',
        '',
        1,
        'f'
        ),
        array(
        'input type="checkbox" value="1"',
        'replace_meta',
        'Import/Replace Metas',
        0
        ),
    array(
        'input type="checkbox" value="1"',
        'replace_terms',
        'Import/Replace Terms',
        0
        ),
    array(
        'input type="checkbox" value="1"',
        'replace_same_titles',
        'Replace Same Titles',
        0
        )));
}



function export_import_99_admin_message($class,$response)
{
    $r=array($class,export_import_99_messages($response));
    add_action('admin_notices', function() use ($r) {
        echo '<div class="'.$r[0].' notice"><p>'.$r[1].'</p></div>';
    });
}


function export_import_99_messages($v)
{
    $data='';
    switch ($v) {
        case 0:
            $data=__('Data Exported Succesfully',e_x_99.'_data_exported');
            break;
        case 1:
            $data=__('No Data Exported',e_x_99.'_data_export_empty');
        break;
        case 2:
            $data=__('No File Imported',e_x_99.'_data_import_file_empty');
        break;
        case 3:
            $data=__('No Valid Data',e_x_99.'_data_import_file_no_data');
        break;
        case 4:
            $data=__('No Posts to Import',e_x_99.'_data_import_file_no_posts');
        break;
        case 5:
            $data=__('Post type Does Not Exist',e_x_99.'_data_import_file_no_post_type');
        break;
        default:
            $data=__('Import Comleted. Posts Changed:'.$v[0].' | Terms Changed:'.$v[1],e_x_99.'_data_import_file_no_post_type');
            break;
    }
    return $data;
}


function e_x_99_cmp($a, $b) {
    if ($a['defaults']['parent'] == $b['defaults']['parent']) {
        return 0;
    }
    return ($a['defaults']['parent']< $b['defaults']['parent']) ? -1 : 1;
}


