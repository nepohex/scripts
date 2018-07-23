<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.06.2018
 * Time: 18:01
 * Разбиваем папку с картинками на части - разбивка по количеству будующих постов.
 * Также проверка если где по WH ratio картинки разные посчитались одинаковыми (как было с coloring)
 * На вход нужны предварительные шаги:
 * - Del_crops.php (Скачанные папки слить в одну, удалить и почистить левые файлы).
 * - Visual Dup полученную в шаге 1 папку.
 */
include "../includes/functions.php";
$fp_log = __DIR__ . "/debug_data/log.txt";
$double_log = 1;
$debug_mode = 1;
prepare_dir(dirname($fp_log));

//Dir to split
$dir = 'D:\maps'; //без слеша
$parts = 2;
$csv_dups = "D:/maps.csv"; //Visual Similar CSV . Prepared BEFORE WH check ratio!
$csv_done = "D:/maps_clean.csv"; //Visual Similar CSV . Prepared AFTER WH check ratio!


//region Удаление дублей после DUPImage по W/h ratio
$arr = csv_to_array2($csv_dups, ',', null, true);
$i = 0;
$group_trigger = 1; //Номер группы, устанавливаю 1 для удобства 1го обхода
$g = 0; //Итератор группы, номер файла

foreach ($arr as $item) {
    $i++;
    list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
//    $new_name = basename($full_path);
    if ($group_trigger == $group && @$g == 0) {
        $group_ratio = round($width / $height, 1);
        $g = 1;
    } else if ($group_trigger == $group && @$g > 0) {
        $whratio = round($width / $height, 1);
        if ($whratio !== $group_ratio) {
            $bad_group[] = $group;
            $bad_group = array_unique($bad_group);
        }
        $g++;
    } else if ($group_trigger < $group) {
        $group_trigger = $group;
        $group_ratio = round($width / $height, 1);
        $g = 1;
    }
    $i % 5000 == 0 ? echo_time_wasted($i, " / " . count($bad_group)) : '';
}

foreach ($arr as $key => &$item) {
    list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
    if (in_array($group, $bad_group)) {
        unset($arr[$key]);
        $b++;
    }
}
array_to_csv($csv_done, $arr, false, 0, 'w', ',');
unset($arr);
//endregion

for ($i = 1; $i <= $parts; $i++) {
    prepare_dir($dir . '/pt' . $i);
    $pt[$i] = 0;
}

$csv = csv_to_array2($csv_done, ",", null, true);
$files = scandir($dir);
$tmp = count_dup_values_multidim_arr($csv, '0');
echo2("Файлов в папке " . count($files) . " . Дублей В CSV " . count($csv) . " . Групп " . count($tmp));

for ($i = 0; $i < 100; $i++) {
    shuffle($files);
}

$i = 0;
foreach ($files as $item) {
    $arr_num = tmp_get_smaller_arr($pt);
    $full_path = $dir . '\\' . $item;
    $i++;
    if (is_file($full_path)) {
        //Находим ID массива с дублем в CSV
        $tmp2 = multidim_arr_search_value($csv, $full_path, 1); // 1 = номер колонки, где содержится FileName (полный путь)
        //Если есть дубли в CSV
        if ($tmp2 !== FALSE) {
            $trigger = 1; //для подсчета веса
            //Получили ID группы
            $group_id = $csv[$tmp2]['0'];
            //Получить ID массивов данной группы
            $tmp3 = array_column($csv, 0); //0 - номер колонки = группа
            $tmp3 = array_keys($tmp3, $group_id); //ID массивов CSV Файлов с дублями
            //Переместить все файлы группы в указанную папку
            foreach ($tmp3 as $tmp2) {
                $fname = basename($csv[$tmp2][1]);
                $new_path = $dir . '/pt' . $arr_num . '/' . $fname;
                rename($csv[$tmp2][1], $new_path);
                $pt[$arr_num] += 1; //Увеличить счетчик папки на количество перемещенных файлов
                $trigger == 1 ? $pt_size[$arr_num] += $csv[$tmp2][2] : '';
                $trigger += 1;
                $pt_size2[$arr_num] += $csv[$tmp2][2];
            }
            unset($trigger, $csv[$tmp2]);
        } else {
            $new_path = $dir . '/pt' . $arr_num . '/' . $item;
            rename($full_path, $new_path);
            $pt[$arr_num] += 1; //Увеличить счетчик папки на количество перемещенных файлов
            $pt_size[$arr_num] += filesize($new_path);
        }
    }
    $i % 10000 == 0 ? echo_time_wasted($i) : '';
}

echo2("Файлов раскидали по папкам " . print_r($pt, TRUE));
$pt_size = array_map("convert", $pt_size);
$pt_size2 = array_map("convert", $pt_size2);
echo2("Вес файлов по папкам (с учетом будущего удаления дублей) " . print_r($pt_size, TRUE));
echo2("Вес файлов по папкам (текущий) " . print_r($pt_size2, TRUE));

function tmp_get_smaller_arr($arr)
{
    foreach ($arr as $key => $item) {
        $arr2[$key] = $item;
    }
    $tmp = array_keys($arr2, min($arr2));
    return $tmp[0];
}