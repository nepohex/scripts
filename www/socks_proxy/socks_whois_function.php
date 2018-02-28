<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.02.2018
 * Time: 13:37
 */
require('Socks5Socket.class.php');

$SocksClient = new \Socks5Socket\Client();
//EXAMPLE PROXY
//$Client->configureProxy(array(
//    'hostname' => '81.177.180.76',
//    'port' => 5055,
//    'username' => '',
//    'password' => ''
//));

//DEBUG
//$proxy[0] = '81.177.180.76';
//$proxy[1] = '5055';
//
//$tld_json = json_decode(file_get_contents('tld.json'));
//$domain = '004.ru';
//
//$tmp = whois_socks_proxy($SocksClient, $proxy, $tld_json, $domain);


/** Поддержка нескольких серверов под каждую зону, рандомный порядок.
 * @param class $Client
 * @param mixed $proxy Array Or String ip:port:login:pwd
 * @param json $tld_json decoded
 * @param $domain nohttp
 * @return array|mixed
 */
function whois_socks_proxy($Client, $proxy, $tld_json, $domain)
{
    if (!is_array($proxy)) {
        $proxy = explode(":", $proxy);
    }
    //Получаем зону домен
    $tmp = explode('.', $domain);
    $tmp = end($tmp);
    //Получаем сервер для коннекта по данной зоне
    $whois_server_class = tmp_tld_get_json_data($tld_json, $tmp);

    //Если не нашлось Сервера для доменной зоны
    if ($whois_server_class == FALSE) {
        return 'NO TLD SERVER';
    }
    //Нагородил чтобы под некоторые зоны было несколько серверов.
    if (is_object($whois_server_class->whoisServer)) {
        //Преобразование элементов класса в массив
        $server = get_object_vars($whois_server_class->whoisServer);
        shuffle($server);
        $server = end($server);
    } else {
        $server = $whois_server_class->whoisServer;
    }

    $Client->configureProxy(array('hostname' => $proxy[0], 'port' => $proxy[1], 'username' => $proxy[2], 'password' => $proxy[3]));
    $Client->connect($server, '43');
    $Client->send("$domain\r\n");

    $tmp = $Client->readAll();
    return $tmp;
    //Регулярка получать дату например
    // $tmp = preg_match_all('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $tmp, $tmp2);
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
    return FALSE;
}