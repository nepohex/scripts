<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.10.2017
 * Time: 0:36
 */
include "multiconf.php";
next_script(0, 1);
Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = 'multi_int_dump.sql', $work_dir . '/');
echo2("Закончили генерацию! " . $_SERVER['SCRIPT_FILENAME'] . " Ставим конфигу статус FIN и если есть еще конфиги - идем дальше по списку создания");
get_config($fin = true);
next_script(0, 0, 1);