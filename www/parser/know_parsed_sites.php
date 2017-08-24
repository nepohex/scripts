<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 14.08.2017
 * Time: 23:05
 * В SPIN базе лежит 3700 картинок для Pophaircut > В Images 335
 * hairstylehub 2255 > 1017
 * Остальных доменов нет вообще (еще ~28к картинок).
 */
include('../new/includes/functions.php');
$debug_mode = 1;

error_reporting(E_ERROR);

$db_usr = 'root';
$db_name = 'hair_spin';
mysqli_connect2($db_name);

$res = dbquery("SELECT `img_url` FROM `data`", true);

foreach ($res as $item) {
    $domains[] = parse_url($item, PHP_URL_HOST);
}
$image_per_domain = array_count_values($domains);
print_r($image_per_domain);

foreach ($image_per_domain as $key => $item) {
    echo ("SELECT `site_id` FROM `image_index`.`domains` WHERE `domain` = '$key';");
}
