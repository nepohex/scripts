<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 21:59
 */
include "multiconf.php";
next_script (0,1);

//Больше вариантов генерации
#todo Дописать 3юю функцию генерации с подстановкой цифр и типа подборок, из тех же Bing тайтлов-вариантов
switch ($gen_addings) {
    case 2:
        $uniq_addings = $uniq_addings_nch;
        break;
    case 3:
        $uniq_addings = array_merge($uniq_addings,$uniq_addings_nch);
        break;
}

$srlz_post_titles = unserialize(file_get_contents($result_dir.$res2));

$i = 0 ;
$new_title_counter = 0 ; // Счетчик скольким строкам присводили новые тайтлы из KK
$gen_title_counter = 0 ; // Счетчик скольким строкам не нашлось хорошего тайтла из KK и поэтому добавили вначале слово из $uniq_addings для уникализации
$old_title_counter = 0 ; // Счетчик старых заголовков которые итак были уникальны в пределах файла CSV первоначального, лимит уникальности устанавливается переменной $limit_uniq из прошлого файла
$counter_srlz_items = count($srlz_post_titles);

foreach ($srlz_post_titles as $item) {
    if ($item['uniq'] < count($item['preg_kk'])) {
        shuffle($item['preg_kk']);
        $srlz_post_titles[$i]['new_title'] = $item['preg_kk'][0]['title'];
        $srlz_post_titles[$i]['new_adwords'] = $item['preg_kk'][0]['adwords'];
        $srlz_post_titles[$i]['new_concurent'] = $item['preg_kk'][0]['concurent'];
        if (preg_match($year_pattern, $srlz_post_titles[$i]['new_title'])) {
            $srlz_post_titles[$i]['new_title'] = preg_replace($year_pattern, $year_to_replace, $item['preg_kk'][0]['title']);
        }
        $new_title_counter++;
        $new_adwords_counter += $srlz_post_titles[$i]['new_adwords'];
        $old_adwords_counter += $srlz_post_titles[$i]['adwords'];
    } else if ((count($item['preg_kk']))!=0) {
        shuffle($uniq_addings);
        $srlz_post_titles[$i]['new_title'] = $uniq_addings[1] . $srlz_post_titles[$i]['title'];
        $srlz_post_titles[$i]['new_title'] = preg_replace($year_pattern, $year_to_replace, $srlz_post_titles[$i]['new_title']);
        $gen_title_counter++;
    } else {
        $srlz_post_titles[$i]['new_title'] =  $srlz_post_titles[$i]['title'];
        $old_title_counter++;
    }
    $srlz_post_titles[$i]['new_title'] = trim(ucwords($srlz_post_titles[$i]['new_title'])); // Преобразуем каждое слово заголовка с большой буквы
    $old_adwords_counter += $srlz_post_titles[$i]['adwords'];
    if ($clean_variants == true) {
        unset($srlz_post_titles[$i]['preg_kk']);
    }
    $i++;
    if (is_int($i/300)) {
        echo_time_wasted($i);
    }
}
echo2 ("New_title получают из KK с ненулевой частотой $new_title_counter, а для тех кому не нашлись годные - генерим по маске используя для подстановки массив из переменной 'uniq_addings' из ".count($uniq_addings) . " элементов в пропорциях от популярности запросов по Гуглу. Итого таких генереных получилось $gen_title_counter");
echo2 ("Нетронутых, уникальных в пределах сайта Title оказалось _ $old_title_counter _ , их не трогали!");
echo2 ("По старой частоте было _". chunk_split($old_adwords_counter,3," ")." _ , с новой частотой стало _".chunk_split($new_adwords_counter,3," ")." _");
echo2 ("Старая частота будет не верна - не сравнивали сколько были, новая - верна.");
$srlz_post_titles = serialize($srlz_post_titles);
file_put_contents($result_dir.$res3,$srlz_post_titles);
echo2 ("Результаты сохранили в папку со скриптом, ".$result_dir.$res3);
next_script ();