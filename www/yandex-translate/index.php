<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.05.2017
 * Time: 23:30
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
//$language_id = 9;
//$lang_name = 'cs'; //pt -> 0 , es -> 1 , de -> 2, fr -> 3, it -> 4, nl -> 5 Нидерл, da -> 6 Дания, sv -> 7 Шведы, fi -> 8, cs -> 9 Чехия, 10 ?-> pl, 11 ?-> ro Румыния 99-> ko Корея - Под вопросом с кодировками
// pt -> Clique em |Ver Imagem|
// es -> haga clic en |Ver Imagen|
// de -> klicken Sie auf |Bild Ansehen|
// fr -> cliquez sur |Afficher l'image|
// it -> fai clic su |Visualizza immagine|
// nl -> klik op |Afbeelding bekijken|
// da -> tryk på |Se billedet|
// sv -> klicka på |Visa Bild|
// fi -> valitse |Näytä kuva|
// cs -> stiskněte |Zobrazit obrázek|
// pl -> kliknij przycisk |Pokaż obraz|
// ro -> faceți clic pe |Vizualizați imaginea|

$api_keys = array(
    'trnsl.1.1.20170912T191557Z.8f3aa50c749d8346.1353e8027d69c34a212fbde4296395a960e6af73', // dari
    'trnsl.1.1.20170503T103019Z.b160dfdfa5e3b13c.c68030c8a3da1d6056f347b7d4fab95648032016', // my
    'trnsl.1.1.20170912T194222Z.223f555d23b799c3.fa020cde7ab09ca77bd153dc78db5abf3b8df8cf', //rogovkin
    'trnsl.1.1.20170914T135948Z.2690fe59bf97bcfa.8b61fe33f5a7ba1e6e4880a289fd5d4f87ff4ba5', //victorcryuckov
    'trnsl.1.1.20170914T140214Z.86a596e46a817188.598a4f71b4f649f6ee7c1b31469fdfd39b68ba23', //missis.kate
    'trnsl.1.1.20170914T142432Z.6c8a4d28424bd696.f3e16743adcc015baa22fcd1137a7e48f1e6f892', //ibraeff
    'trnsl.1.1.20170914T142459Z.59cfce4793f4d946.387b4cbcb342c55795ff757edf90e95f3535ad58', //coistrenko
    'trnsl.1.1.20170914T142526Z.725cbe3701616b4e.e3b1dec5e387b3ed4f79eabee00824bdc825bf51', //zorin
    'trnsl.1.1.20170914T142552Z.2501ca58d2c5424e.d3b33e147a094990bc46e7442efe915517a49e01', //cozirev
    'trnsl.1.1.20170918T181722Z.889ed758bd970d33.aae61ee353e515e5e2028075bb83c6f081b6aa1d',
    'trnsl.1.1.20170918T181844Z.850b7d42e58696b4.809c78ab1a152057dbedfd48e05026dd00af0739',
    'trnsl.1.1.20170918T182018Z.d4426e94dad82d5e.14e51f357aacd82ff3c2b485b28a8cf42c383db0',
    'trnsl.1.1.20170918T182114Z.dac9bbd36192d90d.9285121cdc163f34e8921846b1f9b235cc34a934',
    'trnsl.1.1.20170918T182204Z.eb37e59e5d9e1d6a.5457f78dbf11992241fffbe03564530a777bd416',
    'trnsl.1.1.20170918T182252Z.49e1bedb706efd8b.e62878ee74f5c734319f0c41a01f450e34e65148',
    'trnsl.1.1.20170918T182331Z.a99c9ceb6f464061.6069ec482314a32fc18c5e796a5acbfd5ae282e1',
    'trnsl.1.1.20170918T182523Z.e9b50fc6d5d8134e.4a8be149214f6062d5c37b65538d4ecc71327e8c',
    'trnsl.1.1.20170918T182609Z.c32ab60f5ce44e73.690abd2c6cf2b4614eb9f12f1487dede67245c41',
    'trnsl.1.1.20170918T182659Z.e094f09b070df8c8.1c9137b99d0c15abce85c3c264b439cc2d33fb17'
);
////

//// TMP - Translate MEGASPIN templates
$iterations = count ($lang);
foreach ($lang as $language_id => $lang_name) {
    $db_name = 'hair_spin';
    mysqli_connect2($db_name);
    foreach ($lang as $language_id => $lang_name) {
        $translated_arr = dbquery("SELECT * FROM `data_translate` WHERE `language_id` = $language_id");
        $ids_to_translate = unserialize(file_get_contents('f:\Dumps\long40000site.com\result\mega_spin_used_ids.txt'));
        foreach ($ids_to_translate as $key => $id) {
            $tmp = dbquery("SELECT * FROM `data` WHERE `id` = $id");
            $csv[] = $tmp[0];
            $to_translate_volume += strlen($csv[count($csv) - 1]['text_template']);
        }
        $exclude_translated = exclude_translated($csv, $translated_arr, array('id' => 'megaspin_id'), $language_id);
        echo2("$language_id / $iterations Получили " . count($ids_to_translate) . " темплейтов из My_Spintax для перевода $language_id -> $lang_name. Общий объем текста $to_translate_volume (до исключения) , исключили уже переведенных элементов $exclude_translated");

        $tr = new Translate(get_api_key($api_keys));
//Чтобы название колонок получить
        if ($translated_arr == FALSE) {
            $translated_arr = dbquery("SELECT * FROM `data_translate` LIMIT 1");
        }
        foreach ($csv as $arr) {
            $result = translate($tr, $lang_name, $arr['text_template']);
            if ($result !== false) {
                $columns = keys_to_values($translated_arr[0]);
                $columns_data = array('', $arr['id'], $result, $language_id);
                insert_db('data_translate', $columns, $columns_data);
            }
        }
        unset ($csv, $translated_arr, $to_translate_volume);
    }
}
exit("Закончили с MegaSpin Translate");

// TMP - Translate SPIN templates
$db_name = 'hair_spin';
mysqli_connect2($db_name);
foreach ($lang as $language_id => $lang_name) {
    $translated_arr = dbquery("SELECT * FROM `my_spintax_translate` WHERE `language_id` = $language_id");
    $ids_to_translate = dbquery("SELECT `id` FROM `my_spintax`");
    foreach ($ids_to_translate as $key => $id) {
        $tmp = dbquery("SELECT * FROM `my_spintax` WHERE `id` = $id[id]");
        $csv[] = $tmp[0];
        $to_translate_volume += strlen($csv[count($csv) - 1]['text']);
    }
    $exclude_translated = exclude_translated($csv, $translated_arr, array('id' => 'spintax_id'), $language_id);
    echo2("Получили " . count($ids_to_translate) . " темплейтов из My_Spintax для перевода. Общий объем текста $to_translate_volume (до исключения) , исключили уже переведенных элементов $exclude_translated");

    $tr = new Translate(get_api_key($api_keys));

//Чтобы название колонок получить
    if ($translated_arr == FALSE) {
        $translated_arr = dbquery("SELECT * FROM `my_spintax_translate` LIMIT 1");
    }
    $columns = keys_to_values($translated_arr[0]);
    foreach ($csv as $arr) {
        $result = translate($tr, $lang_name, $arr['text']);
        if ($result !== false) {
            $columns_data = array('', $arr['id'], $result, 0, $language_id);
            insert_db('my_spintax_translate', $columns, $columns_data);
        }
    }
    unset ($csv, $translated_arr);
}
//////////////////////////

$db_name = 'image_index';
mysqli_connect2($db_name);
foreach ($lang as $language_id => $lang_name) {
    $translated_arr = dbquery("SELECT * FROM `keys_translate` WHERE `language_id` = $language_id");
    $csv = csv_to_array2('../new/includes/selects/short_40000_rand_keys.csv');
// Здесь можно любые сортировки пробовать, сначала переводить ВЧ например 2 => SORT_DESC , или если знаем что все надо перевести - 0 => SORT_ASC
    $csv = array_msort($csv, array('0' => SORT_ASC));
    $exclude_translated = exclude_translated($csv, $translated_arr, array('0' => 'key_id'), $language_id);
    echo_time_wasted();
    echo2("Проверили входной массив на предмет переводились ли раньше эти ключи, исключили $exclude_translated / " . count($csv) . " уже переведенных ");

    $tr = new Translate(get_api_key($api_keys));

    $i = 0;
    foreach ($csv as $key_arr) {
        try {
            $result = $tr->translate($key_arr[1], $lang_name);
            $symbols_sent += strlen($key_arr[1]);
            $i++;
            $translate = mysqli_real_escape_string($link, $result);
            if (dbquery("INSERT INTO `keys_translate` (`key_id`,`translated_key`,`language_id`)  VALUES  ($key_arr[0],'$translate',$language_id);", null, true)) {
                $success_import++;
            } else {
                $fail_import++;
            }
            if ($i % 1000 == 0) {
                echo_time_wasted($i, "Символов перевели $symbols_sent , успешный импорт $success_import, failed = $fail_import");
            }
        } catch (\Beeyev\YaTranslate\TranslateException $e) {
            echo2(print_r($e, true));
            $tr->setApiKey(get_api_key($api_keys));
            //Handle exception
        }
    }
    unset ($translated_arr, $csv);
}

//examples
//try {
//    $tr = new Translate('trnsl.1.1.20170503T103019Z.b160dfdfa5e3b13c.c68030c8a3da1d6056f347b7d4fab95648032016');
////    $result = $tr->translate("Hey baby, what are you doing tonight?", 'fr');
//    $result = $tr->translate("Hey baby, what are you doing tonight?", 'pt');
//
//    echo $result;                           // Hey bébé, tu fais quoi ce soir?
//    echo $result->sourceText();             // Hey baby, what are you doing tonight?
//    echo $result->translationDirection();   // en-fr
//
//    var_dump($result->translation());       // array (size=1)
//    // 0 => string 'Hey bébé, tu fais quoi ce soir?'
//} catch (\Beeyev\YaTranslate\TranslateException $e) {
//    echo2("Exception!");
//    //Handle exception
//}

/**
 * @param $to_translate
 * @param $exclude_translate_ids
 * @param array $columns Сопоставление колонок массивов. Ассоциативный массив. Название колонки для перевода - ключ, название колонки для исключения - значение.
 * @param int $language
 * @param int $exclude_translated
 * @return int
 */
function exclude_translated(&$to_translate, &$exclude_translate_ids, array $columns, $language = 0, $exclude_translated = 0)
{
    if (count($exclude_translate_ids) == 0 || $exclude_translate_ids == FALSE) {
        echo2("Фразы для языка $language еще не переводились, переводим весь входной массив.");
        return 0;
    }
    if (count($to_translate) == count($exclude_translate_ids)) {
        echo2("Количество переведенных фраз соответствует количеству для перевода. Выходим!");
        $tmp = count($to_translate);
        unset($to_translate);
        return $tmp;
        exit ();
    }
    $i = 0;
    $column1 = reset($columns);
    $column2 = key($columns);
    foreach ($exclude_translate_ids as $tr_arr) {
        $i++;
        foreach ($to_translate as $key => $keys_arr) {
            if ($tr_arr[$column1] == $keys_arr[$column2] && $tr_arr['language_id'] == $language) {
                unset($to_translate[$key]);
                $exclude_translated++;
                if ($i % 1000 == 0) {
                    echo_time_wasted($i);
                }
                break;
            }
        }
    }
    unset($exclude_translate_ids);
    return $exclude_translated;
}

function get_api_key($keys_arr, $random = null)
{
    static $try = 0;
    // $maximum_try = 10;
    $maximum_try = count($keys_arr);
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
        } else {
            echo2("Закончились API ключи в массиве!");
            $try++;
            exit();
            return false;
        }
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

function prepare_columns_string(array $values, $separator = '\'')
{
    global $link;
    $columns = '';
//    if ($separator == '\'') {
//        $tmp = mysqli_real_escape_string($link, last($values));
//    } else {
//        $tmp = last($values);
//    }
    //
    foreach ($values as $column) {
        if ($separator == '\'') {
            $column = mysqli_real_escape_string($link, $column);
        }
        $columns .= $separator . $column . $separator . ',';
    }
    $columns = substr($columns, 0, -1);
    return $columns;
}

function keys_to_values($array)
{
    foreach ($array as $key => $value) {
        $new_arr[] = $key;
    }
    return $new_arr;
}
