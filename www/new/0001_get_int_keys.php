<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.09.2017
 * Time: 18:45
 * //todo Надо к боевому виду привести, первый шаг после спина и правки базы.
 */
include "multiconf.php";
next_script(0, 1);

$result_fname = 'spin_keys_ids.txt';

if (!(is_file($result_dir . $result_fname))) {
    echo2 ("Файла с сопоставлениями еще нет - пробуем готовить!");
    if (is_file($result_dir . 'posts_spin_data.txt')) {
        $csv2 = unserialize(file_get_contents($result_dir . 'posts_spin_data.txt'));
        echo2("Ищем сопоставления ключей, создаем файл");
        $i = 0;
        foreach ($csv2 as $key => $value) {
            $i++;
            $query = "SELECT `post_name` FROM `$dbname[wp]`.`wp_posts` WHERE `post_parent` = $value[ID];";
            if (($csv2[$key]['key'] = dbquery($query)) == FALSE) {
                $false++;
            }
        }

        $i = 0;
        foreach ($csv2 as $key => $value) {
            $i++;
            $tmp = mysqli_real_escape_string($link, $value['key']);
            $query = "SELECT `key_id` FROM `$dbname[image]`.`keys` WHERE `key` = '$tmp';";
            if (($res = dbquery($query)) == FALSE) {
                $fail++;
            } else {
                $csv2[$key]['key_id'] = $res;
            }
            if ($i % 10000 == 0) {
                echo_time_wasted($i);
            }
        }
        file_put_contents($result_dir . $result_fname, serialize($csv2));
        echo2("Создали файл результата и записали $result_dir/$result_fname");
    } else {
        echo2("Не можем открыть файл $result_dir . posts_spin_data.txt , он обязателен!");
        exit();
    }
} else {
    echo2 ("Файл с сопоставлениями INT ключ-картинка-ID-wp уже готов, движемся дальше!");
}

next_script();
//if (!(is_file($result_dir . $result_dir))) {
//    $csv2 = unserialize(file_get_contents($result_dir . $result_fname));
//    mysqli_connect2('image_index');
//    foreach ($csv2 as $k => $v) {
//        $query = "SELECT `translated_key`,`language_id` FROM `keys_translate` WHERE `key_id` = $v[key_id]";
//        $tmp = dbquery($query);
//        $csv2[$k]['lang'] = $tmp;
//    }
//    file_put_contents($result_dir . $result_fin, serialize($csv2));
//    echo_time_wasted();
//}