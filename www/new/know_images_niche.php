<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.12.2016
 * Time: 19:03
 */
$start = microtime(true);
include "config.php";
$db_name = 'image_index';
include "mysqli_connect.php";

//echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);
echo2("Будем выгружать из базы " . $db_name . " данные по нишам картинок которые имеем");
$pattern = '/-.?[0-9]\w+/i';
$image_index_niches = 'image_index_niches.txt';
$image_index_niches2 = 'image_index_niches_count_valid.txt';

$query = "Select count(*) from `images`";
$sqlres = mysqli_query($link, $query);
$row = mysqli_fetch_row($sqlres);
$db_results = $row[0];

$counter_start_limit = 0;
$counter_limit_queries = 10000;
echo2("Получили количество из " . $db_name . " всего ".$db_results." выгрузим пачками по ".$counter_limit_queries);

//while ($counter_start_limit < ($db_results - $counter_limit_queries)) {
        $query = "Select `filename` from `images` LIMIT " . $counter_start_limit . ", $counter_limit_queries ;";
        $sqlres = mysqli_query($link, $query, MYSQLI_USE_RESULT);
        $i = 0;
        while ($row = mysqli_fetch_assoc($sqlres)) {
            $images[] = $row;
            $i++;
        }
        mysqli_free_result($sqlres);
        echo2("Получен массив из таблицы images , строк _ " . count($images));

        $i = 0;
        foreach ($images as $image) {
            $images[$i]['filename'] = preg_replace($pattern, "", $image['filename']); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
            $images[$i]['filename'] = trim(preg_replace('/\d/', "", $images[$i]['filename'])); //добиваем все оставшиеся цифры
            $images[$i]['filename'] = strtolower(trim(str_replace($replace_symbols, ' ', $images[$i]['filename'])));
            $tmp = explode(" ",$images[$i]['filename']);
            foreach ($tmp as $item) {
                if (strpos(str_ireplace($autocat_exclude_words,"|",$item),"|") !== false) {
                } else {
                    $words_used[$item] += 1;
                }
            }
            $i++;
        }
        $counter_start_limit += $counter_limit_queries;
        unset($images, $image, $tmp);
        echo_time_wasted($counter_start_limit);
//    }
arsort($words_used);
reset($words_used);
echo2 ("Посчитали слова, записали результат в файл ".$image_index_niches);
file_put_contents($image_index_niches,print_r($words_used,true));
for ($i = 0 ; $i < 400 ; $i++) {
    $z = key($words_used);
    $query = "Select count(*) as `COUNT` from `images` WHERE `filename` like '%".$z."%' and CHAR_LENGTH (`filename`) < $image_title_max_strlen and CHAR_LENGTH (`filename`) > $image_title_min_strlen;";
    $sqlres = mysqli_query($link, $query);
    $row = mysqli_fetch_row($sqlres);
    $valid_images[$z]['words_used'] = current($words_used);
    $valid_images[$z]['valid_length'] = $row[0];
    next($words_used);
}
foreach ($valid_images as $valid_image) {
    array_multisort($valid_images['valid_length'],SORT_DESC);
}
echo2 ("Посчитали слова, записали результат в файл ".$image_index_niches2);
file_put_contents($image_index_niches2,print_r($valid_images,true));

echo_time_wasted();
?>