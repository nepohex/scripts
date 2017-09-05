<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.12.2016
 * Time: 19:32
 */
include "multiconf.php";
next_script(0, 1);

//$backup_name = "mybackup.sql"; // Не работает, будет названием базы
//$tables = "wp_options"; // Не работает по 1 табле, лень разбираться

//or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables
//$db_name = 'short-instance'; // Если нужно конкретно эту базу дампнуть
Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = 'dump.sql', $work_dir . '/');
echo2("Закончили генерацию! " . $_SERVER['SCRIPT_FILENAME'] . " Ставим конфигу статус FIN и если есть еще конфиги - идем дальше по списку создания");
get_config($fin = true);
next_script(0, 0, 1);