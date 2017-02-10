<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 20.01.2017
 * Time: 23:06
 * Полученный после SpinnerChief файл в формате print_r загружаем в базу.
 */
$start = microtime(true);
include('../new/includes/functions.php');
//Возвращает $link - соединение с DB.
$db_pwd = '';
$db_usr = 'root';
$db_name = 'hair_spin';
$debug_mode = 1;

mysqli_connect2();

$tmp = file_get_contents("result/spinned_texts_dirty5.txt");
$texts = printr_to_array($tmp);
unset ($tmp);
foreach ($texts as $key => $text) {
    $tmp = explode("%%%_ ",$text);
    $text = trim($tmp[1]);
    $text = str_replace('777','',$text);
    $text = addslashes($text);
    $query = "UPDATE `data` SET `text_template` = '$text' WHERE `id` = $key";
    dbquery($query);
}
echo2 ("Закончили!");