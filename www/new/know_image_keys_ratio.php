<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 11.08.2017
 * Time: 23:13
 * Узнаем под сколько ключей у нас имеются подходящие картинки.
 * 873000 - картинок в базе на старте.
 * 935000 - ключей.
 */
error_reporting(E_ALL);
include('includes/functions.php');
$fp_log = "know_image_keys_ratio_log.txt";
$double_log = 1;
$db_usr = 'root';
$db_name = 'image_index';
mysqli_connect2($db_name);

$replace_symbols = array('.', '_', 'min', 'eleganthairstyles', 'hairstyleceleb', 'inethair', 'hairstylesmen', 'hairstylerunew', 'hairvintage', 'hairstyleswell', 'upload', 'hairstyleceleb', 'stylebistro', 'maomaotxt', 'aliexpress', 'dailymotion', 'maxresdefault', 'stayglam', 'shorthairstyleslong', 'thehairstyler', 'that39ll', 'consistentwith', 'harvardsol', 'amp', 'dfemale', 'herinterest', 'iataweb', 'men39s', 'tumblr', 'deva', 'thumbs', 'women39s', 'page', 'blog', 'ngerimbat', 'hair1', 'hairstylehub', 'hairjos', '+', 'jpg', 'jpeg', 'png', 'gif', 'bmp', '-', '!', '-min', '$', '%', '^', '&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия

$total_images = dbquery("SELECT COUNT(*) FROM `images`;");
$total_keys = dbquery("SELECT COUNT(*) FROM `semrush_keys`;");

$counter_start_limit = 0;
$counter_limit_queries = 1000;

//Сколько ключей соответствуют картинкам по прямому вхождению.
$counter_has_keys = 0;
$counter_no_keys = 0;

$global_arr = array();
while ($counter_start_limit < ($total_images - $counter_limit_queries)) {
    $query = "SELECT * from `images` LIMIT " . $counter_start_limit . ", $counter_limit_queries ;";
    $images = dbquery($query);
    $i = 0;
    foreach ($images as $image) {
        $img_clean[$i]['id'] = $images[$i]['id'];
        $img_clean[$i]['title'] = clean_files_name($image['filename']);
        $i++;
    }
    $counter_start_limit += $counter_limit_queries;
//    $tmp = unique_multidim_array($img_clean, 'title');
    $global_arr = index_doubles($img_clean, 'title', 'id', $global_arr);
//    array_to_csv("know_image_keys_ratio.csv", $img_clean);
    unset($images, $image, $tmp, $img_clean);
    if ($counter_start_limit % 100000 == 0) {
        echo_time_wasted("Проверили уже $counter_start_limit картинок");
    }
}

echo2("Картинок с уникальными тайтлами для которых есть/нет прямой ключ $counter_has_keys / $counter_no_keys ");

foreach ($global_arr as $key => $item) {
    $tmp = count($global_arr[$key]) - 1;
    $global_arr[$key]['checked'] > 0 ? $c_images_with_keys += $tmp : $c_images_no_keys += $tmp;
}
echo_time_wasted();

$keys_no_images = $total_images - $counter_has_keys;
echo2("Картинок всего, для которых есть/нет прямого ключа $c_images_with_keys / $c_images_no_keys");
echo2("Всего ключей для которых нет картинок $keys_no_images / $total_images  ( " . round($keys_no_images / $total_images * 100, 1) . "% )");

/** Функция ищет в многомерном массиве дубли по ключу $key_text , и записывает 2ую переменную (например ID) для всех повторяющихся $key_id
 * @param $array
 * @param $key_text
 * @param $key_id
 * @return array
 * @global_arr_return array
 */
function index_doubles($array, $key_text, $key_id, $global_arr_return = null)
{
    global $counter_has_keys, $counter_no_keys;
    $tmp_arr = array();
    if (is_array($global_arr_return)) {
        foreach ($array as $item) {
            $tmp_arr[$item[$key_text]][] = $item[$key_id];
        }
        $global_arr_return = array_merge_recursive($global_arr_return, $tmp_arr);
        foreach ($global_arr_return as $key => $item) {
            if (!isset($global_arr_return[$key]['checked'])) {
                $query = "SELECT COUNT(`key_id`) FROM `semrush_keys` WHERE `key` = '$key';";
                $global_arr_return[$key]['checked'] = dbquery($query);
                $global_arr_return[$key]['checked'] > 0 ? $counter_has_keys++ : $counter_no_keys++;
            }
        }
        return $global_arr_return;
    } else {
        foreach ($array as $item) {
            $tmp_arr[$item[$key_text]][] = $item[$key_id];
        }
        return $tmp_arr;
    }
}