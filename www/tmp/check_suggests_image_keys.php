<?php
// Пизда. Из 562 фраз , 402 упало. И это только по ключу BOB + hairstyles и без хлама. Об этих фразах не знает Semrush...
// В Ahrefs в разы больше (в 3-4 раза). В идеале оттуда напарсить.
include('../new/includes/functions.php');
//Возвращает $link - соединение с DB.
$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'image_index';
$debug_mode = 1;
$content = file ('F:\tmp\bob_suggests.txt');
foreach ($content as $item) {
    $query = "INSERT INTO `semrush_keys` (`key_id`, `key`, `adwords`, `results`) VALUES ('', '" . str_replace(PHP_EOL,'',$item) . "', '3', '99999'); ";
    if ($z = dbquery($query, 0, 1) == 1) {
        $success++;
    }
}
echo2 ("Заполнили из ".count($content)." фраз $success");
