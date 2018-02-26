<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.02.2018
 * Time: 16:52
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$handle = fopen("f:/tmp/SU_Domains_ru-tld_25022018.ru", "r");
//$handle = fopen("f:/tmp/RU_Domains_ru-tld_25022018.ru", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $tmp = preg_split('/[\t]/', $line);
        $tmp = array_map('trim', $tmp);
        $tmp = array_map('strtolower', $tmp);
        $registrant_id = regru_get_registrant($tmp[1]);
        regru_put_domaindata($tmp, $registrant_id);
    }
    fclose($handle);
} else {
    // error opening the file.
}

function regru_get_registrant($regname)
{
    if ($res = dbquery("SELECT `id` FROM `dev_rudomains`.`registrant` WHERE `registrant` = '$regname';")) {
        return $res;
    } else {
        dbquery("INSERT INTO `dev_rudomains`.`registrant` VALUES ('','$regname');");
        return dbquery("SELECT `id` FROM `dev_rudomains`.`registrant` WHERE `registrant` = '$regname';");
    }
}

function regru_put_domaindata($array, $registrant_id)
{
    $array[1] = $registrant_id;
    $tmp2 = $array[2];
    $tmp2 = explode('.', $tmp2);
    $tmp2 = array_last($tmp2);
    $tmp = implode("','", $array);
    $query = "INSERT INTO `dev_rudomains`.`domains` VALUES ('','$tmp','$tmp2','');";
    dbquery($query, null, null, null, 1);
}