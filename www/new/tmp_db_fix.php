<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 14.10.2017
 * Time: 15:44
 * Фикс базы везде где не заполнены были wp_postmeta для attachment чтобы Udinra составляла sitemap image.
 */
include "conf_new/short_conf_debug_config.php";

$date = gen_dates();

$query = "SELECT `posts`.`ID`,`posts`.`guid` FROM `wp_posts` AS `posts` WHERE
`posts`.`post_type` = 'attachment' AND 
NOT EXISTS (SELECT 1 FROM `wp_postmeta` AS `meta` WHERE 
            `posts`.`ID` = `meta`.`post_id`)";

mysqli_connect2();

$result = mysqli_query($link, $query);
while ($row = mysqli_fetch_row($result)) {
    dbquery("INSERT INTO `$dbname[wp]`.`wp_postmeta` (`meta_id`, `post_id`, `meta_key` ,`meta_value`) VALUES ('',$row[0],'_wp_attached_file', '$row[1]');");
}

echo_time_wasted();

function gen_dates ($days_past = 90) {
    $tmp = date('Y-m-d H:i:s',time()-rand(1,$days_past*24*60*60));
    $date['post_date'] = $tmp;
    $date['post_mod'] = date('Y-m-d H:i:s',rand(strtotime($tmp),time()));
    return ($date);
}