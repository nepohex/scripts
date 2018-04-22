<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.01.2017
 * Time: 1:10
 */

if (!isset($start)) {
    $start = microtime(true);
}
//todo Дописать проверку переменных глобальных
/** Коннект к базе которая указана как дефолтная $db_name
 * @param null $db_name
 * @param string $db_host
 */
function mysqli_connect2($db_name = null, $db_host = 'localhost', $db_usr = 'root', $db_pwd = '', $silence = TRUE)
{
    //Возвращает $link - соединение с DB.
    global $link;
    if ($db_name == null) {
        global $db_name;
        if ($db_name == false) {
            echo2("Не указана переменная db_name которая нужна для связи с mysql функции mysqli_connect2");
            exit;
        }
    }

    $link = mysqli_init();

    if (!$link) {
        die('mysqli_init завершилась провалом');
    }

    if (!mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
        die('Установка MYSQLI_OPT_CONNECT_TIMEOUT завершилась провалом');
    }

    if (!mysqli_real_connect($link, $db_host, $db_usr, $db_pwd, $db_name)) {
        die('Ошибка подключения (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
    } else {
        if (!$silence) {
            echo2("Связь с базой $db_name есть.");
        }
    }
}

function convert($memory_usage)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($memory_usage / pow(1024, ($i = floor(log($memory_usage, 1024)))), 2) . ' ' . $unit[$i];
}

function echo_time_wasted($i = null, $msg = null)
{
    global $start;
    if (!$start) {
        $start = microtime(true);
    }
    $time = microtime(true) - $start;
    $format = "сек";
    if ($time > 300) {
        $time = $time / 60;
        $format = "мин";
    }
    if ($i) {
        echo2("Идем по строке " . $i . " $msg . Скрипт выполняется уже " . number_format($time, 2) . " $format " . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    } else {
        echo2("$msg . Скрипт выполняется уже " . number_format($time, 2) . " $format" . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    }

}

function print_r2($array)
{
    echo '<pre>';
    print_r($array);
    echo '</pre>';
    flush();
}

/** $fp_log задать как file handle (fopen) или название файла куда писать, файл будет создан по пути который указан в переменной.
 * @param $str Строка которую вывести
 * @param bool $double_log Метод логирования
 */
function echo2($str, $double_log = false)
{
    global $fp_log, $debug_mode, $double_log, $console_mode;
    if ($console_mode == false) {
        if ($double_log && $fp_log) {
            echo date("d-m-Y H:i:s") . " - " . $str . PHP_EOL;
            flush();
            if (is_resource($fp_log)) {
                fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
                return;
            } else {
                $fp = fopen($fp_log, 'a+');
                fwrite($fp, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
                return;
            }
        }
        if ($debug_mode == true) {
            echo date("d-m-Y H:i:s") . " - " . $str . PHP_EOL;
            flush();
            return;
        }
        if ($fp_log) {
            if (is_resource($fp_log)) {
                fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
            } else {
                $fp = fopen($fp_log, 'a+');
                fwrite($fp, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
            }
        }
    }
}

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
                echo_time_wasted();
                echo2("--------------------------------$i--------------------------------");
                return header('Location: ' . $scripts_chain[$i + 1]);
            }
            $i++;
        }
        echo2("Не можем найти следующего скрипта после " . $php_self);
        exit();
    }
}

function mkdir2($dir, $stfu = null)
{
    if ($stfu) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true)) {
//                echo2("Создали директорию " . $dir);
            } else {
                echo2("Директорию " . $dir . " создать не удалось и ее не существует");
            }
        } else {
//            echo2("Директория " . $dir . " уже существует, все ок");
        }
    } else {
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
}

function pwdgen($length, $include_punctuation = null)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    if ($include_punctuation) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-+*=@#$%^&.,;:?!()[]{}";
    }
    $pwd = substr(str_shuffle($chars), 0, $length);
    return $pwd;

}

/**
 * @param $queryarr mixed SQL запрос или массив запросов. Одиночный запрос имеет ряд параметров на выбор.
 * @param null $fetch_row_not_assoc Делать fetch_row
 * @param null $return_affected_rows Возвращать количество затронутых строк
 * @param null $msg_if_empty_select В случае успеха выводить сообщение
 * @param null $stfu Если какие-либо ошибки из разряда (дублирующая строка) - не выводить о них сообщений.
 * @return array|int Если Select возвращает строки, если нужно вывести COUNT вернет как STRING
 *
 * На входе нужен sql resource $link , mysqli_init
 * ПРИНИМАЕТ: массив или строку Insert / Update запросов.
 * ПРИНИМАЕТ: SELECT запрос, возвращает ассоциативный массив с результатами по дефолту
 * Если нет связи с DB, пробует соединиться по глобальной переменной db_name.
 * Если результат SELECT - единичное поле - возвращает STRING с результатом.
 * Если SELECT - 1 столбец, возвращает 1уровневый массив.
 */
function dbquery($queryarr, $fetch_row_not_assoc = null, $return_affected_rows = null, $msg_if_empty_select = null, $stfu = null, $return_single_row = null)
{
    global $link, $db_name;

    //Проверяем есть ли связь с базой, если нет - пробуем приконнектиться. Для этого глобально должно быть указано $db_name
    if ($link == false) {
        mysqli_connect2($db_name);
        if ($link == false) {
            exit ("В функции dbquery нет переменной коннекта к базе link - она пустая. Связи нет с DB.");
        }
    }
    //Если передали массив с запросами, то выполняем каждый из них.
    if (is_array($queryarr)) {
        foreach ($queryarr as $query) {
            $sqlres = mysqli_query($link, $query);
            if ($error = mysqli_error($link) && $stfu == false) {
                echo2("Mysqli error $error в запросе $query");
            }
        }
    } else { //Если не массив, то может быть и SELECT, можно вернуть значение.
        $sqlres = mysqli_query($link, $queryarr);
        if ($error = mysqli_error($link) && $stfu == false) {
            echo2("Mysqli error $error в запросе $queryarr");
            return FALSE;
        }
        if (strstr($queryarr, "SELECT")) {
            if ($fetch_row_not_assoc) {
                while ($tmp = mysqli_fetch_row($sqlres)) {
                    if (count($tmp) > 1) {
                        $result[] = $tmp;
                    } else {
                        $result[] = $tmp[0];
                    }
                }
            } else {
                while ($tmp = mysqli_fetch_assoc($sqlres)) {
                    $result[] = $tmp;
                }
                if (count($result) == 1 && $return_single_row == TRUE) {
                    return $result[0];
                }
            }
            //Если пустой результат
            if (isset($result)) {
                // Обработка результатов SELECT. Если единичная строка и колонка, то вернем как STRING.
                if (count($result) == 1 && count($result[0]) == 1) {
                    foreach ($result as $value) {
                        foreach ($value as $key => $item) {
                            return $item;
                        }
                    }
                }
                if ($result == false && $msg_if_empty_select == true) {
                    echo2("У нас пустой SELECT получился, что-то не так! Возможно нет связи с DB.");
                }
                return $result;
            } else if ($msg_if_empty_select == true) {
                echo2("Пустой SELECT получился");
                return null;
            }
        } else if ($return_affected_rows) {
            return mysqli_affected_rows($link);
        }
    }
}

/**
 * @param $site_name Домен без слешей и прочего.
 * @param $wp_conf_db_prefix Префикс базы данных
 * @param string $keyword Ключевик сайта, если его нет, будет использован $site_name
 */
function gen_wp_db_conf($site_name, $wp_conf_db_prefix, $keyword = false)
{
    global $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd;
    if ($keyword == false) {
        $keyword = $site_name;
    }
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

function printr_to_array($str)
{
    /**
     * Чужая функция, одномерный массив в формате print_r вернуть с ключами обратно в массив.
     */
    //Initialize arrays
    $keys = array();
    $values = array();
    $output = array();

    //Is it an array?
    if (substr($str, 0, 5) == 'Array') {

        //Let's parse it (hopefully it won't clash)
        $array_contents = substr($str, 7, -2);
        $array_contents = str_replace(array('[', ']', '=>'), array('#!#', '#?#', ''), $array_contents);
        $array_fields = explode("#!#", $array_contents);

        //For each array-field, we need to explode on the delimiters I've set and make it look funny.
        for ($i = 0; $i < count($array_fields); $i++) {

            //First run is glitched, so let's pass on that one.
            if ($i != 0) {

                $bits = explode('#?#', $array_fields[$i]);
                if ($bits[0] != '') $output[$bits[0]] = $bits[1];

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

function csv_to_array($csv_filepath, $delimiter = ';', $column_number = null)
{
    if (is_file($csv_filepath)) {
        $csv = array_map('str_getcsv', file($csv_filepath));
        foreach ($csv as $line) {
            if ($column_number) {
                $tmp = explode($delimiter, $line[0]);
                $csv_lines[] = $tmp[$column_number];
            } else {
                $csv_lines[] = explode($delimiter, $line[0]);
            }
        }
        return $csv_lines;
    } else {
        echo2("Функция csv_to_array не может получить контент файла $csv_filepath (должен быть CSV)");
    }
}

/**
 * Csv to array с игнором двойных кавычек
 * @param $csv_filepath string путь к файлу csv
 * @param $delimiter string разделитель csv
 * @param $column_number int нумерация с 0! если нужен 2ой столбец в файле, ставим 1.
 * @param $ignore_header bool не вставлять 1ую строку с заголовками в результат
 * @return array
 */
function csv_to_array2($csv_filepath, $delimiter = ';', $column_number = null, $ignore_header = null)
{
    if (is_file($csv_filepath)) {
        $fp = fopen($csv_filepath, "r");
        $i = 0;
        while (($row = fgetcsv($fp, 0, $delimiter)) !== FALSE) {
            if ($ignore_header && $i == 0) {
            } else {
                if ($column_number) {
                    $csv_lines[] = $row[$column_number];
                } else {
                    $csv_lines[] = $row;
                }
            }
            $i++;
        }
        fclose($fp);
        return $csv_lines;
    } else {
        echo2("Функция csv_to_array не может получить контент файла $csv_filepath (должен быть CSV)");
    }
}

function array_to_csv($fname_csv, $array, $header = false, $success_msg = null, $write_mode = 'a', $csv_delimiter = ';')
{
    $i = 0;
    $head = '';
    if ($fp = fopen($fname_csv, $write_mode)) {
        if ($i == 0 && $header == true) {
            foreach ($array[0] as $key => $value) {
                $head .= $key . $csv_delimiter;
            }
            $head .= PHP_EOL;
            fputs($fp, $head);
        }
        foreach ($array as $row) {
            fputcsv($fp, $row, $csv_delimiter);
        }
        fclose($fp);
        if ($success_msg) {
            echo2("$success_msg");
        }
        return true;
    } else {
        return false;
    }
}

/**
 * @param $array Ассоциативный массив который сортируем
 * @param $array Название колонки по которой сортируем, можно несколько. Пример использования $arr2 = array_msort($arr1, array('name'=>SORT_DESC, 'cat'=>SORT_ASC));
 * @return array
 */
function array_msort($array, array $cols)
{
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) {
            $colarr[$col]['_' . $k] = strtolower($row[$col]);
        }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
    }
    $eval = substr($eval, 0, -1) . ');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k, 1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;

}

/** Функция выявляет только уникальные элементы многомерного массива и возвращает массив с уникальными элементами по ключу
 * @param $array массив для сортировки
 * @param $key ключ многомерного массива по которому определяет уникальность
 * @return array
 */
function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

/**
 * При выгрузке из базы названий картинок чистит эти названия и превращает в будущие Title
 */
function clean_files_name($string, $pattern = null, $replace_symbols = null)
{
    if ($pattern == false || $replace_symbols == false) {
        global $pattern, $replace_symbols;
    }
    if ($pattern == false) {
        $pattern = '/-.?[0-9]\w+/i';
    }
    if ($replace_symbols == null) {
        $replace_symbols = '';
    }
    //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
    if (stripos($string, 'over')) {
        $z = explode("-", $string);
        $k = array_search(strtolower('over'), array_map('strtolower', $z));
        preg_match('/\d{2}/', $z[$k + 1], $matchez);
    }
    $string = preg_replace($pattern, "", $string); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
    $string = trim(preg_replace('/\d/', "", $string)); //добиваем все оставшиеся цифры
    $string = strtolower(trim(str_replace($replace_symbols, ' ', $string)));
    $string = explode(' ', $string);
    $final = '';
    foreach ($string as $word) {
        if (strlen($word) < 14) {
            $final .= $word . ' ';
        }
    }
    //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
    if (isset($matchez[0])) {
        $final .= ' ' . $matchez[0];
    }
    $final = trim(str_replace('  ', ' ', $final));
    return $final;
}

/**
 * Функция подготовки содержимого файла installer который будет запускаться из папки домена из хостинга.
 * Создание базы и пользователя, прав, импорт дампа в базу.
 * @param string $conf_tpl Путь к файлу который будет шаблоном нашего инсталлер файла
 * @param string $final_conf_path Полный путь с названием файла Инсталлера
 * @param string $installer_db_host Хост удаленной базы данных (99% - localhost)
 * @param string $installer_db_usr Пользователь, чаще рут
 * @param $installer_db_pwd Пароль рута под которым зайдем и будем создавать базу, пользователя и импортировать в нее.
 * @param $wp_conf_db_name Сгенеренные скриптом название базы
 * @param $wp_conf_db_usr Юзер
 * @param $wp_conf_db_pwd Пароль
 * @param $sql_dump Название дампа который будем импортировать в БД
 */
function gen_installer($conf_tpl, $final_conf_path, $installer_db_host = 'localhost', $installer_db_usr = 'root', $installer_db_pwd, $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd, $sql_dump)
{
    $tmp = file_get_contents($conf_tpl);
    $installer_data = '<?php' . PHP_EOL . '$wp_conf_db_name = \'' . $wp_conf_db_name . '\';' . PHP_EOL . '$wp_conf_db_usr = \'' . $wp_conf_db_usr . '\';' . PHP_EOL . '$wp_conf_db_pwd = \'' . $wp_conf_db_pwd . '\';' . PHP_EOL . '$installer_db_host = \'' . $installer_db_host . '\';' . PHP_EOL . '$installer_db_usr = \'' . $installer_db_usr . '\';' . PHP_EOL . '$installer_db_pwd = \'' . $installer_db_pwd . '\';' . PHP_EOL . '$sql_dump = \'' . $sql_dump . '\';' . PHP_EOL . $tmp;
    file_put_contents($final_conf_path, $installer_data);
    if (is_file($final_conf_path)) {
        echo2("Инсталлер для удаленного хоста по адресу $final_conf_path создан и записан!");
    } else {
        echo2("Не удалось записать файл конфига по адресу $final_conf_path");
    }
}

/**
 * Функция ищет все синонимы и объединяет их с основным словом, которое указано в начале массива synonyms Как первый элемент.
 * @param $words_used array Массив где ключ = Слово, значение = цифра
 * @param $synonyms array Многомерный массив где первое значение - родитель, а дети далее в массиве
 * @param int $limit_iterations сколько раз пробегать по массиву слов, оптимально 200-300.
 * @return array Массив с отсортированными словами с учетом синонимов
 */
function merge_synonyms($words_used, $synonyms, $limit_iterations = 200)
{
    arsort($words_used);
    reset($words_used);
    $z = 0;
    $words_used = array_change_key_case($words_used);
    $synonyms = array_change_key_case($synonyms);
    foreach ($words_used as $word => $count) {
        foreach ($synonyms as $synonym) {
            $i = 0;
            $parent_syn = $synonym[0];
            foreach ($synonym as $alternative) {
                if ($alternative == $word && $i !== 0) {
                    $words_used[$parent_syn] += $words_used[$alternative];
                    unset($words_used[$alternative]);
                }
                $i++;
            }
            unset($i);
        }
        if ($z == $limit_iterations) {
            break;
        }
    }
    arsort($words_used);
    return $words_used;
}

class Spintax
    /**
     * Spintax - A helper class to process Spintax strings.
     * @name Spintax
     * @author Jason Davis - https://www.codedevelopr.com/
     * Tutorial: https://www.codedevelopr.com/articles/php-spintax-class/
     * EXAMPLE USAGE
     * $spintax = new Spintax();
     * $string = '{Hello|Howdy|Hola} to you, {Mr.|Mrs.|Ms.} {Smith|Williams|Davis}!';
     * echo $spintax->process($string);
     * NESTED SPINNING EXAMPLE
     * echo $spintax->process('{Hello|Howdy|Hola} to you, {Mr.|Mrs.|Ms.} {{Jason|Malina|Sara}|Williams|Davis}');
     */
{
    public function process($text)
    {
        return preg_replace_callback('/\{(((?>[^\{\}]+)|(?R))*)\}/x', array($this, 'replace'), $text);
    }

    public function replace($text)
    {
        $text = $this->process($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}

//Упрощенная функция для генерации текста
function gen_text($spintax_class, $spec_separator, $spin_fragments_separator, $spin_text, $title, $stlen = 0, $before_spin_html = '<div class="text-content">', $after_spin_html = '</div>')
{
    $tmp = '';
    $tmp .= $spec_separator;
    $tmp .= $spintax_class->process($spin_text);
    $tmp .= $spin_fragments_separator;
    $tmp = str_ireplace('%post_title%', $title, $tmp);
    $tmp = str_replace('  ', ' ', $tmp);
    if (strlen($tmp) >= $stlen) {
        $tmp = $before_spin_html . $tmp . $after_spin_html;
    }
    return $tmp;
}

//todo функции нужна валидация
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
        echo2("Дампнули базу данных в " . $result_dir . $backup_name . " Вес базы " . convert(strlen($content)));
    } else {
        echo2("Дампнуть базу данных ИЛИ сохранить в папку $result_dir не получилось");
    }
}

function remove_none_word_chars($string)
{
    return preg_replace('~[^\\pL\d]+~u', ' ', $string);
}

function write_status($arr)
{
    foreach ($arr as $key => $item) {
        $queries[] = "UPDATE `image_index`.`generated_sites` SET `$key` = '$item'";
    }
    dbquery($queries);
}

/** Функция генерит под каждый язык заданный в массиве Lang категорию и возвращает подробный массив о категориях.
 * Если категории уже созданы то только возвращает.
 * @param array $lang
 * @return mixed
 */
function set_int_cats(array $lang)
{
    global $db_name;
    $ai = get_ai('wp_terms');
    $ai_term_taxonomy = get_ai('wp_term_taxonomy');
    $i = 0;
    foreach ($lang as $key => $lang_name) {
        if ($tmp = dbquery("SELECT `term_id` FROM `wp_terms` WHERE `name` = '$lang_name' OR `slug` = '$lang_name'")) {
            $res[$key]['language_id'] = $key;
            $res[$key]['lang_name'] = $lang_name;
            $res[$key]['term_id'] = $tmp;
            $tmp = dbquery("SELECT `term_taxonomy_id` FROM `wp_term_taxonomy` WHERE `term_id` = $tmp;");
            $res[$key]['term_taxonomy_id'] = $tmp;
            $i++;
        } else {
            $cat_descr = "Parent category for $lang_name";
            dbquery("INSERT INTO `wp_terms` (`term_id`, `name`, `slug`,`term_group`) VALUES ($ai,'$lang_name','$lang_name',$key);");
            dbquery("INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ($ai_term_taxonomy, $ai, 'category', '$cat_descr', '0', '0');");
            $res[$key]['language_id'] = $key;
            $res[$key]['lang_name'] = $lang_name;
            $res[$key]['term_id'] = $ai;
            $res[$key]['term_taxonomy_id'] = $ai_term_taxonomy;
            $ai_term_taxonomy++;
            $ai++;
            $i++;
        }
    }
    return $res;
}

/** На вход надо подать 1ым аргументом ID / имя языка категории (2 буквы), 2ой параметр массив ассоциативный полученный из set_int_cats функции с номераим катов.
 * Можно также подать только lang_name / lang_id чтобы получить ID категории языка.
 * @param $lang_id_or_name
 * @param null $wp_lang_terms
 * @return array|bool|int
 */
function get_termID_cat($lang_id_or_name, $get_term_taxonomy_id = FALSE, $wp_lang_terms = NULL)
{
    if ($wp_lang_terms) {
        foreach ($wp_lang_terms as $item) {
            if ($lang_id_or_name == $item['language_id'] OR $lang_id_or_name == $item['lang_name']) {
                if ($get_term_taxonomy_id) {
                    return $item['term_taxonomy_id'];
                }
                return $item['term_id'];
            }
        }
    } else {
        $tmp = dbquery("SELECT `term_id` FROM `wp_terms` WHERE `name` = '$lang_name' OR `slug` = '$lang_name';", TRUE);
        if ($get_term_taxonomy_id) {
            $tmp = dbquery("SELECT `term_taxonomy_id` FROM `wp_term_taxonomy` WHERE `term_id` = $tmp;");
        }
        return $tmp;
    }
    return false;
}

/** Генерим с любого языка название ЧПУ, при этом удаляя те которых нет в UTF (промучался день, так и не смог добиться полноценной транслитерации на Windows с заменой автоматической всех нестандартных символов)
 * @param $text
 * @param string $separator
 * @return bool|mixed|string
 */
function str_to_url($text, $separator = '_')
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', $separator, $text);

    // transliterate
    // Здесь стоит IGNORE вместо TRANSLIT потому что на Windows не работает корректно set_locale, целое дело, поэтому просто удаляем все лишнии символы
    $text = iconv('utf-8', 'ASCII//IGNORE', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, $separator);

    // remove duplicate -
    $text = preg_replace('~-+~', $separator, $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return FALSE;
    }

    return $text;
}

function gen_new_title($title)
{
    global $uniq_addings, $year_pattern, $year_to_replace, $seasonal_add, $seasonal_titles, $year_end_percent, $bad_symbols;
    static $i;
    shuffle($uniq_addings);
    $title = $uniq_addings[1] . ' ' . $title;
    $title = preg_replace($year_pattern, $year_to_replace, $title);
    $title = trim($title);
    $title = str_replace($bad_symbols, ' ', $title);
    $title = str_replace('  ', ' ', $title);
    $tmp = explode(' ', $title);
    $tmp = array_unique($tmp);
    $title = implode(' ', $tmp);
    if ($seasonal_add !== false && $i % $seasonal_titles == 0) {
        $z = (rand(0, 10000) < $year_end_percent * 100) ? 1 : 2;
        switch ($z) {
            case 1:
                $title .= ' ' . $year_to_replace;
                break;
            case 2:
                $title = $year_to_replace . ' ' . $title;
                break;
        }
    }
    $i++;
    $title = ucwords($title);
    return $title;
}

function get_ai($table_name)
{
    global $dbname;
    $query = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$dbname[wp]' AND TABLE_NAME = '$table_name';";
    return dbquery($query);
}

/**
 * Функция обновляет количество итемов принадлежащих определенной категории, не важно меню это или просто категория - нужно для корректной работы WP. На вход term_taxonomy_id
 * @param $cat_id int wp_term_relationships.term_taxonomy_id
 * @param bool $return_count
 * @return array|int|void
 */
function update_cat_count_items($term_taxonomy_id, $return_count = FALSE, $only_return_count = FALSE)
{
    $cats_count_fact = dbquery("SELECT COUNT(*) FROM `wp_term_relationships` WHERE `term_taxonomy_id` = $term_taxonomy_id;");
    if ($only_return_count) {
        return $cats_count_fact;
    }
    dbquery("UPDATE `wp_term_taxonomy` SET `count` = $cats_count_fact WHERE `term_taxonomy_id` = $term_taxonomy_id;");

    if ($return_count) {
        return $cats_count_fact;
    }
}

/** Получает на вход term_id категории INT, возвращает ID дочерних категорий (например для конкретного языка).
 * @param $wp_lang_term_id
 * @return array|int|void
 */
function get_child_cats($wp_lang_term_id, $columns = 'term_id')
{
    $columns = prepare_columns_string($columns, '`');
    $created_cats = dbquery("SELECT $columns FROM `wp_term_taxonomy` WHERE `parent` = '$wp_lang_term_id';", TRUE);
    if ($created_cats) {
        return $created_cats;
    } else {
        echo2("Нет дочерних категорий для term_id $wp_lang_term_id");
        return FALSE;
    }
}

function get_int_addings($lang_id)
{
    global $int_mode, $uniq_tpls, $gen_addings, $uniq_addings, $uniq_addings_nch;
    $uniq_addings = get_uniq_tpls($int_mode, $lang_id, $uniq_tpls, 0);
    $uniq_addings_nch = get_uniq_tpls($int_mode, $lang_id, $uniq_tpls, 1);
    switch ($gen_addings) {
        case 1:
            break;
        case 2:
            $uniq_addings = $uniq_addings_nch;
            break;
        case 3:
            $uniq_addings = array_merge($uniq_addings, $uniq_addings_nch);
            break;
    }
}

/** Преобразует аргументы массива или строки к запросу в MYSQL, например колонки или поля которые надо запросить-обновить и т.п.
 * @param $values
 * @param string $separator
 * @return string
 */
function prepare_columns_string($values, $separator = '\'')
{
    global $link;
    $columns = '';
    if (is_array($values)) {
        foreach ($values as $column) {
            if ($separator == '\'') {
                $column = mysqli_real_escape_string($link, $column);
            }
            $columns .= $separator . $column . $separator . ',';
        }
    } else {
        $columns .= $separator . $values . $separator . ',';
    }
    $columns = substr($columns, 0, -1);
    return $columns;
}

/** Генератор чтобы небольшими порциями выгребать из wp_posts нужные данные. Внимание! Выгребает только со статусом Publish и Post!
 * @param $term_taxonomy_id тега по которому доставать посты
 * @param int $count сколько за раз постов доставать
 * @param array $columns какие колонки из wp_posts доставать
 * @param bool $iterator_mode выгружать со сдвигом все посты
 * @return Generator
 */
function wp_get_posts($term_taxonomy_id, $count = 1000, $columns = array('post_title'), $iterator_mode = FALSE)
{
    $columns = prepare_columns_string($columns, '`');
    if ($iterator_mode) {
        if (empty($c_post_num)) {
            $c_post_num = update_cat_count_items($term_taxonomy_id, TRUE, TRUE);
        }
        for ($i = 0; $i < $c_post_num; $i += $count) {
            $post_ids = dbquery("SELECT `object_id` FROM `wp_term_relationships` WHERE `term_taxonomy_id` = $term_taxonomy_id LIMIT $count OFFSET $i;", TRUE);
        }
        $tmp = implode(",", $post_ids);
        $post_titles = dbquery("SELECT $columns FROM `wp_posts` WHERE `post_type` = 'post' AND `post_status` = 'publish' AND `ID` IN ($tmp)", TRUE);
        if ($post_titles) {
            yield $post_titles;
        } else {
            RETURN;
        }
    } else {
        while ($post_ids = dbquery("SELECT `object_id` FROM `wp_term_relationships` WHERE `term_taxonomy_id` = $term_taxonomy_id LIMIT $count;", TRUE)) {
            $tmp = implode(",", $post_ids);
            $post_titles = dbquery("SELECT $columns FROM `wp_posts` WHERE `post_type` = 'post' AND `post_status` = 'publish' AND `ID` IN ($tmp)", TRUE);
            if ($post_titles) {
                yield $post_titles;
            } else {
                RETURN;
            }
        }
    }
    RETURN;
}

/** Проверяет полный ли урл, есть ли в нем протокол http / https / etc
 * @param $url
 * @return bool
 */
function is_abs_url($url)
{
    $tmp = parse_url($url);
    if ($tmp['scheme']) {
        return TRUE;
    } else
        return FALSE;
}

/** Удаляет HTML теги, новые строки и двойные пробелы. Чисто вычислить контент.
 * @param $string
 * @return int
 */
function count_strlen_html($string, $return_content = FALSE)
{
    $string = trim(preg_replace('/\s+/', ' ', strip_tags($string)));;
    if ($return_content) {
        return $string;
    } else {
        return mb_strlen($string); //Считает реальное количество СИМВОЛОВ, а не байт и т.п.
    }
}

/** Проверяет содержится ли в строке любое из слов массива
 * @param $str
 * @param array $arr
 * @return bool
 */
function contains($str, array $arr)
{
    foreach ($arr as $a) {
        if (stripos($str, $a) !== false) return TRUE;
    }
    return FALSE;
}

function prepare_dir($path)
{
    if (is_dir($path)) {
        return TRUE;
    } else {
        if (@mkdir($path, 0777, TRUE)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

/** Возвращает массив с imageinfo если картинка, если нет файла или не картинка - false.
 * @param $local_img_path
 * @return array|bool
 */
function is_image($local_img_path)
{
    if (@is_file($local_img_path)) {
        if ($tmp = @getimagesize($local_img_path)) {
            if (@is_array($tmp)) {
                return $tmp;
            } else {
                return FALSE;
            }
        }
    }
    return FALSE;
}

function get_table_max_id($table_name, $column_name = 'id', $db_name = FALSE)
{
    if ($db_name == FALSE) {
        return dbquery("SELECT MAX(`$column_name`) FROM `$table_name`;");
    } else {
        return dbquery("SELECT MAX(`$column_name`) FROM `$db_name`.`$table_name`;");
    }
}

//function last($array)
//{
//    return end($array);
//}

function first($array)
{
    return array_shift($array);
}