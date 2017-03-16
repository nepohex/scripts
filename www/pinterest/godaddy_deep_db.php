<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.03.2017
 * Time: 16:29
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

$console_mode = 1;
$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'pinterest';
$table_name = 'godaddy_buynow';
$table_gold = 'godaddy_buynow_gold';
mysqli_connect2($db_name);
//$pin_acc = 'inga.tarpavina.89@mail.ru';
//$pin_pwd = 'xmi0aJByoB';
//pinterest_local_login($pin_acc, $pin_pwd);
//$domains = array('69-withjesus.com');
$login_data = get_thread_data();
pinterest_login($login_data['id'], $login_data['proxy'], $login_data['pin_acc']);
while ($domains = get_deep_domains_to_parse(1)) {
    foreach ($domains as $domain) {
        $domain_db_id = $domain['id'];
        $domain = $domain['domain'];
        $i++;
        //По дефолту fromsource отдает 50 результатов пинов, чтобы получить все надо 0 поставить, может долго думать
        $pins = $bot->pins->fromSource($domain, 0)->toArray();
        file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_deep_start_srlz.txt", serialize($pins));
        // вернет сразу все, может долго выполняться, пока не получит от апи все пины
        if (count($pins) > 0) {
            echo2("Получили пины для сайта $domain, всего " . count($pins));
            $days_7 = 0; //Функцией запишем сколько активностей по всем пинам за последние 7 дней
            $days_30 = 0; //Функцией запишем сколько активностей по всем пинам за последние 30 дней
            $days_99 = 0; //Функцией запишем сколько активностей по всем пинам за последние 99 дней
            $created_activity = '0'; //Сколько из общих активностей по пину было именно создано новых
            $repins_activity = '0'; //Репинов. Также сюда запишем все неуникальные URL пинов.
            $likes_activity = '0'; //Лайков
            $pin_ids = get_pin_ids($pins);
            get_pin_activity($pin_ids);
            $domains[0]['7_days_all_pins_actions'] = $days_7;
            $domains[0]['30_days_all_pins_actions'] = $days_30;
            $domains[0]['99_days_all_pins_actions'] = $days_99;
            $domains[0]['created_activity'] = $created_activity;
            $domains[0]['repins_activity'] = $repins_activity;
            $domains[0]['likes_activity'] = $likes_activity;
            echo2(print_r($domains[0], 1));
            update_deep_parsed_domain($domains[0], $domain_db_id);
            put_deep_parsed_domain($domains[0]);
            file_put_contents("debug_data/pins_deep_" . $domain . "_" . count($pins) . "_new2_srlz.txt", serialize($domains[0]));
        } else {
            echo2("#$i Для домена $domain нету пинов!");
        }
    }
}
get_thread_data(1);

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

function get_top_pins($count = 10)
{
    global $pins, $domain, $top1_pin_url, $top1_pin_activity;
    foreach ($pins as $pin) {
        if (preg_match('/.*' . $domain . '.*/i', $pin['domain'])) {
            $tmp_pin_actions = $pin['repin_count'] + $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
            $tmp_top_pins[$tmp_pin_actions]['id'] = $pin['id'];
        }
    }
    krsort($tmp_top_pins);
    $top1_pin_activity = key($tmp_top_pins);
    $tmp_counter = count($tmp_top_pins);
    if ($tmp_counter > $count) {
        for ($i = 0; $i < $count; $i++) {
            $tmp = array_shift($tmp_top_pins);
            if ($i == 0) {
                $top1_pin_url = 'http://pinterest.com/pin/' . $tmp['id'];
            }
            $top_pins[] = $tmp['id'];
        }
    } else {
        for ($i = 0; $i < $tmp_counter; $i++) {
            $tmp = array_shift($tmp_top_pins);
            if ($i == 0) {
                $top1_pin_url = 'http://pinterest.com/pin/' . $tmp['id'];
            }
            $top_pins[] = $tmp['id'];
        }
    }
    return $top_pins;
}

function get_pin_activity($pin_ids, $get_actions_per_pin = 5, $days_active = 31)
{
    global $days_7, $days_30, $days_99, $days_100, $created_activity, $repins_activity, $likes_activity;
    $i = 0; // Сколько пинов пробежали
    $counter_actions = 0; // Сколько активнотей достали всего
    $counter_pins = count($pin_ids); // Сколько пинов всего
    foreach ($pin_ids as $id) {
        $i++;
        $activity = get_pin_actions_till_date($id, $get_actions_per_pin, $days_active);
        if (count($activity) > 0) {
            foreach ($activity as $item) {
                $counter_actions++;
                $timestamps[] = strtotime($item['timestamp']);
                if ($item['type'] == 'pincreationactivity') {
                    $created_activity++;
                } else if ($item['type'] == 'repinactivity') {
                    $repins_activity++;
                } else if ($item['type'] == 'likepinactivity') {
                    $likes_activity++;
                }
            }
        }
        echo_time_wasted($i, "/ $counter_pins http://pinterest.com/pin/$id Активностей вытащили $counter_actions");
    }
    foreach ($timestamps as $timestamp) {
        $tmp_seconds_passed = time() - $timestamp;
        $tmp_days_passed = round($tmp_seconds_passed / 86400);
        if ($tmp_days_passed < 8) {
            $days_7++;
        } else if ($tmp_days_passed < 31) {
            $days_30++;
        } else if ($tmp_days_passed < 99) {
            $days_99++;
        } else if ($tmp_days_passed > 100) {
            $days_100++;
        }
    }
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
        return $activity;
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

function get_thread_data($finish = false, $db_proxy_id = false, $login_success = false)
{
    global $link, $login_data;
    if ($finish) {
        $query = "UPDATE `proxy` SET `used` = '0' , `pid` = '', `finish_time` = NOW() WHERE `id` = " . $login_data['id'];
        dbquery($query);
//        mysqli_close($link);
    } else if ($db_proxy_id == false) {
        $query = "SELECT * FROM `proxy` WHERE `used` = 0 LIMIT 1";
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

function get_domains_to_parse($count)
{
    global $table_name;
    $list = dbquery("SELECT `id`,`domain` from `$table_name` WHERE status = 0 LIMIT $count");
    $ids = '';
    foreach ($list as $item) {
        $ids .= $item['id'] . ' , ';
    }
    $ids = substr($ids, 0, -2);
    dbquery("UPDATE `$table_name` SET `status` = 2 WHERE `id` IN ($ids)");
    if (count($list) > 100) {
        return $list;
    } else {
        return false;
    }
}

function update_parsed_domain($domain_review, $domain_db_id)
{
    global $table_name;
    if ($domain_review) {
        $domain_review_numeric = array_values($domain_review);
        $query = "UPDATE `$table_name` SET 
`status` = '1',
`domain` = '$domain_review_numeric[0]',
`pins_total` = $domain_review_numeric[1], 
`boards_unique` = $domain_review_numeric[2],
`pins_unique_url` = $domain_review_numeric[3],
`saves` = $domain_review_numeric[4], 
`likes` = $domain_review_numeric[5], 
`repins` = $domain_review_numeric[6], 
`stolen_pins` = $domain_review_numeric[7], 
`stolen_saves` = $domain_review_numeric[8], 
`stolen_likes` = $domain_review_numeric[9], 
`stolen_repins` = $domain_review_numeric[10], 
`7_days_top10_pins_actions` = $domain_review_numeric[11], 
`30_days_top10_pins_actions` = $domain_review_numeric[12], 
`top10_pins_oldest_action` = $domain_review_numeric[13], 
`top1_pin_url` = '$domain_review_numeric[14]', 
`top1_pin_activity` = $domain_review_numeric[15] 
WHERE `id` = $domain_db_id ;";
        dbquery($query);
    } else {
        $ids = '';
        foreach ($domain_db_id as $id) {
            $ids .= $id . ' , ';
            if (count($ids) == 1000) {
                $ids = substr($ids, 0, -2);
                $query = "UPDATE `$table_name` SET `status` = '1' WHERE `id` IN ($ids)";
                dbquery($query);
                $ids = '';
            }
        }
    }

}

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

function get_pin_ids($pins)
{
    global $repins_activity;
    $urls = array();
    if (count($pins) > 0) {
        foreach ($pins as $pin) {
            $repins_activity++;
            $url = clean_url($pin['link']);
            //Для некоторых URL бывает по 100к досок, а иногда по 0.
            $aggr_stat = $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['done'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
            //Если URL один и тот же считаем его как 1 пин
            $arr_element = $url . $aggr_stat;
            if ($urls[$arr_element] == null) {
                $urls[$arr_element][] = $pin['id'];
                $repins_activity--;
            }
        }
        foreach ($urls as $id) {
            $ids[] = $id[0];
        }
    } else {
        echo2("Пинов 0 передали, не можем получить Pin ids");
    }
    echo2("После очистки и проверки на уникальность URL осталось всего " . count($ids) . " страниц для парсинга");
    return $ids;
}

function get_deep_domains_to_parse($count)
{
    global $table_name;
    // status = '0 - не чекали, 1 - чекнули, 2 - в процессе (чекнули = пустой),  3 - start deep parse each pin, 4 - finish deep parse'
    $list = dbquery("SELECT * FROM `$table_name` WHERE `status` = 1 AND (`7_days_top10_pins_actions` > 100 OR `30_days_top10_pins_actions` > 200) ORDER BY `30_days_top10_pins_actions`  DESC LIMIT $count");
//    $list = dbquery("SELECT * FROM `$table_name` WHERE `status` IN (1,2) AND `top1_pin_activity` > 100 ORDER BY `top1_pin_activity`  DESC LIMIT $count");
//    $list = dbquery("SELECT * FROM `$table_name` WHERE `status` IN (1,2) AND `top1_pin_activity` > 1000 ORDER BY `30_days_top10_pins_actions`  DESC LIMIT $count");
//    $list = dbquery("SELECT * FROM `$table_name` WHERE `domain` = 'chasefencing.net'");
    if (count($list) > 0) {
        $ids = '';
        foreach ($list as $item) {
            $ids .= $item['id'] . ' , ';
        }
        $ids = trim(substr($ids, 0, -2));
        dbquery("UPDATE `$table_name` SET `status` = 3 WHERE `id` IN ($ids)");
        echo2("Загрузили " . count($list) . " доменов из базы со статусом 1 или 2. Обновили им статус на 3 = в процессе");
        return $list;
    } else {
        echo2("Нет больше доменов для парсинга");
        get_thread_data(1);
        return false;
    }
}

function update_deep_parsed_domain($domain_review, $domain_db_id)
{
    global $table_name;
    if ($domain_review) {
        $query = "UPDATE `$table_name` SET `status` = '4' WHERE `id` = $domain_db_id ;";
        dbquery($query);
    }
}

function put_deep_parsed_domain($domain_arr)
{
    global $table_gold;
    if ($domain_arr) {
        $num = array_values($domain_arr);
        $query = "INSERT INTO  `$table_gold` SET 
`id` = $num[0],
`domain` = '$num[1]', 
`status` = 4, 
`pins_total` = $num[3], 
`boards_unique` = $num[4],
`pins_unique_url` = $num[5] ,
`saves` = $num[6],
`likes` = $num[7],
`repins` = $num[8],
`stolen_pins` = $num[9],
`stolen_saves` = $num[10],
`stolen_likes` = $num[11],
`stolen_repins` = $num[12],
`7_days_all_pins_actions` = $num[21],
`30_days_all_pins_actions` = $num[22],
`99_days_all_pins_actions` = $num[23],
`created_activity` = $num[24],
`repins_activity` = $num[25],
`likes_activity` = $num[26],
`price` = $num[16],
`end_date` = '$num[17]',
`top1_pin_url` = '$num[18]',
`top1_pin_activity` = $num[19],
`check_date` = NOW();";
        dbquery($query);
    }
}