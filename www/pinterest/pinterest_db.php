<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 24.02.2017
 * Time: 22:31
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

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

function get_pin_activity($pin_ids, $get_actions_per_pin = 5)
{
    global $days_7, $days_30, $top10_pins_oldest_action;
    foreach ($pin_ids as $id) {
        $activity = get_pin_actions_till_date($id, $get_actions_per_pin, 31);
        if (count($activity) > 0) {
            foreach ($activity as $item) {
                $timestamps[] = strtotime($item['timestamp']);
            }
        }
    }
    foreach ($timestamps as $timestamp) {
        $tmp_seconds_passed = time() - $timestamp;
        $tmp_days_passed = round($tmp_seconds_passed / 86400);
        if ($tmp_days_passed < 8) {
            $days_7++;
        } else if ($tmp_days_passed < 31) {
            $days_30++;
        }
        $days_passed[] = $tmp_days_passed;
    }
    asort($days_passed);
    $top10_pins_oldest_action = end($days_passed);
    return $days_passed;
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
        echo2 ("Activity fasle! Возможно меньше чем мы запрашиваем есть экшенов!");
    }
    while (round((time() - strtotime($activity[$get_actions_per_pin - 1]['timestamp'])) / 86400) < $days_to_get) {
        $get_actions_per_pin += $get_actions_per_pin;
        if ($activity = $bot->pins->activity($id, $get_actions_per_pin)) {
            $activity = $activity->toArray();
        } else {
            echo2 ("Activity fasle! Возможно меньше чем мы запрашиваем есть экшенов!");
        }
    }
    return $activity;
}

function get_thread_data($finish = false)
{
    global $link,$login_data;
    if ($finish) {
        $query = "UPDATE `proxy` SET `used` = '0' WHERE `id` = " . $login_data['id'];
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
        dbquery("UPDATE `proxy` SET `used` = '1' WHERE `id` = $db_proxy_id");
    } else {
        echo2("LOGIN FAILED! Proxy ==> $proxy_data Account ==> $pinterest_account");
        echo2 ("SETTING PROXY TO STATUS 2 (аккаунт пинтереста возможно не рабочий!)");
        dbquery("UPDATE `proxy` SET `used` = '2' WHERE `id` = $db_proxy_id");
        exit();
    }
}

function get_domains_to_parse($count)
{
    global $table_name;
    $list = dbquery("SELECT `id`,`domain` from `$table_name` WHERE status = 0 LIMIT $count");
    $ids = '';
    if (count($list) > 0) {
        foreach ($list as $item) {
            $ids .= $item['id'] . ' , ';
        }
        $ids = substr($ids, 0, -2);
        dbquery("UPDATE `$table_name` SET `status` = 2 WHERE `id` IN ($ids)");
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

$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'pinterest';
$table_name = 'godaddy_closeout';
mysqli_connect2($db_name);
$login_data = get_thread_data();
pinterest_login($login_data['id'], $login_data['proxy'], $login_data['pin_acc']);
while ($domains = get_domains_to_parse(200)) {
    echo2("Загрузили " . count($domains) . " доменов из базы со статусом 0. Обновили им статус на 2 = в процессе");
    foreach ($domains as $domain) {
        $domain_db_id = $domain['id'];
        $domain = $domain['domain'];
        $i++;
//По дефолту fromsource отдает 50 результатов пинов, чтобы получить все надо 0 поставить, может долго думать
        $pins = $bot->pins->fromSource($domain, 0)->toArray();
// вернет сразу все, может долго выполняться, пока не получит от апи все пины
        if (count($pins) > 0) {
            echo2("Получили пины для сайта $domain, всего " . count($pins));
            echo_time_wasted();
            $domain_pins = array();
            foreach ($pins as $pin) {
                $pinned_url = clean_url($pin['link']);
                //Эти данные пока неверны, пинтерест выдает некорректные данные для каждого пина. Будем их В следующем цикле обрабатывать.
                $domain_pins['summary']['pins'] += 1;
                $domain_pins['summary']['saves'] = $pin['aggregated_pin_data']['aggregated_stats']['saves'];
                $domain_pins['summary']['done'] = $pin['aggregated_pin_data']['aggregated_stats']['done'];
                $domain_pins['summary']['likes'] = $pin['aggregated_pin_data']['aggregated_stats']['likes'];
                $domain_pins['summary']['repins'] = $pin['repin_count'];
                //Количество пинов
                $domain_pins[$pinned_url]['summary']['pins_count'] += 1;
                $domain_pins[$pinned_url]['summary']['pins_saves'] = check_max($domain_pins[$pinned_url]['summary']['pins_saves'], $pin['aggregated_pin_data']['aggregated_stats']['saves']);
                $domain_pins[$pinned_url]['summary']['pins_done'] = check_max($domain_pins[$pinned_url]['summary']['pins_done'], $pin['aggregated_pin_data']['aggregated_stats']['done']);
                $domain_pins[$pinned_url]['summary']['pins_likes'] = check_max($domain_pins[$pinned_url]['summary']['pins_likes'], $pin['aggregated_pin_data']['aggregated_stats']['likes']);
                $domain_pins[$pinned_url]['summary']['repins'] = check_max($domain_pins[$pinned_url]['summary']['repins'], $pin['repin_count']);
                //Инфа по каждому пину отдельно взятому
                $domain_pins[$pinned_url]['pins'][$pin['id']]['saves'] = $pin['aggregated_pin_data']['aggregated_stats']['saves'];
                $domain_pins[$pinned_url]['pins'][$pin['id']]['done'] = $pin['aggregated_pin_data']['aggregated_stats']['done'];
                $domain_pins[$pinned_url]['pins'][$pin['id']]['likes'] = $pin['aggregated_pin_data']['aggregated_stats']['likes'];
                $domain_pins[$pinned_url]['pins'][$pin['id']]['repin_count'] = $pin['repin_count'];
                //Доски, проверяем сразу на повторы в досках одних и тех же пинов.
                $domain_pins[$pinned_url]['boards'][$pin['board']['id']] += 1;
                $boards_tmp[] = $pin['board']['id'];
            }

            $tmp = count(array_unique($boards_tmp));
            $domain_pins['summary']['boards_unique'] = $tmp;

//Делаем короткую дату для каждого домена для CSV в дальнейшем
            $domain_review = array(
                'domain' => '',
                'pins_total' => 0,
                'boards_unique' => 0,
                'pins_unique_url' => 0,
                'saves' => 0,
                'likes' => 0,
                'repins' => 0,
                'stolen_pins' => 0,
                'stolen_saves' => 0,
                'stolen_likes' => 0,
                'stolen_repins' => 0,
                '7_days_top10_pins_actions' => 0,
                '30_days_top10_pins_actions' => 0,
                'top10_pins_oldest_action' => 0,
                'top1_pin_url' => 0,
                'top1_pin_activity' => 0);
            $domain_review['domain'] = $domain;
            $domain_review['pins_total'] = count($boards_tmp);
            $domain_review['boards_unique'] = $tmp;
            foreach ($domain_pins as $key => $pin) {
                if (preg_match('/.*' . $domain . '.*/i', $key)) {
                    $domain_review['pins_unique_url'] += 1;
                    $domain_review['saves'] += $pin['summary']['pins_saves'];
                    $domain_review['likes'] += $pin['summary']['pins_likes'];
                    $domain_review['repins'] += $pin['summary']['repins'];
                } else if ($key !== 'summary') {
                    $domain_review['stolen_pins'] += 1;
                    $domain_review['stolen_saves'] += $pin['summary']['pins_saves'];
                    $domain_review['stolen_likes'] += $pin['summary']['pins_likes'];
                    $domain_review['stolen_repins'] += $pin['summary']['repins'];
                }
            }
            if (($domain_review['saves'] + $domain_review['likes'] + $domain_review['repins']) > 1000) {
                echo2("Домен имеет больше 1000 сигналов, парсим активности по топ10 пинов за последние 30 дней!");
                $days_7 = 0; //Функцией запишем сколько активностей в топ10 пинов за последние 7 дней
                $days_30 = 0; //Функцией запишем сколько активностей в топ10 пинов за последние 30 дней
                $top1_pin_url = ''; //URL топ пина
                $top1_pin_activity = ''; // Активностей TOP1 пина
                $top10_pins_oldest_action = ''; //Функцией запишем сколько дней прошло с момента первой активности по топ 10 пинам на основе полученных активностей (топ5 например по каждому пину)
                $top_pins = get_top_pins();
                $top10_pins_days_active = get_pin_activity($top_pins);
                $domain_review['7_days_top10_pins_actions'] = $days_7;
                $domain_review['30_days_top10_pins_actions'] = $days_30;
                $domain_review['top10_pins_oldest_action'] = $top10_pins_oldest_action;
                $domain_review['top1_pin_url'] = $top1_pin_url;
                $domain_review['top1_pin_activity'] = $top1_pin_activity;
            }
            echo2("----- Закончили с доменом $domain -----");
            echo2(print_r($domain_review, 1));
            update_parsed_domain($domain_review, $domain_db_id);
            unset ($boards_tmp, $domain_review, $tmp, $days_7, $days_30, $top1_pin_url, $top10_pins_oldest_action, $top10_pins_days_active, $top1_pin_activity);
        } else {
            echo2("#$i Для домена $domain нету пинов!");
//            $zero_domains[] = $domain_db_id;
        }
    }
}
get_thread_data(1);
//if (count($zero_domains) > 0) {
//    echo2 ("Дошли до конца базы, пытаемся обновить статус для проверенных ".count($zero_domains)." без упоминаний на Pinterest");
//    update_parsed_domain(null, $zero_domains);
//}