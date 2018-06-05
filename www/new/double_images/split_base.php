<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 17.05.2018
 * Time: 14:58
 * Разбивка базы на равные части для нескольких фрагментов сайтов
 * Адовый говнокод, но задачу делает.
 * Результат - файл с PARENT_ID для импорта
 */
//SELECT `parent_id`, `new_name`,COUNT(`id`) AS `COUNT` FROM `image_doubles` GROUP BY `parent_id` ORDER BY `count` DESC;
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;
$db_name = 'image_index2';
$t_name = 'image_doubles';

$theme = 3; // Указать тематику картинок условную, обязательно перед стартом скрипта!

$start_id = 1;
$fin_id = get_table_max_id($t_name); //Сюда можно вручную цифру вписать вместо этой функции
$sites = 4;

//SELECT `parent_id`,COUNT(`id`) AS `COUNT` FROM `image_doubles` WHERE `parent_id` != 0 AND `theme` = 2 GROUP BY `parent_id` ORDER BY `count` DESC;
$query = "SELECT `parent_id`,COUNT(`id`) AS `COUNT` FROM `$t_name` WHERE `parent_id` != 0 AND `theme` = $theme GROUP BY `parent_id` ORDER BY `count` DESC;";
$res = dbquery($query, 1);

$arr[1]['sum'] = 0;
$arr[2]['sum'] = 0;
$arr[3]['sum'] = 0;
$arr[4]['sum'] = 0;
$tmp = tmp_select_smaller_arr($arr);

foreach ($res as $item) {
    $tmp = tmp_select_smaller_arr($arr);
    $arr[$tmp]['ids'][] = $item;
    $arr[$tmp]['sum'] += $item[1];
}

echo2("Закончили с заливкой картинок с родительскими элементами, теперь получаем размер");

foreach ($arr as $key => $item) {
    echo2("Array $key => ITEMS " . count($item['ids']) . " KEYS => " . $item['sum']);
}

foreach ($arr as $key => $item) {
    foreach ($item['ids'] as $k2 => $i2) {
        $tmp = dbquery("SELECT `size` FROM `$t_name` WHERE `id` = $i2[0];");
        $arr[$key]['ids'][$k2]['size'] = $tmp;
        $arr[$key]['size'] += $tmp;
    }
}

echo2("Размеры картинок с родительскими элементами");
foreach ($arr as $key => $item) {
    echo2("Array $key => ITEMS " . count($item['ids']) . " KEYS => " . $item['sum'] . " SIZE => " . convert($item['size']));
}

#SELECT `id` FROM `image_doubles` WHERE `parent_id` = 0 AND `id` NOT IN (SELECT `parent_id` FROM `image_doubles` WHERE `parent_id` = 0 GROUP BY `parent_id`);
$double_imgs = dbquery("SELECT `parent_id` FROM `$t_name` WHERE `parent_id` != 0 AND `theme` = $theme GROUP BY `parent_id`;", 1);
$uniq_imgs = dbquery("SELECT `id`,`parent_id`,`size` FROM `$t_name` WHERE `parent_id` = 0 AND `theme` = $theme AND `id` NOT IN (" . implode(',', $double_imgs) . ");", 1);

foreach ($uniq_imgs as $item) {
    $tmp = tmp_select_smaller_arr($arr);
    $arr[$tmp]['ids'][] = $item;
    $arr[$tmp]['sum'] += 1;
    $arr[$tmp]['size'] += $item[2];
}

echo2("Размеры картинок с родительскими элементами и уникальными картинками");
foreach ($arr as $key => $item) {
    echo2("Array $key => ITEMS " . count($item['ids']) . " KEYS => " . $item['sum'] . " SIZE => " . convert($item['size']));
}

file_put_contents("./debug_data/split_DB_items_theme$theme.txt", serialize($arr));

//Дальше не дописана функция
exit ();

foreach ($arr as $key => $item) {
    foreach ($item['ids'] as $v) {
        $arr2[$key][] = dbquery("SELECT `id` FROM `$t_name` WHERE `id` = $v[0] OR `parent_id` = $v[0];", 1);
    }
}

foreach ($arr2 as $key => $item) {
    foreach ($item as $v) {
        $arr3[$key] = array_merge($arr3[$key], $v);
    }
}

function tmp_select_smaller_arr($arr)
{
    foreach ($arr as $key => $item) {
        $arr2[$key] = $item['sum'];
    }
    $tmp = array_keys($arr2, min($arr2));
    return $tmp[0];
}