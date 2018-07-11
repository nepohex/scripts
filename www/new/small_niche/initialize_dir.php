<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.06.2018
 * Time: 0:22
 * Генерация WP
 *  * На вход нужны предварительные шаги:
 * - Del_crops (Скачанные папки слить в одну, удалить и почистить левые файлы).
 * - Visual Dup полученную в шаге 1 папку.
 * - Check_wh_ratio.php - удалить плохие группы из CSV
 * - split_dir - разбивка папки с исходниками на части.
 *
 */
include "config.php";
foreach ($project_dirs as $dir) {
    mkdir2($dir, 1);
}
#####################################
###############CONFIG################
#####################################
define('NO_WORDS_CHECK', FALSE); // не проверять на Bad_words , использовать вместо этого $bad_words2
define('REPLACE_NUMBERS', TRUE); // удалять цифры из названий файлов и как следствие тайтлов
define('CAT_LENGTH', 3); // минимальное количество символов для названия категории
define('LIMIT_TIME_WORDS_COUNT', 3600); //количество секунд максимум считать хорошие-плохие слова. Например, больше часа (~80000 строк считается) считать нет смысла. Для большой базы в 350к считает около 6 часов!
$cats = 75; // Сколько категорий автоматом создать
$max_posts_per_cat = 30; //20 означает максимум 5% постов в 1 категорию. Если активна Multicat, то лучше разрешить все посты в 1 категорию. По факту, лучше не становится другим категориям от уменьшения больших.
$bad_words2 = array('www' => '', 'http' => '', 'blogspot' => '', 'youtube' => '', 'jpg' => '', 'png' => '', 'jpeg' => '', 'bmp' => '', 'gif' => '', 'p' => '', 'com' => ''); // BAD WORD = KEY, not VALUE
$theme = 4; //Тематика дублей которую берем из базы (2 = human body)
$part = 3; //Номер папки (начиная с 1) из которой будем наполнять
##### Важно чтобы папка соответствовала до символа папке в которой прогонялся CSV (диск - с большой буквы, в конце - слеш)
$imgs_path_parent = 'F:\tmp\_tmp\\'; //Путь к картинкам, привязка к тематике, менять обязательно!
####
$imgs_path = $imgs_path_parent . 'pt' . $part;
$csv_path = 'f:\tmp\_tmp\pt3\test_data\test.csv'; //Подготовленный после проверки WH_RATIO!
$fpfp = 'color_facts_facts.txt'; //Путь фактов
is_file($includes_dir . $fpfp) ? $facts = file($includes_dir . $fpfp, FILE_IGNORE_NEW_LINES) : '';
$spfp = 'color_facts_texts_spin.txt'; //Путь спинов
is_file($includes_dir . $spfp) ? $spins = file($includes_dir . $spfp, FILE_IGNORE_NEW_LINES) : exit("Не нашли файл спинов $includes_dir" . $spfp . " Выходим!");
is_file($csv_path) ? '' : echo2("Не найден CSV от Visual Dups ! Выходим.") . exit();
is_dir($imgs_path) ? '' : echo2("Не найдена папка с картинками! $imgs_path !") . exit();
// Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$autocat_exclude_words = array($keyword, $year_to_replace, 'length', 'choose', 'when', 'youtube', 'amp', 'inspir', 'gallery', 'view', 'pic', 'about', 'your', 'idea', 'design', 'hair', 'style', 'women', 'very', 'with', 'picture', 'image', 'pinterest', 'woman', 'tumblr', 'from', 'side', 'pictures', 'ideas', 'style', 'photos');
//Строгое исключение данных слов в качестве категории
$autocat_strict_word_exclude = array('a', 'you', 'it', 'cut', 'to', 'in', 'the', 'on', 'what', 'of', 'for', 'at', 'by', 'is', 'in', 'and', 'do', 'how', 'this', 'that', 'can', 'part', 'new', 'with', 'in', 'can', 'be', 'or', 'as', 'its', 'as', 'an', 'its', 'will', 'by', 'into', 'get', 'cuts', 'over', 'life', 'bring', 'make', 'human', 'body', 'anatomy', 'list', 'many', 'tag', 'all', 'are', 'my', 'their', 'its', 'about', 'color', 'coloring');
$default_cat_name = 'New'; //Название и URL стандартной (1) категории WP, сюда попадет все что не попало в другие категории.
$default_cat_slug = 'new'; // URL категории default (1)
$menu_guid = 299999; //Не трогать (за исключением когда базы очень большие)
$postmeta_id = 277776; // не трогать (за исключением когда базы очень большие)

//region Не меняем, стандарт
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

import_db_instance();
//endregion

//region Импорт "фактов" в базу! Вручную, закомментить если не надо.
if (is_array($facts)) {
    foreach ($facts as $fact) {
        $fact = addslashes($fact);
        dbquery("INSERT INTO `$db_name`.`tips` VALUES ('','$fact');");
    }
}
//endregion

//region Подсчет и вывод исходных данных
$files = scandir($imgs_path);
echo2("Просканировали папку, найдено файлов " . count($files));
$csv = csv_to_array2($csv_path, ",", null, true);
$tmp = count_dup_values_multidim_arr($csv, '0');
echo2("Файлов в папке " . count($files) . " . Дублей В CSV " . count($csv) . " . Групп " . count($tmp) . " Спинов " . count($spins) . " Фактов " . count($facts));

for ($i = 0; $i < 100; $i++) {
    shuffle($files);
}
//endregion

//region Считаем все использованные слова
//Если задан лимит по времени подсчета, и задана фильтрация по Bad_names, то лимит игнорируем - надо найти все плохие слова!
echo2("Начинаем считать использованные слова в названиях картинок");
$final = array();
$i = 0;
if (!is_file(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt')) {
    foreach ($files as $item) {
        $i++;
        $tmp = preg_replace('/[^\w\d]/i', ' ', $item); //Замена всех не слов пробелами
        $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
        $tmp = trim($tmp);
        $tmp = count_words($tmp, ' ');
        $final = named_arrays_summ($final, $tmp);

        $i % 5000 == 0 ? echo_time_wasted($i) : '';
        if (NO_WORDS_CHECK && LIMIT_TIME_WORDS_COUNT) {
            if (!isset($limit_start_time)) {
                $limit_start_time = number_format(microtime(true) - $start);
            }
            if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
                echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
                break;
            }
        }
        //todo Шаг надо упразднить вообще. Это временное прерывание.
        if (LIMIT_TIME_WORDS_COUNT) {
            if (!isset($limit_start_time)) {
                $limit_start_time = number_format(microtime(true) - $start);
            }
            if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
                echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
                break;
            }
        }
    }
    file_put_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt', serialize($final));
} else {
    echo2("File with TOPWords already exists! Saving time!");
    $final = unserialize(file_get_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt'));
}
echo2("Топ ключей выводим, по количеству категорий которые планируем создать $cats (еще без учета good/bad)");
echo2(print_r(array_slice($final, 0, $cats), true));
unset ($final, $limit_start_time);
//endregion

//region Good/Bad Words
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

    if (NO_WORDS_CHECK && LIMIT_TIME_WORDS_COUNT) {
        if (!isset($limit_start_time)) {
            $limit_start_time = number_format(microtime(true) - $start);
        }
        if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
            echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
            break;
        }
    }
    //todo временно
    if (LIMIT_TIME_WORDS_COUNT) {
        if (!isset($limit_start_time)) {
            $limit_start_time = number_format(microtime(true) - $start);
        }
        if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
            echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
            break;
        }
    }
}
arsort($good_words);
arsort($bad_words);
echo_time_wasted("Посчитали хорошие " . count($good_words) . " и плохие " . count($bad_words) . " слова по словарю");
if (NO_WORDS_CHECK) {
    $bad_words = $bad_words2;
}
unset ($words, $limit_start_time);
//endregion

//region WP FILL: FILES + POSTS
//Дикий код! 2 раза дублируется функция вставки в WP.
$meta_id = get_table_max_id('wp_postmeta', 'meta_id', $db_name) + 1;
$image_id = get_table_max_id('wp_posts', 'ID', $db_name) + 1;
$post_id = $image_id + 1;
$wp_postmeta_start_pos = $meta_id;
$cat_id = get_catid_by_name($db_name, $default_cat_slug);

$i = 0;
$z = 0;
unset ($debug_data);
echo2("Начинаем заполнять таблицу постами");
foreach ($files as $img) {
    $z++;
    $full_path = $imgs_path . '/' . $img;
    $full_path_inCsv = $imgs_path_parent . $img; //Путь по которому прогонялся Visual Dup, еще до перемещения в новую папку (pt1/2/3 etc)
    $debug['is_file1'] += debug_process_time();
    if (is_file($full_path)) {
        $debug['is_file1'] += debug_process_time();
        //Находим ID массива с дублем в CSV
        $debug['multidim1'] += debug_process_time();
        $tmp2 = multidim_arr_search_value($csv, $full_path_inCsv, 1); // 1 = номер колонки, где содержится FileName (полный путь)
        $debug['multidim1'] += debug_process_time();
        //Если есть дубли в CSV
        if ($tmp2 !== FALSE) {
            //Получили ID группы
            $group_id = $csv[$tmp2]['0'];
            //Получить ID массивов данной группы
            $debug['array_column1'] += debug_process_time();
            $tmp3 = array_column($csv, 0); //0 - номер колонки = группа
            $debug['array_column1'] += debug_process_time();
            $debug['array_keys1'] += debug_process_time();
            $tmp3 = array_keys($tmp3, $group_id); //ID массивов CSV Файлов с дублями
            $debug['array_keys1'] += debug_process_time();
            //Работа с группой файлов-дублей. Внимание! Dimension в CSV может быть указан неверно, ориентироваться на размер!
            //Находим самую большую картинку в группе
            foreach ($tmp3 as $key => $tmp2) {
                if ($top_size < $csv[$tmp2][2]) {
                    $top_size = $csv[$tmp2][2]; // Не забыть потом Unset чтобы группа не пересеклась с другой группой! (Далее размер еще используется при импорте)
                    $top_id = $tmp2;
                    $top_key = $key;
                }
            }
            //Удаляем сначала этот ID из группы
            unset ($tmp3[$top_key]);
            //Добавляем первым в массив группы
            array_unshift($tmp3, $top_id);

            foreach ($tmp3 as $tmp2) {
                if (isset($trigger)) { //Идентификатор первой, родительской картинки для группы.

                } else {
                    $trigger = TRUE;
                    //Отменяем сразу всю группу, если самый большой (который единственный будет скопирован) файл группы не подходит по параметрам
                    if ($top_size > $min_img_size && $top_size < $max_img_size) {
                        $img_name = basename($csv[$tmp2][1]);
                        $parent_img_full_path = $imgs_path . '/' . $img_name;
                        $site_path_dir = '/wp-content/uploads/' . $wp_image_upload_date_prefix . $img_name;
                        $debug['copy'] += debug_process_time();
                        copy($full_path, $img_dir . $img_name);
                        $debug['copy'] += debug_process_time();
                        @$counter_file_written++;
                        @$c_group_imgs++;
                        @$counter_img_filesize_total += $top_size;
                    } else {
                        @$counter_small_files++;
                        break;
                    }
                }
                $keys = basename($csv[$tmp2][1]); //Реальный текущий файл. Должно быть с точкой
                $debug['is_image'] += debug_process_time();
                if ($img_info = is_image($parent_img_full_path)) {
                    $debug['is_image'] += debug_process_time();
                    $array_to_postmeta = gen_image_postmeta($parent_img_full_path, $site_path_dir, $img_info);
                    if (NO_WORDS_CHECK) {
                        $debug['clean_fname'] += debug_process_time();
                        $filtered_fname = tmp_clean_fname($keys, $bad_words2, '-', REPLACE_NUMBERS);
                        $debug['clean_fname'] += debug_process_time();
                    } else {
                        $debug['dictionary_check'] += debug_process_time();
                        $filtered_fname = dictionary_check($keys, '-', REPLACE_NUMBERS);
                        $debug['dictionary_check'] += debug_process_time();
                    }
                    if ($filtered_fname !== FALSE && mb_strlen($filtered_fname) > 10) {
                        $debug['gen_and_post'] += debug_process_time();
                        $gen_title = gen_easy_title($filtered_fname);
                        $post_name = gen_post_name($i, $gen_title, 0, 6);
                        //Пропускаем картинку если в этой группе уже есть точно такой же тайтл
                        $group_trigger_titles = count($group_titles);
                        $group_titles[] = $gen_title;
                        if (count(array_unique($group_titles)) > $group_trigger_titles) {
                            $i++;
                            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attached_file','" . $site_path_dir . "');";
                            $meta_id++;
                            //Example ARR a:1:{s:64:"/wp-content/uploads/2018/06/11b7666cceb23e98468bf0d5357a40c5.jpg";i:59562;}
                            //$tmp = unserialize('a:1:{s:64:"/wp-content/uploads/2018/06/070bec7e6499449609a6cc5a6459f426.jpg";i:205420;}');
                            //Ключ - адрес картинки, значение - ID в базе.
                            $yoast_image_arr[$site_path_dir] = $image_id;
                            $yoast_image_arr = serialize($yoast_image_arr);
                            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $post_id . ",  '_yoast_wpseo_post_image_cache','" . $yoast_image_arr . "');";

                            $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($image_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','', '" . $gen_title . "', '', 'inherit', 'closed', 'closed', '', '" . $gen_title . "', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', $post_id , '$site_path_dir', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";

                            $post_content = "<img src=\"$site_path_dir\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$array_to_postmeta[width]\" height=\"$array_to_postmeta[height]\" class=\"alignnone size-full wp-image-" . $image_id . "\" />";

                            $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($post_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','" . $post_content . "', '" . $gen_title . "', '', 'publish', 'closed', 'closed', '', '$post_name', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', 0, '/?p=" . $post_id . "', 0, 'post', '', 0);";
//INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
                            $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $cat_id, 0);";

                            dbquery($queries);
                            $meta_id++;
                            $image_id += 2;
                            $post_id += 2;
                            @$p++;
                            unset ($queries, $yoast_image_arr);
                        } else {
                            //Удаляем последний не уникальный элемент
                            array_pop($group_titles);
                        }
                        $debug['gen_and_post'] += debug_process_time();
                    }
                } else {
                    $debug['is_image'] += debug_process_time();
                    @$counter_image_not_found++;
                }
            }
            unset ($top_size, $trigger, $group_titles);
            //Удаляем все файлы группы после ее закачки
            $debug['unlink2'] += debug_process_time();
            foreach ($tmp3 as $tmp2) {
                $img_name = basename($csv[$tmp2][1]);
                @unlink($imgs_path . '/' . $img_name);
                unset ($csv[$tmp2]);
            }
            $debug['unlink2'] += debug_process_time();
        } else {
            $local_img_path = $full_path;
            $site_path_dir = '/wp-content/uploads/' . $wp_image_upload_date_prefix . $img;
            $keys = $img; //Должно быть с точкой
            if ($img_info = is_image($local_img_path)) {
                $i++;
                $filesize = @filesize($local_img_path);
                if ($filesize > $min_img_size && $filesize < $max_img_size) {
                    copy($full_path, $img_dir . $img);
                    @$counter_file_written++;
                    @$c_uniq_imgs++;
                    @$counter_img_filesize_total += $filesize;
                } else {
                    @$counter_small_files++;
                    continue;
                }
                $array_to_postmeta = gen_image_postmeta($local_img_path, $site_path_dir, $img_info);
                if (NO_WORDS_CHECK) {
                    $filtered_fname = tmp_clean_fname($keys, $bad_words2, '-', REPLACE_NUMBERS);
                } else {
                    $filtered_fname = dictionary_check($keys, '-', REPLACE_NUMBERS);
                }
                if ($filtered_fname !== FALSE && mb_strlen($filtered_fname) > 10) {
                    $gen_title = gen_easy_title($filtered_fname);
                    $post_name = gen_post_name($i, $gen_title, 0, 6);

                    $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attached_file','" . $site_path_dir . "');";
                    $meta_id++;
                    //Example ARR a:1:{s:64:"/wp-content/uploads/2018/06/11b7666cceb23e98468bf0d5357a40c5.jpg";i:59562;}
                    //$tmp = unserialize('a:1:{s:64:"/wp-content/uploads/2018/06/070bec7e6499449609a6cc5a6459f426.jpg";i:205420;}');
                    //Ключ - адрес картинки, значение - ID в базе.
                    $yoast_image_arr[$site_path_dir] = $image_id;
                    $yoast_image_arr = serialize($yoast_image_arr);
                    $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $post_id . ",  '_yoast_wpseo_post_image_cache','" . $yoast_image_arr . "');";

                    $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($image_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','', '" . $gen_title . "', '', 'inherit', 'closed', 'closed', '', '" . $gen_title . "', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', $post_id , '$site_path_dir', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";

                    $post_content = "<img src=\"$site_path_dir\" alt=\"$gen_title\" title=\"$gen_title\" width=\"$array_to_postmeta[width]\" height=\"$array_to_postmeta[height]\" class=\"alignnone size-full wp-image-" . $image_id . "\" />";

                    $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($post_id, 1, '2018-05-18 00:05:53', '2018-05-18 21:05:53','" . $post_content . "', '" . $gen_title . "', '', 'publish', 'closed', 'closed', '', '$post_name', '', '', '2018-05-18 00:05:53', '2018-05-18 21:05:53', '', 0, '/?p=" . $post_id . "', 0, 'post', '', 0);";
//INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
                    $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $cat_id, 0);";

                    dbquery($queries);
                    $meta_id++;
                    $image_id += 2;
                    $post_id += 2;
                    @$p++;
                    unset ($queries, $yoast_image_arr);
                } else {
                    @$b++; //Плохое название файла было, и как следствие тайтл и прочее - не заполняем в базу, пропускаем.
                }
            } else {
                @$f++; //Не найден файл картинки!
            }
        }
    } else {
        $debug['is_file1'] = debug_process_time();
        @$counter_image_not_found++;
    }
    $debug['unlink'] = debug_process_time();
    @unlink($full_path);
    $debug['unlink'] = debug_process_time();
    $z % 10000 == 0 ? echo_time_wasted($z) : '';
}
echo2("Debug time " . print_r($debug, TRUE));
echo2("Плохое название файла пропустили картинку или запись ( $b ) раз , не найден файл картинки ( $f ) раз");
echo2("Циклов $z. Постов ( $p ). Закачали $c_uniq_imgs уникальных и $c_group_imgs групповых картинок. Всего $counter_file_written файлов записали весом " . convert($counter_img_filesize_total));
echo2("Не прошли по размеру картинки и группы - $counter_small_files .");

//endregion

//region STEP 5 #CREATE CATS # CREATE MENU #
############## STEP 5 ##############
$i = 0;
//Нужно для того чтобы preg_grep сработал, работает по значениям а не по ключам. У нас же слова = ключи.
foreach ($good_words as $word => $value) {
    $cat_words[] = $word;
}

foreach ($good_words as $word => $value) {
    if ((!in_array($word, $autocat_exclude_words)) && (!in_array($word, $autocat_strict_word_exclude)) && (mb_strlen($word) >= CAT_LENGTH)) {
        $reg = "/^$word" . '[a-z]{0,3}$/i'; //Привязка к началу и концу строки, чтобы дубли и множественные числа выпадали.
        $matches = preg_grep($reg, $cat_words);
        if (count($matches) > 1) {
            unset ($good_words[$word]);
        }
    } else {
        unset ($good_words[$word]);
    }
}
foreach ($good_words as $word => $value) {
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
dbquery($queries);
unset ($queries, $good_words, $cat_words);

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
//endregion

//region # AUTO FILL CATS #
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
//endregion

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
    $tmp = str_replace('_', ' ', $tmp); //Замена Нижних подсчеркиваний (underscore) потому как \w\d не воспринимает!
    if ($replace_numbers) {
        $tmp = preg_replace('/\d/', ' ', $tmp); //цифры
    }
    $tmp = trim($tmp);
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $arr = explode(' ', $tmp);
    foreach ($arr as $key => &$item) {
        foreach ($bad_words as $key2 => $bad_word) {
            if (strtolower($item) == strtolower($key2)) {
                $arr[$key] = '';
                break;
            }
        }
    }
    if (count($arr) > 0) {
        $tmp = implode($separator, $arr);
        return $tmp;
    } else {
        return FALSE;
    }
}

/** На вход строка, проверка по словарю каждого слова, удаление лишних, и склейка слов сепаратором.
 * @param $string
 * @param string $separator
 * @param $replace_numbers
 * @return bool|mixed|string
 */
function dictionary_check($string, $separator = '_', $replace_numbers)
{
    global $dbname;
    $tmp = preg_replace('/[^\w\d]/i', ' ', $string); //Замена всех не слов пробелами
    $tmp = str_replace('_', ' ', $tmp); //Замена Нижних подсчеркиваний (underscore) потому как \w\d не воспринимает!
    if ($replace_numbers) {
        $tmp = preg_replace('/\d/', ' ', $tmp); //цифры
    }
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $tmp = trim($tmp);
    $arr = explode(' ', $tmp);
    foreach ($arr as $key => &$word) {
        if (($tmp2 = dbquery("SELECT `id` FROM `$dbname[image]`.`dictionary` WHERE `word` = '$word';")) == FALSE) {
            unset ($arr[$key]);
        }
    }
    if (count($arr) > 0) {
        $tmp = implode($separator, $arr);
        return $tmp;
    } else {
        return FALSE;
    }
}

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
