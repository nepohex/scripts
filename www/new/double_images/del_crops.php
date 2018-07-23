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
include "../includes/functions.php";
$fp_log = __DIR__ . "/debug_data/log.txt";
$double_log = 1;
$debug_mode = 1;

//Директории-источники картинок, без слеша
$source_dirs = array('f:\Dumps\downloaded sites\24.media.tumblr.com', 'f:\Dumps\downloaded sites\40.media.tumblr.com');

//Файл куда будут заливаться ключи из всех папок
$keys_csv = 'F:\tmp\_tmp.csv';
$keys_csv_fp = fopen($keys_csv, 'a');
$keys_csv_separator = '$$$$$$$$$$$$$$$'; //сепаратор между папками

//Директория куда будут сливаться все.
$dir = 'F:\tmp\_tmp'; //без слеша
prepare_dir($dir);

//Минимальные размеры для картинок, если любой из параметров меньше - картинка удаляется.
$min_width = 250;
$min_height = 250;
//Bad CROPS sizes
$crops = array('570x320', '150x150', '76x58', '100x100');
//!! Внимание!!
$mandatory_words = array('baby', 'shower', 'party'); // Любое. Обязательные слова которые должны быть в названии файла!

define("CHECK_PHOTOS", FALSE); //Проверять фотография или рисунок (по наличию в Exif Model). Перемещать в папку $movedir если фото.
define("DEL_SIMILAR_NAMES", TRUE); // Удалять картинки в названиях которых отличается только размер кропа. Осторожно с функцией!
define("CLEAN_TRASH", TRUE); // Удалять из названия картинки треш типа 04Df9319F700 (2мя цифрами и более в слове = удалять).


if (CHECK_PHOTOS) {
    $movedir = $dir . '/' . 'photos';
}
$movedir2 = $dir . '/' . 'no_mandatory';
prepare_dir($movedir);

$i = 0;
echo2("Начинаем обход поданных папок source_dirs в количестве " . count($source_dirs) . " и перенос файлов в единую папку $dir");
DEL_SIMILAR_NAMES ? echo2("Активирована функция чистки кропов! Попытаемся Не копировать файлы которые генерят темы, вида filename.jpg (ОК) / filename-900x900.jpg (НЕ ОК)") : '';
foreach ($source_dirs as $source_dir) {
    if (is_dir($source_dir)) {
        echo2("Nachinaem Obhod papki $source_dir");
        ###Запись папок из родительской папки, там обычно ключи находятся
        $keys_in_dir = scandir($source_dir);
        fputs($keys_csv_fp, $source_dir . $keys_csv_separator . PHP_EOL);
        foreach ($keys_in_dir as $row) {
            if (strpos($row, '.') == FALSE) { //Пишем только без точки, чтобы файлы не попадались
                fputs($keys_csv_fp, $row . PHP_EOL);
            }
        }
        ####
        $fnames = getDirContents($source_dir);
        echo2("Founded " . count($fnames) . " files");
        foreach ($fnames as $fname) {
            $i++;
            $tmp = basename($fname);
            if (DEL_SIMILAR_NAMES) {
                //Ищем регуляркой кропы
                preg_match('/.[0-9]{2,4}x[0-9]{2,4}\./i', $fname, $matches);
                //Если кропы найдены - пропускаем копирование файлика
                if (count($matches) > 0) {
                    $no_crop_name = str_replace(last($matches), '.', $fname);
                    if (in_array($no_crop_name, $fnames)) {
                        $report[$source_dir]['crops'] += 1;
                        continue;
                    }
                }
            }
            if (is_file($dir . '/' . $tmp)) {
                $report[$source_dir]['renamed'] += 1;
                $new_name = rand(100, 999) . '_' . $tmp;
            } else {
                $new_name = $tmp;
            }
            rename($fname, $dir . '/' . $new_name);
            $report[$source_dir]['copied'] += 1;
        }
        echo_time_wasted("Oboshli papky $source_dir . $i files");
    } else {
        echo2("Папки $source_dir не существует, пропускаем");
    }
    $i = 0;
}
echo2("Отчет " . print_r($report, TRUE));
$report2 = array_column($report, 'copied');
echo2("Скопировано всего итемов " . array_sum($report2));

if ($mandatory_words) {
    prepare_dir($movedir2);
//Удаляем фотки где нет указанных слов как хороших, то есть удаляются все файлы в названии которых нет указанных слов.
    $files = scandir($dir);
    echo2("Проверка на обязательные слова. Подали на проверку файлов в папке " . count($files));
    $i = 0;
    foreach ($files as $item) {
        $i++;
        $fullpath = $dir . '/' . $item;
        $tmp = mb_strlen($item);
        if (mb_strlen(str_ireplace($mandatory_words, '', $item)) == $tmp) {
            @rename($fullpath, $movedir2 . '/' . rand(100, 999) . "_" . $item);
//        unlink($fullpath);
//        echo2($item);
            @$bad_name++;
        } else {
            @$good_name++;
        }
    }
    echo2("Прошли файлы по маске плохих слов, переместили/удалили в папку $movedir2 которые не содержат обязательных слов ($bad_name) / $i");
}

//Удаляем тупо маленькие картинки 150х150 размером и детектим кропы
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fullpath = $dir . '/' . $item;
    if (($tmp = is_image($fullpath)) !== FALSE) {
        $width = $tmp[0];
        $height = $tmp[1];
        $mime = get_mime_extension($tmp);
        @$sizes[$tmp[0] . 'x' . $tmp[1]] += 1;
        if ($width <= $min_width || $height <= $min_height) {
            unlink($fullpath);
            @$z++;
        } else {
            in_array($width . 'x' . $height, $crops) ? unlink($dir . '/' . $item) : '';
        }
        //Fix_fname
        if (!strpos($item, '.')) {
            $new_name = $item . '.' . $mime;
            @rename($fullpath, $dir . '/' . $new_name);
            @$r++;
        }
        //
    }
    if ($i % 5000 == 0) {
        echo2("$i / $z Идем по строке, удалили размером меньше указанного, а также размером указанных кропов");
    }
}
arsort($sizes);
echo2("Выводим топ 10 размеров кропов");
print_r(array_slice($sizes, 0, 10));
echo2("Удалено файлов маленьких и указанных плохих размеров $z/$i . Также переименовали файлов у которых не было расширения $r");


echo2("Начинаем переименовывать файлы удаляя лишние символы из названий и мусорные слова типа 5345k345hjg");
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fullpath = $dir . '/' . $item;
    CLEAN_TRASH ? $new_name = tmp_clean_fname($item) : $new_name = tmp_clean_fname($item, FALSE);
    if ($new_name !== $item) {
        if (is_file($dir . '/' . $new_name)) {
            $new_name = rand(100, 999) . '_' . $new_name;
            @rename($fullpath, $dir . '/' . $new_name);
            @$n++;
        }
    }
    $i % 5000 == 0 ? echo_time_wasted($i) : '';
}
echo2("Переименовали $n files ");

if (CHECK_PHOTOS) {
//Определение фотографий где в Exif содержится тег Model
    $files = scandir($dir);
    $i = 0;
    foreach ($files as $item) {
        $i++;
        $fullpath = $dir . '/' . $item;
        $tmp2 = @exif_read_data($fullpath);
//    print_r($tmp2);
        if (@key_exists('Model', $tmp2)) {
            //PERENOS
            @rename($fullpath, $movedir . '/' . $item);
            //COPY + DEL
//        copy($fullpath, $movedir . '/' . $item);
//        unlink($fullpath);
            @$m++;
        }
    }
    echo2("Проверили файлы, определили как фотографии $m / $i , перенесли в папку $movedir");
}

function tmp_clean_fname($name, $clean_trash = TRUE)
{
    $tmp2 = pathinfo($name);
    $tmp = $tmp2['filename'];
    $tmp = str_replace('_', ' ', $tmp);
    $tmp = preg_replace('/[^\w\d]/i', ' ', $tmp); //Замена всех не слов пробелами
    if ($clean_trash) {
        $tmp = preg_replace('/\b(?=(?:\w*\d){2,})(\w+)/i', '', $tmp); // Ищет все слова с 2мя цифрами и более, треш типа 04Df9319F700.
    }
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $tmp = trim($tmp);
    $tmp = trim($tmp) . '.' . $tmp2['extension'];
    $tmp = str_replace(' ', '-', $tmp);
    return $tmp;
}