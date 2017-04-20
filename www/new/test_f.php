<?php
$sites = array('bun-hairstyles.com','graduatedhairstyles.info','dreadlocks2017.xyz','platinumhair.info','sidepartedhairstyles.men','punkhairstyles.us');
foreach ($sites as $site_name) {
    $work_dir = 'F:\Dumps\\' . $site_name; // Пока нигде не использовано
// База данных Wordpress
    $db_instance = 'includes/db_instance.sql'; // Пустая база данных с таблицами Wordpress, которая будет создаваться каждый раз для нового сайта. Лежать будет пока в папке со скриптом.
    $db_usr = 'root';
    $db_name = $site_name;
// База данных с картинками
    $db_name_img = 'image_index';
    $db_host = 'localhost';
    $db_pwd = '';
    $wp_conf_tpl = 'wp_conf_empty.txt';
    $wp_conf_cache_tpl = 'wp-cache-conf_empty.txt';
    Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = 'dump.sql', $work_dir . '/');
}

function Export_Database($host, $user, $pass, $name, $tables = false, $backup_name = false, $result_dir = false)
{
    if ($result_dir == false) {
        global $result_dir;
    }
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
    if (is_file($result_dir . $backup_name)) {
        echo2("Дампнули базу данных в " . $result_dir . $backup_name);
    } else {
        echo2("Дампнуть базу данных ИЛИ сохранить в папку $result_dir не получилось");
    }
}

exit();
$debug_mode = 1;
$fp_log = 'log.txt';
$tmp = get_resource_type($fp_log);
$fp_log = fopen($fp_log,'a');
$tmp = get_resource_type($fp_log);
function echo2($str, $double_log = false)
{
    global $fp_log, $debug_mode, $double_log, $console_mode;
    if ($console_mode == false) {
        if ($double_log && $fp_log) {
            echo "$str" . PHP_EOL;
            flush();
            fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
        } elseif ($debug_mode == 'true' | $debug_mode == '1') {
            echo date("d-m-Y H:i:s") . " - " . $str . PHP_EOL;
            flush();
        } else {
            fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
        }
    }
}
$scripts_chain = array('status.txt');
function next_script($php_self = null, $start = null, $fin = null)
{
    global $scripts_chain;

    if ($php_self == false) {
        $php_self = $_SERVER['SCRIPT_FILENAME'];
    }
    if ($fin == true) {
        echo2("Достигли конца генерации сайта, пробуем перейти на новый круг! " . $php_self);
        return header('Location: ' . array_shift($scripts_chain));
    }
    if ($start == true) {
        echo2("Начинаем выполнять скрипт " . $php_self);
    } else {
        $i = 0;
        $php_self = array_pop(explode('/', $php_self));
        foreach ($scripts_chain as $script) {
            if ($script == $php_self) {
                echo2("Закончили со скриптом " . $_SERVER['SCRIPT_FILENAME'] . " Переходим к NEXT");
                echo2("--------------------------------$i--------------------------------");
                return header('Location: ' . $scripts_chain[$i + 1]);
            }
            $i++;
        }
        echo2("Не можем найти следующего скрипта после " . $php_self);
    }
}
//next_script(0,1);
next_script();