<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.02.2018
 * Time: 2:38
 * Временный файл для тестов и проверки! Все работает, в 1 поток 30 доменов за 30 сек.
 */
include '../../vendor/autoload.php';
include '../new/includes/functions.php';
require('Socks5Socket.class.php');

$debug_mode = 1;
$double_log = 1;
$fp_log = 'log.txt';
$tld_json = json_decode(file_get_contents('tld.json'));

$tmp = 'com';
$tmp = tmp_tld_get_json_data($tld_json, $tmp);

$proxy_list = file('f:\tmp\socks_proxy.txt', FILE_IGNORE_NEW_LINES);
$domains = file('tmp_list.txt', FILE_IGNORE_NEW_LINES);

$com_whois = array('whois.crsnic.net', 'whois.verisign-grs.com', 'com.whois-servers.net',
//    'whois.centralnic.com'
);


$Client = new \Socks5Socket\Client();
//$Client->configureProxy(array(
//    'hostname' => '81.177.180.76',
//    'port' => 5055
//));
foreach ($domains as $domain) {
    $proxy = tmp_get_rand_prox($proxy_list);
    shuffle($com_whois);

    $Client->configureProxy(array('hostname' => $proxy[0], 'port' => $proxy[1]));
    $Client->connect($com_whois[0], '43');
    $Client->send("$domain\r\n");

    $tmp = $Client->readAll();
    $tmp = preg_match_all('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $tmp, $tmp2);
    $tmp = pack('A30A20A30', $domain, $tmp2[1][0], $com_whois[0]);
    echo2($tmp);
}

function tmp_get_rand_prox(array $tmp)
{
    shuffle($tmp);
    $tmp2 = last($tmp);
    $tmp2 = explode(":", $tmp2);
    return $tmp2;
}

/** Возвращает инфу по зоне - сервер, шаблон.
 * @param array $json
 * @param $tld
 * @return mixed
 */
function tmp_tld_get_json_data(array $json, $tld)
{
    foreach ($json as $data) {
        if ($tld === $data->tld) {
            return $data;
        }
    }
}