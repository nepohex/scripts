<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.03.2017
 * Time: 21:47
 * Prepare pin dead DB parse
 */
include('../new/includes/functions.php');

$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'pinterest';

$queries[] = "UPDATE `pin_check2` SET `checked` = 0 WHERE `checked` = 2;";
$queries[] = "UPDATE `proxy` SET `used` = 0, `pid` = '' , `php_self` = '';";
$queries[] = "UPDATE `pin_top10` SET `status` = 5 WHERE `status` = 3;";
dbquery($queries);
print_r($queries);
echo "success";
