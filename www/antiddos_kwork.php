<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.12.2016
 * Time: 0:24
 */
#########################################################################
# High Activity AntiRip                                                 #
# --------------------------------------------------------------------- #
# Скрипт служит для защиты сайта от тотального скачивания "под корень". #
# Запись запрещающей строки в .htaccess осуществляется автоматически.   #
# --------------------------------------------------------------------- #
# Взято из инета, куплено у уебка за 500р который неправильно настроил  #
# его и пришлось править и он не работал. https://kwork.ru/user/iweb    #
#########################################################################
/*
1. проверить чтобы в конце файла .htaccess было
Order Allow,Deny
Allow From All
2. Создать-проверить папки, выставить 777 права для $dirfiles , /antirip/logfiles/
3. Инклуд в index.php , код include ('antiddos_kwork.php');
4. Проверять через 29+ запросов к сайту, должен появиться белый экран. Потом проверить через CTRL + f5, будет 403 ошибка.
*/

//error_reporting(0);
$address = $_SERVER['REMOTE_ADDR'];
if ($_SERVER['HTTP_REFERER']) {
    $ref = $_SERVER['HTTP_REFERER'];
}
$url = urldecode($_SERVER['REQUEST_URI']);
$limit = 20; // Максимально допустимое количество обращений к сайту с одного IP-адреса в минуту.
$timenow = time();
$browser = $_SERVER['HTTP_USER_AGENT'];
$htaccess = $_SERVER['DOCUMENT_ROOT']."/.htaccess"; //Проверить чтобы в конце файла было
$dirfiles = $_SERVER['DOCUMENT_ROOT']."/antirip/logfiles/";
$logfiles = "$dirfiles".$address;
$hostname = gethostbyaddr($address);
$datetime = date("Y-m-d H:i:s");
$ip1 = getenv("HTTP_X_FORWARDED_FOR");
$ip2 = getenv("REMOTE_ADDR");
if ($ip1 !== false) {
    $hostip1 = gethostbyaddr($ip1);
}
$hostip2 = gethostbyaddr($ip2);
if ($ip1 != $ip2) {
    $htstring = PHP_EOL;
    if (!empty($ip1)) {
        preg_match_all('/[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}/', $ip1, $ip1);
        $ip1 = array_unique($ip1[0]);
        foreach ($ip1 as $v) {
            $htstring.="Deny from ".$v." #	\\\\ Заблокирован Внутренний IP	\\\\ $hostip1\r\n";
        }
    }
    if (!empty($ip2)) {
        $htstring.="Deny from ".$ip2." #	\\\\ Заблокирован IP Proxy	\\\\ $hostip2\r\n";
    }
} else {
    $htstring = "Deny from ".$address." #	\\\\ Заблокирован Внешний IP	\\\\ $hostname\r\n";
}
$excludes = array(
    "yandex.ru",
    "bing.com",
    "googlebot.com",
    "yahoo.com",
    "search.live.com",
);

if ($opendir = opendir($dirfiles)) {
    while (false !== ($log = readdir($opendir))) {
        if ($log != "." and $log != "..") {
            $timelog = date(filemtime("$dirfiles"."$log"));
            if ($timelog < ($timenow - 60)) {
                unlink("$dirfiles"."$log");
            }
        }
    }
}

foreach ($excludes as $v) {
    if (preg_match('/'.$v.'/', $hostname)) {exit;}
}

if (!file_exists($logfiles)) {fopen($logfiles, "w+");}
$write = "$datetime - $hostname<br>Browser: $browser<br>Referer: $ref<br>URL: $url<br>\r\n";
if ($logfiles) {
    if (is_writable($logfiles)) {
        if (!$handle = fopen($logfiles, 'a')) {exit;}
        if (fwrite($handle, $write) === FALSE) {exit;}
        fclose($handle);
    }
}

if ((count(file($logfiles)) > $limit) and ($timelog > ($timenow - 60))) {
    if ($htaccess) {
        foreach (file($htaccess) as $h) {
            if ($h === $htstring) {
                exit;
            }
        }
        if (is_writable($htaccess)) {
            if (!$handle = fopen($htaccess, 'a')) {exit;}
            if (fwrite($handle, $htstring) === FALSE) {exit;}
            fclose($handle);
        }
    }
}
?>