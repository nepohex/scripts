<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 17.05.2018
 * Time: 20:40
 * Генерация маленьких ниш, берем из базы ключи
 * Берутся файлы картинок из базы (предварительно импортнутые), их имена используются как тайтлы.
 * Есть "родительские" картинки, и ключи к ним как отдельные строки.
 * Сделано для экономии места. Например, в анатомии из 100гб распределено на 10гб всего, картинки часто дублируются.
 * Также есть возможность брать не всю базу целиком, а только часть (для этого надо запустить double_images/split_base.php и подготовить файл импорта).
 */
include "config.php";
foreach ($project_dirs as $dir) {
    mkdir2($dir, 1);
}
#####################################
###############CONFIG################
#####################################
define('NO_WORDS_CHECK', TRUE); // не проверять на Bad_words , использовать вместо этого $bad_words2
define('REPLACE_NUMBERS', TRUE); // удалять цифры из названий файлов и как следствие тайтлов
$bad_words2 = array('www' => '', 'http' => '', 'blogspot' => '', 'youtube' => '', 'jpg' => '', 'png' => '', 'jpeg' => '', 'bmp' => '', 'gif' => '', 'p' => '', 'com' => ''); // BAD WORD = KEY, not VALUE
$theme = 3; //Тематика дублей которую берем из базы (2 = human body)
$db_parts = unserialize(file_get_contents(__DIR__ . '/includes/split_DB_items_theme' . $theme . '.txt')); //Разбитая на части база split_base.php
$part = 1; //Часть массива которую берем после разделения базы на количество сайтов скриптом split_base.php
$imgs_path = 'f:\Dumps\google_images\coloring\\'; //Путь к картинкам, привязка к тематике, менять обязательно!
// Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$autocat_exclude_words = array($keyword, $year_to_replace, 'length', 'choose', 'when', 'youtube', 'amp', 'inspir', 'gallery', 'view', 'pic', 'about', 'your', 'idea', 'design', 'hair', 'style', 'women', 'very', 'with', 'picture', 'image', 'pinterest', 'woman', 'tumblr', 'from', 'side', 'pictures', 'ideas', 'style', 'photos');
//Строгое исключение данных слов в качестве категории
$autocat_strict_word_exclude = array('a', 'you', 'it', 'cut', 'to', 'in', 'the', 'on', 'what', 'of', 'for', 'at', 'by', 'is', 'in', 'and', 'do', 'how', 'this', 'that', 'can', 'part', 'new', 'with', 'in', 'can', 'be', 'or', 'as', 'its', 'as', 'an', 'its', 'will', 'by', 'into', 'get', 'cuts', 'over', 'life', 'bring', 'make', 'human', 'body', 'anatomy', 'list', 'many', 'tag', 'all', 'are', 'my', 'their', 'its', 'about', 'color', 'coloring');
$default_cat_name = 'New'; //Название и URL стандартной (1) категории WP, сюда попадет все что не попало в другие категории.
$default_cat_slug = 'new'; // URL категории default (1)
$menu_guid = 299999; //Не трогать
$postmeta_id = 277776; // не трогать
//$debug_data = unserialize(file_get_contents(__DIR__ . "/debug_data/badimgs_$theme" . "_$part" . ".txt"));
$fpfp = 'color_facts_facts.txt';
is_file($includes_dir . $fpfp) ? $facts = file($includes_dir . $fpfp, FILE_IGNORE_NEW_LINES) : '';
$spfp = 'color_facts_texts_spin.txt';
is_file($includes_dir . $spfp) ? $spins = file($includes_dir . $spfp, FILE_IGNORE_NEW_LINES) : exit("Не нашли файл спинов $includes_dir" . $spfp . " Выходим!");

$pwd_log = fopen($includes_dir . '/passwords_log.txt', "a");
$installer_log = fopen($includes_dir . '/installer_command.txt', "a");

echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);

//Генерим данные для хостинга-конфига
mkdir2($work_dir . '/wp-content');
gen_wp_db_conf($site_name, $installer['db_prefix'], $keyword);

$tmp = file_get_contents($includes_dir . $wp_conf_tpl);
file_put_contents($work_dir . '/wp-config.php', '<?php' . PHP_EOL . 'define (\'WPCACHEHOME\', ABSPATH.\'wp-content/plugins/wp-super-cache/\');' . PHP_EOL . 'define(\'DB_NAME\', \'' . $wp_conf_db_name . '\');' . PHP_EOL . 'define(\'DB_USER\', \'' . $wp_conf_db_usr . '\');' . PHP_EOL . 'define(\'DB_PASSWORD\', \'' . $wp_conf_db_pwd . '\');' . PHP_EOL . $tmp);
echo2("Сгенерили wp-config для нового сайта, db_name , db_user , db_pwd : $wp_conf_db_name $wp_conf_db_usr $wp_conf_db_pwd");

$tmp = file_get_contents($includes_dir . $wp_conf_cache_tpl);
file_put_contents($work_dir . '/wp-content/wp-cache-config.php', '<?php' . PHP_EOL . '$cache_path = ABSPATH.\'wp-content/cache/\';' . PHP_EOL . $tmp);

copy($includes_dir . 'wp_instance_052018.zip', $work_dir . '/site.zip');

gen_installer($includes_dir . 'installer_instance.txt', $work_dir . '/installer.php', $installer['db_host'], $installer['db_usr'], $installer['db_pwd'], $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd, 'dump.sql');
echo2("Записываем команду инсталлера в лог __DIR__.'/installer_command.txt");
fwrite($installer_log, $installer['command'] . PHP_EOL);
echo2($installer['command']);
// Закончили

import_db_instance();

//Импорт "фактов" в базу! Вручную, закомментить если не надо.
if (is_array($facts)) {
    foreach ($facts as $fact) {
        $fact = addslashes($fact);
        dbquery("INSERT INTO `$db_name`.`tips` VALUES ('','$fact');");
    }
}
//region STEP 1 #GET KEYS # FILTER # GET TOP WORDS
################ STEP 1 ##################
echo2("Получили на вход родительских ID " . count($db_parts[$part]['ids']) . " начинаем выгружать из базы потомков");
if (!is_file(__DIR__ . '/debug_data/images_part_' . $part . '_theme_' . $theme . '.txt')) {
    $i = 0;
    foreach ($db_parts[$part]['ids'] as $item) {
        $tmp2 = dbquery("SELECT `id`, `old_name`, `new_name`, `size` FROM `$dbname[image]`.`image_doubles_copy` WHERE `id` = $item[0] OR `parent_id` = $item[0] ORDER BY `new_name` DESC;");
        if ($tmp2[0]['new_name'] == FALSE) {
            @$f++;
        } else {
            $images[] = $tmp2;
        }
//    if (count($images) % 5 == 0) { // debug
//        break;
//    }
        $i++;
        $i % 1000 == 0 ? echo_time_wasted($i) : '';
    }
    file_put_contents(__DIR__ . '/debug_data/images_part_' . $part . '_theme_' . $theme . '.txt', serialize($images));
} else {
    echo2("File with parentID already exists! Saving time!");
    $images = unserialize(file_get_contents(__DIR__ . '/debug_data/images_part_' . $part . '_theme_' . $theme . '.txt'));
}
foreach ($images as $item) {
    $tmp3 += count($item);
}
unset ($f, $db_parts);
echo2("Начинаем считать использованные слова в названиях картинок, всего картинок из базы получили $tmp3 записей");
$final = array();
$i = 0;
if (!is_file(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt')) {
    foreach ($images as $tmp3) {
        foreach ($tmp3 as $row) {
            $i++;
//        $tmp = explode('.', $row['old_name']);
//        $tmp4 = count_words($tmp[0], '-');
//        $final = named_arrays_summ($final, $tmp4);

            $tmp = preg_replace('/[^\w\d]/i', ' ', $row['old_name']); //Замена всех не слов пробелами
            $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
            $tmp = trim($tmp);
            $tmp = count_words($tmp, ' ');
            $final = named_arrays_summ($final, $tmp);

            $i % 5000 == 0 ? echo_time_wasted($i) : '';
        }
    }
    file_put_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt', serialize($final));
} else {
    echo2("File with TOPWords already exists! Saving time!");
    $final = unserialize(file_get_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt'));
}
echo2("Топ ключей выводим, по количеству категорий которые планируем создать $cats (еще без учета good/bad)");
echo2(print_r(array_slice($final, 0, $cats), true));
unset ($final);
//endregion


//region STEP 2 #GET BAD WORDS # GET GOOD WORDS #
##################STEP 2 #########################
$words = unserialize(file_get_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt'));

$i = 0;
foreach ($words as $word => $freq) {
    $i++;
    $word = addslashes(strtolower($word));
    if (($tmp = dbquery("SELECT `id` FROM `$dbname[image]`.`dictionary` WHERE `word` = '$word';")) == FALSE) {
        $bad_words[$word] = $freq;
    } else {
        $good_words[$word] = $freq;
    }
    $i % 5000 == 0 ? echo_time_wasted($i) : '';
}
arsort($good_words);
arsort($bad_words);
echo_time_wasted("Посчитали хорошие " . count($good_words) . " и плохие " . count($bad_words) . " слова по словарю");
if (NO_WORDS_CHECK) {
    $bad_words = $bad_words2;
}
unset ($words);
//endregion

//region STEP 3 # CHECK SIZE / CHECK EXIST / UPLOAD IN WP DIP
############## STEP 3 #####################
echo2("Начинаем загружать картинки в IMGDIR");
foreach ($images as $key1 => $parent_img) {
    foreach ($parent_img as $key2 => $child) {
        if ($child['new_name'] !== '') {
            $source_img_path = $imgs_path . $child['new_name']; // Путь к картинке в скачанных директориях
            if (($filesize = @filesize($source_img_path)) !== FALSE) {
                if ($filesize > $min_img_size && $filesize < $max_img_size) {
                    if (!is_file($img_dir . $child['new_name'])) {
                        $tmp = file_get_contents($source_img_path);
                        @$counter_img_filesize_total += file_put_contents($img_dir . $child['new_name'], $tmp);
                        @$counter_file_written++;
                    }
                } else {
                    @$counter_small_files++;
                    $debug_data['bad_size'][$key1][$key2] = $child;
                }
            } else {
                $debug_data['image_not_found'][$key1][$key2] = $child;
            }
        }
    }
}
$counter_img_filesize_total = $counter_img_filesize_total / 1024 / 1024; // Размер в MB картинок

echo2("Из них не прошли по размеру " . $counter_small_files);
echo2("Не были найдены или не картинки " . $counter_image_not_found);
echo2("Файлов которые были записаны в папку " . $counter_file_written . " общим размером " . $counter_img_filesize_total . " MB");
#echo2("Файлов которые ранее использованы на других сайтах (есть инфа о размере в базе) " . $counter_used_images);
#echo2("Сайты-доноры и сколько с них картинок взяли, также сохраняем результаты сюда " . $result_dir . $images_used_stat_filename);
//endregion

//region STEP 4 # IMPORT IN WP POSTS / IMAGES #
########## STEP 4 ############
//Крайне долго идет цикл заполнения! 60000 записей заполняются больше часа! Найти где ошибка или в чем задержка.
//debug to clean if needed
$debug_data['wp_posts'] = get_table_max_id('wp_posts', 'id', $db_name);
$debug_data['wp_postmeta'] = get_table_max_id('wp_postmeta', 'meta_id', $db_name);
$debug_data['wp_term_relationships'] = get_table_max_id('wp_term_relationships', 'object_id', $db_name);
file_put_contents(__DIR__ . "/debug_data/debug_data.txt", serialize($debug_data));
//DELETE
//$debug_data = unserialize(file_get_contents(__DIR__ . "/debug_data/debug_data.txt"));
//dbquery("DELETE FROM `$db_name`.`wp_posts` WHERE `id` > $debug_data[wp_posts];");
//dbquery("DELETE FROM `$db_name`.`wp_postmeta` WHERE `meta_id` > $debug_data[wp_postmeta];");
//if ($debug_data['wp_term_relationships']) {
//    dbquery("DELETE FROM `$db_name`.`wp_term_relationships` WHERE `object_id` > $debug_data[wp_term_relationships];");
//} else {
//    dbquery("DELETE FROM `$db_name`.`wp_term_relationships` WHERE `object_id` > 0;");
//}

$meta_id = get_table_max_id('wp_postmeta', 'meta_id', $db_name) + 1;
$image_id = get_table_max_id('wp_posts', 'ID', $db_name) + 1;
$post_id = $image_id + 1;
$wp_postmeta_start_pos = $meta_id;
$cat_id = get_catid_by_name($db_name, $default_cat_slug);

$i = 0;
$z = 0;
unset ($debug_data);
echo2("Начинаем заполнять таблицу постами");
foreach ($images as $key1 => $parent_img) {
    foreach ($parent_img as $key2 => $child) {
        @$r++;
//        //Находим название родительской картинки => Обязательно должна быть 1ым элементом в массиве!
        if ($img == FALSE) {
            $img = $child['new_name'];
        }

        if ($img !== FALSE) {
            $local_img_path = $img_dir . $img;
            $site_path_dir = '/wp-content/uploads/' . $wp_image_upload_date_prefix . $img;
            $keys = $child['old_name']; //Должно быть с точкой!
//            $tmp = explode('.', $keys); // 0 = название файла , 1 = расширение

            if ($img_info = is_image($local_img_path)) {
                $i++;
                $array_to_postmeta = gen_image_postmeta($local_img_path, $site_path_dir, $img_info);
//                $filtered_fname = tmp_check_filename($bad_words, $bad_symbols, $keys, '-');
                $filtered_fname = tmp_clean_fname($keys, $bad_words, '-', REPLACE_NUMBERS);
                if ($filtered_fname !== FALSE && mb_strlen($filtered_fname) > 10) {
//                    $filtered_fname = explode('.', $filtered_fname); //Hook для функции чтобы не переписывать ее, т.к. она возвращает с расширением файла
                    $gen_title = gen_easy_title($filtered_fname);
                    $post_name = gen_post_name($i, $gen_title, 0, 6);

                    $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attached_file','" . $site_path_dir . "');";
                    $meta_id++;
                    //MetaData без тумбов, не нужна нифига, но можно и оставить. Только базу засирает.
                    //$queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ($meta_id,$image_id,  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
                    $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($image_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','', '" . $gen_title . "', '', 'inherit', 'closed', 'closed', '', '" . $gen_title . "', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', $post_id , '$site_path_dir', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";

                    $post_content = "<img src=\"$site_path_dir\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$array_to_postmeta[width]\" height=\"$array_to_postmeta[height]\" class=\"alignnone size-full wp-image-" . $image_id . "\" />";

                    $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($post_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','" . $post_content . "', '" . $gen_title . "', '', 'publish', 'closed', 'closed', '', '$post_name', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', 0, '/?p=" . $post_id . "', 0, 'post', '', 0);";
//INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
                    $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $cat_id, 0);";

                    dbquery($queries);
                    $meta_id++;
                    $image_id += 2;
                    $post_id += 2;
                    unset ($queries);
                } else {
                    $debug_data['short_name'][$key1][$key2] = $child;
                    @$b++; //Плохое название файла было, и как следствие тайтл и прочее - не заполняем в базу, пропускаем.
                }
            } else {
                $debug_data['not_image'][$key1][$key2] = $child;
                @$f++; //Не найден файл картинки!
            }
        } else {
            $debug_data['no_parent_img_name'][$key1][$key2] = $child;
        }
        $r % 5000 == 0 ? echo_time_wasted($r, " Постов записали $i") : '';
        unset($z);
    }
    unset ($img);
}
echo2("Плохое название файла пропустили картинку или запись ( $b ) раз , не найден файл картинки ( $f ) раз");
echo2("Пропущенные картинки записали в debug_data " . file_put_contents(__DIR__ . "/debug_data/badimgs_$theme" . "_$part" . ".txt", serialize($debug_data)));

unset ($images);
//endregion

############## STEP 5 ###############
# CREATE CATS # CREATE MENU #
# КОД НА СОПЛЯХ ВЗЯЛ СТАРОЕ ГАВНО #

$i = 0;
foreach ($good_words as $word => $value) {
    if ((!in_array($word, $autocat_exclude_words)) && (!in_array($word, $autocat_strict_word_exclude))) {
        $i++;
        $cats_created[] = $word; // Для вывода дальнейшего
        $wp_terms_ids[] = $wp_postmeta_start_pos;
        $queries[] = "INSERT INTO  `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ( " . $wp_postmeta_start_pos . ",  '" . ucwords($word) . "','" . strtolower($word) . "',  '0' );";
        $queries[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (" . $wp_postmeta_start_pos . ", " . $wp_postmeta_start_pos . ", 'category', '', '0', '0');";
        $wp_postmeta_start_pos++;
        $menu_order_counter[] = $i;
        if ($i == $cats) {
            break;
        }
    }
}
dbquery($queries);
unset ($queries, $good_words);

// Начинаем делать меню из этих категорий, внимание ГОВНОКОД на соплях!
$menu_order_counter[] = $i++; // Это мы добавляем количество чтобы еще стандартная 1ая категория тоже пошла по этапу, а не только вновь созданные.
$c = $postmeta_id; // wp_postmeta.meta_id стартовый (+1)
array_unshift($wp_terms_ids, 1);
$menu_guid2 = $menu_guid;
$query_menu[] = "INSERT INTO `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ('" . $menu_guid . "','Mfa_Me_nu','mfa_me_nu','0');"; //Слеша добавлены чтобы MEN категория не определялась
$query_menu[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ('" . $menu_guid . "','" . $menu_guid . "','nav_menu','','0','0');";
$query_menu[] = "INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES (" . $menu_guid . ", 'theme_mods_2017theme', 'a:2:{i:0;b:0;s:18:\"nav_menu_locations\";a:1:{s:7:\"primary\";i:$menu_guid;}}','yes');";
foreach ($menu_order_counter as $num) {
    $wp_posts_menu_id[] = $menu_guid++;
    $query_menu[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . current($wp_posts_menu_id) . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', '', '', 'publish', 'closed', 'closed', ''," . current($wp_posts_menu_id) . ", '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "?p=" . current($wp_posts_menu_id) . "', " . $num . ", 'nav_menu_item', '', 0);";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_type','taxonomy');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_menu_item_parent',0);";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_object_id','" . current($wp_terms_ids) . "');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_object','category');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_target','');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_classes','a:1:{i:0;s:0:\"\";}');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_xfn','');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $c++ . "," . current($wp_posts_menu_id) . ",'_menu_item_url','');";
    $query_menu[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (" . current($wp_posts_menu_id) . "," . $menu_guid2 . ",0);";
    next($menu_order_counter);
    next($wp_posts_menu_id);
    next($wp_terms_ids);
}
dbquery($query_menu);

echo2("Создали категории и меню из них. Список созданных категорий печатаем.");
echo2(print_r($cats_created, true));

############# STEP 6 #############
# AUTO FILL CATS #
# OLD CODE #

$wp_terms = dbquery("SELECT `term_id`, `name` FROM `$db_name`.`wp_terms`;");
$wp_posts = dbquery("SELECT `ID`, `post_title` FROM `$db_name`.`wp_posts` WHERE `post_type` = 'post';");

// Начинаем обход постов и поиск в них названий категорий
$i = 0;
$z = 0;
foreach ($wp_posts as $item) {
    shuffle($wp_terms); //Массив каждый раз с категориями мешаем, чтобы определенной категории не перепадало изначально больше внимания
    foreach ($wp_terms as $term) {
        if (stripos($item['post_title'], $term['name']) && !($wp_new_term[$term['name']] > count($wp_posts) / $max_posts_per_cat)) {
            if ($multicat == true) {
                $wp_posts[$i]['new_term'][] = $term['term_id'];
                $wp_new_term[$term['name']] += 1;
                $z++;
            } else {
                $wp_posts[$i]['new_term'] = $term['term_id'];
                $wp_new_term[$term['name']] += 1;
                $z++;
                break;
            }
        } else if ($term['synonim'] && !($wp_new_term[$term['name']] > count($wp_posts) / $max_posts_per_cat)) {
            foreach ($term['synonim'] as $syn) {
                if (stripos($item['post_title'], $syn)) {
                    if ($multicat == true) {
                        $wp_posts[$i]['new_term'][] = $term['term_id'];
                        $wp_new_term[$term['name']] += 1;
                        $z++;
                    } else {
                        $wp_posts[$i]['new_term'] = $term['term_id'];
                        $wp_new_term[$term['name']] += 1;
                        $z++;
                        break;
                    }
                }
            }
        }
    }
    $i++;
}

arsort($wp_new_term);
echo2(print_r($wp_new_term, true));
echo2("Нашли новые категории, итого распределили по новым категориям ( multicat = TRUE если категорий больше постов )_" . $z . " / " . count($wp_posts) . " _ записей! Начинаем обновлять базу:");

//Обновляем таблицу с категориями, присваиваем посту новую категорию.
foreach ($wp_posts as $item) {
    if ($item['new_term'] && $multicat == false) {
        $query = "UPDATE `wp_term_relationships` SET `term_taxonomy_id` =" . $item['new_term'] . " WHERE `object_id` = " . $item['ID'] . ";";
        dbquery($query);
    } elseif (is_array($item['new_term'])) {
        foreach ($item['new_term'] as $cat_id) {
            $query = "UPDATE `wp_term_relationships` SET `term_taxonomy_id` =" . $cat_id . " WHERE `object_id` = " . $item['ID'] . ";";
            dbquery($query);
        }
    }
}

// Считаем сколько постов в каждой категории получилось, обновляем данные в таблице, чтобы в админке на странице Рубрик были нормальные данные.
// SELECT `term_taxonomy_id`,count(*) FROM `wp_term_relationships` GROUP BY `term_taxonomy_id`
$query = "SELECT `term_taxonomy_id`, COUNT(*) AS `term_count` FROM `wp_term_relationships` GROUP BY `term_taxonomy_id`;";
$wp_terms_count = dbquery($query);
foreach ($wp_terms_count as $item) {
    // Создаем меню из получившихся категорий
    $query = "UPDATE `wp_term_taxonomy` SET `count` = '" . $item['term_count'] . "' WHERE `term_taxonomy_id` = " . $item['term_taxonomy_id'] . ";";
    dbquery($query);
}

//region STEP 7 # PENDING #
########### STEP 7 ###############
# PENDING #
$query = "SELECT `ID` FROM `$db_name`.`wp_posts` WHERE `post_status` = 'publish' AND `post_type` = 'post';";
$post_ids = dbquery($query, 1);
shuffle($post_ids);

$i = 0;
foreach ($post_ids as $ids) {
    $query = "UPDATE `wp_posts` SET `post_status` = 'pending' WHERE `ID` ='" . $ids . "';";
    dbquery($query);
    if ($i > (count($post_ids) * $publish / 100)) {
        break;
    }
    $i++;
}
echo2("Поставили статус PENDING для _ " . (count($post_ids) * $publish / 100) . " _ / " . count($post_ids) . " PUBLISH строк из wp_posts. Если мало, можно запустить еще раз и отправить еще такой же % от текущих PUBLISH в PENDING.");
unset ($post_ids);
//endregion

//region STEP 8 # SPIN TEXT #
########## STEP 8 ##########
# SPIN TEXT #
$tmp = $spins;
echo2("Получили варианты Спина, всего массив из " . count($tmp) . " шаблонов");
$tmp2 = dbquery("SELECT `ID` FROM `wp_posts` WHERE `post_type` = 'post';", 1);
$spintax = new Spintax();
$f = 0;
foreach ($tmp2 as $item) {
    shuffle($tmp);
    $tmp3 = gen_text($spintax, '', '', $tmp[0], 'dgfdgdf', 0); //dgfdgdf - т.к. нету подмены Post Title сейчас, вставлены левые символы
    dbquery("UPDATE `wp_posts` SET `post_content` = CONCAT(`post_content`,'" . addslashes($tmp3) . "') WHERE `ID` = '$item';", 1, 1) ? @$s++ : @$f++;
}
echo2("Закончили со спинами, обновили строк ( $s ) , неуспешно ( $f )");
//endregion

//region MIX & MASH DB
######### STEP 9 ##########
# SHUFFLLE OLD TO NEW random POST ID #
unset ($tmp, $tmp2, $tmp3);
$q1 = "SELECT `ID` FROM `$db_name`.`wp_posts` WHERE `post_type` = 'post' ORDER BY `ID` DESC;";
$res = dbquery($q1);

foreach ($res as $item) {
    $tmp[] = $item['ID'];
}

$tmp2 = $tmp;
asort($tmp2);
for ($i = 0; $i < 100; $i++) {
    shuffle($tmp2);
}

$i = 0;
foreach ($tmp as $key => $item) {
    $i++;
    $tmp3[$i]['old'] = $item;
    $tmp3[$i]['new'] = $tmp2[$key];
}
//UPDATES
$i = 0;
foreach ($tmp3 as $item) {
    $tmp = $item['old'] + 1000000;
    $tmp2 = $item['new'] + 1000000;

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $tmp WHERE `ID` = $item[old];";
    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $tmp2 WHERE `ID` = $item[new];";

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $item[old] WHERE `ID` = $tmp2;";
    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $item[new] WHERE `ID` = $tmp;";

    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $tmp WHERE `object_id` = $item[old];";
    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $tmp2 WHERE `object_id` = $item[new];";

    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $item[old] WHERE `object_id` = $tmp2;";
    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $item[new] WHERE `object_id` = $tmp;";

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `post_parent` = $item[new] WHERE `post_parent` = $item[old] AND `post_type` = 'attachment';"; // Это новые ID постов сюда пойдут

    dbquery($q);
    unset($q);

    $i++;
    $i % 5000 == 0 ? echo_time_wasted($i) : '';
}
//endregion

function import_db_instance()
{

    global $db_usr;
    global $db_pwd;
    global $db_host;
    global $db_instance;
    global $db_name;
    global $site_url;
    global $blogname;
    global $blogdescription;
    global $default_cat_name;
    global $site_name;
    global $default_cat_slug;
    global $pwd_log;
    global $int_mode;

    $link = mysqli_init();

// Соединяемся с базой 1ый раз, создаем ее
    if (@mysqli_real_connect($link, $db_host, $db_usr, $db_pwd, $db_name)) {
        if ($int_mode) {
            change_collation('utf8mb4', 'utf8mb4_unicode_ci', $db_name);
            echo2("INT_MODE = TRUE , меняем db collation каждой таблицы и базы данных на мультиязычную (utf8mb4_unicode_ci)");
        }
        echo2("База уже есть, больше ее не трогаем.");
    } else {
        mysqli_real_connect($link, $db_host, $db_usr, $db_pwd);
        $query = "CREATE DATABASE `" . $db_name . "`;"; // Не знаю почему но почему-то выводит ошибку что уже создана база данных, как ни крути
        if (mysqli_query($link, $query)) {
            echo2("Создали базу данных для работы, теперь ее заполняем " . $db_name);
            mysqli_query($link, "USE `" . $db_name . "`;");
            $templine = '';
            $lines = file($db_instance);
            if ($lines == false) {
                echo2("Не можем найти файл с импортом для таблицы, или пустой файл!");
                exit();
            }
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || $line == '\n')
                    continue;
                $line = str_replace("\n", "", $line);
                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    mysqli_query($link, $templine) or echo2('Ошибка выполнения запроса' . $templine . ': ' . mysqli_error($link));
                    $templine = '';
                }
            }
            $tmp_pwd = pwdgen(14);
            echo2("Пароль для нового сайта $tmp_pwd");
            fwrite($pwd_log, $site_name . PHP_EOL . $tmp_pwd . PHP_EOL);
            $tmp_pwd = md5($tmp_pwd);
            $queries_prepare[] = "UPDATE  `wp_terms` SET  `name` =  '" . $default_cat_name . "', `slug` =  '" . $default_cat_slug . "' WHERE  `wp_terms`.`term_id` =1;";
            $queries_prepare[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (NULL, '1', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '[kwayy-sitemap]', 'Sitemap', '', 'publish', 'closed', 'closed', '', 'sitemap', '', '', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '', '0', '" . $site_url . "?page_id=50', '0', 'page', '', '0');";
            $queries_prepare[] = "UPDATE `wp_users` SET `user_pass` ='" . $tmp_pwd . "' , `user_login` = 'wtfowned' WHERE `id` = 1;";
            $queries_prepare[] = 'UPDATE `wp_options` SET `option_value` =\'' . $site_url . '\' WHERE `option_id` = 1 OR `option_id` = 2;';
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogname . "' WHERE `option_name` = 'blogname';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogdescription . "' WHERE `option_name` = 'blogdescription';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='http://" . $site_name . "/' WHERE `option_name` = 'ossdl_off_cdn_url';";
            $queries_prepare[] = "SELECT `option_value` FROM `wp_options` WHERE `option_name` = 'wpseo'";
            foreach ($queries_prepare as $query_pre) {
                $sqlres = mysqli_query($link, $query_pre);
            }
            $tmp = mysqli_fetch_row($sqlres);
            $ggf = unserialize($tmp[0]);
            $ggf['website_name'] = $blogname;
            $query = "UPDATE `wp_options` SET `option_value` = '" . addslashes(serialize($ggf)) . "' WHERE `option_name` = 'wpseo';";
            mysqli_query($link, $query);
            return echo2("Таблицы в базе данных заполнили!");
        } else {
            echo2("Создать базу данных " . $db_name . " не получилось, либо уже существует " . mysqli_error($link));
            echo2("Чтобы заполнить таблицу, ее нужно удалить и заново запустить скрипт.");
        }
    }
}

function change_collation($charset, $collation, $dbname)
{
    $table_list = dbquery("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`='$dbname' AND `TABLE_TYPE`='BASE TABLE'");
    dbquery("ALTER DATABASE `$dbname` CHARACTER SET $charset COLLATE $collation");
    foreach ($table_list as $table) {
        $table = $table['TABLE_NAME'];
        dbquery("ALTER TABLE `$dbname`.`$table` CONVERT TO CHARACTER SET $charset COLLATE $collation;");
    }
}

/** Проверяет и удаляет плохие слова из названия файла. Плохия слова идут как ключ входного массива.
 * @param array $bad_words Массив с ключом = плохим слово
 * @param array $bad_symbols Массив с левыми символами подлежащими замене в имени файла на другой символ
 * @param $filename
 * @param string $separator Символ Пробел между словами в имени файла
 * @return string
 */
function tmp_check_filename(array $bad_words, array $bad_symbols, $filename, $separator = '_')
{
    $tmp = explode('.', $filename);
    if (count($tmp) == 2) {
        $tmp[0] = str_replace($bad_symbols, $separator, $tmp[0]);
//        $tmp[0] = str_replace($separator, ' ', $tmp[0]); //временно пробелы между словами
    } else if (count($tmp) > 2) {
        $tmp2 = strripos($filename, '.');
        $tmp3 = substr($filename, 0, -$tmp2); // file_name
        $tmp[0] = str_replace($bad_symbols, $separator, $tmp3);
        $tmp[1] = last($tmp);
//        $tmp[0] = str_replace($separator, ' ', $tmp[0]); //временно пробелы между словами
    } else {
        return FALSE;
    }

    foreach ($bad_words as $word => $value) {
        if (isset($tmp2)) {
            if (($pos = stripos($separator . $tmp2 . $separator, $separator . $word . $separator)) !== (0 || FALSE)) {
                $tmp2 = str_replace($word, '', $tmp2);
            }
        } else {
            if (($pos = stripos($separator . $tmp[0] . $separator, $separator . $word . $separator)) !== (0 || FALSE)) {
                if (isset($tmp2)) {
                    $tmp2 = str_replace($word, '', $tmp2);
                } else {
                    $tmp2 = str_replace($word, '', $tmp[0]);
                }
            }
        }
    }

    if ($tmp2) {
        return $tmp2 . '.' . $tmp[1];
    } else {
        return $tmp[0] . '.' . $tmp[1];
    }

}

/** Bad_words => КЛЮЧ массива = плохое слово, а не содержание!
 * @param $fname
 * @param $bad_words
 * @param string $separator
 * @return mixed|string
 */
function tmp_clean_fname($fname, $bad_words, $separator = '_', $replace_numbers)
{
    //5 утра было...
    $tmp = preg_replace('/[^\w\d]/i', ' ', $fname); //Замена всех не слов пробелами
    if ($replace_numbers) {
        $tmp = preg_replace('/\d/', ' ', $tmp); //цифры
    }
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $tmp = trim($tmp);
    $arr = explode(' ', $tmp);
    foreach ($arr as $key => &$item) {
        foreach ($bad_words as $key2 => $bad_word) {
            if (strtolower($item) == strtolower($key2)) {
                $arr[$key] = '';
                break;
            }
        }
    }
    $tmp = implode(' ', $arr);
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp);
    $tmp = trim($tmp);
    $tmp = str_replace(' ', $separator, $tmp);
    return $tmp;
}

/** Проверяет в папке дубли файлов, если есть то заменяет название на filename+++456456454(microtime)+++.jpg
 * @param $dir
 * @param $fname
 * @return string
 */
function tmp_check_double_files($dir, $fname)
{
    // Так сложно все и убого потому что точки могут быть в названии файла
    $tmp = scandir($dir);
    if (in_array($fname, $tmp)) {
//        $tmp = strripos($fname, '.');
//        $tmp2 = substr($fname, $tmp); // file_extension
//        $tmp3 = substr($fname, 0, -$tmp2); // file_name
//        $fname = $tmp3 . '+++' . microtime(true) . '+++' . $tmp2;
        $tmp = explode('.', $fname);
        $fname = $tmp[0] . '+++' . microtime() . '+++.' . $tmp[1];
    }
    return $fname;
}
