<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.09.2017
 * Time: 20:56
 * Здесь создаем родительские категории под каждый язык и закиываем туда все посты int поязычно.
 * //todo для постов-картинок можно попробовать 2 варианта: давать им alt / title уже сгенереные или стандартные. Сгенереные повысят уник сайтмапа имг.
 * //todo генерить случайную даты модификации и публикации поста для большей видимости реальности $date = date('Y-m-d H:i:s',time()-rand(1,90*24*60*60));
 */
include "multiconf.php";
next_script(0, 1);

$result_fname = 'spin_keys_ids.txt';

//DEBUG
//$debug[] = "DELETE FROM `wp_terms` WHERE `term_id` > 199999;";
//$debug[] = "DELETE FROM `wp_term_taxonomy` WHERE `term_taxonomy_id` > 199999;";
//$debug[] = "DELETE FROM `wp_term_relationships` WHERE `term_taxonomy_id` > 199999;";
//$debug[] = "DELETE FROM `wp_posts` WHERE `ID` > 200024;";
//$debug[] = "DELETE FROM `wp_options` WHERE `option_name` LIKE '%taxonomy%';";
//$debug[] = "DELETE FROM `wp_options` WHERE `option_name` = 'category_children';";
//dbquery($debug);

if (is_file($result_dir . $result_fname)) {
    $csv2 = unserialize(file_get_contents($result_dir . $result_fname));
} else {
    echo2("Не можем открыть файл $result_dir . $result_fname , он обязателен!");
    exit();
}

$ai = get_ai('wp_posts');

//Получаем строку-список ID языков которые хотим залить.
$langs_str = implode(',', array_keys($lang));

// Генерим категории под каждый язык и получаем массив с их ID. Каждая категория с именем языка. Дальше посты будут укладываться в эти категории.
$wp_int_cat_ids = set_int_cats($lang);

$i = 0;
$counter = count($csv2);
$spinclass = new Spintax();
//Счетчик сколько для текущего языка будет использовано обычного Spintax / MegaSpin.
$spinstats = array();
foreach ($csv2 as $key => $item) {
    $img_info = get_wp_img_info($item['ID']);
    if ($res = get_int_keys($item['key_id'], $langs_str)) {
        foreach ($res as $int_arr) {
            get_int_addings($int_arr['language_id']);
            $term_taxonomy_id = get_termID_cat($int_arr['language_id'], TRUE, $wp_int_cat_ids);
            $gen_title = gen_new_title($int_arr['translated_key']);
            $img_path = hook_get_img_path($img_info['file'], $wp_image_upload_date_prefix);
            //Получаем ID картинки как она сохранена после парсинга из Google где название - ID в базе картинок. ID картинки будет использован для URL.
            $global_img_id = stristr(hook_get_img_path($img_info['file'], FALSE, TRUE), ".", TRUE);
            $post_name = gen_post_name($global_img_id, $gen_title, $bad_symbols);
            $date = gen_dates(150);
            //Если спинтакс текста нет - пропускаем ключ.
            isset($item['mega_tpl_id']) ? $tpl_id = $item['mega_tpl_id'] : $tpl_id = FALSE;
            if ($spintext = get_int_spintax($int_arr['language_id'], $tpl_id)) {
                $spintext = gen_text($spinclass, '', $spin_fragments_separator, $spintext, $gen_title);
                $post_content = "<img src=\"$img_info[file]\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$img_info[width]\" height=\"$img_info[height]\" class=\"alignnone size-full wp-image-" . $img_info['wp_image_id'] . "\" />";
                $post_content .= $spintext;
                $post_content = mysqli_real_escape_string($link, $post_content);
                $post_id = $ai + 1;
                $queries[] = "INSERT INTO `$dbname[wp]`.`wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) 
VALUES (" . $ai . ", 1, '$date[0]', '$date[0]','', '" . $gen_title . "', '', 'inherit', 'closed', 'closed', '', '" . $gen_title . "', '', '', '$date[1]', '$date[1]', '', $post_id , '$img_path', 0, 'attachment', '" . $img_info['sizes']['thumbnail']['mime_type'] . "', 0);";
                $queries[] = "INSERT INTO `$dbname[wp]`.`wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) 
VALUES (" . $post_id . ", 1, '$date[0]', '$date[0]','" . $post_content . "', '" . $gen_title . "', '', 'publish', 'closed', 'closed', '', '$post_name', '', '', '$date[1]', '$date[1]', '', 0, '/?p=" . $post_id . "', 0, 'post', '', 0);";
                //Пост ложится в категорию с ID языка.
                $queries[] = "INSERT INTO `$dbname[wp]`.`wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $term_taxonomy_id, 0);";
                $queries[] = "INSERT INTO `$dbname[wp]`.`wp_postmeta` (`meta_id`, `post_id`, `meta_key` ,`meta_value`) VALUES ('',$ai,'_wp_attached_file', '$img_path');";
                $ai += 2;
                dbquery($queries);
                unset ($queries);
            }
        }
    }
    $i++;
    if ($i % 5000 == 0) {
        echo_time_wasted($i, "/$counter ключевиков обработано, для каждого из которых загружены INT версии.");
//                exit();
    }
}
echo2("Заполнили базу постами INT, статистика по Spintax поязычная :");
echo2(print_r($spinstats, true));
next_script();

function get_wp_img_info($post_parent_id)
{
    global $db_name;
    dbquery("USE `$db_name`;");
    $query = "SELECT `ID` FROM `wp_posts` WHERE `post_parent` = $post_parent_id";
    $tmp = dbquery($query);
    $query = "SELECT `meta_value` FROM `wp_postmeta` WHERE `meta_key` = '_wp_attachment_metadata' AND `post_id` = $tmp;";
    if ($res = dbquery($query)) {
        $res = unserialize($res);
        $res['wp_image_id'] = $tmp;
        return $res;
    } else
        return false;
}

function get_int_keys($key_id, $lang_ids)
{
    global $tname, $dbname;
    $query = "SELECT `language_id`, `translated_key` FROM `$dbname[keys]`.`$tname[keys_tr]` WHERE `key_id` = $key_id AND `language_id` IN ($lang_ids);";
    if ($res = dbquery($query)) {
        return $res;
    } else {
        return false;
    }
}

function gen_post_name($image_id, $post_title, $bad_symbols = NULL)
{
    $post_title = str_to_url($post_title);
    $post_name = $image_id . "_" . $post_title;
    return $post_name;
}

function hook_get_img_path($img_path, $wp_image_upload_date_prefix = FALSE, $return_name_only = FALSE)
{
    $tmp = explode("/", $img_path);
    $fname = end($tmp);
    if ($return_name_only) {
        return $fname;
    }
    $fname = $wp_image_upload_date_prefix . $fname;
    return $fname;
}

function get_int_spintax($lang_id, $mega_tpl_id = FALSE)
{
    global $dbname, $tname, $spinstats;
    if ($mega_tpl_id) {
        $text = dbquery("SELECT `text_template` FROM `$dbname[spin]`.`$tname[megaspin_tr]` WHERE `megaspin_id` = $mega_tpl_id AND `language_id` = $lang_id LIMIT 1;");
        if (isset($spinstats[$lang_id]['spin'])) {
            $spinstats[$lang_id]['spin'] += 1;
        } else {
            $spinstats[$lang_id]['spin'] = 1;
        }
    } else {
        //шаблонов не так много, поэтому не заморачиваемся и фигачим рандомом (всего 290 шабов на язык)
        $text = dbquery("SELECT `text` FROM `$dbname[spin]`.`$tname[spintax_tr]` WHERE `language_id` = $lang_id ORDER BY RAND() LIMIT 1;");
        if (isset($spinstats[$lang_id]['megaspin'])) {
            $spinstats[$lang_id]['megaspin'] += 1;
        } else {
            $spinstats[$lang_id]['megaspin'] = 1;
        }
    }
    if (is_array($text)) {
        shuffle($text);
        return array_pop($text[0]);
    }
    return $text;
}

function gen_dates ($days_past = 90) {
    $tmp = date('Y-m-d H:i:s',time()-rand(1,$days_past*24*60*60));
    $date[] = $tmp;
    $date[] = date('Y-m-d H:i:s',rand(strtotime($tmp),time()));
    return ($date);
}