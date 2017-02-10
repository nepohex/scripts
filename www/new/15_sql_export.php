<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.12.2016
 * Time: 19:32
 */
include "multiconf.php";
next_script(0,1);

//$backup_name = "mybackup.sql"; // Не работает, будет названием базы
//$tables = "wp_options"; // Не работает по 1 табле, лень разбираться

//or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables
//$db_name = 'short-instance'; // Если нужно конкретно эту базу дампнуть
Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = false);

function Export_Database($host, $user, $pass, $name, $tables = false, $backup_name = false) {
    global $result_dir;
    $mysqli = new mysqli($host, $user, $pass, $name);
    $mysqli->select_db($name);
    $mysqli->query("SET NAMES 'utf8'");

    $queryTables = $mysqli->query('SHOW TABLES');
    while ($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
    }
    if ($tables !== false) {
        $target_tables = array_intersect($target_tables, $tables);
    }
    foreach ($target_tables as $table) {
        $result = $mysqli->query('SELECT * FROM ' . $table);
        $fields_amount = $result->field_count;
        $rows_num = $mysqli->affected_rows;
        $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $TableMLine = $res->fetch_row();
        $content = (!isset($content) ? '' : $content) . "\n\n" . $TableMLine[1] . ";\n\n";

        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
            while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
                if ($st_counter % 100 == 0 || $st_counter == 0) {
                    $content .= "\nINSERT INTO " . $table . " VALUES";
                }
                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) {
                        $content .= '"' . $row[$j] . '"';
                    } else {
                        $content .= '""';
                    }
                    if ($j < ($fields_amount - 1)) {
                        $content .= ',';
                    }
                }
                $content .= ")";
                //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                    $content .= ";";
                } else {
                    $content .= ",";
                }
                $st_counter = $st_counter + 1;
            }
        }
        $content .= "\n\n\n";
    }
    //$backup_name = $backup_name ? $backup_name : $name."___(".date('H-i-s')."_".date('d-m-Y').")__rand".rand(1,11111111).".sql";
    $backup_name = $backup_name ? $backup_name : $name . ".sql";
//    header('Content-Type: application/octet-stream');
//    header("Content-Transfer-Encoding: Binary");
//    header("Content-disposition: attachment; filename=\"".$backup_name."\"");
    file_put_contents($result_dir . $backup_name, $content);
//    echo $content;
    echo2("Дампнули базу данных в " . $result_dir . $backup_name);
}

echo2("Закончили генерацию! " . $_SERVER['SCRIPT_FILENAME'] . " Ставим конфигу статус FIN и если есть еще конфиги - идем дальше по списку создания");
get_config($fin = true);
next_script(0,0,1);
?>