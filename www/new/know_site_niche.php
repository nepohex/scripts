<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 03.01.2017
 * Time: 0:02
 * Запускаем с ключем чтобы посмотреть какие слова употребляются чаще всего с ним, для генерации Description.
 */
header('Content-Type: text/html; charset=utf-8');
$start = microtime(true);
$db_usr = 'root';
$db_name = 'image_index';
$db_pwd = '';
$link = mysqli_init();

$pattern = '/-.?[0-9]\w+/i';
$replace_symbols = array('.','_','+','jpg','jpeg','png','gif','-','!','-min','$', '%', '^','&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия

if (!mysqli_real_connect($link, 'localhost', $db_usr, $db_pwd, $db_name)) {
    die('Ошибка подключения (' . mysqli_connect_errno() . ') '
        . mysqli_connect_error());
}

$key = $_GET['key'];
$query = "Select `filename` from `images` where `filename` like '%".$key."%';";
$sqlres = mysqli_query($link, $query);
$i = 0;
while ($row = mysqli_fetch_assoc($sqlres)) {
    $images[] = $row;
    $i++;
}
mysqli_free_result($sqlres);
echo("Получен массив из таблицы images , строк _ " . count($images)); flush();

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
    if ($i < 100) {
        echo $image['filename']."<br/>";
    }
}
arsort($words_used);
echo '<pre>',print_r($words_used,1),'</pre>';