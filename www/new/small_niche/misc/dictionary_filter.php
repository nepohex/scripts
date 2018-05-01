<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 24.04.2018
 * Time: 19:04
 * Попробуем фильтрануть ключи по базе и отсортировать хорошие-плохие, составить статистику и время на сортировку
 * Отлично и быстро работает! 5000 слов менее чем за минуту прогоняет.
 * Проблема что некоторых картиночных ключей нет в базе таких как
 * pinterest
 * clipart
 * blog
 * iphone
 * infographic
 * и другие
 */
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$db_name = 'image_index';

$words = unserialize(file_get_contents(__DIR__ . '/debug_data/srlz.txt'));

$i = 0;
foreach ($words as $word => $freq) {
    $i++;
    $word = addslashes(strtolower($word));
    if (($tmp = dbquery("SELECT `id` FROM `$db_name`.`dictionary` WHERE `word` = '$word';")) == FALSE) {
        $bad_words[$word] = $freq;
    } else {
        $good_words[$word] = $freq;
    }
    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
}
