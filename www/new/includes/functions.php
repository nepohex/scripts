<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.01.2017
 * Time: 1:10
 */

function mysqli_connect2($db_name = null)
{
    //Возвращает $link - соединение с DB.
    global $db_pwd, $db_usr, $link;
    if ($db_name == null) {
        global $db_name;
    }
    $link = mysqli_init();

    if (!$link) {
        die('mysqli_init завершилась провалом');
    }

    if (!mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
        die('Установка MYSQLI_OPT_CONNECT_TIMEOUT завершилась провалом');
    }

    if (!mysqli_real_connect($link, 'localhost', $db_usr, $db_pwd, $db_name)) {
        die('Ошибка подключения (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
    } else {
        echo2("Связь с базой $db_name есть.");
    }
}

function convert($memory_usage)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($memory_usage / pow(1024, ($i = floor(log($memory_usage, 1024)))), 2) . ' ' . $unit[$i];
}

function echo_time_wasted($i = null)
{
    global $start;
    $time = microtime(true) - $start;
    if ($i) {
        echo2("Идем по строке " . $i . " Скрипт выполняется уже " . number_format($time, 2) . " сек" . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    } else {
        echo2("Скрипт выполняется уже " . number_format($time, 2) . " сек" . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    }

}

function print_r2($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
    flush();
}

function echo2($str)
{
    global $fp_log, $debug_mode;
    if ($debug_mode == 'true' | $debug_mode == '1') {
        echo "$str" . PHP_EOL;
        flush();
    } else {
        fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
    }
}

function next_script($php_self, $fin = null)
{
    global $scripts_chain;
    if ($fin == true) {
        echo2("Достигли конца генерации сайта, пробуем перейти на новый круг! " . $php_self);
        return header('Location: ' . array_shift($scripts_chain));
    }
    $i = 0;
    $php_self = array_pop(explode('/', $php_self));
    foreach ($scripts_chain as $script) {
        if ($script == $php_self) {
            return header('Location: ' . $scripts_chain[$i + 1]);
        }
        $i++;
    }
    echo2("Не можем найти следующего скрипта после " . $php_self);
}

function mkdir2($dir)
{
    echo2("Пробуем создать директорию " . $dir);
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo2("Создали директорию " . $dir);
        } else {
            echo2("Директорию " . $dir . " создать не удалось и ее не существует");
        }
    } else {
        echo2("Директория " . $dir . " уже существует, все ок");
    }
}

function pwdgen($length)
{

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars), 0, $length);

}

function dbquery($queryarr, $fetch_row_not_assoc = null)
    /**
     * Можно отправлять массив или строку Insert / Update запросов.
     * Можно отправить SELECT запрос, возвращает ассоциативный массив с результатами по дефолту
     * 2ой параметр - fetch_row, передавать нужно любую не пустую переменную.
     * Оповещает об ошибках.
     */
    #todo провести рефакторинг кода, найти все места где использованы единичные SELECT или иные запросы, использовать эту функцию.
{
    global $link;
    if (is_array($queryarr)) {
        foreach ($queryarr as $query) {
            $sqlres = mysqli_query($link, $query);
            if ($error = mysqli_error($link)) {
                echo2("Mysqli error $error в запросе $query");
            }
        }
    } else {
        $sqlres = mysqli_query($link, $queryarr);
        if ($error = mysqli_error($link)) {
            echo2("Mysqli error $error в запросе $queryarr");
        }
        if (strstr($queryarr, "SELECT")) {
            if ($fetch_row_not_assoc) {
                while ($tmp = mysqli_fetch_row($sqlres)) {
                    $result[] = $tmp;
                }
            } else {
                while ($tmp = mysqli_fetch_assoc($sqlres)) {
                    $result[] = $tmp;
                }
            }
        }
        return $result;
    }
}

function gen_wp_db_conf()
{
    global $site_name, $keyword, $wp_conf_db_prefix;
    global $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd;
    $tmp = strlen($wp_conf_db_prefix . $keyword);
    if ($tmp < 16) {
        $wp_conf_db_name = $wp_conf_db_prefix . $keyword . pwdgen(15 - $tmp);
        $wp_conf_db_usr = $wp_conf_db_prefix . $keyword . pwdgen(15 - $tmp);
    } elseif ($tmp >= 16) {
        $wp_conf_db_name = substr($wp_conf_db_prefix . $keyword, 0, 16);
        $wp_conf_db_usr = substr($wp_conf_db_prefix . $keyword, 0, 14) . pwdgen(2);
    }
    $wp_conf_db_pwd = pwdgen(12);
}

function printr_to_array($str) {
    /**
     * Чужая функция, одномерный массив в формате print_r вернуть с ключами обратно в массив.
     */
    //Initialize arrays
    $keys = array();
    $values = array();
    $output = array();

    //Is it an array?
    if( substr($str, 0, 5) == 'Array' ) {

        //Let's parse it (hopefully it won't clash)
        $array_contents = substr($str, 7, -2);
        $array_contents = str_replace(array('[', ']', '=>'), array('#!#', '#?#', ''), $array_contents);
        $array_fields = explode("#!#", $array_contents);

        //For each array-field, we need to explode on the delimiters I've set and make it look funny.
        for($i = 0; $i < count($array_fields); $i++ ) {

            //First run is glitched, so let's pass on that one.
            if( $i != 0 ) {

                $bits = explode('#?#', $array_fields[$i]);
                if( $bits[0] != '' ) $output[$bits[0]] = $bits[1];

            }
        }

        //Return the output.
        return $output;

    } else {

        //Duh, not an array.
        echo 'The given parameter is not an array.';
        return null;
    }

}