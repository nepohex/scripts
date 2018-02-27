<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.02.2018
 * Time: 0:10
 * Собираем домены 2ого уровня, по 100 на страницу, за 3 месяца, по всем странам.
 * Таблицы менялись, нужно проверять инсерты по количеству колонок!
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = __DIR__ . '/debug/log.txt';
mysqli_connect2('dev_rudomains');

### Переменные ###
$page = 'http://webomer.ru/cgi-bin/wr.fcgi?action=stat&position=&period=3month&sitet=2l&pagesize=100&page=';

//Debug
//$n = 1;
//$tmp = file_get_contents('./debug/debug.txt');
//preg_match_all('/put_one_site_stat(.)*\n/i', $tmp, $matches);
//$sites_data = wm_sanitize_sitedata($matches[0]);
//wm_put_db($sites_data, 1);

//Рассчет TLDS
wm_know_tlds($link);
echo_time_wasted();
exit();

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
        $query[] = "INSERT INTO `dev_rudomains`.`webomer` VALUES ('',$start_id,'$tmp','');";
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

/* Только для доменов 2ой зоны! */

function wm_know_tlds($link)
{
    $query = "SELECT `id`,`site_url` FROM `dev_rudomains`.`webomer`;";
    $res = mysqli_query($link, $query);
    while ($row = mysqli_fetch_row($res)) {
        $tmp = explode('.', $row[1]);
        $tmp = array_last($tmp);
        //Надо переписывать этот кусок ибо ниже был дописан другой для индексов по зонам.
//        mysqli_query($link, "UPDATE `dev_rudomains`.`tlds` SET `count` = `count`+1 WHERE `tld` = '$tmp';");
//        if (mysqli_affected_rows($link) < 1) { //Осторожно! В дебаге affected rows не срабатывает почему то если пошагово делать, надо проскакивать
//            $query = "INSERT INTO `dev_rudomains`.`tlds` VALUES ('','$tmp','1');";
//            dbquery($query);
//        }
        $query = "SELECT `id` FROM `dev_rudomains`.`tlds` WHERE `tld` = '$tmp';";
        $tmp = dbquery($query);
        $query = "UPDATE `dev_rudomains`.`webomer` SET `tld_id` = '$tmp' WHERE `id` = '$row[0]';";
        dbquery($query);
    }
}