<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 04.04.2018
 * Time: 15:44
 * Смотреть надо вручную (или джойнить) по term_taxonomy_id , А не по тому что WP показывает при наведении в рубриках (это term_id).
 * Снятие с публикации страниц без ссылок из рубрик:
 * Новости 1 - то что без внешних ссылок
 * Звезды 14 - то что без внешних ссылок
 * Подиум 15 - то что без внешних ссылок
 * Яостальное 1804 (из рублик Mira /  GGL) - то что без внешних ссылок
 * Дополнительно для всех из этих рубрик:
 * Опросы 16 - вручную
 * Тесты 17 - вручную
 * Астрология 1512 - вручную
 * Лунный календарь стрижек 553 - вручную
 *
 * статус поставили trash
 *
 * Дата не моложе 6 мес
 *
 * Результаты:
 * 04-04-2018 16:39:52 - Всего постов в категориях1
 * Array
 * (
 * [0] => Array
 * (
 * [term_taxonomy_id] => 1804
 * [count] => 564
 * )
 *
 * [1] => Array
 * (
 * [term_taxonomy_id] => 14
 * [count] => 346
 * )
 *
 * [2] => Array
 * (
 * [term_taxonomy_id] => 1
 * [count] => 343
 * )
 *
 * [3] => Array
 * (
 * [term_taxonomy_id] => 15
 * [count] => 29
 * )
 *
 * )
 * 04-04-2018 16:39:52 - Изменили статус на Trash для постов которые без ссылок1
 * Array
 * (
 * [1] => 175
 * [14] => 174
 * [15] => 26
 * [17] => 2
 * [1804] => 103
 * )
 *
 * 04-04-2018 16:40:14 - Изменили статус на Trash для постов из рубрик Опросы, Тесты, Астрология, Лунный Календарь Стрижек1
 * Array
 * (
 * [1] => 175
 * [14] => 174
 * [15] => 26
 * [17] => 4
 * [1804] => 103
 * [16] => 4
 * [553] => 8
 * [1512] => 29
 * )
 * Итого снято с публикации 523 / 3400 поста.
 *
 * С предыдущим скриптом были пересечения, поэтому в итоге осталось из 3400 постов всего 2566 постов опубликованных.
 * В пред скрипте (нет трафика, и нет внешних ссылок) было 723 поста, здесь 523. Значит где-то 412 постов пересекались.
 *
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_lk_old';
mysqli_connect2($dbname['wp']);
$domain = "http://ladykiss1.ru/";

$res = dbquery("SELECT `term_taxonomy_id`,COUNT(*) AS `count` FROM `$dbname[wp]`.`wp_posts_new` JOIN `wp_term_relationships` ON `wp_posts_new`.`ID` = `wp_term_relationships`.`object_id` WHERE `post_status` = 'publish' AND `term_taxonomy_id` IN (1,14,15,1804) GROUP BY `term_taxonomy_id` ORDER BY `count` DESC;");
echo2("Всего постов в категориях" . print_r($res));

$res = dbquery("SELECT `wp_posts_new`.`ID`,`wp_term_relationships`.`term_taxonomy_id`,`wp_posts_new`.`post_date` FROM `$dbname[wp]`.`wp_posts_new` JOIN `wp_term_relationships` ON `wp_posts_new`.`id` = `wp_term_relationships`.`object_id` WHERE `post_content` NOT LIKE '%href%' AND `post_status` = 'publish' AND `term_taxonomy_id` IN (1,14,15,17,1512,553,1804);", 1);

if ($res) {
    foreach ($res as $row) {
        $tmp[$row[1]] += 1;
        $ID = $row[0];
        dbquery("UPDATE `$dbname[wp]`.`wp_posts_new` SET `post_status` = 'trash' WHERE `ID` = $ID;");
    }
    echo2("Изменили статус на Trash для постов которые без ссылок" . print_r($tmp));
}

$res = dbquery("SELECT `wp_posts_new`.`ID`,`wp_term_relationships`.`term_taxonomy_id` FROM `$dbname[wp]`.`wp_posts_new` JOIN `wp_term_relationships` ON `wp_posts_new`.`ID` = `wp_term_relationships`.`object_id` WHERE `post_status` = 'publish' AND `term_taxonomy_id` IN (16,17,1512,553);", 1);

if ($res) {
    foreach ($res as $row) {
        $tmp[$row[1]] += 1;
        $ID = $row[0];
        dbquery("UPDATE `$dbname[wp]`.`wp_posts_new` SET `post_status` = 'trash' WHERE `ID` = $ID;");
    }
    echo2("Изменили статус на Trash для постов из рубрик Опросы, Тесты, Астрология, Лунный Календарь Стрижек" . print_r($tmp));
}

echo2("Итого снято с публикации постов" . array_sum($tmp));