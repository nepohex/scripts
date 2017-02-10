<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 23:59
 * После того как определили неуникальные тайтлы, сопоставили их с KK, сгенерили новые, теперь заливаем их в базу.
 * На этом этапе добавляем "год" -сезонность в начало или конец запроса, в соответствии с настройками.
 */
#todo объеденить с предыдущим файлом одной функцией
include "multiconf.php";
mysqli_connect2();
next_script(0, 1);
$srlz_post_titles = unserialize(file_get_contents($result_dir . $res3));

$queries_done = 0;

$i = 0;
$counter_year_to_end = 0; //Скольким тайтлам добавили год в конец
$counter_year_to_start = 0; //В начало
foreach ($srlz_post_titles as $r) {
    if ($seasonal_add !== false && $i % $seasonal_titles == 0) {
        $z = (rand(0, 10000) < $year_end_percent * 100) ? 1 : 2;
        switch ($z) {
            case 1:
                $r['new_title'] .= ' ' . $year_to_replace;
                $counter_year_to_end++;
                break;
            case 2:
                $r['new_title'] = $year_to_replace . ' ' . $r['new_title'];
                $counter_year_to_start++;
                break;
        }
    }
    $query = "UPDATE `wp_posts` SET `post_title` = '" . $r['new_title'] . "' WHERE `ID` = " . $wp_postmeta_start_pos . ";";
    $queries_done += dbquery($query, 0, 1);
    $wp_postmeta_start_pos++;
    $i++;
}

echo2("Добавили ~сезонности~ запросам, в начало/конец $counter_year_to_end / $counter_year_to_start дописали год $year_to_replace , запросов успешно прошло " . $queries_done);
next_script();