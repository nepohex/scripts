<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.02.2017
 * Time: 17:24
 * в 1 поток из файловой системы
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

//$tmp = unserialize(file_get_contents('result/pins_myowntattoos.com_11_start_srlz.txt'));
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
        foreach ($activity as $item) {
            $timestamps[] = strtotime($item['timestamp']);
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
    $activity = $bot->pins->activity($id, $get_actions_per_pin)->toArray();
    while (round((time() - strtotime($activity[$get_actions_per_pin - 1]['timestamp'])) / 86400) < $days_to_get) {
        $get_actions_per_pin += $get_actions_per_pin;
        $activity = $bot->pins->activity($id, $get_actions_per_pin)->toArray();
    }
    return $activity;
}


$double_mode = 1;
$fp_log = fopen('log_db_mix.txt', "a+");
$result_fp = 'result/rand_com_domains.csv';
$dirfiles = scandir("sources"); // из файла с сайта https://member.expireddomains.net/domains/expiredcom201606/
foreach ($dirfiles as $parsefname) {
    if (strpos($parsefname, ".csv")) {
        $csv_expireddomains_net = 'sources/' . $parsefname;
        $domains = csv_to_array2($csv_expireddomains_net, ';', 1, 1); // из файла с сайта https://member.expireddomains.net/domains/expiredcom201606/
        echo2("Загрузили доменов из файла " . count($domains));
        $exclude_domains = array(); // Будет наполнен из файла result_fp теми доменами которые уже проверяли
        $csv_header = array(
            'domain',
            'pins_total',
            'boards_unique',
            'pins_unique_url',
            'saves',
            'likes',
            'repins',
            'stolen_pins',
            'stolen_saves',
            'stolen_likes',
            'stolen_repins',
            '7_days_top10_pins_actions',
            '30_days_top10_pins_actions',
            'top10_pins_oldest_action',
            'top1_pin_url',
            'top1_pin_activity',
        );
        if (is_file($result_fp) == false) {
            $csv_result = fopen($result_fp, "a");
            fputcsv($csv_result, $csv_header, ';');
        } else {
            $csv_result = fopen($result_fp, "a");
            $tmp = csv_to_array($result_fp, ";");
            foreach ($tmp as $tmp2) {
                $exclude_domains[] = $tmp2[0];
            }
        }
        unset($tmp, $tmp2);
        $domains = array_map('trim', array_diff($domains, $exclude_domains));
        echo2("Доменов которые еще не проверили " . count($domains) . " из файла $csv_expireddomains_net");

        if (count($domains) > 0) {

            $bot = PinterestBot::create();

            $bot->auth->login('inga.tarpavina.89@mail.ru', 'xmi0aJByoB');

            if ($bot->auth->isLoggedIn()) {
                echo2("login success!");
            } else {
                echo2("login failed!");
                exit();
            }
            $i = 0;
            foreach ($domains as $domain) {
                $i++;
//По дефолту fromsource отдает 50 результатов пинов, чтобы получить все надо 0 поставить, может долго думать
                $pins = $bot->pins->fromSource($domain, 0)->toArray();
// вернет сразу все, может долго выполняться, пока не получит от апи все пины
                if (count($pins) > 0) {
                    echo2("Получили пины для сайта $domain, всего " . count($pins) . " , записываем результаты в папку, обрабатываем");
                    echo_time_wasted();
                    file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_start_srlz.txt", serialize($pins));

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
                        file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_finish_bigdata_printr.txt", print_r($domain_pins, true));
                        file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_finish_overview_printr.txt", print_r($domain_review, true));
                        file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_finish_bigdata_srlz.txt", serialize($domain_pins));
                        file_put_contents("debug_data/pins_" . $domain . "_" . count($pins) . "_finish_top10_actions_days_printr.txt", print_r($top10_pins_days_active, true));
                    }
                    echo2("----- Закончили с доменом $domain -----");
                    echo2(print_r($domain_review, 1));
                    fputcsv($csv_result, $domain_review, ';');
                    unset ($boards_tmp, $domain_review, $tmp, $days_7, $days_30, $top1_pin_url, $top10_pins_oldest_action, $top10_pins_days_active, $top1_pin_activity);
                } else {
                    echo2("#$i Для домена $domain нету пинов! Проверить либо связь, либо все ок!");
                    $tmp_arr = array($domain, '0');
                    fputcsv($csv_result, $tmp_arr, ';');
                }
            }
        } else {
            echo2("Нет доменов для проверки! Массив domains пустой. Берем следующий файл!");
        }
        unset($exclude_domains, $csv_expireddomains_net, $domains, $csv_header, $bot, $domain_pins);
        fclose($csv_result);
    }
}