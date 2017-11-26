<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 22.11.2017
 * Time: 21:05
 * Немного костыльно.
 * 1. Находим первый блок с контентом по id/class
 * 2. Чистим в нем весь хлам. Есть основной массив для всех сайтов, и под каждый отдельный сайт надо свой составлять.
 * 3. Через регулярку удаляем комментарии кода, т.к. через DOM не получается никак.
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
use DiDom\Document;

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'dev_wp_parser';
$fp_log = 'log.txt';

if (!$mysqli) {
    $mysqli = new mysqli('localhost', $db_usr, $db_pwd, $db_name);
}

// domain / content_tag / bad_elems / tags_elems
$domain = 'http://golmozg.ru';
$urls = file('golmozg_list2.txt', FILE_IGNORE_NEW_LINES);
$url = 'http://golmozg.ru/stroenie/golovnoj-mozg-stroenie-i-funkcii-obshhee-opisanie.html';
// div[id*=venus] находит все элементы div содержащие в id слово venus
// :empty псевдокласс, находит все пустые элементы => работает некорректно, удалил.
$bad_elems = array('script', 'iframe', 'ins.adsbygoogle', 'div[id*=yandex]', 'div[id*=venus]', 'div[id*=SC_TBlock]', 'div[id*=rating]', '#law_changes');
//Будем использовать для поиска адреса или фрагмента URL категории которую присвоим.
$tags_elems = array('.breadcrumb', '.breadcrumbs', '.tag', '.tags', '.cat', '.cats', '.category', '.tags', '.tag', '#breadcrumb', '#breadcrumbs', '#tag', '#tags', '#cat', '#cats', '#category', '#tags', '#tag');
//домен который парсим, с протоколом и без слешей в конце.
$content_selector = '.entry';
$local_save_path = 'debug/' . substr($domain, strripos($domain, '/') + 1); //golmozg.ru -> будут сохраняться картинки и дебуг
prepare_dir($local_save_path);

$i = 0;
foreach ($urls as $url) {
    $i++;
//$content = file_get_contents("http://golmozg.ru/stroenie/golovnoj-mozg-stroenie-i-funkcii-obshhee-opisanie.html");
    $content = file_get_contents($url);
//file_put_contents("source.txt", $content);
//$content = file_get_contents("source.txt"); //debug content

    $document = new Document($content, false);
    $doc_clone2 = $document;
    $try_doc2 = $document->first($content_selector)->toDocument();

    foreach ($bad_elems as $selector) {
        foreach ($try_doc2->find($selector) as $del_elem) {
            $del_elem->remove();
        }
    }
    $try_elem2 = $try_doc2->toElement(); //суть в том что поиск и удаление элементов возможно только в документе, а замена элемента - только если элемент
    $doc_clone2->first($content_selector)->replace($try_elem2); //поэтому все операции проводим над документом контента, а потом в основном документе подменяем элемент контента

//Очистка через регулярки финальная
    $try_html = $try_elem2->toDocument()->format()->html();
    $try_html = preg_replace('/<!--(.+?)-->/', '', $try_html);
    $try_html = str_replace("\r\n", '', $try_html);
//Очистка через регулярки финальная

//debug
    file_put_contents($local_save_path . '/content_' . basename($url), $try_html); //финал элемент, код который пойдет в WP
    file_put_contents($local_save_path . '/html_' . basename($url), $doc_clone2->html()); //весь код html страницы которую парсим
//file_put_contents($local_save_path . '/' . "element2.html", $try_elem2->toDocument()->format()->html());

//get main data
    $img_urls = dom_get_img_urls($try_doc2, array('src', 'srcset'));
    if (is_array($img_urls)) {
        get_images($img_urls, $local_save_path, 1);
    }
    $article_data = dom_get_article_data($doc_clone2);
    $article_data['cat'] = try_get_tag($url, $doc_clone2, $tags_elems);
// prepare to db values
    $db_meta = serialize($article_data);
    $db_images = count($img_urls);
    $db_strlen = count_strlen_html($try_html);
    if ($i < 2) {
        if (!($stmt = $mysqli->prepare("INSERT INTO `content` (`id`,`post_content`,`post_title`, `url`, `meta` , `text_strlen`, `images`) VALUES ('',?,?,?,?,?,?);"))) {
            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
        }
    }
//http://www.php.su/mysqli_stmt_bind_param
    if (!$stmt->bind_param('ssssii', $try_html, $article_data['title'], $url, $db_meta, $db_strlen, $db_images)) {
        echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
    }
    /* execute prepared statement */
//    $stmt->execute();
    if ($i % 50 == 0) {
        echo_time_wasted($i);
    }
}
echo("fin");

function try_get_tag($url, $domDocument, array $tags_elems = null)
{
    $tmp = explode("/", $url);
    //Предпоследний элемент URL будем считать рубрикой
    if (isset($tmp[count($tmp) - 2])) {
        return $res = $tmp[count($tmp) - 2];
    }
//НЕ ДОПИСАЛ! осталась 2ая часть после получения всех ссылок из тегов получить названия рубрик из урлов
//    if ($tags_elems) {
//        foreach ($tags_elems as $elem) {
//            if ($elem = $domDocument->first($elem)) {
//                $hrefs = $elem->find('a');
//                if (is_array($hrefs)) {
//                    foreach ($hrefs as $aitem) {
//                        $tmp2[] = $aitem->getAttribute('href');
//                    }
//                }
//            }
//        }
//        //начиная отсюда отделить пути и обнаружить теги
//        if ($tmp2) {
//            foreach ($tmp2 as $item) {
//                explode('/', $item);
//            }
//        }
//    }
}

function recursive_dom($element)
{
    while ($element->hasChildren()) {
        recursive_dom($element);
    }
    return $element;
}

function dom_get_img_urls($domDocument, array $attributes)
{
    if ($domDocument->find('img')) {
        foreach ($domDocument->find('img') as $img) {
            $imgs[] = $img->attributes($attributes);
        }
        if (is_array($imgs)) {
            foreach ($imgs as $img_sizes) {
                foreach ($img_sizes as $img) {
                    if ($img !== '') {
                        $tmp = explode(' ', $img);
                        foreach ($tmp as $tmp2) {
                            if (filter_var($tmp2, FILTER_VALIDATE_URL)) {
                                $img_urls[] = $tmp2;
                            }
                        }
                    }
                }
            }
            return array_unique($img_urls);
        }
    }
    return FALSE;
}

/** Локальный путь без слеша на конце
 * @param array $image_urls
 * @param $path_local
 * @param $is_wp
 */
function get_images(array $image_urls, $path_local, $sleep = 1)
{
    foreach ($image_urls as $url) {
        $path = prepare_url($url);
        prepare_dir($path_local . dirname($path[1]));
        get_res($path_local . $path[1], $path[0], $sleep);
    }
}

/** Возвращает массив где 0 элемент - полный адрес к удаленной картинке, 1 - структура пути к картинке с ее именем.
 * @param $url
 * @return array
 */
function prepare_url($url)
{
    global $domain;
    if (!isset($domain)) {
        exit ("Не задан домен для парсинга, не можем содавать пути и качать картинки!");
    }
    $tmp1 = pathinfo($url);
    $tmp = parse_url($tmp1['dirname']);
    if (is_abs_url($url)) {
        $path[] = $url; //полный путь к картинке
        $path[] = $tmp['path'] . '/' . $tmp1['basename']; //структура пути к картинке от корневого домена
    } else {
        $path[] = $domain . $tmp['path'];
        $path[] = $tmp['path'] . '/' . $tmp1['basename'];
    }
    return $path;
}

function prepare_dir($path)
{
    is_dir($path) ? true : mkdir($path, 0777, TRUE);
}

function get_res($local_path, $remote_path, $sleep = 1)
{
    if (!is_file($local_path)) {
        file_put_contents($local_path, file_get_contents($remote_path));
        sleep($sleep);
    }
}

function is_WP($string)
{
    if (strstr($string, 'wp-content')) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function dom_get_article_data($document)
{
    $elements = array('title' => 'title', 'h1' => 'h1', 'description' => 'meta[name=description]', 'keywords' => 'meta[name=keywords]');
    $tmp = $elements;
    foreach ($elements as $key => $find) {
        if ($item = $document->first($find)) {
            if (in_array($key, array('description', 'keywords'))) {
                $result[$key] = $item->getAttribute('content');
            } else {
                $result[$key] = $item->text();
            }
        }
    }
    return $result;
}