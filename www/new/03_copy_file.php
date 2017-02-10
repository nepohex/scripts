<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.11.2016
 * Time: 23:01
 * #1
 */
include "multiconf.php";
next_script (0,1);

mysqli_connect2($db_name_img);
$fp = fopen($result_dir.$log_file,"a");

echo2 ("Папка куда будем закачивать фотки ".$img_dir." c минимальным размером в ".$min_img_size." байт");

$db_domains = dbquery("SELECT * FROM `domains`");

$csv = array_map('str_getcsv', file($work_file));
if (!$csv) {
    die("Не удалось открыть CSV файл в рабочей директории! Должен быть по пути ".$work_file);
}
$i=0;
// Из цикла должен выйти массив, где будет полный адрес картинки, например ['fullpath'] F:/Dumps/downloaded sites/cutediyprojects.com/wp-content/uploads/2015\04\Wavy-Hairstyle-For-Medium-Length-Hair.jpg
foreach ($csv as $csvstring) {
    $r = explode(";",$csvstring[0]);
    foreach ($r as $rr) {
        $csv_new[$i][] = trim($rr);
    }
    foreach ($db_domains as $db_domain) {
        if ($csv_new[$i][1] == $db_domain['site_id']) {
            $csv_new[$i]['site_dir'] = $db_domain['site_dir'];
            break;
        }
    }
    $jpgpath = $csv_new[$i]['site_dir'].trim($csv_new[$i][2],"\"")."\\".$csv_new[$i][3];
    $csv_new[$i]['fullpath'] = $jpgpath;
    $i++;
}
unset($csv,$rr,$r,$jpgpath,$csvstring,$db_domains,$db_domain);
$i = 0;

$counter_img = count($csv_new);
echo2 ("CSV импорта обработали, загрузили в массив _".$counter_img." _ строк, начинаем проверять на размер и импортировать в папку");
$counter_image_not_found = 0 ; // Счетчик сколько картинок не нашлось по ссылкам из базы, файлов нет таких
foreach ($csv_new as $filepath) {
    if (is_file(end($filepath)) && $image_data[$i]['getimagesize'] = getimagesize(end($filepath))) {
        $image_data[$i]['filesize'] = filesize(end($filepath));
        $image_data[$i]['id'] = $filepath[0];
        $filepath[4] = str_replace(" ", $image_words_separator, trim(str_replace($bad_symbols, " ", $filepath[4])));
        switch ($image_data[$i]['getimagesize']['mime']) {
            case 'image/jpeg':
                $filepath[4] .= ".jpg";
                break;
            case 'image/gif':
                $filepath[4] .= ".gif";
                break;
            case 'image/png':
                $filepath[4] .= ".png";
                break;
        }
        if ($image_data[$i]['filesize'] > $min_img_size) {
            $img_new_fullpath = $img_dir . $i . '_' . $filepath[4];
            if (file_exists($img_new_fullpath)) {
                //$fname[] = $i . '_' . $filepath[4];
                $counter_file_existed += 1;
            } else {
                $z = file_get_contents(end($filepath));
                $fpath_paste = file_put_contents($img_new_fullpath, $z);
                //$fname[] = $i . '_' . $filepath[4];
                $counter_file_written += 1;
            }
            $counter_img_filesize_total += $image_data[$i]['filesize'];
        } else {
            $counter_small_files += 1;
        }
        if ($i % 500 == 0) {
            echo_time_wasted($i);
        }
        $img_source_site[$filepath['site_dir']] += 1;
        $i++;
    } else {
        $counter_image_not_found++;
    }
}
echo2 ("Собрали данные по картинкам, обновим базу FILESIZE / W / H на будущее...");
$counter_used_images = 0; // Переменная-счетчик, если она обновляется значит картинку ранее на сайтах использовали (узнаем это потому что в базе есть инфа о ее размере в байтах)
foreach ($image_data as $insert) {
    if ($insert['getimagesize']) {
        $query = "INSERT INTO `image_size` VALUES (".$insert['id'].",".$insert['filesize'].",".$insert['getimagesize'][0].",".$insert['getimagesize'][1].");";
        $sqlres = mysqli_query($link,$query);

        if (mysqli_error($link)) {
            //echo2 (mysqli_error($link)); // Чтобы не захламлять LOG записями DUPLICATE ENTRY
            $counter_used_images++;
        }

    } else   {
        $query = "INSERT INTO `image_size` VALUES (".$insert['id'].",".$insert['filesize'].",'','');";
        $sqlres = mysqli_query($link,$query);
        if (mysqli_error($link)) {
            //echo2 (mysqli_error($link));
            $counter_used_images++;
        }
    }
}
$counter_img_filesize_total = $counter_img_filesize_total/1024/1024; // Размер в MB картинок

//echo2 ("Итого обработали _".$counter_img."_ первоначальных файлов. ");
echo2 ("Из них не прошли по размеру ".$counter_small_files);
echo2 ("Не были найдены или не картинки ".$counter_image_not_found);
echo2 ("Файлов которые были записаны в папку ".$counter_file_written." общим размером ".$counter_img_filesize_total." MB");
echo2 ("Файлов которые ранее использованы на других сайтах (есть инфа о размере в базе) ".$counter_used_images);
echo2 ("Сайты-доноры и сколько с них картинок взяли, также сохраняем результаты сюда ".$result_dir.$images_used_stat_filename);
arsort($img_source_site);
file_put_contents($result_dir.$images_used_stat_filename,print_r($img_source_site,true));
next_script ();