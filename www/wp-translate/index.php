<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.11.2017
 * Time: 1:49
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
use DiDom\Document;
use Beeyev\YaTranslate\Translate;

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'dev_gz';
$fp_log = 'log.txt';

//TRANSLATE PART
$api_keys = file('../yandex-translate/api_keys.txt', FILE_IGNORE_NEW_LINES);
$tr = new Translate(get_api_key($api_keys, TRUE));
//

$mysqli = new mysqli("localhost", $db_usr, $db_pwd, $db_name);

/* проверка подключения */
if (mysqli_connect_errno()) {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
}

//////////////////////////TEST MULTITHREADING////////////////////
$query = "SELECT `meta_id`,`meta_value` FROM `$db_name`.`wp_postmeta_bg` WHERE `meta_key` IN ('_wp_attachment_image_alt','_yoast_wpseo_title','_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw') and `meta_value` !='';";
if ($result = $mysqli->query($query)) {

    /* выборка данных и помещение их в массив */
    while ($row = $result->fetch_row()) {
        $tmp = translate($tr, 'bg', $row[1]); //translate title
        $escaped = mysqli_real_escape_string($mysqli, $tmp[0]);
        $query_insert = "INSERT INTO `$db_name`.`multithreading` (`id`,`text`) VALUES ('','$escaped');";
        dbquery($query_insert);
    }
}
//////////////////////////TEST MULTITHREADING////////////////////

///////////////////SWAP fin posts////////////////////////
//$query = "SELECT `ID`,`post_content`,`post_title` FROM `wp_posts2` WHERE `post_type` = 'post' AND `post_status` = 'publish';";
//if ($result = $mysqli->query($query)) {
//
//    /* выборка данных и помещение их в массив */
//    while ($row = $result->fetch_row()) {
//        /* подготавливаемый запрос, первая стадия: подготовка */
//        if (!($stmt = $mysqli->prepare("UPDATE `wp_posts3` SET `post_content` = ? , `post_title` = ? WHERE `ID` = ?;"))) {
//            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
//        }
////http://www.php.su/mysqli_stmt_bind_param
//        if (!$stmt->bind_param('ssi', $row[1], $row[2], $row[0])) {
//            echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
//        }
//        /* execute prepared statement */
//        $stmt->execute();
//    }
//}
///////////////////SWAP fin posts////////////////////////

//////////////TRANSLATE META ////////////////
$query = "SELECT `meta_id`,`meta_value` FROM `$db_name`.`wp_postmeta_bg` WHERE `meta_key` IN ('_wp_attachment_image_alt','_yoast_wpseo_title','_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw') and `meta_value` !='';";
//$query = "SELECT `meta_id`,`meta_value` FROM `$db_name`.`wp_postmeta_pt` WHERE `meta_key` IN ('_wp_attachment_image_alt','_yoast_wpseo_title','_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw') and `meta_value` !='' AND `meta_id` > 10899;";
if ($result = $mysqli->query($query)) {

    /* выборка данных и помещение их в массив */
    while ($row = $result->fetch_row()) {
        $tmp = translate($tr, 'pt', $row[1]); //translate title
//        sleep(1);
        /* подготавливаемый запрос, первая стадия: подготовка */
        if (!($stmt = $mysqli->prepare("UPDATE `$db_name`.`wp_postmeta_bg` SET `meta_value` = ? WHERE `meta_id` = ?;"))) {
            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
        }
//http://www.php.su/mysqli_stmt_bind_param
        if (!$stmt->bind_param('si', $tmp[0], $row[0])) {
            echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
        }
        /* execute prepared statement */
        $stmt->execute();
    }
}
////////////////TRANSLATE META ////////////////

///////////////////SWAP fin POSTMETA////////////////////////
//$query = "SELECT `meta_id`,`meta_value` FROM `wp_postmeta2` WHERE `meta_key` IN ('_wp_attachment_image_alt','_yoast_wpseo_title','_yoast_wpseo_metadesc') and `meta_value` !='';";
//if ($result = $mysqli->query($query)) {
//
//    /* выборка данных и помещение их в массив */
//    while ($row = $result->fetch_row()) {
//        /* подготавливаемый запрос, первая стадия: подготовка */
//        if (!($stmt = $mysqli->prepare("UPDATE `wp_postmeta3` SET `meta_value` = ? WHERE `meta_id` = ?;"))) {
//            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
//        }
////http://www.php.su/mysqli_stmt_bind_param
//        if (!$stmt->bind_param('si', $row[1], $row[0])) {
//            echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
//        }
//        /* execute prepared statement */
//        $stmt->execute();
//    }
//}
///////////////////SWAP fin POSTMETA////////////////////////

//////////////TRANSLATE WP POSTS////////////////
$query = "SELECT `ID`,`post_content`,`post_title` FROM `$db_name`.`wp_posts` WHERE `post_type` = 'post' AND `post_status` = 'publish';";
$query = "SELECT `ID`,`post_content`,`post_title` FROM `$db_name`.`wp_posts5` WHERE `post_type` = 'post' AND `post_status` IN ('publish','pending') AND `ID` < 3280;";
$counter_to_translate = dbquery("SELECT COUNT(*) FROM `$db_name`.`wp_posts5` WHERE `post_type` = 'post' AND `post_status` IN ('publish','pending') AND `ID` < 3280;");
if ($result = $mysqli->query($query)) {
    /* выборка данных и помещение их в массив */
    while ($row = $result->fetch_row()) {
        $i++;
        $content = str_replace("\t", '|||', $row[1]);
        $content = str_replace("\n", '__', $content);
        $cont_arr = explode('__', $content);
        foreach ($cont_arr as $content) {
            if (check_content($content)) {
                $tmp = translate($tr, 'pt', $content);
                $translated[] = $tmp[0];
            }
        }
        $content_fin = implode('__', $translated);
        $content_fin = str_replace('|||', "\t", $content_fin);
        $content_fin = str_replace('__', "\r\n", $content_fin);
        $content_fin = str_replace("\nthe ", "\n", $content_fin); //непонятный глюк добавляет иногда вначало строки 'the '
        $tmp = translate($tr, 'pt', $row[2]); //translate title
        /* подготавливаемый запрос, первая стадия: подготовка */
        if (!($stmt = $mysqli->prepare("UPDATE `wp_posts6` SET `post_content` = ? , `post_title` = ? WHERE `ID` = ?;"))) {
            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
        }
//http://www.php.su/mysqli_stmt_bind_param
        if (!$stmt->bind_param('ssi', $content_fin, $tmp[0], $row[0])) {
            echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
        }
        /* execute prepared statement */
        $stmt->execute();
        //debug
        //printf("%d Row inserted.\n", $stmt->affected_rows);
        echo "$i/$counter_to_translate переведено постов";
        unset($translated);
    }

    /* очищаем результирующий набор */
    $result->close();
}
//////////////TRANSLATE WP POSTS////////////////

$content = dbquery("SELECT `post_content` FROM `wp_posts` WHERE `ID` = '1608';");
$content = str_replace("\t", '|||', $content);
$content = str_replace("\r\n", '__', $content);
$cont_arr = explode('__', $content);
$content_fin = $content;
$content = str_replace('[', '<', $content);
$content = str_replace(']', '>', $content);
$content = str_replace("\t", '', $content);
//preg_match_all("/<[^>]+><[^>]+>(.*)<\\/[^>]+>/is", $content, $matches);
$document = new Document($content, false);
//$document = new Document('http://golovazdorova.ru', TRUE);
//$try = $document->find('#main');

//foreach ($try as $item) {
//    echo $item->text();
//    var_dump($item->children());
//}
//$all_elem = $try->children();
$all_elem = $document->text();
$tr_elems = $all_elem;
$array = preg_split('/$\R?^/m', $all_elem);

foreach ($array as $key => $item) {
    if (check_content($item)) {
        $result = translate($tr, 'en', $item);
        if ($result !== false) {
            echo $result . PHP_EOL;
            $tr_elems = str_replace($item, $result, $tr_elems);
            $tr_arr[$key][0] = $item;
            $tr_arr[$key][1] = $result;
            $content_fin = str_replace($item, $result, $content_fin);
        }
//        echo $item . PHP_EOL;
    }
}
echo_time_wasted();
foreach ($tr_arr as $item) {
    $content_fin = str_replace($item[0], $item[1], $content_fin);
}
echo $all_elem;

function check_content($content)
{
    if (strlen($content) < 1) {
        return FALSE;
    } else {
//        if (stristr($content, array('addthis', 'youtube'))) {
        if (preg_match('/addthis|youtube/', $content)) {
            return FALSE;
        }
    }
    return TRUE;
}

function get_api_key($keys_arr, $random = null)
{
    static $try = 0;
    // $maximum_try = 10;
    $maximum_try = count($keys_arr);
    if ($random) {
        if ($try < $maximum_try) {
            $try++;
            $tmp = $keys_arr[array_rand($keys_arr, 1)];
            echo2("Пробуем случайный ключ $tmp");
            return $tmp;
        } else {
            echo2("Больше $maximum_try получали API ключ!");
            exit();
            return false;
        }
    } else {
        if (isset($keys_arr[$try])) {
            echo2("Пробуем ключ $keys_arr[$try]");
            $try++;
            return $keys_arr[$try - 1];
        } else {
            echo2("Закончились API ключи в массиве!");
            $try++;
            exit();
            return false;
        }
    }
}

function translate(&$class, $lang_short_code, $string, $report_period = 100)
{
    global $api_keys;
    static $symbols_sent = 0, $i = 0;
    try {
        $result = $class->translate($string, $lang_short_code);
        $symbols_sent += strlen($string);
        $i++;
        if ($i % $report_period == 0) {
            echo_time_wasted($i, "Символов перевели $symbols_sent");
        }
        return $result->translation();
    } catch (\Beeyev\YaTranslate\TranslateException $e) {
//        echo2(print_r($e, true));
        $class->setApiKey(get_api_key($api_keys));
//        return false;
        //Фрагмент дописал чтобы не было пустых переводов на смене ключа
        $result = $class->translate($string, $lang_short_code);
        $symbols_sent += strlen($string);
        $i++;
        if ($i % $report_period == 0) {
            echo_time_wasted($i, "Символов перевели $symbols_sent");
        }
        return $result->translation();
    }
}