<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 03.04.2018
 * Time: 19:24
 * удаления оглавлений
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_lk_old';
mysqli_connect2($dbname['wp']);
$domain = "http://ladykiss1.ru/";

$res = dbquery("SELECT `ID`,`post_content` FROM `wp_posts` WHERE `post_type` = 'post' AND `post_status` = 'publish' AND `post_content` LIKE '%oglavlenie%';", 1);

foreach ($res as $row) {
    $content_fin = $row[1];

    preg_match('/<div class\s?=\s?"\s?oglavlenie\s?"\s?>(.|\t|\n|\r)*?<\/div>\r?\n?/si', $content_fin, $matches);
    if (strpos($matches[0], 'oglavlenie')) {
        $content_fin = str_ireplace($matches[0], '', $content_fin);
        $content_fin = mysqli_real_escape_string($link, $content_fin);
        dbquery("UPDATE `$dbname[wp]`.`wp_posts_new` SET `post_content` = '$content_fin' WHERE `ID` = $row[0];");
    }

}