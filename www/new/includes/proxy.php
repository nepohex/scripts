<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 18.04.2018
 * Time: 23:50
 * Proxy_get_data править под нужды прокси, каждый раз...автодетекта Socks/ Https нету
 */

$proxy_list = file('C:\OpenServer\domains\scripts.loc\www\new\includes\proxy\proxy_list.txt', FILE_IGNORE_NEW_LINES);
$proxy_private = file('C:\OpenServer\domains\scripts.loc\www\new\includes\proxy\proxy_private.txt', FILE_IGNORE_NEW_LINES);
$useragent_list = file('C:\OpenServer\domains\scripts.loc\www\new\includes\proxy\user_agent.txt', FILE_IGNORE_NEW_LINES);

if (!is_array($proxy_list) || !is_array($useragent_list)) {
    echo2("Нет списка нужных для работы Proxy или UserAgent!");
    exit("Нет списка нужных для работы Proxy или UserAgent!");
}

prepare_dir(dirname($_SERVER['PHP_SELF']) . "/debug");

function proxy_get_valid($list, $bad_messages = 'Squid Error pages')
{
    static $loop;
    $loop += 1;
    do {
        $rand_id = rand(0, count($list));
        $tmp_proxy = $list[$rand_id];
        $res = proxy_test($tmp_proxy, 2, 2);
        $loop++;
        if ($loop > 30) {
            echo2("30 tries in row, no valid proxy!");
            exit();
        }
        if (stripos($res, 'yandex')) {
            $loop = 0;
            return $tmp_proxy;
        }
    } while (!stripos($res, 'yandex'));
}

/** Mode 1 = check proxy, Mode 2 = return result
 * @param $proxy
 * @param int $timeout
 * @param int $mode
 * @param bool $cookie
 * @return bool|mixed
 */
function proxy_test($proxy, $timeout = 2, $mode = 1, $cookie = FALSE)
{
// Тест через прокси
    if (strpos($proxy, '@') === 0) {
        $proxy = substr($proxy, 1);
        $socks = TRUE;
    }
    $tmp = explode(":", $proxy);
    if (count($tmp) > 2) {
        $pwd = TRUE;
    }
    $ip_port = $tmp[0] . ":" . $tmp[1];
    $ch = curl_init();
    $url = 'http://ya.ru';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 1); // вывод заголовков в ответе
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout . 'L');// таймаут соединения
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout * 2 . 'L');// таймаут получения данных
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_PROXY, $ip_port);
    if ($pwd) {
        $log_pwd = $tmp[2] . ':' . $tmp[3];
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $log_pwd);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, './debug/' . $ip_port . 'cookie.txt'); // сохранять куки в файл
        curl_setopt($ch, CURLOPT_COOKIEFILE, './debug/' . $ip_port . 'cookie.txt');
    }
    if ($socks) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    }
//    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
    $data = curl_exec($ch);
    $curl_error = curl_error($ch);
    if ($data === FALSE) {
        return FALSE;
    } else if ($mode == 2) {
        return $data;
    } else {
        return TRUE;
    }
}

/**
 * @param $proxy
 * @param string $url
 * @param int $timeout на получение данных
 * @param bool $cookie
 * @param bool $debug
 * @return bool|mixed
 */
function proxy_get_data($proxy, $url = '', $timeout = 3, $cookie = FALSE, $socks = FALSE, $debug = FALSE)
{
    global $useragent_list;
    $debug_dir = dirname($_SERVER['PHP_SELF']) . "/debug/";
    $rand_id = rand(0, count($useragent_list));
    $useragent = $useragent_list[$rand_id];

    if ($url == FALSE) {
        echo2("Не задан URL для CURL!!!");
    }

    if (strpos($proxy, '@') === 0) {
        $proxy = substr($proxy, 1);
        $socks = TRUE;
    }
    $tmp = explode(":", $proxy);
    if (count($tmp) > 2) {
        $pwd = TRUE;
    }
    $ip_port = $tmp[0] . ":" . $tmp[1];
    if ($ip_port == FALSE) {
        echo2("WTF");
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);// таймаут4
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout . 'L');// таймаут получения данных
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_PROXY, $ip_port);
    curl_setopt($ch, CURLOPT_REFERER, 'https://majestic.com/');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: ru-RU,ru;q=0.9,en;q=0.8',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'DNT: 1',
        'Host: majestic.com',
        'Pragma: no-cache',
        'Referer: https://majestic.com/',
        'Upgrade-Insecure-Requests: 1'
    ));

    if ($pwd) {
        $log_pwd = $tmp[2] . ':' . $tmp[3];
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $log_pwd);
    }
    if ($cookie) { //Не уверен насчет этого кода!
        if (is_file($debug_dir . $tmp[0] . 'cookie.txt')) {
            //Tell libcurl to activate the cookie engine, and to read the initial set of cookies from the given file. Read-only.
            curl_setopt($ch, CURLOPT_COOKIEFILE, $debug_dir . $tmp[0] . 'cookie.txt');
        } else {
            //Tell libcurl to activate the cookie engine, and when the easy handle is closed save all known cookies to the given cookiejar file. Write-only.
            curl_setopt($ch, CURLOPT_COOKIEJAR, $debug_dir . $tmp[0] . 'cookie.txt'); // сохранять куки в файл
        }
    }
    if ($socks) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
    }
//    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
    if ($debug) {
        curl_setopt($ch, CURLOPT_HEADER, 1); // вывод заголовков в ответ
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1); //вывод request headers в ответ
        echo_time_wasted($ip_port . " Перед получением данных");
    }

    $data = curl_exec($ch);
    $curl_error = curl_error($ch);

    if ($debug) {
        $info = curl_getinfo($ch);
        echo_time_wasted($ip_port . " После получения данных. $curl_error");
    }
    if ($data === FALSE) {
        return FALSE;
    } else {
        return $data;
    }
}