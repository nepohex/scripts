<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.04.2018
 * Time: 19:00
 */
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$tmp = csv_to_array2('F:\tmp\tmp_celebs.csv');

$celebs = __DIR__ . '/fin/result_names.txt';
$celebs = file($celebs, FILE_IGNORE_NEW_LINES);

$celebs = __DIR__ . '/fin/result.txt';
$celebs = unserialize(file_get_contents($celebs));

echo2("Селебов " . count($celebs) . " строк (ключей) " . count($tmp));
foreach ($tmp as $row) {
    $i += 1;
    foreach ($celebs as $key => $name) {
        $name2 = implode(' ', array_reverse(explode(' ', $key))); //Меняем слова местами
        $tmp2 = stripos($row[0], $key);
        $tmp3 = stripos($row[0], $name2);
        if ($tmp2 !== FALSE || $tmp3 !== FALSE || $tmp2 === 0 || $tmp3 === 0) {
            $keys_celeb[$i] = $row;
            $keys_celeb[$i][] = implode(',', $name['tag']);
            $trigger = TRUE;
            break;
        }
    }
    if ($trigger == FALSE) {
        $keys_not_celeb[] = $row;
    }
    unset ($trigger);

    if ($i % 100 == 0) {
        echo_time_wasted($i);
        array_to_csv(__DIR__ . '/debug_data/celeb_autorecognition_tmp.csv', $keys_celeb, false, false, 'a+');
    }
}
array_to_csv(__DIR__ . '/debug_data/celeb_autorecognition_fin.csv', $keys_celeb, false, false, 'w');
echo_time_wasted();
echo "fin";