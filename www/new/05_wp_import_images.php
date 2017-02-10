<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 2:03
 * #3
 */
include "multiconf.php";
mysqli_connect2();
next_script (0,1);

// пример того что падает в wp_postmeta массив с данными о картинке
$exmpl = unserialize('a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}');

//Это сам запрос для инсерта
//INSERT INTO  `sh_parse1`.`wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ( '4',  '4',  '_wp_attachment_metadata', 'a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}' );

// Должно получиться нечто : INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (10000, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', 'Medium Length Layered Haircut With Bangs', '', 'publish', 'closed', 'closed', '', '5126_medium-length-layered-haircut-with-bangs', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, 'http://mh_parse.loc/?p=10000', 0, 'post', '', 0);
$query_wp_posts = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $post_guid . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','" . $item['post_content'] . "', '" . $item['post_title'] . "', '', 'publish', 'closed', 'closed', '', '" . $item['post_name'] . "', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "?p=" . $post_guid . "', 0, 'post', '', 0);";

$img_names = array_slice(scandir($img_dir), 2);
//Исключаем из массива файлы кропы
$i = 0;
foreach ($img_names as $img_name) {
    if (strpos($img_name,$crop_width.'x'.$crop_height)) {
        unset($img_names[$i]);
    }
    $i++;
}
sort($img_names);

$i = 0;
$counter_img = count($img_names);
$meta_id = $wp_postmeta_start_pos;

foreach ($img_names as $img_name) {

    $tmp = explode(".", $img_name);
    $cropped_img_name = $tmp[0] . "-150x150." . $tmp[1];

    if (is_file($img_dir . $img_name)) {
        $tmp2 = getimagesize($img_dir . $img_name);
        $array_to_postmeta['width'] = $tmp2[0];
        $array_to_postmeta['height'] = $tmp2[1];
        $array_to_postmeta['file'] = $wp_image_upload_date_prefix . $img_name;
        $array_to_postmeta['sizes']['thumbnail']['file'] = $cropped_img_name;
        $array_to_postmeta['sizes']['thumbnail']['width'] = $crop_width;
        $array_to_postmeta['sizes']['thumbnail']['height'] = $crop_height;
        $array_to_postmeta['sizes']['thumbnail']['mime_type'] = $tmp2['mime'];
        $array_to_postmeta['image_meta'] = $exmpl['image_meta'];
        $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $wp_postmeta_start_pos . ",  '_wp_attached_file','" . $array_to_postmeta['file'] . "');";
        $meta_id++;
        $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $wp_postmeta_start_pos . ",  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
        $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $wp_postmeta_start_pos . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', '" . $img_name . "', '', 'inherit', 'closed', 'closed', '', '" . $img_name . "', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_uploads_path . $wp_image_upload_date_prefix . $img_name . "', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";
        dbquery($queries);
        $meta_id++;
        $wp_postmeta_start_pos++;
    }
    $i++;
    if ($i % 500 == 0) {
        echo_time_wasted($i);
    }
    unset($queries);
}
next_script ();