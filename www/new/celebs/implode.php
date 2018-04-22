<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 20.04.2018
 * Time: 20:10
 */
include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

##result
$result = __DIR__ . '/fin/result.txt';
$result = file_get_contents($result);
$result = unserialize($result);
##

$white_list_names = file(__DIR__ . '/debug_data/whitelistnames.txt', FILE_IGNORE_NEW_LINES);
$tmp = scandir(__DIR__ . '/debug_data/ancensored');
$bad_items = array('..', '.', 'log.txt', 'tmpdata.txt');
$tmp = array_diff($tmp, $bad_items);
$result = array();

foreach ($tmp as $tmp2) {
    $tmp3 = file_get_contents(__DIR__ . '/debug_data/ancensored/' . $tmp2);
    $tmp3 = unserialize($tmp3);
    foreach ($tmp3 as $key => &$value) {
        $value['pop'] = $value[0];
        $value['tag'] = $value[1];
        unset($value[0], $value[1]);
    }
    $result = array_merge_recursive($result, $tmp3);
}
$i = 0;
echo2("Всего получилось знаменитостей " . count($result));
foreach ($result as $key => &$value) {
    $i++;
    if (strlen($key) > 5 AND strpos($key, ' ') AND stripos($key, 'unknown') === FALSE) {
        asort($value['tag']);
        if (count(array_intersect(array('hollywood', 'model', 'singer-musician', 'video-vixen'), $value['tag'])) > 0) {
            $topcelebs[$key] = $value;
            if (is_array($topcelebs[$key]['pop'])) {
                unset($topcelebs[$key]['pop']);
                $topcelebs[$key]['pop'] = $value['pop'][0];
            }
        }
        if (is_array($value['pop'])) {
            $tmp = $value['pop'][0];
            unset($value['pop']);
            $value['pop'] = $tmp;
        }
    } else if (!in_array($key, $white_list_names)) {
        $clean[$i][] = $key;
        $clean[$i][] = $value['pop'][0];
        unset($result[$key]);
    } else if (in_array($key, $white_list_names)) {
        asort($value['tag']);
        $topcelebs[$key] = $value;
        if (is_array($topcelebs[$key]['pop'])) {
            unset($topcelebs[$key]['pop']);
            $topcelebs[$key]['pop'] = $value['pop'][0];
        }
    }
}
echo2("После чистки однословных и коротких имен до 5 символов + unknown осталось" . count($result));
echo2("Топ категории 'hollywood', 'model', 'singer-musician', 'video-vixen' " . count($topcelebs));

foreach ($result as $key => $value) {
    $names[] = $key;
}
$tmp = implode(PHP_EOL, $names);
file_put_contents(__DIR__ . '/fin/result_names.txt', $tmp);
##

foreach ($topcelebs as $key => $value) {
    $name[] = $key;
}
$tmp = implode(PHP_EOL, $name);
file_put_contents(__DIR__ . '/fin/result_top_names.txt', $tmp);

$topcelebs = array_msort($topcelebs, array('pop' => 'SORT_DESC'));
$result = array_msort($result, array('pop' => 'SORT_DESC'));

file_put_contents(__DIR__ . '/fin/result.txt', serialize($result));
file_put_contents(__DIR__ . '/fin/result_top_celebs.txt', serialize($topcelebs));
array_to_csv(__DIR__ . '/debug_data/clean_names.csv', $clean, 0, 0, 'w');