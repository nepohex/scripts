<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.09.2017
 * Time: 2:16
 */
include "multiconf.php";
mysqli_connect2($db_name_img);

next_script(0, 1);
echo2("Будем выгружать из базы " . $db_name . " данные по KeyCollector и адреса на картинки");

$pattern = '/-.?[0-9]\w+/i';

// Блок для выгрузки из таблицы KK_KEYS, Если файла с выгрузкой по ключу с таким же количеством строк как в базе сейчас нету - выгружаем. Если есть - проходим мимо.
$query = "SELECT COUNT(*) FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
$db_results = dbquery($query);
//$res_kk = $selects_dir . '/' . $keyword . "_google_keys.csv";
//$res_kk_rand_keys = $selects_dir . '/' . $keyword . "_" . $images_per_site . "_rand_keys.csv";

$res_kk = 'includes/' . $keyword . "_google_keys.csv";
$res_kk_rand_keys = 'includes/' . $keyword . "_" . $images_per_site . "_rand_keys.csv";
if (!(file_exists($res_kk))) {
    echo2("Ключей в таблице semrush_keys по фразе " . $keyword . " всего " . $db_results . ". Файла с выгрузкой обнаружено не было, начинаем выгружать из базы одним запросом! Результат будет сохранен в файл " . $res_kk);
    $query = "SELECT `key_id`,`key`,`adwords` FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
    $tmp = dbquery($query);
    $tmp2 = array_rand($tmp, $images_per_site);
    foreach ($tmp2 as $item) {
        $random_split[] = $tmp[$item];
        shuffle($random_split);
    }
    array_to_csv($res_kk, $tmp, false, "Успешно Записали файл с выгрузкой С ключевыми словами");
    array_to_csv($res_kk_rand_keys, $random_split, false, "Записали рандомные $images_per_site ключи для нового сайта");
} else {
    echo2("Файл с ключевыми словами под ключ $keyword ранее был создан, используем его.");
    $tmp = csv_to_array2($res_kk);
    $tmp2 = array_rand($tmp, $images_per_site);
    foreach ($tmp2 as $item) {
        $random_split[] = $tmp[$item];
        shuffle($random_split);
    }
    array_to_csv($res_kk_rand_keys, $random_split, false, "Записали рандомные ключи для нового сайта");
}

unset($tmp);
echo_time_wasted();

isset($position_limit) ? $position_limit : $position_limit = 10;

$i = 0;
foreach ($random_split as $item) {
    $i++;
    $query = "SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations` AS `t1`
LEFT JOIN `semrush_keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
LEFT JOIN `google_images` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
WHERE `t2`.`key_id` = $item[0] AND `t1`.`position` <= $position_limit LIMIT 100;";
    $tmp = dbquery($query);
    if ($tmp > 0) {
        $image_url[] = dbquery($query);
    }
    if ($i % 500 == 0) {
        echo_time_wasted($i);
        file_put_contents($import_dir . "_dirty_" . $import_file, serialize($image_url));
    }
}
file_put_contents($import_dir . "_dirty_" . $import_file, serialize($image_url));
echo_time_wasted(null, "Записали файл импорта по адресу $import_dir _dirty_$import_file с данными по картинкам. Нашлось " . count($import_file) . " картинок");

//echo2("Активирован новый модуль Google Images Mode -> Выборка будет идти от ключей к картинкам, а не наоборот.");
//
//
//$res_kk2 = $selects_dir . '/' . $keyword . "_google_images.csv";
//
//if (!(file_exists($res_kk2))) {
//    echo2("Файла для импорта картинок под ключ $keyword еще не создано, идем создавать.");
//    // Создаем временную таблицу если результатов в таблице kk_keys относительно общего количества строк в базе мало (например, 20 тысяч из 330). Это должно ускорить процесс, временная таблица хранится в оперативной памяти
//    if ($db_results < 100000) {
//        $query = "CREATE TEMPORARY TABLE `" . $res_kk . "` AS (SELECT `key`,`adwords` FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%');";
//        $sqlres = mysqli_query($link, $query);
//        $table_to_select = $res_kk;
//    } else {
//        $table_to_select = 'semrush_keys';
//    }
//
//} else {
//    echo2("Файл импорта для Google Images Mode с адресами картинок из таблицы semrush_keys для ключа $keyword уже создан, используем его!");
//}