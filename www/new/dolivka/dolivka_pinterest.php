<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.01.2018
 * Time: 23:04
 * FOR PHP 7.0 (list!)
 * Дозалитым постам выставим статус 'private' вместо 'publish' чтобы сделать по ним выборку и добавить им spintax
 */
include "../includes/functions.php";
//error_reporting('E_ERROR');
//$console_mode = 1;
$fp_log = 'log.txt';
$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'dev_wp_dolivka';
$tname = 'instagram';
mysqli_connect2($db_name);

$img_dir = 'f:\Dumps\instagram\all/';
$wp_image_upload_date_prefix = '2018/01/';
$files_dir = 'f:\Dumps\instagram\\' . time() . '/'; // Директория куда будем из общей массы переливть фотки для данного сайта
prepare_dir($files_dir);
$post_status = 'private';

//debug to clean if needed
//$debug_data['wp_posts'] = get_table_last_id('wp_posts', 'id', $db_name);
//$debug_data['wp_postmeta'] = get_table_last_id('wp_postmeta', 'meta_id', $db_name);
//$debug_data['wp_term_relationships'] = get_table_last_id('wp_term_relationships', 'object_id', $db_name);
//file_put_contents("debug_data.txt", serialize($debug_data));
//DELETE
//$debug_data = unserialize(file_get_contents("debug_data.txt"));
//dbquery("DELETE FROM `$db_name`.`wp_posts` WHERE `id` > $debug_data[wp_posts];");
//dbquery("DELETE FROM `$db_name`.`wp_postmeta` WHERE `meta_id` > $debug_data[wp_postmeta];");
//dbquery("DELETE FROM `$db_name`.`wp_term_relationships` WHERE `object_id` > $debug_data[wp_term_relationships];");

//start pos
$meta_id = get_table_max_id('wp_postmeta', 'meta_id', $db_name) + 1;
$image_id = get_table_max_id('wp_posts', 'ID', $db_name) + 1;
$post_id = $image_id + 1;
$wp_postmeta_start_pos = $meta_id;
$cat_id = dolivka_get_catid($db_name);

$imgs = dolivka_get_autoname_pinterest_imgs();

$c_images_before = dbquery("SELECT COUNT(`id`) FROM `$db_name`.`wp_posts` WHERE `post_type` = 'attachment';");
echo2("Начинаем заливку новых картинок, сейчас картинок в базе $c_images_before | стартовый meta_id $meta_id , всего картинок для заливки " . count($imgs));
$i = 0;
foreach ($imgs as $img) {

    //Для PHP 7!
    list ($id, $fname, $auto_name) = $img;
    $local_img_path = $img_dir . $fname;
    $site_path_dir = '/wp-content/uploads/' . $wp_image_upload_date_prefix . $fname;

    if ($img_info = is_image($local_img_path)) {
        copy($local_img_path, $files_dir . $fname); // переносим файл для определенного сайта
        $i++;
        $array_to_postmeta = gen_image_postmeta($local_img_path, $site_path_dir, $img_info);
        $gen_title = gen_title_pins($auto_name, '2018');
        $post_name = gen_post_name($id, $gen_title);

        $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attached_file','" . $site_path_dir . "');";
        $meta_id++;
        //MetaData без тумбов, не нужна нифига, но можно и оставить. Только базу засирает.
        //$queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ($meta_id,$image_id,  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
        $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($image_id, 1, '2018-01-01 00:05:53', '2018-01-01 21:05:53','', '" . $gen_title . "', '', 'inherit', 'closed', 'closed', '', '" . $gen_title . "', '', '', '2018-01-01 00:05:53', '2018-01-01 21:05:53', '', $post_id , '$site_path_dir', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";

        $post_content = "<img src=\"$site_path_dir\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$array_to_postmeta[width]\" height=\"$array_to_postmeta[height]\" class=\"alignnone size-full wp-image-" . $image_id . "\" />";

        $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($post_id, 1, '2018-01-01 00:05:53', '2018-01-01 21:05:53','" . $post_content . "', '" . $gen_title . "', '', '$post_status', 'closed', 'closed', '', '$post_name', '', '', '2018-01-01 00:05:53', '2018-01-01 21:05:53', '', 0, '/?p=" . $post_id . "', 0, 'post', '', 0);";
//INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
        $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $cat_id, 0);";

        dbquery($queries);
        $meta_id++;
        $image_id += 2;
        $post_id += 2;
        unset ($queries);
    }
}
dbquery("UPDATE `$db_name`.`wp_term_taxonomy` SET `count` = $i WHERE `term_taxonomy_id` = $cat_id;");
$c_images_after = dbquery("SELECT COUNT(`id`) FROM `$db_name`.`wp_posts` WHERE `post_type` = 'attachment';");
$c_diff = $c_images_after - $c_images_before;
echo2("Нашлось файлов и были картинками, было попыток заливки в базу $i , всего картинок в базе $c_images_after (+$c_diff)");


function gen_title_pins($auto_name, $add_word)
{
    $auto_name = preg_replace(array('/\W/', '/\s+/', '/\d/'), ' ', $auto_name);
    $auto_name = trim($auto_name);
    $auto_name = ucwords($auto_name) . ' ' . $add_word;
    return $auto_name;
}

function gen_image_postmeta($img_full_path, $upload_path_img_dir, $img_data = FALSE, $crop_width = 150, $crop_height = 150)
{
    //Это просто пример который будем использовать в PostMeta
    $exmpl = unserialize('a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}');
    if (@is_array($img_data)) {
        $tmp2 = $img_data;
    } else {
        $tmp2 = getimagesize($img_full_path);
    }
    $width = $tmp2[0];
    $height = $tmp2[1];
    $tmp = explode(".", basename($img_full_path));
    $cropped_img_name = $tmp[0] . "-" . $crop_width . "x" . $crop_height . '.' . $tmp[1];
    $array_to_postmeta['width'] = $width;
    $array_to_postmeta['height'] = $height;
    //В Postmeta нужен в формате 2017/09/img_name.jpg , иначе Udinra Sitemap неправильные ссылки генерит.
    $array_to_postmeta['file'] = $upload_path_img_dir;
    $array_to_postmeta['sizes']['thumbnail']['file'] = $cropped_img_name;
    $array_to_postmeta['sizes']['thumbnail']['width'] = $crop_width;
    $array_to_postmeta['sizes']['thumbnail']['height'] = $crop_height;
    $array_to_postmeta['sizes']['thumbnail']['mime_type'] = $tmp2['mime'];
    $array_to_postmeta['image_meta'] = $exmpl['image_meta'];
    return $array_to_postmeta;
}

//get imgs
function dolivka_get_autoname_pinterest_imgs($limit = 1000)
{
    return dbquery("SELECT `id`,`fname`,`auto_name` FROM `image_index`.`instagram` WHERE `auto_name` IS NOT NULL GROUP BY `auto_name` ORDER BY `id` ASC LIMIT $limit;", TRUE);
}

function gen_post_name($image_id, $post_title, $bad_symbols = NULL)
{
    $post_title = str_to_url($post_title);
    $post_name = $image_id . "_" . $post_title;
    return $post_name;
}

function dolivka_get_catid($db_name)
{
    if ($res = dbquery("SELECT `term_id` FROM `$db_name`.`wp_terms` WHERE `name` = '2018' OR `slug` = '2018' LIMIT 1;")) {
        return dbquery("SELECT `term_taxonomy_id` FROM `$db_name`.`wp_term_taxonomy` WHERE `term_id` = $res;");
    } else {
        dbquery("INSERT INTO `$db_name`.`wp_terms` (`name`,`slug`) VALUES ('2018','2018');");
        $res = dbquery("SELECT MAX(`term_id`) FROM `$db_name`.`wp_terms`;");
        dbquery("INSERT INTO `$db_name`.`wp_term_taxonomy` (`term_id`, `taxonomy`) VALUES ($res, 'category');");
        $res = dbquery("SELECT `term_taxonomy_id` FROM `$db_name`.`wp_term_taxonomy` WHERE `term_id` = $res;");
        return $res;
    }
}
