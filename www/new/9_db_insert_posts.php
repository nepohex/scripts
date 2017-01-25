<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.11.2016
 * Time: 2:00
 * INSERT не будет обрабатываться если уже существует запись в таблице, ограничение таблиц по primary key.
 * Скрипт:
 * 1. Получает данные по картинкам из wp_postmeta (получает width / height картинки)
 * 2. На их основе генерит содержимое для wp_posts
 * 3. Вставляет все в wp_posts
 * 4. Чтобы не было ошибок также обновляется таблица wp_term_relationships в категорию 1.
 */
//include "multiconf.php";
include "123_conf_debug_config.php";
mysqli_connect2();
$start = microtime(true);
echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);

// Забираем в Массив Wp-postmeta чтобы получить размеры картинок
$query = "SELECT * FROM `wp_postmeta` WHERE `meta_key` = '_wp_attachment_metadata';";
$sqlres = mysqli_query($link, $query, MYSQLI_USE_RESULT);
//echo "Обрабатываем SELECT wp_postmeta , получили строк _ ".mysqli_affected_rows($link)." _ <br>" ; flush();
$i = 0;
while ($row = mysqli_fetch_assoc($sqlres)) {
    $wp_postmetas[] = $row;
    $wp_postmetas[$i]['meta_value'] = unserialize($row['meta_value']);
    $i++;
}
$counter_postmetas = count($wp_postmetas);
echo2("Получен массив из таблицы wp_postmeta , строк _ " . $counter_postmetas);
//Забираем в массив wp_posts.post_title чтобы использовать для заголовка, URL, Alt картинки поста.
$query = "SELECT * FROM `wp_posts` WHERE `post_type` = 'attachment';";
$sqlres = mysqli_query($link, $query, MYSQLI_USE_RESULT);
mysqli_error($link);
//echo "Обрабатываем SELECT wp_posts , получили строк _ ".mysqli_affected_rows($link)." _ <br>" ; flush();
$i = 0;
while ($row = mysqli_fetch_assoc($sqlres)) {
    $post_attach[$i]['ID'] = $row['ID'];
    $post_attach[$i]['post_title'] = $row['post_title'];
    $tmp = explode(".", $row['post_name']);
    $post_attach[$i]['post_name'] = $tmp[0];
    $post_attach[$i]['guid'] = $row['guid'];
    $i++;
}
$counter_post_attach = count($post_attach);
echo2("Получен массив из таблицы wp_posts , строк _ " . $counter_post_attach);

//Сопоставляем и создаем единый массив со всеми нужными данными
$i = 0;
$z = 0;
foreach ($wp_postmetas as $metas) {
    foreach ($post_attach as $post) {
        if ($wp_postmetas[$i]['post_id'] != $post_attach[$z]['ID']) {
            $z++;
        } else {
            //Здесь IF это таймер т.к. скрипт может выполняться долго
            if ($i % 500 == 0) {
                echo_time_wasted($i);
            }
            $wp_postmetas[$i]['post_title'] = $post_attach[$z]['post_title'];
            $wp_postmetas[$i]['post_name'] = $post_attach[$z]['post_name'];
            $wp_postmetas[$i]['guid'] = $post_attach[$z]['guid'];
            $z = 0;
            break;
        }
    }
    $i++;
}
unset ($post_attach);
//Заполняем базу постами, а также обновляем таблицу wp_term_relationships сразу, т.к. будет ошибка WP без ее обновления. Все пишем в категорию 1 - стандартную WP.
$counter_insert_wp_posts = 0; //Считает сколько строк удачно закинули в базу
$counter_insert_wp_term_relationships = 0;
foreach ($wp_postmetas as $item) {
    // Готовим содержимое будущего поста, пример:
    // <img src="http://mh_parse.loc/wp-content/uploads/2016/11/5230_medium-length-hair-styles-layered-hair-styles-55deb08e15624.jpg" alt="Medium Length Hairstyles 2017" title="Medium Length Hairstyles 2017" width="1024" height="1137" class="alignnone size-full wp-image-5124" />
    $post_content = "<img src=\"" . $item['guid'] . "\" alt=\"" . $item['post_title'] . "\" title=\"" . $item['post_title'] . "\" width=\"" . $item['meta_value']['width'] . "\" height=\"" . $item['meta_value']['height'] . "\" class=\"alignnone size-full wp-image-" . $item['post_id'] . "\" />";
    // Должно получиться нечто : INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (10000, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', 'Medium Length Layered Haircut With Bangs', '', 'publish', 'closed', 'closed', '', '5126_medium-length-layered-haircut-with-bangs', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, 'http://mh_parse.loc/?p=10000', 0, 'post', '', 0);
    $query_wp_posts = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . $post_guid . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','" . $post_content . "', '" . $item['post_title'] . "', '', 'publish', 'closed', 'closed', '', '" . $item['post_name'] . "', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "?p=" . $post_guid . "', 0, 'post', '', 0);";
    dbquery($query_wp_posts);

    //INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
    $query_wp_term_relationships = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (" . $post_guid . ", 1, 0);";
    $sqlres = mysqli_query($link, $query_wp_term_relationships);
    dbquery($query_wp_term_relationships);

    //Update post parent для Attachment.
    $query_update = "UPDATE `wp_posts` SET `post_parent` = $post_guid WHERE `id` = " . $item['post_id'] . ";";
    dbquery($query_update);
    $post_guid++;
}
//echo2("Обработали INSERT wp_posts , закинули строк _ " . $counter_insert_wp_posts . " _ из _ " . $counter_postmetas);
//echo2("Обработали INSERT wp_relationships , закинули строк _ " . $counter_insert_wp_term_relationships . " _ из _ " . $counter_postmetas);
echo2("Закончили со скриптом " . $_SERVER['SCRIPT_FILENAME'] . " Переходим к NEXT");
echo_time_wasted();
next_script($_SERVER['SCRIPT_FILENAME']);
?>