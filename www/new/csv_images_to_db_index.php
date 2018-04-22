<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.11.2016
 * Time: 1:45
 * На вход данные в CSV (разделитель ;), обработка в 1млн (100 мь) строк прошла без проблем:
 * path    | size (необязательно, никак не обрабатывается) 3d-anatomy-of-human-body-appendix-stock-photos-images-royalty-free-appendix-images-and.jpg 874 678
 * maxfashionco.com\wp-content\uploads\2015\06\Pretty-Nail-Art-Designs-with-Blue-and-Rainbow-Motif-Ideas-174x174.jpg | 55000
 */
include "includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$tmp4 = is_image('f:\Dumps\downloaded sites\anatomycampus.info\wp-content\uploads\2016\01\fotos-de-micose-de-unha.jpg');

//Сравнивать будем по IMG DATA (ширина-высота, тип, каналы-биты) + FILENAME
$load_only_uniq = FALSE; // Если ставить TRUE то загрузка будет очень долгой, по 35 сек на каждые 100 картинок с проверкой каждой на уникальность в пределах базы.

$csv_delimiter = ';'; // Разделитель CSV файла столбцов

$db_name = 'image_index';
$db_table = 'images';
$db_image_theme = 2; //1 - hair / 2 - body
mysqli_connect2($db_name);

$csv_file = 'F:\Dumps\downloaded sites\import_db_humanbody.csv';
$down_sites_path_prefix = "f:\\Dumps\\downloaded sites\\";
// Можно не задавать фильт по имени, просто закомментировав массив.
//$filename_filters = array('hair', 'beard', 'updo', 'graduat', 'brunett', 'bun', 'bang', 'ponytail', 'ombre', 'brunet', 'bob', 'pixie', 'blond', 'layer', 'curl', 'ombre', 'waves', 'straight', 'highlight', 'mohawk', 'fishtail', 'blunt', 'headband', 'pompadour', 'dread', 'waterfall', 'balayage', 'femini','undercut','messy','chopp','asymmet','twist','burgund','length','african','american','older','face','pony','tail','edgy','copper');
$patterns = array('/[0-9]+x[0-9]+/i', '/.+descr\.wd3/i');

if ($images_offset = get_table_max_id($db_table) == FALSE) {
    $images_offset += 1;
} else {
    $images_offset++;
}

$fp = fopen($csv_file, 'r+');

$i = 0;
$q = 1;
while (($data = fgetcsv($fp)) !== FALSE) {
    $tmp = explode($csv_delimiter, $data[0]);
    $img_full_path = $down_sites_path_prefix . '/' . $tmp[0];
    if (($img_filesize = filesize($img_full_path)) == FALSE) {
        $c_broken_img++;
        continue;
    } else {
        $new_img_data = is_image($img_full_path);
    }
    $csv_import = explode("\\", $tmp[0]);
    $img_name = last($csv_import);
    $domain = $csv_import[0];
    //Если еще не было создано этого домена, заливаем домен
    if (($site_id = dbquery("SELECT `site_id` FROM `$db_name`.`image_domains` WHERE `domain` = '$domain';")) == FALSE) {
        if ($csv_import[1] == "wp-content") {
            $wp_prefix = "\\wp-content\\uploads\\";
            $is_wp = 1;
            $site_dir = addslashes($down_sites_path_prefix . $csv_import[0] . $wp_prefix);
            dbquery("INSERT INTO `image_domains` VALUES ('','$csv_import[0]','$site_dir',$is_wp,$db_image_theme);");
        } else {
            $wp_prefix = false; // Дописать позже
            $is_wp = 0;
        }
    }
    if (isset($filename_filters)) {
        foreach ($filename_filters as $filename_filter) {
            $tmp2 = stripos($img_name, $filename_filter);
            if ($tmp2 === 0 || $tmp2 == TRUE) {
                $valid_name = 1;
                break;
            } else {
                $valid_name = 0;
            }
        }
    } else {
        $valid_name = 1;
    }
    if ($valid_name == 1) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $img_name)) {
                $valid_name = 0;
                break;
            }
        }
    }
    if ($valid_name == 1) {
        $site_id = dbquery("SELECT `site_id` FROM `$db_name`.`image_domains` WHERE `domain` = '$domain';");
        $img_path_piece = addslashes(implode("\\", array_slice($csv_import, 3, count($csv_import) - 4))); //На выходе должно быть 2016\\09 например. Если первая цифра в array_splice = 1 ,то переменная с wp-content\uploads, если 3, то без
        if ($load_only_uniq == true) { //Сравнивать будем по IMG DATA (ширина-высота, тип, каналы-биты).
            if (($res = dbquery("SELECT COUNT(*) FROM `$db_table` WHERE `filename` ='$img_name';")) == FALSE) {
                tmp_dbimg_insert();
            } else {
                foreach ($res as $row) {
                    $full_img_path = $down_sites_path_prefix . $row['domain'] . '/' . $row['image_path'] . '/' . $row['filename'];
                    $old_img_data[] = is_image($full_img_path);
                    if (last($old_img_data) == $new_img_data) {
                        $c_double_imgs++; //todo Не протестирован выход из цикла. Нужно возвращаться в самый верх While
                        break;
                    }
                }
                tmp_dbimg_insert();
            }
        } else {
            tmp_dbimg_insert();
        }
    }
    $i++;
    if ($i % 10000 == 0) {
        echo_time_wasted($i);
    }
}
function tmp_dbimg_insert()
{
    global $db_name, $db_table, $site_id, $img_path_piece, $img_name, $img_filesize, $new_img_data, $counter_new_images, $db_image_theme;
    dbquery("INSERT INTO `$db_table` VALUES ('',$site_id,'$img_path_piece','$img_name');");
    $img_id = get_table_max_id($db_table);
    dbquery("INSERT INTO `image_size` VALUES ($img_id,$img_filesize,$new_img_data[0],$new_img_data[1]);");
    $counter_new_images++;
}

echo_time_wasted("Загрузили новых картинок $counter_new_images / $i . Битых картинок $broken_img . Дублей картинок которые уже были в базе $c_double_imgs и мы их не залили");
?>