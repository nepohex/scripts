<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.03.2018
 * Time: 23:29
 *
 * Цель - убить 2 зайцев.
 * 1. Сделать наконец полностью адаптивный дизайн. Текущие NextGen вручную вставлена ширина и при адаптиве слишком мелко.
 * 2. Удалить описания картинок из NextGen (хнык-хнык, уплочены сотни тыс) чтобы попытаться избежать Бадена который уже настал.
 * Алгоритм :
 * 1. Импорт NextGen фоток в базу WP как обычные картинки, ньюансы:
 *      - Всего 32371 фото, из них 9710 имеют уникальные имена. Значит нужно переименование. Варианты:
 *          1. Воссоздать структуру NextGen в папке wp-uploads с папками под каждую галерею как делает NextGen, вместо помесячной стандартной WP.
 *          2. Убить вообще все упоминания NG. Импортнуть по месяцам, переименовать все фотки, например по ID nextgen.
 *          -3. Использовать текущую структуру папок NextGen для импорта фоток в wp_posts. Сохранится история NextGen + не надо будет дублировать фотки. Из минусов - костыли на будущее.
 *              - Не прокатил 3 способ потому что из wp_postmeta
 *      - Импорт делать с ID NextGen добавив вначало единичку. Яндекс индексирует только TITLE (поле ЗАГОЛОВОК, в базе "post_excerpt") фотки.:
 *          1*****, с заголовком и описанием
 *          2*****, без описания
 *          3***** вообще без ничего.
 *
 * Яндекс индексирует только TITLE и обычный текст.
 *
 * WP:
 * post_content = Описание в WP (не идет никуда в обычной галерее, в Tile идет как data-image-description (не смог найти сайтов с этим тегом чтобы понят отношение ПС))
 * post_title = Заголовок (не идет никуда в обычной галерее, в Tile идет как Title)
 * post_excerpt = Подпись (идет текстом и там и там отдельным блоком, но в Tile display NONE изначально)
 *
 * Сейчас в NG:
 * `wp_ngg_pictures`.`description` - длинное описание картинки, сейчас не идет вообще никуда (!)
 * `wp_ngg_pictures`.`alttext` - заголовок фотки, идет в Alt/Title
 *
 * Связки которые делаем:
 * MODE 1 (ниже в коде выбирать) - $offset 100000
 * `wp_ngg_pictures`.`alttext` = post_title
 * `wp_ngg_pictures`.`description` = post_excerpt
 * `wp_ngg_pictures`.`description` = post_content
 *
 * MODE 2 - $offset 200000 - не доделано
 * `wp_ngg_pictures`.`alttext` = post_title
 * `wp_ngg_pictures`.`description` = post_excerpt
 * `wp_ngg_pictures`.`description` = post_content
 *
 * 2. Замена регуляркой всех галерей NG на шорткод галереи для Tile, пример:
 *  [gallery type="rectangular" link="file" ids="43612,43611,43610"]
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_houzz';
mysqli_connect2($dbname['wp']);
$domain = "http://houzz1.ru/";
//$img_dir = 'f:\Dumps\_LK\_rework_gallery_tmp\\';
$img_dir = 'c:\OpenServer\domains\houzz1.ru\\';
$exmpl = unserialize('a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}');

$offset = 100000; //Чтобы картинки в базе были видны
//DEBUG CLEAR
dbquery("DELETE FROM `$dbname[wp]`.`wp_posts` WHERE `ID` > $offset;");
dbquery("DELETE FROM `$dbname[wp]`.`wp_postmeta` WHERE `post_id` > $offset;");

$res = dbquery("SELECT `t1`.`pid`,`t1`.`imagedate`,`t1`.`filename`,`t1`.`description`, `t1`.`alttext`,`t2`.`path` FROM `wp_ngg_pictures` AS `t1` JOIN `wp_ngg_gallery` AS `t2` ON `t1`.`galleryid` = `t2`.`gid`;", 1);
echo2 ("Нашлось ".count($res)." картинок для переноса");
foreach ($res as $row) {
    $ai = $row[0] + $offset;
    $img_path2 = $row[5] . '/' . $row[2]; // OLD PATH relative

    // Папка куда будут складываться файлы в галерею WP
    $tmp = explode("-", $row[1]);
    if ($tmp[0] < '2010') {
        $wp_image_upload_date_prefix = '2010/' . $tmp[1] . '/';
    } else {
        $wp_image_upload_date_prefix = $tmp[0] . '/' . $tmp[1] . '/';
    }

    $new_file_name = str_pad($ai, 6, 0, STR_PAD_LEFT);
    // CropName
    $tmp = explode(".", $row[2]);
    $cropped_img_name = $new_file_name . "-150x150." . $tmp[1];
    $file_extension = $tmp[1];

    if (is_file($img_dir . $img_path2)) {
        $tmp2 = getimagesize($img_dir . $img_path2);
        $array_to_postmeta['width'] = $tmp2[0];
        $array_to_postmeta['height'] = $tmp2[1];
        $array_to_postmeta['file'] = $wp_image_upload_date_prefix . $new_file_name . '.' . $file_extension;
        $array_to_postmeta['sizes']['thumbnail']['file'] = $cropped_img_name;
        $array_to_postmeta['sizes']['thumbnail']['width'] = 150;
        $array_to_postmeta['sizes']['thumbnail']['height'] = 150;
        $array_to_postmeta['sizes']['thumbnail']['mime_type'] = $tmp2['mime'];
        $array_to_postmeta['image_meta'] = $exmpl['image_meta'];

        // NEW PATH URL absolute
        $img_path_abs = $domain . 'wp-content/uploads/' . $array_to_postmeta['file'];
        // NEW PATH URL relative
        $img_path_rel = 'wp-content/uploads/' . $array_to_postmeta['file'];

        //Создание новой структуры папок
        if (!is_file($img_dir . $img_path_rel)) {
            prepare_dir(dirname($img_dir . $img_path_rel));
            copy($img_dir . $img_path2, $img_dir . $img_path_rel);
        }

        //Подготовка описаний - выбрать 1 из вариантов.
        #MODE 1 - индексировать только Заголовок фотки (alttext), описание невидимо для поисковика
        # 100000 offset
        $post_title = @mysqli_real_escape_string($link, strip_tags($row[4]));;
        $post_content = @mysqli_real_escape_string($link, strip_tags($row[3]));
        #MODE 2 - индексировать - не доделано

        $queries[] = "INSERT INTO `$dbname[wp]`.`wp_postmeta` (`meta_id`, `post_id`, `meta_key` ,`meta_value`) VALUES ('',$ai,'_wp_attached_file', '$array_to_postmeta[file]')";
        $queries[] = "INSERT INTO  `$dbname[wp]`.`wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai,  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
        $queries[] = "INSERT INTO `$dbname[wp]`.`wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) 
VALUES (" . $ai . ", 1, '$row[1]', '$row[1]','$post_content', '$post_title', '$post_content', 'inherit', 'closed', 'closed', '', $ai, '', '', '$row[1]', '$row[1]', '', 0 , '$img_path_abs', 0, 'attachment', '$tmp2[mime]', 0);";

        dbquery($queries);
        unset($queries);
    } else {
        $not_file++;
    }
    if ($ai % 1000 == 0) {
        echo_time_wasted($ai, "/ $not_file не файл");
    }
}
