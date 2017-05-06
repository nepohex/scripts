<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 19:25
 * На массив 5000 строк на 6000 строк выполняется 80 минут! Осторожно при запуске! Результат около 20 мб.
 */
include "multiconf.php";
next_script (0,1);

echo2 ("Ресурсоемкий скрипт, будем подбирать по регулярке варианты для неуникальных тайтлов из базы KK $kk_file");
$srlz_post_titles = unserialize(file_get_contents($result_dir.$res));
$count_srlz_items = count($srlz_post_titles); //Для вывода статуса работы

$csv_new = csv_to_array ($kk_file);
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
//        if ($z % 500 == 0) {
//            echo_time_wasted($z);
//        }
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
$counter_absolute_unique = 0; // Абсолютно уникальных в пределах сайта
$counter_unique_titles = 0 ; // Уникальных тайтлов на сайте для которых не ищем новые.
$counter_non_unique_titles = 0; // Неуникальных, для которых нашли варианты.
$counter_looked_for_titles = 0; // Для них искали варианты и не нашли.
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
            $s = 1;
            if ($matches[0] != null && stripos($pattern,' men') && stripos($item[0],' men')) {
                $srlz_post_titles[$i]['preg_kk'][$z]['title'] = $matches[0];
                $srlz_post_titles[$i]['preg_kk'][$z]['adwords'] = $item[1];
                $z++;
                $t = 1;
                if ($z == 100) {
                    break; //Лимитируем чтобы было не более 100 вариантов тайтлов!
                }
            } else if ($matches[0] != null && stripos($pattern,' men') == false && stripos($item[0],' men') == false) {
                $srlz_post_titles[$i]['preg_kk'][$z]['title'] = $matches[0];
                $srlz_post_titles[$i]['preg_kk'][$z]['adwords'] = $item[1];
                $z++;
                $t = 1;
                if ($z == 100) {
                    break; //Лимитируем чтобы было не более 100 вариантов тайтлов!
                }
            }
        }
        if ($t == 1) {
            $counter_non_unique_titles++;
        }
        if ($s == 1) {
            $counter_looked_for_titles++;
        }
        $variants += $z;
        $z = 0;
        $i++;
        unset($t,$s);
    } else {
        if ($r['uniq'] == 1) {
            $counter_absolute_unique++;
        }
        $counter_unique_titles++;
        $i++;
        if ($i % 500 == 0) {
            echo2 ("Уже найдено _ ".$variants." _ вариантов, а строка только _ ".$i." _ из _ ".$count_srlz_items." _, ждем еще!");
            echo_time_wasted();
        }
    }
}

echo2 ("Абсолютно уникальных / Условно Уник (повторов меньше чем в $ limit_unique($limit_uniq) / Не уник тайлов было / Получилось сделать из них уник / Сколько вариантов на них пришлось \n $counter_absolute_unique / $counter_unique_titles / $counter_looked_for_titles / $counter_non_unique_titles / $variants");
$srlz_post_titles = serialize($srlz_post_titles);
file_put_contents($result_dir.$res2,$srlz_post_titles);
echo2 ("Результаты сохранили в папку со скриптом, ".$result_dir.$res2);
next_script ();