<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.05.2017
 * Time: 1:12
 * Выгружаем логи с сервера
 * Составляем сводную по топовым картинкам которые запрашивались с сервера по убыванию
 * Определяем в байтах по логам размер картинки (200 код)
 * Размеры картинок закидываем в TXT
 * По размеру в байтах выгружаем из DB `image_size` , определяем с какого сайта картинка была успешной
 * Сопоставляем с первоначально созданным файлом на момент импорта какие картинки в % от какого сайта были успешными
 */
$start = microtime(true);
$db_usr = 'root';
$db_host = 'localhost';
$db_pwd = '';
$db_name = 'image_index';
include "../includes/functions.php";
$debug_mode = 1;

$bytes = file('list_bytes.txt', FILE_IGNORE_NEW_LINES);
$used_times = file('used_times.txt', FILE_IGNORE_NEW_LINES);
mysqli_connect2();

$i = 0;
foreach ($bytes as $size) {
    if (ctype_digit($size)) {
        //RAND для того чтобы не вынимать 1ый результат из таблицы т.к. картинок дублей больше 50%
        $query = "SELECT `id` FROM `image_size` WHERE `size` = $size;";
        $tmp = dbquery($query);
        if (is_array($tmp)) {
            $img_id = $tmp[rand(0,count($tmp))-1]['id'];
        } else {
            $img_id = $tmp;
        }
        if ($img_id) {
            $sizes[$i]['image_id'] = $img_id;
            $sizes[$i]['images_size'] = $size;
            $query = "SELECT `site_id` FROM `images` WHERE `id` = $img_id;";
            $site_id = dbquery($query);
            $sizes[$i]['site_id'] = $site_id;
            $query = "SELECT `site_dir` FROM `domains` WHERE `site_id` = $site_id;";
            $site_dir = dbquery($query);
            $sizes[$i]['site_dir'] = $site_dir;
            $sizes[$i]['used_times'] = $used_times[$i];
        }
    }
    $i++;
    unset($img_id);
    if ($i % 500 == 0) {
        echo_time_wasted($i);
    }
}
foreach ($sizes[$i-1] as $k => $v) {
    $header[] = $k;
}
$zz = fopen($start."_images.csv", 'a');
fputcsv($zz, $header, ";");
array_to_csv($start."_images.csv", $sizes);
