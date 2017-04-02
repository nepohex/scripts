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
$queries[] = "UPDATE `proxy` SET `used` = 0, `pid` = '' , `php_self` = '' WHERE `used` NOT IN(2,3,4);";
$queries[] = "UPDATE `pin_top10` SET `status` = 5 WHERE `status` = 3;";
$queries[] = "UPDATE `pin_dead` SET `status` = 0 WHERE `status` = 1;";
$queries[] = "UPDATE `godaddy_buynow` SET `status` = 0 WHERE `status` = 3;";
$queries[] = "UPDATE `pin_houzz` SET `checked` = 0 WHERE `checked` = 2;";
//$queries[] = "UPDATE `pin_houzz_dead` SET `status` = 0 WHERE `status` = 1;";
$queries[] = "UPDATE `pin_houzz_top10` SET `status` = 5 WHERE `status` = 3;";
$i = 0;
foreach ($queries as $query) {
    $tmp = dbquery($query,false,true);
    echo2("Affected rows $tmp => $query");
}