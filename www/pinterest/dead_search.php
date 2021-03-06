<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 10.03.2017
 * Time: 3:06
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

$console_mode = 1;
$debug_mode = 1;
$start_time = time();

$db_pwd = '';
$db_usr = 'root';
$db_name = 'pinterest';
$table_name = 'pin_check2';
$table_result = 'pin_dead';
mysqli_connect2($db_name);

//$pin_acc = 'inga.tarpavina.89@mail.ru';
//$pin_pwd = 'xmi0aJByoB';
//pinterest_local_login($pin_acc, $pin_pwd);
$login_data = get_thread_data();
pinterest_login($login_data['id'], $login_data['proxy'], $login_data['pin_acc']);
$db_proxy_id = $login_data['id'];
//$pin_ids = file("pin_ids.txt", FILE_IGNORE_NEW_LINES);

$added = 0; //Нашли мертвых Нужных нам доменов
$i = 0; // Проверено изначальных источников (пинов)
$counter_pins = 0; // Перебрали пинов по источникам
$valid_tld = 0; //Пины прошли проверку Не субдомен и Не закачан юзером
$query = "SELECT `id`, `pin` FROM `$table_name` WHERE `checked` = 0 LIMIT 1";
//echo_time_wasted(null, "#$i перебрали всего источников. Прошли http://pinterest.com/pin/$pin_db[1] нашли $counter_pins пинов всего, подошли по критериям домена $valid_tld , пытались загрузить в базу (возможно дубль) $try_add , загрузили в базу $added");
while ($pins_db = dbquery($query, 1, 1)) {
    $check_id = $pins_db[0][0];
    dbquery("UPDATE `$table_name` SET `checked` = 2 WHERE `id` = $check_id");
    foreach ($pins_db as $pin_db) {
        $related = $bot->pins->related($pin_db[1], 500);
//        $i++;
        foreach ($related as $pin) {
            $counter_pins++;
            //$queries[] = "INSERT INTO `$table_name` SET `pin` ='" . $pin['id'] . "' , `image_signature` = '" . $pin['image_signature'] . "';";
            if ($pin['domain'] !== 'Uploaded by user' && substr_count($pin['domain'], '.') == 1) {
                $valid_tld++;
                $domain = $pin['domain'];
                $pin_found = $pin['id'];
                if (checkdnsrr($domain, 'ns') == false && checkdnsrr($domain, 'a') == false) {
//                    $try_add++;
                    if (dbquery("INSERT INTO `$table_result` SET `domain` = '$domain', `pin_id` = '$pin_db[0]';", null, true, null, 1) == 1) {
//                        $added++;
                    }
                }
            }
        }
        $queries[] = "UPDATE `$table_name` SET `checked` = 1 WHERE `id` = $pin_db[0];";
        $queries[] = "UPDATE `proxy` SET `iterations` = $counter_pins WHERE `id` = $db_proxy_id";
        dbquery($queries, null, null, null, 1);
        unset($queries);
//        echo_time_wasted(null, "#$i $counter_pins / $valid_tld / $try_add / $added http://pinterest.com/pin/$pin_db[1]");
        /** if ((time() - $start_time) > 7200) {
         * get_thread_data(1);
         * $com = new Com('WScript.shell');
         * $com->run('php C:\OpenServer\domains\scripts.loc\www\pinterest\exec.php 1 5 2>&1', 0, false); //2ой параметр положительный чтобы консоль видимой была
         * exit();
         * } */
    }
}
get_thread_data(1);

function pinterest_local_login($pin_acc, $pin_pwd)
{
    global $bot;
    $bot = PinterestBot::create();
    $bot->auth->login($pin_acc, $pin_pwd);
    if ($bot->auth->isLoggedIn()) {
        echo2("login success! Local IP and $pin_acc:$pin_pwd");
    } else {
        echo2("login failed!");
        exit();
    }
}

function get_thread_data($finish = false, $db_proxy_id = false, $login_success = false)
{
    global $link, $login_data;
    if ($finish) {
        $query = "UPDATE `proxy` SET `used` = '0' , `pid` = '', `finish_time` = NOW() WHERE `id` = " . $login_data['id'];
        dbquery($query);
//        mysqli_close($link);
    } else if ($db_proxy_id == false) {
        //Для замеров скорости новых проксей.
//        $query = "SELECT * FROM `proxy` WHERE `used` = 0 AND `speed` = 0 LIMIT 1";
        //Стандартно по скорости
        $query = "SELECT * FROM `proxy` WHERE `used` = 0 ORDER BY `speed` DESC LIMIT 1";
        $login_data = dbquery($query);
        if (count($login_data) == 0) {
            echo2("Нет больше не занятых проксей и аккаунтов! Проверить статусы!");
            exit();
        }
        return $login_data[0];
    } else if ($db_proxy_id == true && $login_success == true) {
        $z = getmypid();
        $name = basename($_SERVER['PHP_SELF']);
        dbquery("UPDATE `proxy` SET `used` = '1', `pid` = $z , `php_self` = '$name' , `iterations` = 0 , `start_time` = NOW(), `update_time` = NOW() WHERE `id` = $db_proxy_id ;");
        return true;
    } else if ($db_proxy_id == true && $login_success == false) {
        dbquery("UPDATE `proxy` SET `used` = '2' WHERE `id` = $db_proxy_id");
    }
}

function pinterest_login($db_proxy_id, $proxy_data, $pinterest_account)
{
    global $bot;
    $proxy_data = explode(':', $proxy_data);
    $pinterest_account = explode(':', $pinterest_account);

    $bot = PinterestBot::create();
    $bot->getHttpClient()->useProxy($proxy_data[0], $proxy_data[1], $proxy_data[2] . ':' . $proxy_data[3]);
    $bot->auth->login($pinterest_account[0], $pinterest_account[1]);

    $proxy_data = implode(":", $proxy_data);
    $pinterest_account = implode(":", $pinterest_account);
    if ($bot->auth->isLoggedIn()) {
        echo2("Login Success! Proxy ==> $proxy_data Account ==> $pinterest_account");
        get_thread_data(false, $db_proxy_id, true);
    } else {
        echo2("LOGIN FAILED! Proxy ==> $proxy_data Account ==> $pinterest_account");
        echo2("SETTING PROXY TO STATUS 2 (аккаунт пинтереста возможно не рабочий!)");
        get_thread_data(false, $db_proxy_id, false);
        exit();
    }
}