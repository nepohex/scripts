<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.02.2018
 * Time: 21:43
 *
 * Алгоритм #1
 * За 10 часов в 2 потока прочекалось 50к доменов из вебомера всего, позор.
 *
 * Алгоритм 2
 * 100 сопоставлений = 53 сек.
 * 1 час = 7500 сопоставлений в 1 поток
 * в 2 потока (50% CPU) за 12 часов это где-то 180к.
 *
 * Самое большое время занимает (0.5 сек) выборка из базы Webomer и сопоставление по названию домена.
 * В базе 1.2кк доменов, значит за каждый час в 1 поток скорость будет расти на 0.5%
 *
 * КАК ИТОГ ПРОЧИТАЛ ЕЩЕ РАЗ ПРО ИНДЕКСЫ И ДОБАВИЛ ИНДЕКС (UNIQUE) ДЛЯ ПОЛЯ SITE_URL в таблице Webomer.
 * Что не делал с индексами не получалось не грузить всю базу каждый раз, а с UNIQUE при нахождении первого вхождения
 * сразу все заработало быстро!
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$allowed_zone = array('ru', 'su'); //Список доменных зон по которым у нас готовы Whois

#### Алгоритм 1
//$query = "SELECT * FROM `dev_rudomains`.`webomer` ORDER BY `id` DESC;";
//$res = mysqli_query($link, $query);
//$i = 1;
//while ($row = mysqli_fetch_assoc($res)) {
//    $i++;
//    $tmp3 = explode('.', $row['site_url']);
//    if (@in_array($tmp3[1], $allowed_zone)) {
//        $tmp = dbquery("SELECT `id` FROM `dev_rudomains`.`domains` WHERE `domain` = '$row[site_url]';");
//        if ($tmp) {
//            $query = "UPDATE `dev_rudomains`.`domains` SET `webomer_id` = '$row[id]' WHERE `id` ='$tmp';";
//            dbquery($query, null, null, null, 1);
//        }
//        unset ($tmp, $tmp3);
//    }
//    if ($i % 100000 == 0) {
//        echo_time_wasted($i);
//    }
//}

#### Алго 2
$query1 = "SELECT `id`,`domain` FROM `dev_rudomains`.`domains` WHERE `webomer_checked` = 0 AND `id` >= 50000 AND `id` <= 100000;";
$query1 = "SELECT `id`,`domain` FROM `dev_rudomains`.`domains` WHERE `webomer_checked` = 0 AND `id` >= 50000 AND `id` <= 550000;";
$query1 = "SELECT `id`,`domain` FROM `dev_rudomains`.`domains` WHERE `webomer_checked` = 0;";
$res = mysqli_query($link, $query1);
$i = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $i++;
//    echo_time_wasted($i, "GET NEXT ROW");
    $tmp = dbquery("SELECT `id` FROM `dev_rudomains`.`webomer` WHERE `whois_checked` != '1' AND `tld_id` IN ('2','41') AND `site_url` = '$row[domain]';");
//    echo_time_wasted($i, "SELECT WEBOMER");
    if ($tmp) {
        $query[] = "UPDATE `dev_rudomains`.`domains` SET `webomer_id` = '$row[id]', `webomer_checked` = '1' WHERE `id` ='$row[id]';";
        $query[] = "UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = '1' WHERE `id` = $tmp;";
    } else {
        $query[] = "UPDATE `dev_rudomains`.`domains` SET `webomer_checked` = '1' WHERE `id` ='$row[id]';";
    }
    dbquery($query, null, null, null, 1);
//    echo_time_wasted($i, "UPDATE TABLES");
    unset ($tmp, $tmp3, $query);
    if ($i % 10000== 0) {
        echo_time_wasted($i);
//        exit();
    }
}