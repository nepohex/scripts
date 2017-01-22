<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.01.2017
 * Time: 19:59
 * Закидываем обработанные данные парсинга сайта trh.com в базу
 * http://hairstylezz.com/
 * http://tophairstyles.net/
 * http://machohairstyles.com/
 * http://therighthairstyles.com/
 */
$start = microtime(true);
include("nokogiri.php");
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');
$db_name = 'hair_spin';
$db_pwd = '';
$db_usr = 'root';

$domain_name = 'machohairstyles.com';
$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";
$image_niches = $result_dir . '/image_' . $domain_name . '_niches.txt';
$image_niches_full_text = $result_dir . '/image_' . $domain_name . '_niches_full_text.txt';
// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('$', '%', '^', '&', '(', ')', '=', '+', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '.', '"', '  '); //Заменим эти символы в имени файла на пробелы

if (is_file($result_fname) == false) {
    $fp = fopen($result_fname, 'r');
    while ($tmp = fgetcsv($fp, '', ';')) {
        $csv[] = $tmp;
        $tmp2 = explode(" ", $tmp[2]);
        foreach ($tmp2 as $word) {
            $word = str_ireplace($bad_symbols, '', $word);
            $words_used[trim(strtolower($word))] += 1;
        }
    }
    arsort($words_used);
    reset($words_used);
    echo2("Посчитали слова, записали результат в файл " . $image_niches_full_text);
    file_put_contents($image_niches_full_text, print_r($words_used, true));

    echo_time_wasted();
} else {
    $fp = fopen($result_fname, 'r');
    while ($tmp = fgetcsv($fp, '', ';')) {
        $csv[] = $tmp;
    }
    echo2("Использованные слова для домена $domain_name уже посчитали, записали в файл $image_niches_full_text");
}

foreach ($csv as $row) {
    $tmp[] = $row[0];
}

$tmp = array_unique($tmp);
foreach ($tmp as $item) {
    $article_urls[] = $item;
}


// Получаем последний ID из таблиц для URL.
$query = "SELECT `id` FROM `data` ORDER BY `id` DESC LIMIT 1";
$i = dbquery($query, 1); // ID для картинок.
if ($i == false) {
    $i = 0; // ID для картинок , если таблица пустая еще.
}
$i++;

// Получаем последний ID из таблиц для картинок
$query = "SELECT `id` FROM `urls` ORDER BY `id` DESC LIMIT 1";
$z = dbquery($query); // ID для картинок.
if ($z == false) {
    $z = 0; // ID для URL , если таблица пустая еще.
}
$z++;
$constant_start_url_index = $z; //Нужно для цикла каждый раз перезадавать

$queries1 = array();
foreach ($csv as $key => $row) {
    $z = $constant_start_url_index; // ID для URL в таблице
    foreach ($article_urls as $article_url) {
        if ($row[0] == $article_url) {
            $csv[$key][0] = $z;
            $query1 = "INSERT INTO `urls` (`id`,`url`) VALUES ('" . $z . "','" . $article_url . "');";
            // INSERT INTO `hair_spin`.`data` (`id`, `url_id`, `h3`, `text_start`, `img_url`, `img_alt`, `img_source`, `text_template`, `place`, `variants`, `comment`, `avg_len`, `used`) VALUES ('0', '0', 'African American Queen', 'Short hairstyles for black women are killing the hair game lately - this look is leading the pack. The side part of this gorgeous bob sits lower than your average side part for a statement sweep that speaks chic and perfection.', 'http://i1.wp.com/therighthairstyles.com/wp-content/uploads/2013/11/2-black-sideparted-bob.jpg?w=500', 'Black Side-Parted Bob', 'https://www.instagram.com/p/BAQD8fiBjv6/', '', '', '100', '', '300', '0');
            $queries2[] = "INSERT INTO `hair_spin`.`data` (`id`, `url_id`, `h3`, `text_start`, `img_url`, `img_alt`, `img_source`, `text_template`, `place`, `variants`, `comment`, `avg_len`, `used`) VALUES ('$i', '$z', '" . addslashes(trim($row[1])) . "', '" . addslashes(trim($row[2])) . "', '$row[3]', '" . addslashes(trim($row[4])) . "', '$row[5]', '', '', '0', '', '" . strlen($row[2]) . "', '0');";
            if ($query1 !== end($queries1)) {
                $queries1[] = $query1;
            }
            break;
        }
        $z++;
    }
    $tmp = explode(" ", $row[4]);
    foreach ($tmp as $word) {
        $words_used[strtolower($word)] += 1;
    }
    $i++;
}
arsort($words_used);
reset($words_used);
echo2("Посчитали слова, записали результат в файл " . $image_niches);
file_put_contents($image_niches, print_r($words_used, true));
dbquery($queries1);
dbquery($queries2);
echo_time_wasted();


