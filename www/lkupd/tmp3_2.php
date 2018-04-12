<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.04.2018
 * Time: 18:33
 */
require_once '../new/includes/functions.php';
include_once 'C:\OpenServer\domains\scripts.loc\www\parser\simpledom\simple_html_dom.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_houzz';
mysqli_connect2($dbname['wp']);
$domain = "http://ihouzz1.ru/";


$res = dbquery("SELECT `ID`,`post_content` FROM `wp_posts` WHERE `post_type` = 'post' AND `post_status` = 'publish' AND `post_content` LIKE '%oglavlenie%';", 1);

foreach ($res as $row) {
    $content_fin = $row[1];

    $tmp = str_get_html($content_fin);
    foreach ($tmp->find('div.oglavlenie') as $element) {
        $element->outertext = '';
        $tmp->load($tmp->save());
    }
    $content_fin = (string)$tmp;
    $content_fin = mysqli_real_escape_string($link, (string)$tmp);
    dbquery("UPDATE `wp_posts` SET `post_content` = '$content_fin' WHERE `ID` = $row[0]");
}