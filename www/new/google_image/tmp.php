<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.08.2017
 * Time: 21:56
 * Выборки из новых баз
 *
 * KEY > IMAGES
 * SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations` AS `t1`
LEFT JOIN `semrush_keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
LEFT JOIN `google_images` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
WHERE `t2`.`key` LIKE '%ponytail%' AND `t1`.`position` < 15 LIMIT 100;
 *
 * TAGS
 * SELECT `t1`.*,`t2`.*,`t3`.* from `google_images_relations` AS `t1`
LEFT JOIN `google_images` AS `t2` ON `t1`.`image_id` = `t2`.`image_id`
LEFT JOIN `semrush_keys` AS `t3` ON `t1`.`key_id` = `t3`.`key_id`
WHERE `t2`.`image_url` = 'https://i.pinimg.com/736x/6f/4f/16/6f4f16fd50f5f44a765dd9431599c1f5--ponytail-hairstyles-for-black-women-bangs-ponytail-and-bangs-weave.jpg'
 */
require_once('../includes/functions.php');

$fp_log = "log.txt";

$db_usr = 'root';
$db_name = 'image_index';

//debug
ini_set('ERROR_REPORTING', E_ALL);
mysqli_connect2($db_name);
$debug_mode = 1;
//

$query = "SELECT `t1`.`relation_id`,`t1`.`key_id`, `t1`.`image_id`,`t2`.`key`,`t3`.`image_url`,`t3`.`width`,`t3`.`height`,`t3`.`size`,`t1`.`position`
FROM `google_images_relations` AS `t1`
LEFT JOIN `semrush_keys` AS `t2` ON `t1`.`key_id`=`t2`.`key_id`
LEFT JOIN `google_images` AS `t3` ON `t1`.`image_id` = `t3`.`image_id`
WHERE `t2`.`key` LIKE '%ponytail%' AND `t1`.`position` < 15 LIMIT 100;";

$table = dbquery($query);

foreach ($table as $item) {
    print_r("<img src ='$item[image_url]'><br />#$item[image_id] , position $item[position] <a href='$item[image_url]'>$item[key]<br/>$item[image_url]<br /></a>");
}