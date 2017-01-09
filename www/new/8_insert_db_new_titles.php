<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.11.2016
 * Time: 23:59
 * После того как определили неуникальные тайтлы, сопоставили их с KK, сгенерили новые, теперь заливаем их в базу.
 */
include "multiconf.php";
include( "mysqli_connect.php" );
echo2 ("Начинаем выполнять скрипт ".$_SERVER['SCRIPT_FILENAME']);
$srlz_post_titles = unserialize(file_get_contents($result_dir.$res3));

$queries_done = 0 ;

$i = 0;
foreach ($srlz_post_titles as $r) {
    if ($seasonal_add !== false && $i % $seasonal_titles == 0) {
        $z = (rand(0, 10000) < $year_end_percent*100)?1:2;
        switch ($z) {
            case 1:
                $r['new_title'] .= ' '.$year_to_replace;
                break;
            case 2:
                $r['new_title'] = $year_to_replace.' '.$r['new_title'];
                break;
        }
    }
    $query = "UPDATE `wp_posts` SET `post_title` = '".$r['new_title']."' WHERE `ID` = ".$wp_postmeta_start_pos.";";
    if ($sqlres = mysqli_query($link,$query)) {
        $queries_done++;
    };
    if ($mysql_error = mysqli_error($link)){
        mysqli_error($link);
        $mysql_error = false;
    }
    unset($sqlres);
    $wp_postmeta_start_pos++;
    $i++;
}

echo2 ("Обновляем для wp_posts post_title на новые после генерации прошлой части скрипта, итого сделали UPDATE в базу _ ".$queries_done);
echo2 ("Закончили со скриптом ".$_SERVER['SCRIPT_FILENAME']." Переходим к NEXT");
echo_time_wasted();
next_script ($_SERVER['SCRIPT_FILENAME']);
?>