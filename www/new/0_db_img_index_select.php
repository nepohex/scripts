<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.11.2016
 * Time: 18:08
 * Выбираем из базы данных по ключу все KeyCollector данные и все данные по картинкам, один из самых ресурсоемких. Занимает 15 минут на 87к строк картинок т.к. тут регулярки и т.п.
 */
include "multiconf.php";
$db_name = 'image_index';
include "mysqli_connect.php";

echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);
echo2("Будем выгружать из базы " . $db_name . " данные по KeyCollector и адреса на картинки");

$pattern = '/-.?[0-9]\w+/i';

// Блок для выгрузки из таблицы KK_KEYS, Если файла с выгрузкой по ключу с таким же количеством строк как в базе сейчас нету - выгружаем. Если есть - проходим мимо.
$query = "SELECT COUNT(*) FROM `kk_keys` WHERE `key` LIKE '%" . $keyword . "%';";
$sqlres = mysqli_query($link, $query);
$row = mysqli_fetch_row($sqlres);
$db_results = $row[0];
$res_kk = $keyword . "_kk.csv";
if (!(file_exists($res_kk))) {
    $fp = fopen($res_kk, 'a');
    echo2("Ключей в таблице KK_KEYS по фразе " . $keyword . " всего " . $row[0] . ". Файла с выгрузкой обнаружено не было, начинаем выгружать из базы одним запросом! Результат будет сохранен в файл " . $res_kk);

    $query = "SELECT `key`,`adwords` FROM `kk_keys` WHERE `key` LIKE '%" . $keyword . "%';";
    $sqlres = mysqli_query($link, $query);
    while ($row = mysqli_fetch_row($sqlres)) {
        fputcsv($fp, $row, ";");
    }
    fclose($fp);
}
echo2("Записали файл с выгрузкой из KK");
$res_kk2 = $keyword . "_images.csv";

if (!(file_exists($res_kk2))) {
// Создаем временную таблицу если результатов в таблице kk_keys относительно общего количества строк в базе мало (например, 20 тысяч из 330). Это должно ускорить процесс, временная таблица хранится в оперативной памяти
    if ($db_results < 100000) {
        $query = "CREATE TEMPORARY TABLE `" . $res_kk . "` AS (SELECT `key`,`adwords` FROM `kk_keys` WHERE `key` LIKE '%" . $keyword . "%');";
        $sqlres = mysqli_query($link, $query);
    }

    $query = "SELECT COUNT(*) FROM `images` WHERE `filename` LIKE '%" . $keyword . "%';";
    $sqlres = mysqli_query($link, $query);
    $row = mysqli_fetch_row($sqlres);
    $db_results = $row[0];

    $counter_start_limit = 0;

    echo2("Результатов по ключу " . $keyword . " всего " . $row[0] . ". Начинаем выгружать из базы " . $db_name . "! Результат будет сохранен в файл " . $res_kk2 . " пачками по " . $counter_limit_queries . " строк после обработки");
    if ($db_results < 5000) {
        $counter_limit_queries = 50;
    } else {
        $counter_limit_queries = 1000;
    }
    while ($counter_start_limit < ($db_results - $counter_limit_queries)) {
        $query = "Select * from `images` where `filename` LIKE '%" . $keyword . "%' LIMIT " . $counter_start_limit . ", $counter_limit_queries ;";
        $sqlres = mysqli_query($link, $query, MYSQLI_USE_RESULT);
        $i = 0;
        while ($row = mysqli_fetch_assoc($sqlres)) {
            $images[] = $row;
            $i++;
        }
        mysqli_free_result($sqlres);
        //echo2("Получен массив из таблицы images , строк _ " . count($images));

        $i = 0;
        foreach ($images as $image) {
            //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
            if (stripos($images[$i]['filename'], 'over')) {
                $z = explode("-", $image['filename']);
                $k = array_search(strtolower('over'), array_map('strtolower', $z));
                preg_match('/\d{2}/', $z[$k + 1], $matchez);
            }
            //
            $images[$i]['title'] = preg_replace($pattern, "", $image['filename']); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
//    $images[$i]['title'] = preg_replace($year_pattern,$year_to_replace,$images[$i]['title']);
            $images[$i]['title'] = trim(preg_replace('/\d/', "", $images[$i]['title'])); //добиваем все оставшиеся цифры
            $images[$i]['title'] = strtolower(trim(str_replace($replace_symbols, ' ', $images[$i]['title'])));
            //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
            if ($matchez) {
                $images[$i]['title'] .= ' '.$matchez[0];
                unset($matchez);
            }
            //
            $query = "SELECT `adwords` FROM `" . $res_kk . "` WHERE `key` = '" . $images[$i]['title'] . "' LIMIT 1;";
            $sqlres2 = mysqli_query($link, $query);
            if ($tmp = mysqli_fetch_row($sqlres2)) {
                $images[$i]['adwords'] = $tmp[0];
            } else {
                $images[$i]['adwords'] = 0;
            }
            $i++;
        }
        $fp = fopen($res_kk2, 'a');
        foreach ($images as $image) {
            fputcsv($fp, $image, ";");
        }
        fclose($fp);
        $counter_start_limit += $counter_limit_queries;
        unset($images, $image, $tmp);
        //echo_time_wasted($counter_start_limit);
    }
}
echo_time_wasted();
echo2("Закончили со скриптом " . $_SERVER['SCRIPT_FILENAME'] . " Переходим к NEXT");
next_script($_SERVER['SCRIPT_FILENAME']);
?>