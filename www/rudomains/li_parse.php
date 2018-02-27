<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.02.2018
 * Time: 2:13
 * Отлично работает! 7к доменов в час чекает в 1 поток. Юзал 120 прокси, вроде без банов все обошлось.
 * В текущем виде все готово в 4 потока спокойно делать. Если допилить можно N потоков с базой настроить
 * или использовать ступеньки по базе.
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$proxy_list = file('f:\tmp\work_prox.txt', FILE_IGNORE_NEW_LINES);
$proxy_list = file('f:\tmp\payed_proxy.txt', FILE_IGNORE_NEW_LINES);

$query1 = "SELECT `id`,`place`,`site_url` FROM `dev_rudomains`.`webomer` WHERE `place` >= 290000 AND `place` <= 300000;";
//$query = "SELECT `id`,`place`,`site_url` FROM `dev_rudomains`.`webomer` WHERE `place` > 10000 AND `place` < 200000 ORDER BY `place` DESC;";
//$query = "SELECT `id`,`place`,`site_url` FROM `dev_rudomains`.`webomer` WHERE `place` > 10000 AND `place` < 200000 ORDER BY `domain_idx` DESC;";
//$query = "SELECT `id`,`place`,`site_url` FROM `dev_rudomains`.`webomer` WHERE `place` > 10000 AND `place` < 200000 ORDER BY `domain_idx` ASC;";
$res = mysqli_query($link, $query1);
$i = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $i++;
    $li_str = 'http://counter.yadro.ru/values?site=' . $row['site_url'];
//    $li_str = 'http://counter.yadro.ru/values?site=' . 'yaplakal.com'; // DEBUG

    $tmp_proxy = proxy_get_valid($proxy_list);
    $li_result = proxy_get_data($tmp_proxy, $li_str);

    $status = li_parse_result($li_result);
    @$report[$status] += 1;

    if ($status == 3) {
        $tmp = preg_match('/month_vis = (\d+);/', $li_result, $monthly);
        $li_result = addslashes($li_result);
        $query[] = "INSERT INTO `dev_rudomains`.`li` VALUES ('',$status,$row[id],'$li_result','$monthly[1]',NOW())";
    } else {
        $query[] = "INSERT INTO `dev_rudomains`.`li` VALUES ('',$status,$row[id],'','',NOW());";
    }
    $query[] = "UPDATE `dev_rudomains`.`webomer` SET `li_checked` = 1 WHERE `id` = '$row[id]';";
    dbquery($query, null, null, null, 1);
    unset ($query);
    if ($i % 1000 == 0) {
        echo_time_wasted($i, print_r($report, TRUE));
    }
}

function li_parse_result($res)
{
    if (stripos($res, 'denied')) {
        return 1;
    } else if (stripos($res, 'Unregistered')) {
        return 2;
    } else if (stripos($res, 'LI_month_vis')) {
        return 3;
    } else {
        return 4;
    }
}

function proxy_get_valid($list, $bad_messages = 'Squid Error pages')
{
    static $loop;
    $loop += 1;
    $rand_id = rand(0, count($list));
    $tmp_proxy = $list[$rand_id];
//    shuffle ($list);
//    $tmp_proxy = $list[0];
    if ($loop > 30) {
        echo2("30 tries in row, no valid proxy!");
        exit();
    }
    $res = proxy_test($tmp_proxy, 2, 2);
    if ($res && !stripos($res, $bad_messages)) {
        $loop = 0;
        return $tmp_proxy;
    } else {
        proxy_get_valid($list);
    }
}
