<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 10.05.2017
 * Time: 14:33
 */
include('includes/functions.php');

$fp_log = 'google_search_import_log.txt';
$double_log = 1;
$db_usr = 'root';

$source_db = 'google';
$source_table = 'search_analytics';
$import_db = 'image_index';
$import_table = 'semrush_keys';

mysqli_connect2($source_db);
$start_offset = 1000000;
$limit_queries = 10000;

$source_keys = dbquery("SELECT COUNT(*) FROM `$source_db`.`$source_table`;");
$import_keys = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");

echo2("Было в таблице $import_table записей с ключами = $import_keys , в таблице с ключами $source_table из Google Search Console - $source_keys");
$replace = array(' ', '2017', '\'');
$replace2 = array('2017', '2016', '2014', '2013', '2015');

while (($start_offset + $limit_queries) < $source_keys) {
    $query = "SELECT `query` from `$source_db`.`$source_table` LIMIT $start_offset , $limit_queries";
    $result = dbquery($query, true);
    foreach ($result as $item) {
        $tmp = str_replace($replace, '', $item);
        //Проверка на латиницу + цифры
        if (ctype_alnum($tmp)) {
            $item = trim(str_replace('  ', ' ', str_replace($replace2, ' ', $item)));
            $queries[] = "INSERT INTO `$import_db`.`$import_table` (`key_id`, `key`, `adwords`, `results`) VALUES (NULL, '$item', '', '');";
            $valid++;
        } else {
//            echo2($item);
            $invalid++;
        }
    }
    dbquery($queries, null, null, null, true);
    $start_offset += $limit_queries;
    unset ($queries);
}
$tmp = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");
$new_keys = $tmp - $import_keys;
echo2("Влилось в базу ключей $new_keys . $valid ключей было отправлено на вставку, $invalid ключей было других языков и с левыми символами.");
echo_time_wasted();