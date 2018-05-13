<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.05.2018
 * Time: 23:22
 * Удаляет из папки файлы кропов, ищет фотографии и их в отделькую папку.
 */
include "includes/functions.php";
$double_log = 1;
$debug_mode = 1;
//BAD FILES
$dir = 'f:\Dumps\downloaded sites\https@humananatomychart.us\wp-content\uploads\2018';
//Bad CROPS sizes
$crops = array('570x320', '150x150');

$movedir = $dir . '/' . 'photos';
prepare_dir($movedir);

$files = scandir($dir);
echo2("Подали на проверку файлов в папке " . count($files));
$i = 0;
//Удаляем где в названии есть кропы 150x150 , 200x200 и т.п.
foreach ($files as $item) {
    $i++;
    if (preg_match('/[0-9]+x[0-9]+/i', $item)) {
//        rename($dir . '/' . $item, $dir . '/crop/' . $item);
        unlink($dir . '/' . $item);
        @$f++;
    }
}

echo2("Прошли регуляркой, удалили $f файлов. Начинаем проверять сами картинки по размеру...");
//Удаляем тупо маленькие картинки 150х150 размером и детектим кропы
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fullpath = $dir . '/' . $item;
    if (($tmp = is_image($fullpath)) !== FALSE) {
        $width = $tmp[0];
        $height = $tmp[1];
        @$sizes[$tmp[0] . 'x' . $tmp[1]] += 1;
        if ($tmp['0'] < 210 || $tmp['1'] < 210) {
            unlink($fullpath);
            @$z++;
        } else {
//            in_array($width . 'x' . $height, $crops) ? unlink($dir . '/' . $item) : '';
        }
    }
    if ($i % 1000 == 0) {
        echo2("$i / $z Идем по строке, удалили размером меньше указанного.");
    }
}
arsort($sizes);
echo2("Выводим топ 10 размеров кропов");
print_r(array_slice($sizes, 0, 10));
echo2("Удалено файлов всего $f/$i + $z");

//Определение фотографий где в Exif содержится тег Model
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fullpath = $dir . '/' . $item;
    $tmp2 = @exif_read_data($fullpath);
    print_r($tmp2);
    if (@key_exists('Model', $tmp2)) {
        copy($fullpath, $movedir . '/' . $item);
        unlink($fullpath);
        @$m++;
    }
}
echo2("Проверили файлы, определили как фотографии $m / $i , перенесли в папку $movedir");