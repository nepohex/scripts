<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.03.2018
 * Time: 1:32
 * Все очень простинько, никаких наворотов и полноценных обработок ошибок.
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

use AntonShevchuk\YandexXml\Client;
use AntonShevchuk\YandexXml\Request;
use AntonShevchuk\YandexXml\Response;
use AntonShevchuk\YandexXml\Exceptions\YandexXmlException;

$request = Client::request('maxeremin', '03.8246265:5fa0f4b8bc08cf84dc1f84eab4b6aed0');
//$request = Client::request('love-sl-adv', '03.85219829:c7cf252ce40c12d6380aa75b7528a777');

//$query = "SELECT `t1`.`id`,`t1`.`site_url` FROM `webomer` AS `t1` JOIN `domains` AS `t2` ON `t1`.`id` = `t2`.`webomer_id`
//WHERE `t1`.`place` > 10000 AND `t1`.`place` < 300000 AND `t1`.`tld_id` IN (1,2,7,15,14,41,31,4,13,20,22,23,38,8,37,47,83,44) AND `t1`.`whois_checked` = 1 AND `t2`.`reg_year` IN (2016,2017) AND `t1`.`yandex_ind` IS NULL
//ORDER BY `t1`.`id` DESC LIMIT 10000;";
//$query = "SELECT `t1`.`id`,`t1`.`site_url` FROM `webomer` AS `t1` JOIN `domains` AS `t2` ON `t1`.`id` = `t2`.`webomer_id`
//WHERE `t1`.`place` > 10000 AND `t1`.`place` < 300000 AND `t1`.`tld_id` IN (1,2,7,15,14,41,31,4,13,20,22,23,38,8,37,47,83,44) AND `t1`.`whois_checked` = 1 AND `t2`.`reg_year` IN (2016,2017) AND `t1`.`yandex_ind` IS NULL
//ORDER BY `t1`.`id` ASC LIMIT 10000;";

//LAST
//$query ="SELECT `t1`.`id`,`t1`.`site_url` FROM `webomer` AS `t1` JOIN `domains` AS `t2` ON `t1`.`id` = `t2`.`webomer_id`
//WHERE `t1`.`place` > 10000 AND `t1`.`place` < 300000 AND `t1`.`yandex_ind` IS NULL
//ORDER BY `t1`.`id` DESC LIMIT 10000;";
//$query ="SELECT `t1`.`id`,`t1`.`site_url` FROM `webomer` AS `t1` JOIN `domains` AS `t2` ON `t1`.`id` = `t2`.`webomer_id`
//WHERE `t1`.`place` > 10000 AND `t1`.`place` < 300000 AND `t1`.`yandex_ind` IS NULL
//ORDER BY `t1`.`id` ASC LIMIT 10000;";
//$query = "SELECT `id`,`site_url` FROM `webomer`
//WHERE `place` > 10000 AND `place` < 300000 AND `whois_checked` IN (2,3,4,5) AND `yandex_ind` IS NULL
//ORDER BY `id` DESC LIMIT 10000;";
//$query = "SELECT `id`,`site_url` FROM `webomer`
//WHERE `place` > 10000 AND `place` < 300000
//AND `whois_checked` IN (2,3,4,5)
//AND `tld_id` NOT IN (10,6,75,68,121,60,93,3,151,43,153,18,63,35)
//AND `yandex_ind` IS NULL
//ORDER BY `id` DESC LIMIT 10000;";
//$query = "SELECT `t1`.`id`,`t1`.`site_url` FROM `webomer` AS `t1` JOIN `domains` AS `t2` ON `t1`.`id` = `t2`.`webomer_id`
//WHERE `t1`.`place` > 10000 AND `t1`.`place` < 300000 AND `t1`.`tld_id` IN (1,2,7,15,14,41,31,4,13,20,22,23,38,8,37,47,83,44) AND `t1`.`whois_checked` = 1 AND `t2`.`reg_year` IN (2015,2016,2017) AND `t1`.`yandex_ind` IS NULL
//ORDER BY `t1`.`id` DESC LIMIT 10000;";
//$query = "SELECT `id`,`site_url` FROM `webomer`
//WHERE `place` > 10000 AND `place` < 300000
//AND `whois_checked` IN (2,3,4,5)
//AND `tld_id` NOT IN (10,6,75,68,121,60,93,3,151,43,153,18,63,35)
//AND `yandex_ind` IS NULL
//ORDER BY `id` ASC LIMIT 10000;";
$query = "SELECT `id`,`site_url` FROM `webomer` 
WHERE `place` > 10000 AND `place` < 300000 
#AND `whois_checked` IN (2,3,4,5) 
AND `tld_id` NOT IN (10,6,75,68,121,60,93,3,151,43,153,18,63,35)
#AND `whois_checked` = 1
#AND `tld_id` = 2
AND `yandex_ind` IS NULL 
ORDER BY `id` ASC LIMIT 10000;";
$res = dbquery($query, 1);
echo2(count($res) . " Доменов для проверки");
foreach ($res as $row) {
    $i += 1;
    usleep(450000);
    try {
        $response = $request
            ->query('site:' . $row[1])// запрос к поисковику
//        ->lr(2)                         // id региона в Яндекс {@link http://search.yaca.yandex.ru/geo.c2n}
//        ->page('Начать со страницы. По умолчанию 0 (первая страница)')
//        ->limit(100)                    // Количество результатов на странице (макс 100)
//        ->proxy('46.8.228.138', '3128', 'maxeremin53209', 'wy05t6Xw') // Если требуется проксирование запроса
            ->send()                        // Возвращает объект Response
        ;

//    foreach ($response->results() as $i => $result) {
//        echo $result->url;
//        echo $result->domain;
//        echo $result->title;
//        echo $result->headline;
//        echo sizeof($result->passages);
//    }

//        echo $response->total();

        $tmp = $response->total();
    } catch (YandexXmlException $e) {
//        echo "\nВозникло исключение YandexXmlException:\n";
        //Не дописано, надо 32 код обработать суточного лимита
//        switch ($e->getCode()) {
//            case 15:
//                dbquery("UPDATE `dev_rudomains`.`webomer` SET `yandex_ind` = '0' WHERE `id` = $row[0];");
//                break;
//                continue;
//        }
        //Сайт в бане, 0 страниц.
        if ($e->getCode() !== 15) {
            echo2($e->getCode() . $e->getMessage());
            sleep(15 * 60);
        } else {
            dbquery("UPDATE `dev_rudomains`.`webomer` SET `yandex_ind` = '0' WHERE `id` = $row[0];");
            continue;
        }
    } catch (Exception $e) {
        echo "\nВозникло неизвестное исключение:\n";
        echo $e->getMessage() . "\n";
        sleep(15 * 60);
    }

    dbquery("UPDATE `dev_rudomains`.`webomer` SET `yandex_ind` = '$tmp' WHERE `id` = $row[0];");
    unset ($tmp);
    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
}

///**
// * Возвращает массив с результатами
// *
// * $results является массивом из stdClass
// * Каждый элемент содержит поля:
// *  - url
// *  - domain
// *  - title
// *  - headline
// *  - passages
// */
//$results = $response->results();
//
///**
// * Возвращает строку "Нашлось 12 млн. результатов"
// */
//$total = $response->totalHuman();
//
///**
// * Возвращает integer с общим количеством страниц результатов
// */
//$pages = $response->pages();