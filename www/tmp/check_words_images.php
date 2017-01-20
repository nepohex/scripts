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

$bad_symb = array ('-','_','.jpg','.png','.jpeg');
mysqli_connect2();

//for ($z = 0 ; $z < 11; $z++) {
    for ($i = 0; $i < 10000; $i++) {
        $ids[] = rand(1, 875000);
    }
    $query = "SELECT `filename` from `images` WHERE `id` IN (" . implode(',', $ids) . ");";
    $result = dbquery($query,$i);
foreach ($result as $img_name) {
    $tmp = trim(str_replace($bad_symb,' ',$img_name[0]));
    $tmp = explode(' ',$tmp);
    foreach ($tmp as $word) {
        $words_used[strtolower($word)] += 1;
    }
}
arsort($words_used);
file_put_contents("image_name_words.txt",print_r($words_used,true));