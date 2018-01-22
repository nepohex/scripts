<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.01.2018
 * Time: 22:49
 */
include('../../new/includes/functions.php');
$fp_log = __DIR__ . '/debug/log.txt';
//$debug_mode = 1;
$double_log = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'image_index';
$tname = 'instagram';
mysqli_connect2($db_name);
$base_img_dir = 'f:\Dumps\instagram\all_inst\\';

$logins = array('rapunzel_ekb', 'elstilespb');

$query = "SELECT * FROM `image_index`.`instagram_old`";

$result = mysqli_query($link, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $fname = explode("_", $row['fname']);
    unset ($fname[0]);
    $fname = implode("_", $fname);
    foreach ($logins as $login) {
        $full_name = $base_img_dir . $login . '/thumb/' . $fname;
        if (@is_file($full_name)) {
            $img_id = dbquery("SELECT `id` FROM `image_index`.`instagram_images` WHERE `fname` = '$fname';");
            $double = dbquery("SELECT * FROM `image_index`.`instagram` WHERE `img_id` = $img_id");
            if ($double == FALSE) {
                $descr = addslashes($row['related_descriptions']);
                dbquery("INSERT INTO `image_index`.`instagram` (`img_id`, `pinid`, `related_descriptions`, `auto_name`) VALUES ($img_id, '$row[pinid]', '$descr', '$row[auto_name]');");
                dbquery("UPDATE `image_index`.`instagram_images` SET `posted` = 1 WHERE `id` = $img_id");
            }
        }
    }
}