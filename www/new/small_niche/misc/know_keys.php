<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.04.2018
 * Time: 23:15
 * Скрипт чтобы узнать основные частотные слова ниши по названиям картинок из базы, первое знакомство.
 * Также можно по результатам составить список самых частых "плохих" слов и бредосимволов.
 * По результатам скрипта понял что нужен словарь английских слов, и уже с ним работать.
 */
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$db_name = 'image_index';

$tmp = dbquery("SELECT * FROM `$db_name`.`images` AS `t1` JOIN `$db_name`.`image_domains` AS `t2` ON `t1`.`site_id` = `t2`.`site_id` WHERE `t2`.`theme` = 2;");

$final = array();
$i = 0;
foreach ($tmp as $row) {
    $i++;
    $tmp2 = explode('.', $row['filename']);
    $tmp2 = count_words($tmp2[0], '-');
    $final = named_arrays_summ($final, $tmp2);
    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
}
file_put_contents(__DIR__ . '/debug_data/srlz.txt', serialize($final));
echo2(print_r($final, true));