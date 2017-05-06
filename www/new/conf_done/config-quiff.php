<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 25.11.2016
 * Time: 0:35
 */
ini_set('error_reporting', 0); // Нужно чтобы авторедирект работал нормально между файлами
$debug_mode = 0; // 1 = debug , 0 = not. Регулирует вывод в Лог файл (бой), дебуг - обычный вывод.

$start = microtime(true);
include "includes/spin_tpls.php";
include "includes/functions.php";

//Обязательные к изменению функции от сайта к сайту
$site_name = 'quiffhaircut.xyz'; // Без слешей, только домен
$keyword = "quiff";
$blogname = ucwords("Outstanding Quiff Hairstyle Ideas 2017");
$blogdescription = $blogname.". Over 500 best mens quiff hairstyles in single place.";

//HOSTING DATA для инсталлера и корректных конфигов Wp super cache
$wp_conf_db_prefix = 'wtfowned_';
//Это путь к плагину который идет в wp-config
$wpcachehome = '/home/wtfowned/web/' . $site_name . '/public_html/wp-content/plugins/wp-super-cache/';
//Это путь куда будет складываться кеш
$wp_cache_dir = '/home/wtfowned/web/' . $site_name . '/public_html/cache/';
$installer_db_host = 'localhost';
$installer_db_usr = 'root';
$installer_db_pwd = 'RABCkgt0rKhF';

$mega_spin = 1; // Запуск SPIN из шаблонов дополнительной базы, если есть таблицы с текстами под разные картинки, выбираться будут по маске.

// Основные функции с которыми можно "играться" и менять от сайта к сайту
$images_per_site = 2000; // Сколько картинок брать на 1 сайт (без учета их размера, еще может сильно сократиться, обычно на 20% в итоге выходит)
$gen_addings = 1; // 1 = только ВЧ популярные фразы вначале добавляются к Title (переменная $uniq_addings), 2 - только нч берутся, 3 - все.
$posts_spintext_volume = 300; // Количество символов спинтакс текста
$cats = 25; // Сколько категорий автоматом создать
$image_title_max_strlen = 90; // Максимальное количество символов в длине названии картинки, примерно 1/3 базы с очень длинными уникальными названиями которые невозмжно уникализировать или сократить cute-hairstyle-for-medium-length-hair-2016-cute-hairstyles-for-medium-length-hair-tutorial-short-haircuts-1.jpg
$image_title_min_strlen = 10;
$only_uniq_img = false; // Если True то из CSV файла выгрузки из базы картинок возьмем только те которые имеют уникальные тайтлы. Хорошо опробовать на "больших" категориях. В Short ключе например 25% отсекается сразу.
$min_img_size = 40000; // размер в байтах картинки минимальный
$seasonal_add = true; // Будем к Title дописывать год, ниже % скольки тайтлам
$seasonal_titles = 5; // Кратно этой цифре каждому тайтлу будет присвоен $year_to_replace вконце / начале. 5 = 20%, 3 = 33% и т.п.
$year_end_percent = 75; // Сколько годовых тайтлов допишется вконец. 75 = 75% в конец.
$publish = 50; // % постов от текущих PUBLISH сколько отправляем в PENDING
$multicat = true; // Каждому посту будет присвоено больше 1ой категории. Больше упор на ВЧ запросы получается из за бредкрампов.
$max_posts_per_cat = 10; //20 означает максимум 5% постов в 1 категорию. Если активна Multicat, то лучше разрешить все посты в 1 категорию. По факту, лучше не становится другим категориям от уменьшения больших.
$write_used_images = true; // Записывает использованные для ниши картинки (ключа), и дает возможность использовать "добивку" ниши по неиспользованным картинкам. Может замедлять работу скрипта.
$take_only_unused_images = true; //Если запустить 2ой раз в нише с активной $write_used_images , то будут взяты только неисползованные картинки для перебора.

//Диры
$work_dir = 'F:\Dumps\\' . $site_name; // Пока нигде не использовано
$start_script = '0_initialize.php';
$scripts_chain = array('00_initialize.php', '01_db_img_index_select.php', '02_csv_random_split.php', '03_copy_file.php', '04_generate_thumbs.php', '05_wp_import_images.php', '06_wp_check_uniq_titles.php', '07_kk_titles_choose.php', '08_choose_uniq_title.php', '09_insert_db_new_titles.php', '10_db_insert_posts.php', '11_wp_auto_suggest_category.php', '12_choose_category.php', '13_pending_posts.php', '14_spinner.php', '15_sql_export.php'); // Какой скрипт за каким следует
$big_res_to_split = $keyword . "_images.csv"; // Для вычленения отсюда необходимого количества позиций для 1 сайта
$import_file = $keyword . "_images_" . $images_per_site . '_rand_lines.csv';
$kk_import_file = $keyword . "_kk.csv";

$crop_width = 150; //Стандартная ширина тумба для темы нашей medhairs
$crop_height = 150;
$max_doubles = 100; //Лимит подсчета дублей заголовков картинок из базы. Если картинка (она же - тайтл) будет иметь простое название чтобы не бегать по всей базе и не счиать что количество таких картинок тысячи - это бессмысленно. Нам достаточно чтобы их было больше 10 неуникальных на самом деле для генерации/замены TITLE.
$unset_kk_doubles = true; // True = больше уникальности, меньше прессинга на ВЧ запросы. Может быть False. Если True, то из массива KK сразу удаляем все строки с тайтлами картинок, чтобы они не задублировались лишний раз. Экспериментальная функция.
$unset_all_doubles = true; // FALSE = Будем удалять только те строки которые в Title повторяются больше чем $limit_uniq раз. TRUE - Удаляем все повторы вообще тайтлов вне зависимости насколько они уникальны в пределах нашего сайта.
$limit_uniq = 2; // От скольки повторов заголовков начинать искать другой вариант. 2 значит от 2 дубля в базе, ищем замену. Чем больше число, тем быстрее работает скрипт, тем менее уникальный сайт.
$default_cat_name = 2017; //Название и URL стандартной (1) категории WP, сюда попадет все что не попало в другие категории.
$default_cat_slug = 2017; // URL категории default (1)
$before_spin_html = '<div class="text-content">'; //В эти теги будем заключать сгенерированный текст для каждого поста
$after_spin_html = '</div>';
$spin_fragments_separator = '<br>'; //Между генереными текстами разных шаблонных предложений ставим сепаратор

//Основные директории проекта и пути
$site_url = 'http://' . $site_name . '/';
$site_uploads_path = 'http://' . $site_name . '/wp-content/uploads/';
$wp_image_upload_date_prefix = '2016/12/';

//Пути и то что можно не трогать-не менять
$result_dir = $work_dir . "\\result\\";
$import_dir = $work_dir . "\\import\\";
$selects_dir = 'includes/selects';
$work_file = $import_dir . $import_file;
$img_dir = $work_dir . '/wp-content/uploads/' . $wp_image_upload_date_prefix;
$img_crop_dir = $img_dir . "crop\\"; // Более не используется.
//$kk_file = $import_dir.$kk_import_file;
$kk_file = $selects_dir . '/' . $kk_import_file; // Пока что лежит в корне скрипта, в последствии в импорт добавлять будем
$fp_log = $result_dir . 'log.txt';

// База данных Wordpress
$db_instance = 'includes/db_instance.sql'; // Пустая база данных с таблицами Wordpress, которая будет создаваться каждый раз для нового сайта. Лежать будет пока в папке со скриптом.
$db_usr = 'root';
$db_name = $site_name;
// База данных с картинками
$db_name_img = 'image_index';
$db_host = 'localhost';
$db_pwd = '';
$wp_conf_tpl = 'wp_conf_empty.txt';
$wp_conf_cache_tpl = 'wp-cache-conf_empty.txt';
// База со спинами
$db_name_spin = 'hair_spin';

// Загоняем в массив чтобы создать все диры функцией
$project_dirs = array(
    $work_dir,
    $result_dir,
    $import_dir,
    $img_dir,
);

$image_words_separator = "_"; // Между словами в названии картинок вставляем. Здесь можно любой символ персональный задать
$replace_symbols = array('.', '_', 'min', 'eleganthairstyles', 'hairstyleceleb', 'inethair', 'hairstylesmen', 'hairstylerunew', 'hairvintage', 'hairstyleswell', 'upload', 'hairstyleceleb', 'stylebistro', 'maomaotxt', 'aliexpress', 'dailymotion', 'maxresdefault', 'stayglam', 'shorthairstyleslong', 'thehairstyler', 'that39ll', 'consistentwith', 'harvardsol', 'amp', 'dfemale', 'herinterest', 'iataweb', 'men39s', 'tumblr', 'deva', 'thumbs', 'women39s', 'page', 'blog', 'ngerimbat', 'hair1', 'hairstylehub', 'hairjos', '+', 'jpg', 'jpeg', 'png', 'gif', 'bmp', '-', '!', '-min', '$', '%', '^', '&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия
$bad_symbols = array('39s', '$', '%', '^', '&', '(', ')', '=', '+', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '.', '"', '  '); //Заменим эти символы в имени файла на пробелы
$wp_postmeta_start_pos = 100; //Лучше не трогать. Начальный ID для загрузки в базу данных картинок, будет также использован для автокатегорий
$post_guid = 10000; //Лучше не трогать. Стартовый POST_ID /?p= который будет заливаться в WP
$menu_guid = 99999; //Не трогать

$clean_variants = true; // Если TRUE значит удаляем из первоначального массива все лишнее чтобы облегчить итоговый файл и увеличить скорость

//Результаты работы скриптов для дальнейшего исполнения
$images_used_stat_filename = "sites_images_used.txt"; // Сюда запишем результаты с какого сайта в итоге сколько картинок взяли
$res = '3_serialized_array_uniq_titles.txt'; // Куда сохраним результат работы скрипта, сколько уникальных у нас картинок-тайтлов к ним.
$res2 = '4_serialized_array_kk_titles_variants.txt'; // Результирующий файл с вариантами для новых тайтлов
$res3 = '5_new_titles_fin.txt'; // Сюда положим результат работы, содержащий ID для Wordpress картинок, и их новый Title которые нужно Update
$autocat_analyse = "words_used.txt"; //Сюда запишем какие слова использованы в наших тайтлах за исключением переменной $autocat_exclude , по этим данным создадим категории

//Переменные для уникализации
//Это слова которые будут использоваться для добавления вначале заголовка для уникализации тех тайтлов которые не уникальны
$filter_words = array('hairstyles', 'hairstyle ', 'haircuts', 'haircut ', ' hair ', ' for ', ' hairs '); // Слова которые будем заменять на регулярку при поиске, чтобы расширить семантику
$uniq_addings = array(' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' cute ', ' cute ', ' cute ', ' cute ', ' cute ', ' cute ', ' easy ', ' easy ', ' easy ', ' easy ', ' easy ', ' easy ', ' natural ', ' natural ', ' natural ', ' natural ', ' natural ', ' natural ', ' best ', ' best ', ' best ', ' best ', ' best ', ' best ', ' new ', ' new ', ' new ', ' cool ', ' cool ', ' cool ', ' cool ', ' quick ', ' quick ', ' quick ', ' latest ', ' latest ', ' latest ', ' formal ', ' formal ', ' formal ', ' pretty ', ' popular ', ' modern ', ' nice ', ' trendy ', ' teens ', ' elegant ', ' trending ', ' hot ', ' everyday ', ' really ', ' really quick ', ' really easy ', ' really simple ', ' really nice ', ' really cool ', ' unique ', ' fast ', ' classic ', ' young ', ' fancy ', ' stylish ', ' awesome ', ' chic ', ' romantic ', ' sexiest ', ' gorgeous ', ' red carpet ', ' celebrity red carpet ', ' lazy ', ' easy lazy ', ' cute lazy ', ' overnight ', ' coolest ', ' cutest ', ' attractive ', ' youth ');
$uniq_addings_nch = array(' casual ', ' everyday ', ' super ', ' retro ', ' fancy ', ' mature ', ' stylish ', ' public ', ' hipster ', ' goddess ', ' perfect ', ' fifties ', ' hottest ', ' famous ', ' bohemian ', ' amazing ', ' romantic ', ' creative ', ' instagram ', ' mexican ', ' gorgeous ', ' ebony ', ' spanish ', ' sixties ', ' glamorous ', ' feminine ', ' ghetto ', ' easy lazy ', ' european ', ' glam ', ' recent ', ' gypsy ', ' universal ', ' sixteen ');
//Здесь аккуратней с 2-3 буквенными словами, или придется вручную удалять категории потом, что наверное даже лучше
$year_pattern = "/(201[0-9])/"; //Находим в заголовках год, чтобы его заменить
$year_to_replace = 2017; // Год на который меняем
$autocat_exclude_words = array($keyword, $year_to_replace, 'length', 'choose', 'when', 'youtube', 'amp', 'inspir', 'gallery', 'view', 'pic', 'about', 'your', 'idea', 'design', 'hair', 'style', 'women', 'very', 'with', 'picture', 'image', 'pinterest', 'woman', 'tumblr', 'from', 'side', 'pictures', 'ideas', 'style'); // Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$autocat_strict_word_exclude = array('a', 'you', 'it', 'cut', 'to', 'in', 'the', 'on', 'what', 'of', 'for', 'at', 'by', 'is', 'in', 'and', 'do', 'how', 'this', 'that', 'can', 'part', 'new', 'with', 'in', 'can', 'be', 'or', 'as', 'its', 'as', 'an', 'its', 'will', 'by'); //Строгое исключение данных слов в качестве категории

// Синонимы названий категорий. Важно первым элементом использовать существующую категорию из WP, иначе не сработает
$synonyms[] = array('mens', 'men', 'guy', 'boy', 'guys', 'man');
$synonyms[] = array('fine', 'thick', 'thin');
$synonyms[] = array('black', 'african', 'american');
$synonyms[] = array('trend', 'latest', 'new', 'trendy');
$synonyms[] = array('layered', 'layer', 'layers');
$synonyms[] = array('blond', 'blonded', 'blonde');
$synonyms[] = array('braid', 'braided', 'bridal', 'braids');
$synonyms[] = array('curls', 'curly', 'curled');
$synonyms[] = array('girl', 'girls');
$synonyms[] = array('medium', 'mid', 'shoulder');
$synonyms[] = array('updo', 'updos');
$synonyms[] = array('color', 'colors', 'colored');
