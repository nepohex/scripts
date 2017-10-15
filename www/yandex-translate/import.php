<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.10.2017
 * Time: 18:31
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
use Beeyev\YaTranslate\Translate;

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'image_index';
$fp_log = 'log.txt';
mysqli_connect2();

//// Обязательно проверить!
$lang = array(
    0 => 'pt',
    1 => 'es',
    2 => 'de',
    3 => 'fr',
    4 => 'it',
    5 => 'nl',
    6 => 'da',
    7 => 'sv',
    8 => 'fi',
    9 => 'cs',
//    10 => 'pl',
//    11 => 'ro',
);

$lang_flip = array_flip($lang);
$api_keys = file('api_keys.txt', FILE_IGNORE_NEW_LINES);
$tr = new Translate(get_api_key($api_keys, TRUE));

$csv = csv_to_array2('../new/includes/selects/long_40000_rand_keys.csv');
$columns = array('translate_id', 'key_id', 'translated_key', 'language_id');

for ($i = 0; $i < rand(3, 10); $i++) {
    shuffle($csv);
    shuffle($api_keys);
}
foreach ($csv as $item) {
    $key_id = $item[0];
    $key = $item[1];
    $db_res = dbquery("SELECT `language_id` FROM `$db_name`.`keys_translate` WHERE `key_id` = $key_id  AND `translated_key` != '';", TRUE);
    //Если этот $key_id ключ ранее вообще ниразу не переводился.
    if ($db_res == FALSE) {
        foreach ($lang as $lang_id => $lang_name) {
            tmp_tr();
        }
        //Проверяем какие языки ранее не переводились для данного ключа
    } else if ($to_translate_langs = array_diff($lang_flip, $db_res)) {
        foreach ($to_translate_langs as $lang_name => $lang_id) {
            tmp_tr();
        }
    }
}
echo2(print_r(tmp_tr(TRUE), TRUE));

function tmp_tr($return_stats = FALSE)
{
    global $tr, $lang_name, $key, $key_id, $lang_id, $columns, $columns_data;
    static $stats = array();

    if ($return_stats) {
        return $stats;
    }

    $stats['iterations'] += 1;
    $stats['strlen'] += strlen($key);
    $stats['lang'][$lang_name] += 1;

    $result = translate($tr, $lang_name, $key, 1000);
    if ($result !== FALSE) {
        $columns_data = array('', $key_id, $result, $lang_id);
        insert_db('keys_translate', $columns, $columns_data);
    }
}

function translate(&$class, $lang_short_code, $string, $report_period = 100)
{
    global $api_keys;
    static $symbols_sent = 0, $i = 0;
    try {
        $result = $class->translate($string, $lang_short_code);
        $symbols_sent += strlen($string);
        $i++;
        if ($i % $report_period == 0) {
            echo_time_wasted($i, "Символов перевели $symbols_sent");
        }
        return $result;
    } catch (\Beeyev\YaTranslate\TranslateException $e) {
//        echo2(print_r($e, true));
        $class->setApiKey(get_api_key($api_keys));
        return false;
    }
}

function get_api_key($keys_arr, $random = null)
{
    static $try = 0;
    // $maximum_try = 10;
    $maximum_try = count($keys_arr)*5;
    if ($random) {
        if ($try < $maximum_try) {
            $try++;
            $tmp = $keys_arr[array_rand($keys_arr, 1)];
            echo2("Пробуем случайный ключ $tmp");
            return $tmp;
        } else {
            echo2("Больше $maximum_try получали API ключ!");
            exit();
            return false;
        }
    } else {
        if (isset($keys_arr[$try])) {
            echo2("Пробуем ключ $keys_arr[$try]");
            $try++;
            return $keys_arr[$try - 1];
        }
        else {
            echo2("Закончились API ключи в массиве!");
            $try++;
            exit();
            return false;
        }
    }
}

function insert_db($table, array $columns_name, array $columns_data, $report_period = 100)
{
    global $link;
    static $success_import = 0, $fail_import = 0, $i = 0;
    $i++;
    $columns_name = prepare_columns_string($columns_name, '`');
    $columns_data = prepare_columns_string($columns_data, '\'');
    $query = "INSERT INTO `$table` ($columns_name)  VALUES  ($columns_data);";
    if (dbquery($query, null, true) == 1) {
        $success_import++;
    } else {
        $fail_import++;
    }
    if ($i % $report_period == 0) {
        echo_time_wasted($i, "Успешный импорт $success_import, failed = $fail_import");
    }
}