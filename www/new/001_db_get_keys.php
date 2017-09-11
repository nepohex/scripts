<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.09.2017
 * Time: 2:16
 * SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t4`.`domain`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
 * FROM `google_images_relations` AS `t1`
 * LEFT JOIN `semrush_keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
 * LEFT JOIN `google_images2` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
 * LEFT JOIN `google_images_domains2` AS `t4` ON `t3`.`domain_id` = `t4`.`domain_id`
 * WHERE `t2`.`key_id` = 128734 AND `t1`.`position` <= 10 LIMIT 100
 */
include "multiconf.php";
mysqli_connect2($db_name_img);

next_script(0, 1);
echo2("Будем выгружать из базы " . $db_name . " данные по KeyCollector и адреса на картинки");

$pattern = '/-.?[0-9]\w+/i';

// Блок для выгрузки из таблицы KK_KEYS, Если файла с выгрузкой по ключу с таким же количеством строк как в базе сейчас нету - выгружаем. Если есть - проходим мимо.
$query = "SELECT COUNT(*) FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
$db_results = dbquery($query);
$res_kk = $selects_dir . '/' . $keyword . "_google_keys.csv";
$res_kk_rand_keys = $selects_dir . '/' . $keyword . "_" . $images_per_site . "_rand_keys.csv";

if (!(file_exists($res_kk))) {
    echo2("Ключей в таблице semrush_keys по фразе " . $keyword . " всего " . $db_results . ". Файла с выгрузкой обнаружено не было, начинаем выгружать из базы одним запросом! Результат будет сохранен в файл " . $res_kk);
    $query = "SELECT `key_id`,`key`,`adwords` FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
    $tmp = dbquery($query, true);
    $tmp2 = get_rand_imgs($tmp, $images_per_site);
    foreach ($tmp2 as $item) {
        $random_split[] = $tmp[$item];
        shuffle($random_split);
    }
    array_to_csv($res_kk, $tmp, false, "Успешно Записали файл с выгрузкой С ключевыми словами");
    array_to_csv($res_kk_rand_keys, $random_split, false, "Записали рандомные $images_per_site ключи для нового сайта");
} else {
    echo2("Файл с ключевыми словами под ключ $keyword ранее был создан, используем его.");
    $tmp = csv_to_array2($res_kk);
    $tmp2 = get_rand_imgs($tmp, $images_per_site);
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
    $query = "SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t4`.`domain`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations2` AS `t1`
LEFT JOIN `semrush_keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
LEFT JOIN `google_images2` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
LEFT JOIN `google_images_domains2` AS `t4` ON `t3`.`domain_id` = `t4`.`domain_id`
WHERE `t2`.`key_id` = $item[0] AND `t1`.`position` <= $position_limit LIMIT 100;";
    $tmp = dbquery($query);
    if ($tmp > 0 && $tmp <= $limit_imgs_per_key) {
        $image_url[] = $tmp;
    } else if ($tmp > 0 && $tmp >= $limit_imgs_per_key) {
        shuffle($tmp);
        $image_url[] = array_slice($tmp, 0, $limit_imgs_per_key);
    }
}

function get_rand_imgs($array, $images_per_site)
{
    if ($images_per_site > count($array)) {
        $images_per_site = count($array);
        echo2 ("Ключей на сайт получается больше чем есть в базе, берем ВСЕ ключи!");
    }
    return array_rand($array, $images_per_site);
}

file_put_contents($import_dir . "_dirty_" . $import_file, serialize($image_url));
echo_time_wasted(null, "Записали файл импорта по адресу $import_dir _dirty_$import_file с данными по картинкам.");
next_script();