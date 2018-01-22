<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 03.12.2017
 * Time: 22:54
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'dev_wp_pidrnet';
$fp_log = '/debug/log2.txt';

//SELECT * FROM `content_super-ceiling.com` JOIN (SELECT `id` FROM `map_super-ceiling.com` WHERE href LIKE '/fr/pages/%' LIMIT 10)
//AS map_table ON `content_super-ceiling.com`.`map_id` = `map_table`.`id`;

$generator = pidrnet_get_articles('de', 10, 'dev_translated_parse', array('content' => 'content_super-ceiling.com', 'map' => 'map_super-ceiling.com'));

while ($generator) {
    foreach ($generator as $pack) {
        foreach ($pack as $row) {
            $cats = pidrnet_extract_cat($row['category']);
        }
    }
}
echo count($res);

function pidrnet_extract_cat($item, array $delete = array ('first','last'))
{
    $cats = explode("»", $item);
    $cats = array_map('pidrnet_sanitize_cat_data', $cats);
    if (in_array('first',$delete)) {
        
    }
    return $cats;
}

// Уебанство потому что когда складывал в базу делал mysqli real escape + serialize , а на выходе unserialize не работает.
function pidrnet_sanitize_cat_data($string)
{
    $pos_start = stripos($string, "<");
    if ($pos_start) {
        $pos_fin = strripos($string, ">");
        $string = substr($string, $pos_start);
        $string = substr($string, 0, $pos_fin);
    }
    $string = strip_tags($string);
    return trim($string);
}

function pidrnet_get_articles($lang, $count, $db, array $tables)
{
    $i = 0;
    $query = "SELECT * FROM `$db`.`$tables[content]` 
JOIN (SELECT `id` FROM `$db`.`$tables[map]` WHERE `href` LIKE '/$lang/pages/%' LIMIT $i, $count) AS map_table 
ON `$tables[content]`.`map_id` = `map_table`.`id`";
    $res = dbquery($query);
    $i += $count;
    if ($res) {
        yield $res;
    } else {
        return FALSE;
    }
}
