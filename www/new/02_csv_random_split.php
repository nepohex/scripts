<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.11.2016
 * Time: 19:17
 * Будем делить файл с картинками на количество картинок необходимых для 1 сайта.
 */
include "multiconf.php";
echo2 ("Начинаем выполнять скрипт ".$_SERVER['SCRIPT_FILENAME']);

$tmpres = str_replace(".csv","",$big_res_to_split)."_".$images_per_site."_rand_lines.csv";

$fp = fopen($big_res_to_split,"r");
while ($line = fgets($fp, 6096)) {
$tmp = explode(";",$line);
    $ftell[] = ftell($fp);
}
shuffle($ftell);
echo2 ("Загрузили в массив данные о концах строк файла ".$big_res_to_split." в котором ".count($ftell)." строк с данными о картинках, будем выбирать из него случайные ".$images_per_site);
echo_time_wasted();
// ДУмал сделать для освобождения оперативки, но работает очень долго
//$counter_csv_lines = count ($ftell);
//
//while (count($ftell) > $images_per_site) {
//    $i = rand(1,$counter_csv_lines);
//    unset ($ftell[$i]);
//}
$fp2 = fopen($import_dir.$tmpres,"w");
$i = 0 ;
$z = 0;
foreach ($ftell as $id) {
    fseek($fp,$id);
    $rand_string = fgets($fp,6096);
    $tmp = explode(";",$rand_string);
    $strlen = strlen($tmp[count($tmp)-2]); // $tmp[count($tmp)-2] - здесь уже без JPG и хлама, название картинки, например "short hairstyles for round faces and thin fine hair"
    if ($strlen < $image_title_max_strlen && $strlen > $image_title_min_strlen) {
        if ($only_uniq_img == true) {
            if (!(in_array($tmp[4],$tmp_uniq_arr))) {
                $tmp_uniq_arr[] = $tmp[count($tmp)-2];
                fputs($fp2, $rand_string);
                $i++;
            } else {
                $not_uniq_arr[] = $tmp[4];
            }
        } else {
            fputs($fp2, $rand_string);
            $i++;
        }
    }
    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
    if ($i == $images_per_site) {
        break;
    }
    $z++;
}
fclose($fp);
fclose($fp2);

echo2 ("Создали новый рабочий файл с картинками под названием ".$import_dir.$tmpres);
echo2 ("Максимальная длина названия картинки установлена в  ".$image_title_max_strlen);
echo2 ("Картинок в переборе было ".$z." чтобы набрать  ".$images_per_site." Набрали всего $i картинок.");
echo2 ("Закончили со скриптом ".$_SERVER['SCRIPT_FILENAME']." Переходим к NEXT");
echo_time_wasted();
next_script ($_SERVER['SCRIPT_FILENAME']);
?>