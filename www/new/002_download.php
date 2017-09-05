<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.09.2017
 * Time: 14:29
 * Качаем картинки с сайтов + заливаем в папку рабочую
 */
include "multiconf.php";
mysqli_connect2($db_name_img);

next_script(0, 1);

$data = unserialize(file_get_contents($import_dir ."_dirty_". $import_file));
$total_imgs = count($data) * count($data[0]);
echo2("Получили массив с картинками, всего $total_imgs предстоит закачать или найти на локале");

foreach ($data as $key_images) {
    foreach ($key_images as $image) {
        $i++;
        $unique_imgs[] = $image['image_url'];
        if ($file_ext = get_good_filename($image['image_url'])) {
            $filename = $image['image_id'] . "." . $file_ext;
            if (!is_file($global_images_dir . $filename)) {
                $img_data = file_get_contents($image['image_url']);
                if ($img_data) {
//                $img_weight = strlen($img_data);
//                if ($img_weight > $min_img_size && $img_weight < $max_img_size) {
                    $counter_downloaded_data += strlen($img_data);
                    if (file_put_contents($global_images_dir . $filename, $img_data)) {
                        $success_write = TRUE;
                        $counter_written_imgs++;
                    } else {
                        get_bad_domain($image['image_url'], 'cant_write_img');
                        $fail_img = TRUE;
                        $counter_fail_write++;
                    }
//                } else {
//                    $small_or_big++;
//                }
                } else {
                    get_bad_domain($image['image_url'], 'cant_get_img');
                    $fail_img = TRUE;
                    $counter_cant_get_img++;
                }
            } else {
                $success_write = TRUE;
                $counter_already_got_imgs++;
            }
            //Копируем картинки в рабочую папку создаваемого сайта
            if (!isset($fail_img)) {
                if (!isset($img_weight)) {
                    //Качаем большие и маленькие картинки в глобальную папку.
                    if (!isset($img_data)) {
                        $img_weight = filesize($global_images_dir . $filename);
                    } else {
                        $img_weight = strlen($img_data);
                    }
                }
                if ($img_weight > $min_img_size && $img_weight < $max_img_size && $success_write === TRUE) {
                    //Уверены что картинка существует в Глобальной папке
                    $good_imgs[] = $image['image_id'];
                    $counter_imgs_weight += $img_weight;
                    if (!is_file($img_dir . $filename)) {
                        copy($global_images_dir . $filename, $img_dir . $filename) == TRUE ? $f++ : $z++;
                    }
                }
            }
            unset($success_write, $fail_img, $img_data, $img_weight);
        } else {
            get_bad_domain($image['image_url'], 'bad_filename');
        }
    }
}

// Исключаем из массива картинки которые неудачные (не прошли по размеру, не скачались и т.п.), будем использовать как итоговый импорт файл
foreach ($data as $top_key => $top_values) {
    foreach ($top_values as $bot_key => $bot_values) {
        if (!in_array($bot_values['image_id'], $good_imgs)) {
            unset ($data[$top_key][$bot_key]);
        } else {
            $keys_imgs_fin++;
        }
        if (count($data[$top_key]) == 0) {
            unset($data[$top_key]);
        }
    }
}

function get_bad_domain($image_url, $reason)
{
    global $bad_domains;
    $host = parse_url($image_url, PHP_URL_HOST);
    $bad_domains[$reason][$host] += 1;
}

function get_good_filename($image_url)
{
    $tmp = explode(".", $image_url);
    $file_ext = strtok(array_pop($tmp), "?");
    $ext_len = strlen($file_ext);
    if ($ext_len < 3 OR $ext_len > 4) {
        return false;
    } else {
        return $file_ext;
    }
}

file_put_contents($work_file, serialize($data));
$counter_unique_imgs = count(array_unique($good_imgs));
echo_time_wasted(null, "Из $i картинок скачали и сохранили новых в глобальную папку $counter_written_imgs (" . convert($counter_downloaded_data) . "), ранее было $counter_already_got_imgs . Неуспешных: не смогли скачать / не смогли сохранить : $counter_cant_get_img / $counter_fail_write");
echo2("Почистили файл импорта, и сохранили новый только с успешными парами ключ-картинка $keys_imgs_fin , уникальных картинок $counter_unique_imgs весом " . convert($counter_imgs_weight));
echo2("Неудачные картинки и их причины:" . PHP_EOL . print_r($bad_domains, true));