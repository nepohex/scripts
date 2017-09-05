<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.09.2017
 * Time: 23:50
 */
include "multiconf.php";
mysqli_connect2();
next_script(0, 1);

$data = unserialize(file_get_contents($work_file));

// пример того что падает в wp_postmeta массив с данными о картинке
$exmpl = unserialize('a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}');

//Это сам запрос для инсерта
//INSERT INTO  `sh_parse1`.`wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ( '4',  '4',  '_wp_attachment_metadata', 'a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}' );

// Должно получиться нечто : INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (10000, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', 'Medium Length Layered Haircut With Bangs', '', 'publish', 'closed', 'closed', '', '5126_medium-length-layered-haircut-with-bangs', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, 'http://mh_parse.loc/?p=10000', 0, 'post', '', 0);
//$query_wp_posts = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $post_guid . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','" . $item['post_content'] . "', '" . $item['post_title'] . "', '', 'publish', 'closed', 'closed', '', '" . $item['post_name'] . "', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "?p=" . $post_guid . "', 0, 'post', '', 0);";

//$img_names = array_slice(scandir($img_dir), 2);

switch ($gen_addings) {
    case 1:
        break;
    case 2:
        $uniq_addings = $uniq_addings_nch;
        break;
    case 3:
        $uniq_addings = array_merge($uniq_addings, $uniq_addings_nch);
        break;
}

$i = 0;
$meta_id = $wp_postmeta_start_pos;

//debug
//dbquery("DELETE FROM `wp_posts` WHERE `id` >= 100;");
//dbquery("DELETE FROM `wp_postmeta` WHERE `meta_id` >= 100;");
//dbquery("DELETE FROM `wp_term_relationships` WHERE `object_id` >=100");

foreach ($data as $key_top => $key_images) {
    foreach ($key_images as $key_bot => $image) {

        $file_extension = get_good_filename($image['image_url']);
        $site_img_name = $image['image_id'] . "." . get_good_filename($image['image_url']);
        $local_img_path = $img_dir . $site_img_name;
        $relative_site_img_path = '/wp-content/uploads/' . $wp_image_upload_date_prefix . $site_img_name;
        $gen_title = gen_new_title($image['key']);
        $post_name = gen_post_name($image['image_id'],$gen_title);
        $data[$key_top][$key_bot]['new_title'] = $gen_title;

        if (is_file($local_img_path) && stripos(mime_content_type($local_img_path), "image") === 0) {
            // Теги получает, но не дописана вставка и т.п.
//            $post_tags = get_post_tags($image['image_url']);
            $tmp2 = getimagesize($local_img_path);
            $width = $tmp2[0];
            $height = $tmp2[1];
            $cropped_img_name = $image['image_id'] . "-" . $crop_width . "x" . $crop_height . $file_extension;
            $array_to_postmeta['width'] = $width;
            $array_to_postmeta['height'] = $height;
            $data[$key_top][$key_bot]['width'] = $width;
            $data[$key_top][$key_bot]['height'] = $height;
            $array_to_postmeta['file'] = $relative_site_img_path;
            $array_to_postmeta['sizes']['thumbnail']['file'] = $cropped_img_name;
            $array_to_postmeta['sizes']['thumbnail']['width'] = $crop_width;
            $array_to_postmeta['sizes']['thumbnail']['height'] = $crop_height;
            $array_to_postmeta['sizes']['thumbnail']['mime_type'] = $tmp2['mime'];
            $array_to_postmeta['image_meta'] = $exmpl['image_meta'];
            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $wp_postmeta_start_pos . ",  '_wp_attached_file','" . $array_to_postmeta['file'] . "');";
            $meta_id++;
            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $wp_postmeta_start_pos . ",  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
            //Указатель ID post meta + image id + image id
            $wp_postmeta_start_pos++;
            //Указатель на post_image
            $tmp = $wp_postmeta_start_pos - 1;
            $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $tmp . ", 1, '2017-09-01 00:05:53', '2017-09-01 21:05:53','', '" . $image['key'] . "', '', 'inherit', 'closed', 'closed', '', '" . $image['key'] . "', '', '', '2017-09-01 00:05:53', '2017-09-01 21:05:53', '', $wp_postmeta_start_pos , '$relative_site_img_path', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";

            $post_content = "<img src=\"$relative_site_img_path\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$width\" height=\"$height\" class=\"alignnone size-full wp-image-" . $tmp . "\" />";

            $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $wp_postmeta_start_pos . ", 1, '2017-09-01 00:05:53', '2017-09-01 21:05:53','" . $post_content . "', '" . $gen_title . "', '', 'publish', 'closed', 'closed', '', '$post_name', '', '', '2017-09-01 00:05:53', '2017-09-01 21:05:53', '', 0, '/?p=" . $wp_postmeta_start_pos . "', 0, 'post', '', 0);";
            //INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
            $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (" . $wp_postmeta_start_pos . ", 1, 0);";

            dbquery($queries);
            $meta_id++;
            $wp_postmeta_start_pos++;
        }
        $i++;
//    if ($i % 500 == 0) {
//        echo_time_wasted($i);
//    }
        unset($queries);
    }
}

function gen_new_title($title)
{
    global $uniq_addings, $year_pattern, $year_to_replace, $seasonal_add, $seasonal_titles, $year_end_percent;
    static $i;
    shuffle($uniq_addings);
    $title = $uniq_addings[1] . $title;
    $title = preg_replace($year_pattern, $year_to_replace, $title);
    $title = trim($title);
    $tmp = explode(' ', $title);
    $tmp = array_unique($tmp);
    $title = implode(' ', $tmp);
    if ($seasonal_add !== false && $i % $seasonal_titles == 0) {
        $z = (rand(0, 10000) < $year_end_percent * 100) ? 1 : 2;
        switch ($z) {
            case 1:
                $title .= ' ' . $year_to_replace;
                $counter_year_to_end++;
                break;
            case 2:
                $title = $year_to_replace . ' ' . $title;
                $counter_year_to_start++;
                break;
        }
    }
    $i++;
    $title = ucwords($title);
    return $title;
}

function get_good_filename($image_url)
{
    $tmp = explode(".", $image_url);
    $file_ext = strtok(array_pop($tmp), "?");
    $ext_len = strlen($file_ext);
    if ($ext_len < 3 OR $ext_len > 4) {
        return false;
    } else {
        return $file_ext;
    }
}

function get_post_tags($image_url)
{
    global $db_name, $db_name_img;
    dbquery("USE `$db_name_img`");
    $query = "SELECT `t3`.`key` from `google_images_relations` AS `t1`
LEFT JOIN `google_images` AS `t2` ON `t1`.`image_id` = `t2`.`image_id`
LEFT JOIN `semrush_keys` AS `t3` ON `t1`.`key_id` = `t3`.`key_id`
WHERE `t2`.`image_url` = '$image_url'";
    $tags = dbquery($query, TRUE);
    dbquery("USE `$db_name`");
    if (count($tags) > 0) {
        return $tags;
    } else {
        return false;
    }
}

function gen_post_name ($image_id, $post_title) {
    $post_name = strtolower($image_id."_".str_replace(" ","_",$post_title));
    return $post_name;
}