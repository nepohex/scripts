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
mysqli_connect2();
next_script (0,1);

$query = "SELECT `post_title` FROM `wp_posts` WHERE `post_type` = 'attachment'";
$tmp = dbquery($query,1);
foreach ($tmp as $item) {
    $tmp2 = explode('.', $item);
    $titles[]['title'] = trim(str_replace($image_words_separator, ' ', preg_replace('/[0-9]/', '', $tmp2[0])));
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
    $i++;
    if ($i % 500 == 0) {
        echo_time_wasted($i);
    }
}

foreach ($titles as $item) {
    if ($item['uniq'] == 1) {
        $z++;
    }
}

$tmp = serialize($titles);
file_put_contents($result_dir . $res, $tmp);

echo2("Fin! Всего итемов в массиве _" . $counter . " _ , из них уникальных в пределах сайта (повторов = 0) _ " . $z . " _ . Тех у кого нашлось больше " . $max_doubles . " вариантов - " . $counter_too_much_doubles . "_");
echo2("Результат записали в файл " . $result_dir . $res);
next_script ();