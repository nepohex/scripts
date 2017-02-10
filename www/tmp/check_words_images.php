<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 18.01.2017
 * Time: 0:52
 * Временный модуль получть слова котоыре использованы в названиях картинок или их описаниях
 * чтобы найти те которые не надо спинить, не потерять смысла.
 */
include('../new/includes/functions.php');
//Возвращает $link - соединение с DB.
$db_pwd = '';
$db_usr = 'root';
$db_name = 'image_index';
$debug_mode = 1;

$pattern = '/-.?[0-9]\w+/i';
$bad_symbols = array('39s', '$', '%', '^', '&', '(', ')', '=', '+', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '.', '"', '  '); //Заменим эти символы в имени файла на пробелы
$replace_symbols = array('.', '_', 'shorthairstyleslong', 'thehairstyler', 'that39ll', 'consistentwith', 'harvardsol', 'amp', 'dfemale', 'herinterest', 'iataweb', 'men39s', 'tumblr', 'deva', 'thumbs', 'women39s', 'page', 'blog', 'ngerimbat', 'hair1', 'hairstylehub', 'hairjos', '+', 'jpg', 'jpeg', 'png', 'gif', 'bmp', '-', '!', '-min', '$', '%', '^', '&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия
$fname = "image_name_words_" . pwdgen(3) . "_.txt";
mysqli_connect2();

//for ($z = 0 ; $z < 11; $z++) {
for ($i = 0; $i < 50000; $i++) {
    $ids[] = rand(1, 875000);
}
$query = "SELECT `filename` FROM `images` WHERE `id` IN (" . implode(',', $ids) . ");";
$result = dbquery($query, $i);
foreach ($result as $img_name) {
    $test_f = clean_files_name ($img_name);
    $tmp = preg_replace($pattern, "", $img_name); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
//    $images[$i]['title'] = preg_replace($year_pattern,$year_to_replace,$images[$i]['title']);
    $tmp = trim(preg_replace('/\d/', "", $tmp)); //добиваем все оставшиеся цифры
    $tmp = strtolower(trim(str_replace($replace_symbols, ' ', $tmp)));
    $tmp = explode(' ', $tmp);
    foreach ($tmp as $word) {
        if (strlen($word) > 13) {
            continue;
        } else {
            $words_used[strtolower($word)] += 1;
        }
    }
}
arsort($words_used);
file_put_contents($fname, print_r($words_used, true));
echo2("Записали в $fname");