<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 24.04.2018
 * Time: 18:35
 */
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$db_name = 'image_index';

$res = file(__DIR__ . '/inc/english_words.txt', FILE_IGNORE_NEW_LINES);
echo2("Выгрузили из текстового файла слов " . count($res));
foreach ($res as $row) {
    $row = addslashes(strtolower($row));
    dbquery("INSERT INTO `$db_name`.`dictionary` VALUES ('','$row','');");
}
echo_time_wasted("Загрузили в базу");