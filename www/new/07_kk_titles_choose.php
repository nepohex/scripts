<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 19:25
 * На массив 5000 строк на 6000 строк выполняется 80 минут! Осторожно при запуске! Результат около 20 мб.
 */
include "multiconf.php";
echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);

echo2 ("Ресурсоемкий скрипт, будем подбирать по регулярке варианты для неуникальных тайтлов из базы KK $kk_fp");
$srlz_post_titles = unserialize(file_get_contents($result_dir.$res));
$count_srlz_items = count($srlz_post_titles); //Для вывода статуса работы
$csv = array_map('str_getcsv', file($kk_file));
$i=0;
foreach ($csv as $csvstring) {
    $r = explode(";", $csvstring[0]);
    foreach ($r as $rr) {
        if ($rr[1] == '') { $rr[1] = 0;}
        $csv_new[$i][] = $rr;
    }
    $i++;
}
unset($csv,$csvstring,$rr,$r,$i,$res);

// Удаление из KK массива всех ключей которые уже есть у нас в заголовках. Можно удалить все, можно только те которые в пределах сайта неуникальны.
if ($unset_kk_doubles == true) {
    $i = 0;
    $z = 0;
    foreach ($srlz_post_titles as $title) {
        foreach ($csv_new as $kk_item) {
            if ($title['title'] == $kk_item[0]) {
                $unset_arr1[] = $i;
                if ($title['uniq'] > $limit_uniq) { // В этот массив пропишем только те ID которые уже употреблялись больше раз чем допустимо по сайту
                    $unset_arr2[] = $i;
                }
            }
            $i++;
        }
        $i = 0;
        $z++;
        if ($z % 500 == 0) {
            echo_time_wasted($z);
        }
    }
    if ($unset_all_doubles == true) {
        $unset_arr1 = array_unique($unset_arr1);
        foreach ($unset_arr1 as $item) {
            unset ($csv_new[$item]);
        }
        sort($csv_new);
        echo2("Удалили из подстановки KK массива " . count($unset_arr1) . " элементов, удаляем все которые были среди Titles картинок. ");
    } else {
        $unset_arr2 = array_unique($unset_arr2);
        foreach ($unset_arr2 as $item) {
            unset ($csv_new[$item]);
        }
        sort($csv_new);
        echo2("Удалили из подстановки KK массива " . count($unset_arr2) . " элементов, удаляем только те которые были использованы в Titles картинок более $limit_uniq раз");
    }
}
$i = 0;
$z = 0;
foreach ($srlz_post_titles as $r) {
    if ($r['uniq'] > $limit_uniq) {
        $srlz_post_titles[$i]['wp_pattern'] = str_ireplace($filter_words, "(.)*", $r['title']);
        $pattern = "/(.)*" . $srlz_post_titles[$i]['wp_pattern'] . "(.)*/i";
        //Этот костыль с if нужен чтобы *MEN не превращались в WOMEN! Также важно следить за самой регуляркой (в ней сейчас эта звездочка есть)
        if (stripos($pattern,"*men")) {
            $pattern = str_ireplace("*men","* men",$pattern);
        }
        foreach ($csv_new as $item) {
            preg_match($pattern, $item['0'], $matches);
            if ($matches[0] != null && stripos($pattern,' men') && stripos($item[0],' men')) {
                $srlz_post_titles[$i]['preg_kk'][$z]['title'] = $matches[0];
                $srlz_post_titles[$i]['preg_kk'][$z]['adwords'] = $item[1];
                $z++;
                if ($z == 100) {
                    break; //Лимитируем чтобы было не более 100 вариантов тайтлов!
                }
            } else if ($matches[0] != null && stripos($pattern,' men') == false && stripos($item[0],' men') == false) {
                $srlz_post_titles[$i]['preg_kk'][$z]['title'] = $matches[0];
                $srlz_post_titles[$i]['preg_kk'][$z]['adwords'] = $item[1];
                $z++;
                if ($z == 100) {
                    break; //Лимитируем чтобы было не более 100 вариантов тайтлов!
                }
            }
        }
        $variants += $z;
        $z = 0;
        $i++;
    } else {
        $i++;
        if (is_int($i/300)) {
            echo2 ("Уже найдено _ ".$variants." _ вариантов, а строка только _ ".$i." _ из _ ".$count_srlz_items." _, ждем еще!");
            echo_time_wasted();
        }
    }
}

echo2 ("Пробежались по массиву с вариантами из KK в котором содержится _".count($csv_new)." _ строк, нашли для базы в которой содержится _ ".$count_srlz_items." _ строк, всего вариантов ".$variants." _");
$srlz_post_titles = serialize($srlz_post_titles);
file_put_contents($result_dir.$res2,$srlz_post_titles);
echo2 ("Результаты сохранили в папку со скриптом, ".$result_dir.$res2);
echo_time_wasted();
next_script ($_SERVER['SCRIPT_FILENAME']);
?>