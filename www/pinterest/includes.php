<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.03.2017
 * Time: 21:07
 */
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

function get_thread_data($finish = false)
{
    global $link, $login_data;
    if ($finish) {
        $query = "UPDATE `proxy` SET `used` = '0' , `pid` = '', `php_self` = '' WHERE `id` = " . $login_data['id'];
        dbquery($query);
    } else {
        $query = "SELECT * FROM `proxy` WHERE `used` = 0 LIMIT 1";
        $login_data = dbquery($query);
        if (count($login_data) == 0) {
            echo2("Нет больше не занятых проксей и аккаунтов! Проверить статусы!");
            exit();
        }
        return $login_data[0];
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
        $z = getmypid();
        $name = basename($_SERVER['PHP_SELF']);
        dbquery("UPDATE `proxy` SET `used` = '1' WHERE `id` = $db_proxy_id , `pid` = $z , `php_self` = '$name'");
    } else {
        echo2("LOGIN FAILED! Proxy ==> $proxy_data Account ==> $pinterest_account");
        echo2("SETTING PROXY TO STATUS 2 (аккаунт пинтереста возможно не рабочий!)");
        dbquery("UPDATE `proxy` SET `used` = '2' WHERE `id` = $db_proxy_id");
        exit();
    }
}

function check_max($written, $attempt_to_write)
{
    if ($written < $attempt_to_write) {
        $written = $attempt_to_write;
    }
    if ($written === null) {
        $written = 0;
    }
    return $written;
}

function clean_url($url)
{
    //для medhair много урлов с решеткой вконце (addthis), приравниваем
    if (strpos($url, "#") == true) {
        $url = stristr($url, "#", true);
    }
    //https приравниваем к http
    if (preg_match("/https.*/", $url)) {
        $url = 'http' . substr($url, 5);
    }
    return $url;
}

/**
 * @param $id int Pin id
 * @param $get_actions_per_pin int По сколько действий по пину за раз выгружать
 * @param $days_to_get int 31 = Выгрузить все действия не старше чем 31 день.
 */
function get_pin_actions_till_date($id, $get_actions_per_pin, $days_to_get)
{
    global $bot;
    if ($activity = $bot->pins->activity($id, $get_actions_per_pin)) {
        $activity = $activity->toArray();
    } else {
        echo2("Activity fasle! Возможно меньше чем мы запрашиваем есть экшенов!");
    }
    while (round((time() - strtotime($activity[$get_actions_per_pin - 1]['timestamp'])) / 86400) < $days_to_get) {
        $get_actions_per_pin += $get_actions_per_pin;
        if ($activity = $bot->pins->activity($id, $get_actions_per_pin)) {
            $activity = $activity->toArray();
        } else {
            echo2("Activity fasle! Возможно меньше чем мы запрашиваем есть экшенов!");
        }
    }
    return $activity;
}
