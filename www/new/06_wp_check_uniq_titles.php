<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 18:02
 * Из загруженных в базу картинок с названиями получаем заголовки, проверяем на уникальность, отмечаем неуникальные,
 * закидываем в файл массив с результатами для уникализации следующим шагом.
 */
include "multiconf.php";
include("mysqli_connect.php");
echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);

$query = "SELECT `post_title` FROM `wp_posts` WHERE `post_type` = 'attachment'";
$sqlres = mysqli_query($link, $query);
if (mysqli_error($link)) {
    echo mysqli_error($link);
    flush();
}
$i = 0;
while ($row = mysqli_fetch_row($sqlres)) {
    $tmp = explode('.', $row[0]);
    $titles[$i]['title'] = trim(str_replace($image_words_separator, ' ', preg_replace('/[0-9]/', '', $tmp[0])));
    $i++;
}
$i = 0;
$counter = count($titles);
foreach ($titles as $title1) {
    foreach ($titles as $title2) {
        if ($title1 == $title2) {
            $titles[$i]['uniq'] += 1;
            if ($titles[$i]['uniq'] == $max_doubles) {
                $counter_too_much_doubles++;
                break;
            }
        }
    }
    if ($i % 500 == 0) {
        echo_time_wasted($i);
    }
    $i++;
}

foreach ($titles as $item) {
    if ($item['uniq'] == 1) {
        $z++;
    }
}

$tmp = serialize($titles);
file_put_contents($result_dir . $res, $tmp);

echo2("Fin! Всего итемов в массиве _" . $counter . " _ , из них уникальных _ " . $z . " _ . Тех у кого нашлось больше " . $max_doubles . " вариантов - " . $counter_too_much_doubles . "_ <br>");
echo2("Результат записали в файл " . $result_dir . $res);
echo2("Закончили со скриптом " . $_SERVER['SCRIPT_FILENAME'] . " Переходим к NEXT");
echo_time_wasted();
next_script($_SERVER['SCRIPT_FILENAME']);
?>