<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 18.03.2018
 * Time: 15:16
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_wp_zapdoc';
//mysqli_connect2('dev_modx_zapdoc');
$domain = "http://zapdoc1.ru";

echo2 ("hello!");