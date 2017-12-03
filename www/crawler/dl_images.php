<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 29.11.2017
 * Time: 18:52
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
require '../dom/includes/functions.php';
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use DiDom\Document;

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'dev_translated_parse';
$tables['map'] = 'map_medical-best-help.com';
$tables['content'] = 'content_medical-best-help.com';
$fp_log = 'debug/' . $tables['map'] . 'img_log.txt';
$url = 'http://medical-best-help.com';
$dl_dir = 'F://Dumps/TRANSLATE/' . parse_url($url, PHP_URL_HOST);
$threads = 40; //Потоков

echo2($url);
$headers = ['Client' => 'Baiduspider', 'UserAgent' => 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'];
$client = new GuzzleHttp\Client(['base_uri' => $url]);

//$total_posts = dbquery("SELECT COUNT(*) FROM `$db_name`.`$tables[content]` WHERE `images_done` = '';");
$i = 0; //счетчик итераций
$z = 0; //счетчик если ничего не скачали и даже не начинали качать
$c_repeats = 0; //сколько раз качали повторные картинки
$c_imgs_dl = 0; //сколько картинок скачано всего
while ($imgs = crawler_db_get_imgs()) {
//    $imgs = crawler_db_get_imgs();
    $i++;
    $imgs = crawler_make_full_url($imgs, $url);
    if (put_imgs($imgs['images_urls'], $dl_dir, TRUE) == FALSE) {
        $promises = prepare_async_img_promises($client, $imgs['images_urls']);
        $c_imgs_dl += count($promises); //counter
        $results = Promise\settle($promises)->wait();
        $html_results = results_get_imgs_data($results, $imgs);
        $success_imgs = put_imgs($html_results['result'], $dl_dir);
        if ($success_imgs[0] == count($promises)) {
            dbquery("UPDATE `$db_name`.`$tables[content]` SET `images_done` = 1 WHERE `id` = $imgs[id];");
        } else {
            dbquery("UPDATE `$db_name`.`$tables[content]` SET `images_done` = 2 WHERE `id` = $imgs[id];");
        }
        if ($success_imgs[1] > 0) {
            $c_repeats += 1;
            if ($c_repeats % 1000 == 0) {
                echo2("Более 1000 раз были дубли при сохранении картинок / запросов. Скорее всего идем уже по повтору картинок из-за языков");
                exit();
            }
        }
        if ($i % 100 == 0) {
            echo_time_wasted("$i / $total_posts pid " . getmypid() . " Всего картинок скачано $c_imgs_dl / $c_repeats дублей");
        }
    } else {
        $z++;
        dbquery("UPDATE `$db_name`.`$tables[content]` SET `images_done` = 1 WHERE `id` = $imgs[id];");
        if ($z % 1000 == 0) {
            echo_time_wasted("$i строк прошли и даже не начинали качать $z из них. Выходим.  pid " . getmypid() . " Всего картинок скачано $c_imgs_dl / $c_repeats дублей");
            exit ();
        }
    }
}
echo2("Нет больше строк для сбора картинок!");

function put_imgs(array $img_data, $local_storage_path = './', $only_check_imgs = FALSE)
{
    $i = 0;
    $img_done = 0;
    if ($only_check_imgs) {
        foreach ($img_data as $key => $image) {
            $i++;
            $img_relative_path = parse_url($image, PHP_URL_PATH);
            if (is_file($local_storage_path . $img_relative_path)) {
                $img_done++;
            }
        }
        if ($img_done == $i) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        foreach ($img_data as $key => $image) {
            if ($image['code'] == 200) {
                $i++;
                $img_relative_path = parse_url($key, PHP_URL_PATH);
                prepare_dir($local_storage_path . dirname($img_relative_path));
                if (is_file($local_storage_path . $img_relative_path)) {
                    $img_done = 1;
                } else {
                    file_put_contents($local_storage_path . $img_relative_path, $image['html']);
                }
            }
        }
    }
    return array($i, $img_done);
}

function results_get_imgs_data(array $results, array $urls_to_parse = array())
{
    foreach ($results as $key => $res) {
        if ($res['state'] == 'fulfilled') {
            if ($res['value']->getStatusCode() == '200') {
                $code = $res['value']->getStatusCode();
                $urls_to_parse['result'][$key]['code'] = $code;
                $html = (string)$res['value']->getBody();
                if ($html !== FALSE) {
                    $urls_to_parse['result'][$key]['html'] = (string)$res['value']->getBody();
                }
            } else {
                $code = $res['value']->getStatusCode();
                $urls_to_parse['result'][$key]['code'] = $code;
            }
        } else {
            //TMP разобраться с обработчиком ошибок
            $urls_to_parse['result'][$key]['code'] = 500;
        }
    }
    return $urls_to_parse;
}

function prepare_async_img_promises($client, array $urls_to_get)
{
    foreach ($urls_to_get as $id => $item) {
        if (contains($item, array('base64')) == FALSE) {
            $promise[$item] = $client->getAsync($item);
        }
    }
    return $promise;
}

function crawler_make_full_url(array $multidim, $host)
{
    // Ниже ад вместо кода. Я был болен! Это для мультимассива.
//    if (is_array($multidim) && is_abs_url($host)) {
//        foreach ($multidim as $key => $item) {
//            foreach ($item as $imgs_urls_arr_key => $imgs_arr) {
//                if (is_array($imgs_arr)) {
//                    foreach ($imgs_arr as $img_key => $img_url) {
//                        $multidim[$key][$imgs_urls_arr_key][$img_key] = $host . $img_url;
//                    }
//                }
//            }
//        }
//    }
    //альтернативный варик
    if (is_array($multidim) && is_abs_url($host)) {
        foreach ($multidim['images_urls'] as $key => $item) {
            $multidim['images_urls'][$key] = $host . $item;
        }
    } else {
        echo2("Либо передан не массив с адресами картинок, либо неверно указан домен $host;");
        return FALSE;
    }
    return $multidim;
}

function crawler_db_get_imgs($count = 1)
{
    global $db_name, $tables;
    $res = dbquery("SELECT `id`, `images_urls` FROM `$db_name`.`$tables[content]` WHERE `images_done` = 'FALSE' AND `images` != 0 LIMIT $count;");
    if (is_array($res)) {
        $id = $res[0]['id'];
        dbquery("UPDATE `$db_name`.`$tables[content]` SET `images_done` = 3 WHERE `id` = $id;");
        foreach ($res as $key => $item) {
            $res[$key]['images_urls'] = unserialize($item['images_urls']);
            foreach ($res[$key]['images_urls'] as $key2 => $img) {
                if (contains($img, array('/files/')) == FALSE) {
                    unset($res[$key]['images_urls'][$key2]);
                }
            }
        }
        //todo времено только 1ый массив возвращаем
        return $res[0];
    } else {
        return FALSE;
    }
}

function crawler_prepare_async_promises($client, array $urls_to_get)
{
    foreach ($urls_to_get as $db_id => $arr) {
        if (isset($arr['href'])) {
            $promises[$db_id] = $client->getAsync($arr['href']);
        } else {
            echo2("Проверить массив который получили из базы, пытаемся получить колонку HREF, ее возможно не существует или колонка переименована!");
            exit ();
        }
    }
    return $promises;
}