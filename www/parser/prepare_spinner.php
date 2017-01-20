<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.01.2017
 * Time: 21:09
 * Из ДБ
 * Прогоняем тексты через список слов которые не нужно спинить, добавляем им символов, чтобы не спинились в программе.
 * 8500 текстов объемом в среднем 250 символов, 2800 слов которые не спинить. Прогонка 100 строк занимает 25 сек.
 * Итого на 8500 текстов уходит ~35 минут.
 */
$start = microtime(true);
include('../new/includes/functions.php');
//Возвращает $link - соединение с DB.
$db_pwd = '';
$db_usr = 'root';
$db_name = 'hair_spin';
$debug_mode = 1;

mysqli_connect2();
$words_not_spin = file("not_spin_words.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$query = "SELECT `id`,`text_start`,`h3`,`img_alt` FROM `data`";
$texts = dbquery ($query, $debug_mode);

$counter_post_titles = 0;
$z = 0 ;
//Попробуем воссоздать %post_title% , на 8500 текстов всего 2 нашлось.
//foreach ($texts as $text) {
//    if (strstr(strtolower($text[1]),strtolower($text[2])) | strstr(strtolower($text[1]),strtolower($text[3]))) {
//        $counter_post_titles++ ;
//    }
//    $z++;
//    if ($z % 500 == 0) {
//        echo2 ("Нашли Post_TITLE $counter_post_titles");
//        echo_time_wasted($z);
//    }
//}

//Слова которые не спиним дописываем чтобы не мешались потом в спине.
$counter_words_changed = 0 ; //Сюда запишем сколько слов заменили.
$counter_words_total = 0; //Сюда сколько было всего.
$t = 0 ; //Счетчик строк
foreach ($texts as $text) {
    $tmp = explode(" ",$text[1]);
    array_unshift($tmp,$text[0]."_%%%_");
    $counter_words_total += count($tmp);
    $i = 0;
    foreach ($tmp as $item) {
        $z = 0;
        foreach ($words_not_spin as $bad_word) {
            if (strtolower($item) == strtolower($bad_word)) {
                $tmp[$i] = $item."777";
                $counter_words_changed++;
                $i++;
                break;
            }
            $z++;
            if ($z == count($words_not_spin)) {
                $i++;
                break;
            }
        }
    }
    $t++;
    if ($t % 100 == 0) {
        echo_time_wasted(count($tested_texts));
    }
    $tested_texts[$text[0]] = implode(' ',$tmp);
}
echo2 ("Всего пробежались по всем текстам, в них было $counter_words_total слов, заменили $counter_words_changed и не будем уникализировать.");
file_put_contents("prepared_texts.txt",serialize($tested_texts));