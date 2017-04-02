<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.03.2017
 * Time: 17:38
 */
include('C:\\OpenServer\\domains\\scripts.loc\\www\\new\\includes\\functions.php');
//$debug_mode = 1;
$fp_log = fopen("C:\\OpenServer\\domains\\scripts.loc\\www\\pinterest\\synch_log.txt", "a+");
ini_set('log_errors', 'On');
ini_set('error_log', 'C:\OpenServer\domains\scripts.loc\www\pinterest\synch_log.txt');
$double_log = 1;
chdir ("C:\\OpenServer\\domains\\scripts.loc\\www\\pinterest\\");

//Сливание базы с хоста с локальной
$db_pwd = 'Ma4STVXhTc';
$db_usr = 'admin_ipzon';
$db_name = 'admin_pinterest';
$db_host = '93.170.76.157';
mysqli_connect2($db_name, $db_host);

$db_insert = 'pin_houzz_dead';
$db_select = 'pin_houzz_dead';
dbquery("USE $db_name");
$query = "SELECT `pin_id`,`domain`,`timestamp` FROM `$db_select` WHERE `status` = 0;";
//$query2 = "SELECT `id` from `pin_houzz` WHERE `checked` = 1 AND ;"; //Попытка селектов из pin_houzz неуспешна
$insert = dbquery($query, 1);
if (count($insert) > 0) {
    echo2("Загрузили с удаленного хоста " . count($insert) . " доменов со статусом 0, ставим им статус 7 и заливаем в локал базу.");
    dbquery("UPDATE `$db_select` SET `status` = 7 WHERE `status` != 7;");

    $db_pwd = '';
    $db_usr = 'root';
    mysqli_connect2("pinterest");

//foreach ($insert as $item) {
//    $query = "UPDATE `pin_houzz` SET `checked` = 1 WHERE `id` = $item";
//    if (dbquery($query, false, 1, false, 1) == 1) {
//        $new++;
//    }
//}
    foreach ($insert as $item) {
        $query = "INSERT INTO `$db_insert` SET `pin_id` = $item[0], `domain` = '$item[1]' , `timestamp` = '$item[2]'";
        if (dbquery($query, false, 1, false, 1) == 1) {
            $new++;
        }
    }
    echo2("Вставили новых доменов с удаленного хоста $new доменов со статусом 0");
} else {
    echo2("С удаленного хоста загрузили 0 записей, нечего загружать в локал. Проверим в Godaddy если есть что-то с локальных проверок.");
    $db_pwd = '';
    $db_usr = 'root';
    mysqli_connect2("pinterest");
}
$table = 'pin_houzz_dead';
$count = dbquery("SELECT count(*) FROM `$table` WHERE `status` = 0;");
echo2("Начинаем проверку $count доменов в Godaddy");
$query = "SELECT `domain` FROM `$table` WHERE `status` = 0 LIMIT 500";
$res = dbquery($query, 1);
$proxy = get_proxy();
$fail = 0;

while (count($res) > 300) {
    get_proxy();
    $ch = curl_init();
    $domains = urlencode(implode(',', $res));
    $url = 'https://ru.godaddy.com/domains/actions/dodomainbulksearch.aspx?source=%2fdomains%2fbulk-domain-search.aspx';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    if ($proxy_ip) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_login);
    }
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__."/cookie.txt"); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__."/cookie.txt");
    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'domainNames=' . $domains . '&dotTypes=&extrnl=1&bulk=1&redirectTo=customize');
    $data = curl_exec($ch);
    exec_domain_status($data, $res);
    update_status($unknown, $not_ava, $ava, $table);
    curl_close($ch);
    unlink(__DIR__."/cookie.txt");
//    $url = 'https://ru.godaddy.com/domains/actions/json/removedomainsfrompending.aspx?TargetDivID=x&RemoveAll=true';
//    $data = curl_exec($ch);
    unset ($ava, $not_ava, $unknown);
    $res = dbquery($query, 1);
    sleep(120);
}
echo2 ("Не нашлось 300 доменов для проверки. Заканчиваем");

function exec_domain_status($html, $pack)
{
    global $ava, $not_ava, $unknown, $fail, $proxy_ip;
    preg_match_all('/<input type=\'checkbox\' id=\'(.*)\' name/i', $html, $tmp_ava);
    preg_match_all('/<td align="left" class="t11 OneLinkNoTx"><label for=\'(.*)\' title/i', $html, $tmp_not_ava);
    if (count($tmp_not_ava[1]) < 1) {
        echo2("Скорее всего какая-то проблема с Godaddy, результат запроса сохранен в godaddy_response.txt");
        file_put_contents('godaddy_response.txt', $html);
        file_put_contents('godaddy_pack.txt', serialize($pack));
        echo2("$proxy_ip");
        $fail++;
        if ($fail == 10) {
            exit("Уже 10 Fail! Exit!");
        }
    }
    $unknown = array_diff($pack, $tmp_not_ava[1]);
    if (count($unknown) == count($pack)) {
        echo2("100% проблема с Godaddy, результат запроса сохранен в godaddy_response.txt");
        file_put_contents('godaddy_response.txt', $html);
        file_put_contents('godaddy_pack.txt', serialize($pack));
        echo2("$proxy_ip");
        exit();
    }
    $ava = $tmp_ava[1];
    $not_ava = array_diff($tmp_not_ava[1], $tmp_ava[1]);
    echo2(count($unknown) . ' / ' . count($ava) . ' / ' . count($not_ava) . " unknown / ava / notava / proxy $proxy_ip");
}

function update_status($unknown, $not_ava, $ava, $table)
{
    if (count($unknown) > 0) {
        foreach ($unknown as $item) {
            dbquery("UPDATE `$table` SET `status` = 8 WHERE `domain` = '$item';");
        }
    }
    if (count($not_ava) > 0) {
        foreach ($not_ava as $item) {
            dbquery("UPDATE `$table` SET `status` = 6 WHERE `domain` = '$item';");
        }
    }
    if (count($ava) > 0) {
        foreach ($ava as $item) {
            dbquery("UPDATE `$table` SET `status` = 5 WHERE `domain` = '$item';");
        }
    }
}

function get_proxy()
{
    global $link, $login_data, $proxy_ip, $proxy_login;
    if (!$login_data) {
        $query = "SELECT DISTINCT `proxy` FROM `proxy` WHERE `proxy` != 0 AND `used` != 4 ORDER BY `speed` DESC LIMIT 200";
        $login_data = dbquery($query, 1);
        if (count($login_data) == 0) {
            echo2("Нет больше не занятых проксей и аккаунтов! Проверить статусы!");
            exit();
        }
    } else {
        shuffle($login_data);
        $tmp = explode(":", $login_data[0]);
        $proxy_ip = $tmp[0] . ":" . $tmp[1];
        $proxy_login = $tmp[2] . ":" . $tmp[3];
    }
    return $login_data;
}

exit();