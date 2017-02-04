<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.02.2017
 * Time: 1:55
 * Тестируем сколько для какого ключа есть шаблонов. На примере Highlights , 8450 строк.
 * Нашлось 1450 TPLS с этим ключом в h3 / img_alt.
 */
include "123_conf_debug_config.php";
mysqli_connect2($db_name_spin);
$keyword = "burgundy";
$csv_fp = 'f:\Dumps\burgundyhair.info\import\burgundy_images_2000_rand_lines.csv';
$preg_tpls_replace = array('  ', ' .* .* ', ' .* ', ' .*', '.* ', '.*.*');
$csv_arr = csv_to_array($csv_fp);
$excluded_spin_words = array_merge($filter_words, $uniq_addings, $uniq_addings_nch, $autocat_exclude_words);
$excluded_spin_words = array_map('trim', $excluded_spin_words);
$excluded_spin_words = array_unique($excluded_spin_words);
$excluded_spin_words = array_push($excluded_spin_words, 'burgundy', 'plum');
foreach ($autocat_strict_word_exclude as $tmp) {
    $predlogi[] = ' ' . $tmp . ' ';
}
foreach ($csv_arr as $line) {
    $tmp = str_ireplace($excluded_spin_words, ".*", $line[4]); //Удаляем основные слова, меняем на шаблон.
    $tmp = str_ireplace($predlogi, ".*", $tmp); //Предлоги и прочая хрень.
    $tmp = str_ireplace($preg_tpls_replace, '.*', $tmp);
    $tmp = str_replace("\"", '', $tmp);
    $posts_tpls[] = '/(.)*' . $tmp . '(.)*/i';
}
unset($csv_arr);
$query = "SELECT `id`,`h3`,`img_alt`,`avg_len` from `data` WHERE `h3` LIKE '%$keyword%' OR `img_alt` LIKE '%$keyword%';";
$mega_spin_tpls = dbquery($query);
// Начинаем поиск постов подходящих под регулярку.
$i = 0;
$p = 0;
foreach ($posts_tpls as $tpl) {
    $i++;
    if ($i % 50 == 0) {
        echo2 ("идем по строк $i, уже нашлось $success совпадений. Итераций шаблонов было $p");
        echo_time_wasted();
    }
    shuffle($mega_spin_tpls);
    foreach ($mega_spin_tpls as $mega_spin_tpl) {
        $p++;
        if (preg_match($tpl, $mega_spin_tpl['h3']) | preg_match($tpl, $mega_spin_tpl['img_alt'])) {
            $success++;
            $used_tpls[$mega_spin_tpl['id']] +=1;
            break;
        }
    }
}
arsort($used_tpls);
print_r2($used_tpls);
echo $success;