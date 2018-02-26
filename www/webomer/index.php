<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.02.2018
 * Time: 0:10
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = __DIR__ . '/debug/log.txt';
mysqli_connect2('dev_webomer');

### Переменные ###
$page = 'http://webomer.ru/cgi-bin/wr.fcgi?action=stat&position=&period=3month&sitet=2l&pagesize=100&page=';

//Debug
//$n = 1;
//$tmp = file_get_contents('./debug/debug.txt');
//preg_match_all('/put_one_site_stat(.)*\n/i', $tmp, $matches);
//$sites_data = wm_sanitize_sitedata($matches[0]);
//wm_put_db($sites_data, 1);

$n = 1;
$place = $n;
while ($n < 12977) {
    $tmp = file_get_contents($page . $n);
    $tmp = iconv('Windows-1251', 'UTF-8', $tmp);
    preg_match_all('/put_one_site_stat(.)*\n/i', $tmp, $matches);
    $sites_data = wm_sanitize_sitedata($matches[0]);
    wm_put_db($sites_data, $place);
    $place += 100;
    if ($n % 300 == 0) {
        echo_time_wasted($n);
    }
    $n++;
}

function wm_put_db($sites_data, $start_id)
{
    foreach ($sites_data as $site) {
        $tmp = implode("','", $site);
        $query[] = "INSERT INTO `dev_webomer`.`webomer` VALUES ('',$start_id,'$tmp','');";
        $start_id++;
    }
    dbquery($query, null, null, null, 1);
}

function wm_sanitize_sitedata($preg_arr)
{
    foreach ($preg_arr as $match) {
        $tmp2 = explode(',', $match);
        $tmp[] = str_replace(array('put_one_site_stat', '(', ')', ';', '\'', PHP_EOL), '', $tmp2);
    }
    unset ($tmp[0]);
    return $tmp;
}