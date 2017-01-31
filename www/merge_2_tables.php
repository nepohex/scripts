<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.01.2017
 * Time: 21:54
 * Из одной таблы в другую перекачиваем данные, во 2ой табле уникальный параметр - ключ (key), попадут только уникальные записи.
 */
include('new/includes/functions.php');
$debug_mode = 1;
$db_name = 'image_index';
$db_pwd = '';
$db_usr = 'root';
mysqli_connect2();

$t_in= 'semrush_keys';
$t_out = 'kk_keys';

$query = "SELECT `key`,`adwords`,`results` from `$t_out`";
$sqlres = mysqli_query($link,$query);
$i = 0;
while ($tmp = mysqli_fetch_row($sqlres)) {
    $i++;
    $query = "INSERT INTO `$t_in` (`key`,`adwords`,`results`) VALUES ('$tmp[0]','$tmp[1]','$tmp[2]'); ";
    if ($z = dbquery($query, 0, 1) == 1) {
        $counter_uniq_keywords += $z;
    }
    if ($i % 10000) {
        echo_time_wasted($i);
    }
}
echo2 ("Итого закачали новых ключей $counter_uniq_keywords");