<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.02.2018
 * Time: 21:43
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$allowed_zone = array('ru', 'su'); //Список доменных зон по которым у нас готовы Whois
$query = "SELECT * FROM `dev_rudomains`.`webomer` ORDER BY `id` DESC;";
$res = mysqli_query($link, $query);
$i = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $i++;
    $tmp3 = explode('.', $row['site_url']);
    if (@in_array($tmp3[1], $allowed_zone)) {
        $tmp = dbquery("SELECT `id` FROM `dev_rudomains`.`domains` WHERE `domain` = '$row[site_url]';");
        if ($tmp) {
            $query = "UPDATE `dev_rudomains`.`domains` SET `webomer_id` = '$row[id]' WHERE `id` ='$tmp';";
            dbquery($query, null, null, null, 1);
        }
        unset ($tmp, $tmp3);
    }
    if ($i % 100000 == 0) {
        echo_time_wasted($i);
    }
}