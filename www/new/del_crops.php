<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.05.2018
 * Time: 23:22
 * Удаляет из папки файлы кропов, ищет фотографии и их в отделькую папку.
 * !ВНИМАНИЕ!
 * Скрипт надо каждый раздел отдельно проверять нужно ли прогонять или нет!
 */
include "includes/functions.php";
$double_log = 1;
$debug_mode = 1;
//BAD FILES
$dir = 'f:\Dumps\downloaded sites\timeless-miracle.com\wp-content\uploads'; //без слеша
//Bad CROPS sizes
$crops = array('570x320', '150x150');
//!! Внимание!!
$mandatory_words = array('coloring', 'print', 'colouring', 'color-page', 'clipart'); //Обязательные слова которые должны быть в названии файла!

$movedir = $dir . '/' . 'photos';
$movedir2 = $dir . '/' . 'no_mandatory';
prepare_dir($movedir);


if ($mandatory_words) {
    prepare_dir($movedir2);
//Удаляем фотки где нет указанных слов как хороших, то есть удаляются все файлы в названии которых нет указанных слов.
    $files = scandir($dir);
    $i = 0;
    foreach ($files as $item) {
        $i++;
        $fullpath = $dir . '/' . $item;
        $tmp = mb_strlen($item);
        if (mb_strlen(str_ireplace($mandatory_words, '', $item)) == $tmp) {
            @rename($fullpath, $movedir . '/' . $item);
//        unlink($fullpath);
//        echo2($item);
            @$bad_name++;
        } else {
            @$good_name++;
        }
    }
    echo2("Прошли файлы по маске плохих слов, удалили все файлы которые не содержат обязательных слов ($bad_name) / $i");
}

echo2("Начинаем переименовывать файлы удаляя лишние символы из названий и мусорные слова типа 5345k345hjg");
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fullpath = $dir . '/' . $item;
    $new_name = tmp_clean_fname($item);
    if (is_file($dir . '/' . $new_name)) {
        $new_name = '1-' . $new_name;
    }
    @rename($fullpath, $dir . '/' . $new_name);
    $i % 5000 == 0 ? echo_time_wasted($i) : '';
}
echo2("Переименовали ");

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
//    print_r($tmp2);
    if (@key_exists('Model', $tmp2)) {
        copy($fullpath, $movedir . '/' . $item);
        unlink($fullpath);
        @$m++;
    }
}
echo2("Проверили файлы, определили как фотографии $m / $i , перенесли в папку $movedir");

function tmp_clean_fname($name)
{
    $tmp2 = pathinfo($name);
    $tmp = $tmp2['filename'];
    $tmp = str_replace('_', ' ', $tmp);
    $tmp = preg_replace('/[^\w\d]/i', ' ', $tmp); //Замена всех не слов пробелами
    $tmp = preg_replace('/\b(?=(?:\w*\d){2,})(\w+)/i', '', $tmp); // Ищет все слова с 2мя цифрами и более, треш типа 04Df9319F700.
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $tmp = trim($tmp);
    $tmp = trim($tmp) . '.' . $tmp2['extension'];
    $tmp = str_replace(' ', '-', $tmp);
    return $tmp;
}