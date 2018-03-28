<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.03.2018
 * Time: 1:12
 * Импорт рейтингов комментариев исходя из количества запросов (просмотров) страниц с консультациями.
 *
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_wp_zapdoc';
mysqli_connect2('dev_modx_zapdoc');
$domain = "http://zapdoc1.ru";

$tmp = csv_to_array("f:\\tmp\\zapdoc_comm_rating.csv");
foreach ($tmp as $comment) {
    $tmp3 = dbquery("SELECT `name`,`email`,`ip`,`createdon` FROM `dev_modx_zapdoc`.`modx_tickets_comments` WHERE `id` = $comment[0]", 0, 0, 0, 0, 1);
    if ($tmp3) {
        $tmp2 = @dbquery("SELECT `comment_ID` FROM `dev_wp_zapdoc`.`wp_comments` WHERE `comment_author` = '$tmp3[name]' AND `comment_author_email` = '$tmp3[email]' AND `comment_author_IP` = '$tmp3[ip]' AND `comment_date` = '$tmp3[createdon]';");
    }
    if ($tmp2) {
        dbquery("INSERT INTO `dev_wp_zapdoc`.`wp_commentmeta` VALUES ('',$tmp2,'wpdiscuz_votes',$comment[1]);");
    }
}