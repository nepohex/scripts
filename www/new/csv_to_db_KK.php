<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.11.2016
 * Time: 17:52
 */
header('Content-Type: text/html; charset=utf-8');
$start = microtime(true);
$db_usr = 'root';
$db_name = 'image_index';
$db_pwd = '';
include("mysqli_connect.php");

$csv_file = 'F:\Dumps\downloaded sites\hairstyles_kk.csv';
$fp = fopen($csv_file, 'r+');
$i = 0;

while (($data = fgetcsv($fp)) !== FALSE) {
    $tmp = explode(";", $data[0]);
    $query = "INSERT INTO `image_index`.`kk_keys` (`key_id`, `key`, `adwords`, `results`, `ping_pos`, `pin_page`) VALUES (".$i.", '".$tmp[0]."',".$tmp[1].",".$tmp[2].", ".$tmp[3].", '".addslashes($tmp[4])."');";
    $sqlres = mysqli_query($link, $query);
    $i++;
}
?>