<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.12.2016
 * Time: 19:03
 */
$start = microtime(true);
$db_usr = 'root';
$db_host = 'localhost';
$db_pwd = '';
$db_name = 'image_index';
include "includes/functions.php";

$year_to_replace = 2017; // Год на который меняем
$autocat_exclude_words = array($year_to_replace, 'length', 'choose', 'when', 'youtube', 'amp', 'inspir', 'gallery', 'view', 'pic', 'about', 'your', 'idea', 'design', 'hair', 'style', 'women', 'very', 'with', 'picture', 'image', 'pinterest', 'woman', 'tumblr', 'from', 'side', 'pictures', 'ideas', 'style', 'photos'); // Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$replace_symbols = array('.', '_', 'min', 'eleganthairstyles', 'hairstyleceleb', 'inethair', 'hairstylesmen', 'hairstylerunew', 'hairvintage', 'hairstyleswell', 'upload', 'hairstyleceleb', 'stylebistro', 'maomaotxt', 'aliexpress', 'dailymotion', 'maxresdefault', 'stayglam', 'shorthairstyleslong', 'thehairstyler', 'that39ll', 'consistentwith', 'harvardsol', 'amp', 'dfemale', 'herinterest', 'iataweb', 'men39s', 'tumblr', 'deva', 'thumbs', 'women39s', 'page', 'blog', 'ngerimbat', 'hair1', 'hairstylehub', 'hairjos', '+', 'jpg', 'jpeg', 'png', 'gif', 'bmp', '-', '!', '-min', '$', '%', '^', '&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия
$image_title_max_strlen = 75;
$image_title_min_strlen = 15;

$fp_log = 'know_images_niche_log.txt';
$debug_mode = 1;
$double_log = 1;

//echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);
echo2("Будем выгружать из базы " . $db_name . " данные по нишам картинок которые имеем");
$pattern = '/-.?[0-9]\w+/i';
$image_index_niches = 'image_index_niches_deb.txt';
$image_index_niches2 = 'image_index_niches_count_valid_deb.txt';

$query = "SELECT count(*) FROM `images`";
$db_results = dbquery($query);

$counter_start_limit = 0;
$counter_limit_queries = 10000;
echo2("Получили количество из " . $db_name . " всего " . $db_results . " начинаем выгружать пачками по " . $counter_limit_queries);

$query = "SELECT `id` FROM `image_size`;";
$used_images = dbquery($query, 1);
asort($used_images);
echo2("Выгрузили ID всех использованных картинок в количестве " . count($used_images) . " , будем их исключать из прогонов.");

while ($counter_start_limit < ($db_results - $counter_limit_queries)) {
    $query = "SELECT `id`,`filename` FROM `images` LIMIT " . $counter_start_limit . ", $counter_limit_queries ;";
    $images = dbquery($query);
    echo2("Получен массив из таблицы images , строк _ " . count($images));

    $i = 0;

//Исключаем все использованные картинки из перебора , можно закомментировать если не нужно
    foreach ($images as $image) {
        if (in_array($image['id'], $used_images)) {
            unset($images[$i]);
        }
        $i++;
    }
//
    sort($images);
    $i = 0;
    foreach ($images as $image) {
        $images[$i]['filename'] = preg_replace($pattern, "", $image['filename']); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
        $images[$i]['filename'] = trim(preg_replace('/\d/', "", $images[$i]['filename'])); //добиваем все оставшиеся цифры
        $images[$i]['filename'] = strtolower(trim(str_replace($replace_symbols, ' ', $images[$i]['filename'])));
        $tmp = explode(" ", $images[$i]['filename']);
        foreach ($tmp as $item) {
            if (strpos(str_ireplace($autocat_exclude_words, "|", $item), "|") !== false) {
            } else {
                $words_used[$item] += 1;
            }
        }
        $i++;
    }
    $counter_start_limit += $counter_limit_queries;
    unset($images, $image, $tmp);
    echo_time_wasted($counter_start_limit);
}
arsort($words_used);
reset($words_used);
echo2("Посчитали слова, записали результат в файл " . $image_index_niches);
file_put_contents($image_index_niches, print_r($words_used, true));

//1 вариант результата:
// С words_used 2ым элементом массива
//for ($i = 0; $i < 10; $i++) {
//    $z = key($words_used);
//    $query = "SELECT count(*) as `COUNT` FROM `images` WHERE `filename` LIKE '%" . $z . "%' and CHAR_LENGTH (`filename`) < $image_title_max_strlen and CHAR_LENGTH (`filename`) > $image_title_min_strlen;";
//    $valid_images[$z]['valid_length'] = dbquery($query);
//    $valid_images[$z]['words_used'] = current($words_used);
//    next($words_used);
//}
//$valid_images = array_msort($valid_images, array('valid_length' => 'SORT_DESC'));
//Запись в TXT формат
//file_put_contents($image_index_niches2, print_r($valid_images, true));
// конец 1 варианта

//2 вариант:
// Просто ключ и значение валидных картинок.
for ($i = 0; $i < 400; $i++) {
    $z = key($words_used);
    $query = "SELECT count(*) as `COUNT` FROM `images` WHERE `filename` LIKE '%" . $z . "%' and CHAR_LENGTH (`filename`) < $image_title_max_strlen and CHAR_LENGTH (`filename`) > $image_title_min_strlen;";
    $valid_images[$z] = dbquery($query);
    next($words_used);
}
arsort($valid_images);
echo2("Посчитали файлы в базе доступной длины межу $image_title_min_strlen и $image_title_max_strlen под каждый ключ. Записали результат в файл в формате CSV " . $image_index_niches2);
$fp = fopen($image_index_niches2, "w");
foreach ($valid_images as $key => $value) {
    fwrite($fp, $key . ';' . $value . PHP_EOL);
}
//Конец 2 варианта

echo_time_wasted();