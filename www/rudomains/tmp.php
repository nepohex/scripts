<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.03.2018
 * Time: 14:51
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$queries = file('./debug/queries.txt', FILE_IGNORE_NEW_LINES);

$query1 = "SELECT * FROM `domains` WHERE `id` = `webomer_id`;";

$res = mysqli_query($link, $query1);
$i = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $tmp = dbquery("SELECT `id` FROM `dev_rudomains`.`webomer` WHERE `site_url` = '$row[domain]';");
    if ($tmp) {
        $i += 1;
        $query[] = "UPDATE `dev_rudomains`.`domains` SET `webomer_id` = '$tmp', `webomer_checked` = '1' WHERE `id` ='$row[id]';";
        $query[] = "UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = '1' WHERE `id` = $tmp;";
    } else {
        $z += 1;
        $query[] = "UPDATE `dev_rudomains`.`domains` SET `webomer_checked` = '1' WHERE `id` ='$row[id]';";
        $query[] = "UPDATE `dev_rudomains`.`domains` SET `webomer_id` = NULL WHERE `id` = $row[id];";
    }
    dbquery($query);
    unset ($tmp, $query);
}
echo_time_wasted($i . "---" . $z);
