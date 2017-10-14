<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.09.2017
 * Time: 2:16
 * SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t4`.`domain`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
 * FROM `google_images_relations` AS `t1`
 * LEFT JOIN `keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
 * LEFT JOIN `google_images2` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
 * LEFT JOIN `google_images_domains2` AS `t4` ON `t3`.`domain_id` = `t4`.`domain_id`
 * WHERE `t2`.`key_id` = 128734 AND `t1`.`position` <= 10 LIMIT 100
 */
include "multiconf.php";
mysqli_connect2($db_name_img);

next_script(0, 1);
echo2("Будем выгружать из базы " . $db_name . " данные по KeyCollector и адреса на картинки");

$pattern = '/-.?[0-9]\w+/i';
isset($position_limit) ? $position_limit : $position_limit = 10;

// Блок для выгрузки из таблицы KK_KEYS, Если файла с выгрузкой по ключу с таким же количеством строк как в базе сейчас нету - выгружаем. Если есть - проходим мимо.
$query = "SELECT COUNT(*) FROM `$tname[keys]` WHERE `key` LIKE '%" . $keyword . "%';";
$db_results = dbquery($query);
$res_kk = $selects_dir . '/' . $keyword . "_google_keys.csv";
$res_kk_rand_keys = $selects_dir . '/' . $keyword . "_" . $images_per_site . "_rand_keys.csv";

if (!(file_exists($res_kk))) {
    echo2("Ключей в таблице $tname[keys] по фразе " . $keyword . " всего " . $db_results . ". Файла с выгрузкой обнаружено не было, начинаем выгружать из базы одним запросом! Результат будет сохранен в файл " . $res_kk);
    $query = "SELECT `key_id`,`key`,`adwords` FROM `$tname[keys]` WHERE `key` LIKE '%" . $keyword . "%';";
    $tmp = dbquery($query, true);
    $tmp2 = get_rand_imgs($tmp, $images_per_site);
    foreach ($tmp2 as $item) {
        $random_split[] = $tmp[$item];
        shuffle($random_split);
    }
    array_to_csv($res_kk, $tmp, false, "Успешно Записали файл с выгрузкой С ключевыми словами");
    array_to_csv($res_kk_rand_keys, $random_split, false, "Записали рандомные $images_per_site ключи для нового сайта", "w+");
} else if (!file_exists($res_kk_rand_keys)) {
    echo2("Файл с ключевыми словами под ключ $keyword ранее был создан, используем его. Создаем файл с рандомными ключами...");
    $tmp = csv_to_array2($res_kk);
    $tmp2 = get_rand_imgs($tmp, $images_per_site);
    foreach ($tmp2 as $item) {
        $random_split[] = $tmp[$item];
        shuffle($random_split);
    }
    array_to_csv($res_kk_rand_keys, $random_split, false, "Записали рандомные $images_per_site ключи для нового сайта", "w+");
} else {
    echo2("Файл с ключевыми словами под ключ $keyword ранее был создан, используем его.");
    $random_split = csv_to_array2($res_kk_rand_keys);
    echo2("Файл с рандомными $images_per_site ключами был создан ранее, используем его.");
}

unset($tmp);
echo_time_wasted();

$i = 0;
foreach ($random_split as $item) {
    $i++;
    $query = prepare_query($int_mode, $item[0], $position_limit, $lang_id);
    $tmp = dbquery($query);
    if ($tmp > 0 && $tmp <= $limit_imgs_per_key) {
        $image_url[] = $tmp;
    } else if ($tmp > 0 && $tmp >= $limit_imgs_per_key) {
        shuffle($tmp);
        $image_url[] = array_slice($tmp, 0, $limit_imgs_per_key);
    }
    if ($i % 5000 == 0) {
        echo_time_wasted($i);
    }
}

file_put_contents($import_dir . "_dirty_" . $import_file, serialize($image_url));
echo_time_wasted(null, "Записали файл импорта по адресу $import_dir _dirty_$import_file с данными по картинкам.");
next_script();

function prepare_query($int_mode, $key_id, $position_limit, $lang_id = '')
{
    global $tname;
    if ($int_mode) {
        $query = "SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t5`.`translated_key`,`t2`.`key`,`t4`.`domain`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations2` AS `t1`
RIGHT JOIN `$tname[keys]` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
RIGHT JOIN `google_images2` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
RIGHT JOIN `google_images_domains2` AS `t4` ON `t3`.`domain_id` = `t4`.`domain_id`
RIGHT JOIN `$tname[keys_tr]` AS `t5` ON `t1`.`key_id` = `t5`.`key_id`
WHERE `t2`.`key_id` = $key_id AND `t1`.`position` <= $position_limit AND `t5`.`language_id` = $lang_id LIMIT 100;";
    } else {
        $query = "SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t4`.`domain`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations2` AS `t1`
RIGHT JOIN `$tname[keys]` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
RIGHT JOIN `google_images2` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
RIGHT JOIN `google_images_domains2` AS `t4` ON `t3`.`domain_id` = `t4`.`domain_id`
WHERE `t2`.`key_id` = $key_id AND `t1`.`position` <= $position_limit LIMIT 100;";
    }
    return $query;
}

function get_rand_imgs($array, $images_per_site)
{
    if ($images_per_site > count($array)) {
        $images_per_site = count($array);
        echo2("Ключей на сайт получается больше чем есть в базе, берем ВСЕ ключи!");
    }
    return array_rand($array, $images_per_site);
}