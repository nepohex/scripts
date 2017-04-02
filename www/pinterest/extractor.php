<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.03.2017
 * Time: 21:57
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

//$console_mode = 1;
$debug_mode = 1;
$start_time = time();

$db_pwd = '';
$db_usr = 'root';
$db_name = 'pinterest';
$table_name = 'my_domains';
$table_result = 'extractor';
//mysqli_connect2($db_name);

//$login_data = get_thread_data();
//pinterest_login($login_data['id'], $login_data['proxy'], $login_data['pin_acc']);
//$db_proxy_id = $login_data['id'];
//if ($dead_proxy) {
//    dbquery("UPDATE `proxy` SET `used` = '4' WHERE `id` = $db_proxy_id");
//    exit();
//}
$domain = 'goddessbraids.net';
$domain_dir = __DIR__.'/extractor/'.$domain;
$pins_minimum = unserialize(file_get_contents('debug_data/' . $domain . '_pins_minimum_tags.txt'));
//get_imgs($pins_minimum,$domain_dir);
//
//function get_imgs ($arr, $domain_dir) {
//    mk_site_dir($domain_dir);
//    foreach ($arr as $item) {
//        if ($item['image'] == true && is_file($domain_dir.'/'.$item['id']) == false) {
//            file_put_contents($domain_dir.'/'.$item['id'],file_get_contents($item['image']));
//            if (is_file($domain_dir.'/'.$item['id']) == true) {
//                $item['image_saved'] = 1;
//            }
//        }
//    }
//}

function mk_site_dir ($domain_dir) {
    if (is_dir($domain_dir)) {
        return true;
    } else {
        mkdir2($domain_dir);
    }
}

////
$pin_acc = 'inga.tarpavina.89@mail.ru';
$pin_pwd = 'xmi0aJByoB';
pinterest_local_login($pin_acc, $pin_pwd);
//Нужны для функций - не менять!
$my_images = 0; //Сколько уникальных картинок из наших пинов
$similar_images = 0; //Похожих картинок извлекли для наших пинов и для related пинов.
$boards = 0; //Сколько тегов для картинок наших и similar + related
$related_images = 0; //Похожие картинки к пинам у которых больше 500 сигналов.
$domain = 'goddessbraids.net';
// --
$pins = $bot->pins->fromSource($domain, 500)->toArray();
echo2("Получили " . count($pins) . " пинов");
file_put_contents($domain_dir. '_pins.txt', serialize($pins));
$pins = unserialize(file_get_contents($domain_dir. '_pins.txt'));
$pins_unique = extract_unique_pins();
$pins_boards = get_pins_deep($pins_unique);
file_put_contents($domain_dir. '_pins_deep.txt', serialize($pins_boards));
$pins_boards = unserialize(file_get_contents($domain_dir. '_pins_deep.txt'));
$pins_related = get_top_related($pins_boards, 500);
file_put_contents($domain_dir. '_pins_related.txt', serialize($pins_related));
$pins_related = unserialize(file_get_contents($domain_dir. '_pins_related.txt'));
$pins_minimum = minimum_tags($pins_related, $boards, 500);
file_put_contents($domain_dir . '_pins_minimum_tags.txt', serialize($pins_minimum));
$i = 0;
echo2("$domain => my images $my_images / similar images $similar_images / tags $boards / related images $related_images ");

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

function extract_unique_pins()
{
    global $pins, $domain, $my_images;
    $tmp_top_pins = array();
    foreach ($pins as $pin) {
        if (preg_match('/.*' . $domain . '.*/i', $pin['domain'])) {
            $sign = $pin['image_signature'];
            $actions = $pin['repin_count'] + $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
            if ($tmp_top_pins[$sign][0] < $actions) {
                $tmp_top_pins[$sign]['id'] = $pin['id'];
                $tmp_top_pins[$sign]['actions'] = $actions;
                $tmp_top_pins[$sign]['link'] = clean_url($pin['link']);
                $tmp_top_pins[$sign]['description'] = $pin['description'];
                $tmp_top_pins[$sign]['title'] = $pin['title'];
                $tmp_top_pins[$sign]['image'] = $pin['images']['736x']['url'];
            }
        }
    }
    $arr2 = array_msort($tmp_top_pins, array('actions' => SORT_DESC));
    $my_images = count($arr2);
    return $arr2;
}

function get_pins_deep($arr)
{
    global $bot, $similar_images, $boards;
    echo_time_wasted(null, "Собираем визуально похожие для уникальных " . count($arr) . " картинок.");
    $z = 0;
    foreach ($arr as $key => $item) {
        $similar = $bot->pins->visualSimilar($item['id']);
        if (count($similar['result_pins']) > 0) {
            $arr[$key]['boards'] = $similar['annotations'];
            $boards += count($similar['annotations']);
            $i = 0;
            foreach ($similar['result_pins'] as $other_pins) {
                $arr[$key]['similar'][$i]['id'] = $other_pins['id'];
                $arr[$key]['similar'][$i]['actions'] = $other_pins['like_count'] + $other_pins['repin_count'];
                $arr[$key]['similar'][$i]['link'] = $other_pins['link'];
                $arr[$key]['similar'][$i]['description'] = $other_pins['description'];
                $arr[$key]['similar'][$i]['image'] = $other_pins['images']['736x']['url'];
                $i++;
            }
            $z++;
            $similar_images += $i;
            echo_time_wasted($z, "Досок(тегов) $boards");
        }
    }
    return $arr;
}

function get_top_related($arr, $minimum_actions = 500, $step = 100)
{
    global $bot, $related_images;
    echo_time_wasted(null, "Собираем related для топовых пинов где больше 500 экшенов " . count($arr) . " пинов.");
    foreach ($arr as $key => $item) {
        if ($item['actions'] > $minimum_actions) {
            echo2("Нашелся pin с > 500 Actions, парсим $step Related картинок");
            $related = $bot->pins->related($item['id'], $step);
            $i = 0;
            foreach ($related as $pin) {
                $arr[$key]['related'][$i]['id'] = $pin['id'];
                $arr[$key]['related'][$i]['actions'] = $pin['repin_count'] + $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
                $arr[$key]['related'][$i]['link'] = clean_url($pin['link']);
                $arr[$key]['related'][$i]['description'] = $pin['description'];
                $arr[$key]['related'][$i]['image'] = $pin['images']['736x']['url'];
                $i++;
                echo_time_wasted($i);
            }
            $related_images += count($arr[$key]['related']);
        }
    }
    return $arr;
}

function minimum_tags($arr, $boards, $minimum_tags = 1000)
{
    global $bot, $similar_images, $boards;
    echo2("Производим добивку до количества тегов минимум $minimum_tags , парсим Related картинки ищем Similar");
    if ($boards > $minimum_tags) {
        echo2("Количество тегов больше необходимого минимума в $minimum_tags , все ок");
        return $arr;
    }
    foreach ($arr as $key => $item) {
        if ($item['related'] == true) {
            $z = 0;
            foreach ($item['related'] as $key_r => $value_r) {
                $z++;
                $similar = $bot->pins->visualSimilar($value_r['id']);
                if (count($similar['result_pins']) > 0) {
                    $arr[$key]['related'][$key_r]['boards'] = $similar['annotations'];
                    $boards += count($similar['annotations']);
                    $i = 0;
                    foreach ($similar['result_pins'] as $other_pins) {
                        $arr[$key]['related'][$key_r]['similar'][$i]['id'] = $other_pins['id'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['actions'] = $other_pins['like_count'] + $other_pins['repin_count'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['link'] = $other_pins['link'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['description'] = $other_pins['description'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['image'] = $other_pins['images']['736x']['url'];
                        $i++;
                    }
                    $similar_images += $i;
                    echo_time_wasted($z, "Досок(тегов) всего $boards");
                    if ($boards > $minimum_tags) {
                        echo2("Количество тегов больше необходимого минимума в $minimum_tags , все ок");
                        return $arr;
                    }
                }
            }
        }
    }
    echo2("Не набрали минимум тегов в $minimum_tags , используем те что есть $boards");
    return $arr;
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