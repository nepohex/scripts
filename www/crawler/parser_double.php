<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.11.2017
 * Time: 15:59
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
$tables['map'] = 'map_womans-company.com';
$tables['content'] = 'content_womans-company.com';
$fp_log = 'debug/' . $tables['map'] . '_log.txt';

$url = 'http://womans-company.com/';
$content_selector = '.entry-content'; //Основной элемент в котором находится весь контент
$urls_whitelist = array('/pages/'); //Страницы с которых будем складывать контент
$breadcrumbs_selector = '#dle-speedbar'; //Блок с категориями
$threads = 40; //Потоков

$headers = ['Client' => 'Baiduspider', 'UserAgent' => 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'];
$client = new GuzzleHttp\Client(['base_uri' => $url]);
$response = $client->get('/', $headers);
$body = (string)$response->getBody();
file_put_contents("debug/index.html", $body);
$content = file_get_contents("debug/index.html");

$document = new Document();
$unique_hrefs = dom_get_unique_hrefs($content, $document, TRUE, $url, TRUE);
db_import_hrefs($unique_hrefs, $url);
$c_queries = 0; //счетчик запросов к сайту
while ($next = db_get_urls_to_parse($threads)) {
    $c_queries += $threads;
    $promises = prepare_async_promises($client, $next);
    $results = Promise\settle($promises)->wait();
    $html_results = results_get_html($results, $next);
    db_update_map($html_results, $url);
    db_update_codesize($html_results);
    $html_results = crawler_dom_handle_html($html_results, $content_selector, $breadcrumbs_selector, $urls_whitelist);
    db_put_content($html_results, $urls_whitelist);
    if ($c_queries % 1000 == 0) {
        echo_time_wasted($c_queries);
//        $z = crawler_get_db_stats();
//        echo_time_wasted($c_queries, "Потоков $threads " . $z);
    }
}

/** Возвращает статистику по таблицам, при объемах от 100к строк в карте уже начинает конкретно тормозить.
 * Отключил при объеме в 200к строк карты
 * Стало быстрее в 4 раза
 * @return string
 */
function crawler_get_db_stats()
{
    global $db_name, $tables, $link;
    $i[] = 'map parsed urls';
    $i[] = dbquery("SELECT COUNT(*) FROM `$db_name`.`$tables[map]` WHERE `code` != '';"); //map parsed urls
    $i[] = 'map queue';
    $i[] = dbquery("SELECT COUNT(*) FROM `$db_name`.`$tables[map]` WHERE `code` = '';"); // map queue
    $i[] = 'total map urls';
    $i[] = dbquery("SELECT COUNT(*) FROM `$db_name`.`$tables[map]`;"); //total map urls
    $i[] = 'content rows';
    $i[] = dbquery("SELECT COUNT(*) FROM `$db_name`.`$tables[content]`;"); //content rows
    $i[] = 'content total chars';
    $i[] = dbquery("SELECT SUM(`text_size`) FROM `$db_name`.`$tables[content]`;"); //content total chars
    $z = implode("   |   ", $i);
    return $z;
}

function db_put_content(array $data, $urls_whitelist)
{
    global $db_name, $tables, $link;
    foreach ($data as $item) {
        if (contains($item['href'], $urls_whitelist)) {
            $html = mysqli_real_escape_string($link, $item['content']);
            $text_size = count_strlen_html($item['content']);
            if (isset($item['imgs'])) {
                $images_urls = serialize($item['imgs']);
                $images = count($item['imgs']);
            } else {
                $images_urls = '';
                $images = 0;
            }
            $category = serialize(mysqli_real_escape_string($link, $item['category']));
            $meta = mysqli_real_escape_string($link, serialize($item['meta']));
            dbquery("INSERT INTO `$db_name`.`$tables[content]` (`map_id`,`html`,`text_size`,`images_urls`,`category`,`images`,`meta`) 
                      VALUES ('$item[id]','$html',$text_size,'$images_urls','$category',$images,'$meta');");
        }
    }
}


function crawler_dom_handle_html(array $data, $content_selector, $breadcrumbs_selector, $urls_whitelist, $document = FALSE)
{
    if (empty($document)) {
        global $document;
    }
    foreach ($data as $key => $item) {
        if (isset($item['html']) && contains($item['href'], $urls_whitelist)) {
            $document->loadHtml($item['html']);
            $html_meta = dom_get_article_data($document);
            $data[$key]['meta'] = $html_meta;
            $breadcrumbs = $document->first($breadcrumbs_selector)->toDocument()->format()->html();
            $data[$key]['category'] = $breadcrumbs;
            //Теперь начинается работа с самим элементом с контентом
            $try_doc2 = $document->first($content_selector)->toDocument();
            //Очистка от кодов рекламы и т.п. основного блока контента
            $try_doc2 = dom_cleanup_document($try_doc2);
            //Очистка через регулярки финальная от комментариев в коде и того что не удалось удалить через DiDom
            $try_html = $try_doc2->format()->html();
            $try_html = preg_replace('/<!--(.+?)-->/', '', $try_html);
            $try_html = str_replace("\r\n", '', $try_html);
            //Очистка через регулярки финальная
            $data[$key]['content'] = $try_html;
            //Получение URL картинок
            $img_urls = dom_get_img_urls($try_doc2, array('src', 'srcset'));
            if (is_array($img_urls)) {
                $data[$key]['imgs'] = $img_urls;
            }

        }
    }
    return $data;
}


function db_update_map(array $multidimarr, $domain)
{
    foreach ($multidimarr as $key => $item) {
        if (isset($item['html'])) {
            $uniq_hrefs = dom_get_unique_hrefs($item['html'], FALSE, TRUE, $domain, TRUE);
            if (is_array($uniq_hrefs)) {
                $multidimarr[$key]['hrefs'] = $uniq_hrefs;
                db_import_hrefs($uniq_hrefs, $item['href']);
            }
        }
    }
}

function db_update_codesize($multidimarr)
{
    global $db_name, $tables;
    foreach ($multidimarr as $key => $item) {
        if (isset($item['html'])) {
            $size = strlen($item['html']);
            dbquery("UPDATE `$db_name`.`$tables[map]` SET `code` = '$item[code]', `size` = $size WHERE `id` = $item[id];");
        } else {
            dbquery("UPDATE `$db_name`.`$tables[map]` SET `code` = '$item[code]' WHERE `id` = $item[id];");
        }
    }
}

function results_get_html(array $results, array $urls_to_parse)
{
    foreach ($results as $key => $res) {
        if ($res['state'] == 'fulfilled') {
            if ($res['value']->getStatusCode() == '200') {
                $code = $res['value']->getStatusCode();
                $urls_to_parse[$key]['code'] = $code;
                $html = (string)$res['value']->getBody();
                if ($html !== FALSE) {
                    $urls_to_parse[$key]['html'] = (string)$res['value']->getBody();
                }
            } else {
                $code = $res['value']->getStatusCode();
                $urls_to_parse[$key]['code'] = $code;
            }
        } else {
            //TMP разобраться с обработчиком ошибок
            $urls_to_parse[$key]['code'] = 500;
        }
    }
    return $urls_to_parse;
}

function prepare_async_promises($client, array $urls_to_get)
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

function db_get_urls_to_parse($count = 10)
{
    global $db_name, $tables;
    $query = "SELECT `id`,`href` FROM `$db_name`.`$tables[map]` WHERE (`code` = '' OR `size` = '') AND `href` LIKE ('%page%') LIMIT $count;";
    $query = "SELECT `id`,`href` FROM `$db_name`.`$tables[map]` WHERE (`code` = '' OR `size` = '') LIMIT $count;";
    $arr = dbquery($query);
    if (is_array($arr)) {
        foreach ($arr as $item) {
            $keys_as_id[$item['id']] = $item;
        }
    } else {
        echo2("No more urls to parse in DB!");
        return FALSE;
    }
    return $keys_as_id;
}

function db_import_hrefs(array $hrefs, $url)
{
    global $db_name, $tables;
    foreach ($hrefs as $item) {
        dbquery("INSERT INTO `$db_name`.`$tables[map]` (`url`, `href`) VALUES ('$url', '$item');", FALSE, FALSE, FALSE, TRUE);
    }
}

echo strlen($body);



