<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 20.09.2017
 * Time: 0:35
 * //todo При создании и заполнении категорий не исключается главное ключевое слово под которое делался сайт, надо сделать как uniq_tpls чтобы получались.
 */
//debug
/// ########### NOT FIN ###############
ini_set('error_reporting', E_ALL); // Нужно чтобы авторедирект работал нормально между файлами
$debug_mode = 1; // 1 = debug , 0 = not. Регулирует вывод в Лог файл (бой), дебуг - обычный вывод.
$double_log = 1;

$start = microtime(true);
include "includes/spin_tpls.php";
include "includes/unicalization_tpls.php";
include "includes/functions.php";

//Обязательные к изменению функции от сайта к сайту
$site_name = 'mfa_humanbody.com'; // Без слешей, только домен
$db_name = $site_name;
$keyword = "long";
$blogname = ucwords("Human body anatomy diagrams collection");
$blogdescription = $blogname . ". Biggest Anatomy Organ Pictures Database.";

//HOSTING DATA для инсталлера и корректных конфигов Wp super cache
$installer['site_owner'] = 'mounter'; //Логин пользователя владельца файлов сайта на хосте.
$installer['db_prefix'] = 'mounter_';
$installer['site_path'] = '/home/mounter/web/'; // depr
//Это путь к плагину который идет в wp-config.php
//$installer['wpcachepluginpath'] = $installer['site_path'] . $site_name . '/public_html/wp-content/plugins/wp-super-cache/'; // depr
//Это путь куда будет складываться кеш > важно чтобы было синхронно с HTACCESS, идет в wp-content/wp-cache-config.php
//$installer['cache_dir'] = $installer['site_path'] . $site_name . '/public_html/wp-content/cache/'; //depr
//Непосредственно под каким юзером будем логиниться и создавать базу данных, импортировать дамп.
$installer['db_host'] = 'localhost';
$installer['db_usr'] = 'root';
$installer['db_pwd'] = 'NXbZViruS7fLmV6';
// cd /home/wtfowned/web/mileycyrushair.site/public_html/;unzip -o -q site.zip;php installer.php;rm -f installer.php site.zip;chown -R  wtfowned /home/wtfowned/web/mileycyrushair.site/;
$installer['command'] = 'cd ' . $installer['site_path'] . $site_name . '/public_html/;unzip -o -q site.zip;php installer.php;rm -f installer.php site.zip;chown -R ' . $installer['site_owner'] . ' ' . $installer['site_path'] . $site_name . ';';

$mega_spin = 0; // Запуск SPIN из шаблонов дополнительной базы, если есть таблицы с текстами под разные картинки, выбираться будут по маске.
/** @google_images_mode int
 *
 * По обычной схеме идет выборка Select keyword images -> spin image names + keywords (use downloaded other site images containing keyword).
 * По факту уникальных пар ключ-картинка 52к (не уник - 205к) + маска паттернов, может больше в несколько раз. 94% имеющихся ключей не имеют картинок!
 *
 * Google Images Mode = 1 Выборка идет из ключей которые выгружены из всех источников (Semrush / Google Webmaster) , далее по ним был парсинг 100 картинок под каждый ключ.
 * По такой схеме потенциал ключей и их картинок - 900к ключей * 100 картинок к каждой.
 * */
$google_images_mode = FALSE; // 1 / TRUE. Режим гугл картинок.
$position_limit = 20; // Сколько картинок на ключ брать, по сути будет $images_per_site * $position , далее удалятся невалидные.
$limit_imgs_per_key = 2; // Например, если делаем 5000 картинок на сайт, по 50 позиций вынимаем под каждый ключ, то данная настройка вынимает рандомные N из этих 50.

$int_mode = TRUE; //Режим других языков! Переключатель, если FALSE то Language_id не работает.
/** Запускает поверх уже сгенереного сайта методом google_images_mode цепочку которая накладывает все имеющиеся языковые версии.
 * @var $multi_int_mode
 */
$multi_int_mode = TRUE; // Режим мультиязыков. Переключатель нужен чтобы цепочку запустить правильную.
$lang_id = 0; // Если $int_mode = FALSE , не учитывается.

$tname = array(
    'spintax' => 'my_spintax',
    'spintax_tr' => 'my_spintax_translate',
    'megaspin' => 'data',
    'megaspin_tr' => 'data_translate',
    'images' => 'images',
    'keys' => 'keys',
    'keys_tr' => 'keys_translate'
);

$dbname = array(
    'spin' => 'hair_spin',
    'image' => 'image_index',
    'keys' => 'image_index',
    'key' => 'image_index',
    'wp' => $db_name,
);

// Основные функции с которыми можно "играться" и менять от сайта к сайту
$images_per_site = 40000; // Сколько картинок брать на 1 сайт (без учета их размера, еще может сильно сократиться, обычно на 20% в итоге выходит)
$gen_addings = 3; // 1 = только ВЧ популярные фразы вначале добавляются к Title (переменная $uniq_addings), 2 - только нч берутся, 3 - все.
$posts_spintext_volume = 300; // Количество символов спинтакс текста
$cats = 25; // Сколько категорий автоматом создать
$image_title_max_strlen = 85; // Максимальное количество символов в длине названии картинки, примерно 1/3 базы с очень длинными уникальными названиями которые невозмжно уникализировать или сократить cute-hairstyle-for-medium-length-hair-2016-cute-hairstyles-for-medium-length-hair-tutorial-short-haircuts-1.jpg
$image_title_min_strlen = 15;
$only_uniq_img = false; // Если True то из CSV файла выгрузки из базы картинок возьмем только те которые имеют уникальные тайтлы. Хорошо опробовать на "больших" категориях. В Short ключе например 25% отсекается сразу.
$min_img_size = 60000; // размер в байтах картинки минимальный
$max_img_size = 2000000;
$seasonal_add = true; // Будем к Title дописывать год, ниже % скольки тайтлам
$seasonal_titles = 4; // Кратно этой цифре каждому тайтлу будет присвоен $year_to_replace вконце / начале. 5 = 20%, 3 = 33% и т.п.
$year_end_percent = 75; // Сколько годовых тайтлов допишется вконец. 75 = 75% в конец.
$publish = 50; // % постов от текущих PUBLISH сколько отправляем в PENDING
$multicat = true; // Каждому посту будет присвоено больше 1ой категории. Больше упор на ВЧ запросы получается из за бредкрампов.
$max_posts_per_cat = 5; //20 означает максимум 5% постов в 1 категорию. Если активна Multicat, то лучше разрешить все посты в 1 категорию. По факту, лучше не становится другим категориям от уменьшения больших.
$write_used_images = true; // Записывает использованные для ниши картинки (ключа), и дает возможность использовать "добивку" ниши по неиспользованным картинкам. Может замедлять работу скрипта.
$take_only_unused_images = false; //Если запустить 2ой раз в нише с активной $write_used_images , то будут взяты только неисползованные картинки для перебора.

//Диры
$work_dir = 'F:\Dumps\\' . $site_name; // Пока нигде не использовано
$global_images_dir = 'F:\Dumps\google_images\\'; // Сюда будем сохранить картинки из Google
$start_script = '0_initialize.php';
$dump = 'dump.sql';

if ($multi_int_mode && is_file($work_dir . '/' . $dump)) {
    $scripts_chain = array('00_initialize.php', '0001_get_int_keys.php', '0002_wp_import_int.php', '0003_wp_create_cats_int.php', '0004_wp_fill_cats_int.php', '0005_wp_set_pending_int.php', '0006_sql_export.php');
} else if ($multi_int_mode) {
    echo2("Включен режим Multi_int_mode , но файла с дампом сгенереного сайта не найдено в папке $work_dir $dump - не сможем работать! ");
    exit ("Включен режим Multi_int_mode , но файла с дампом сгенереного сайта не найдено в папке $work_dir $dump - не сможем работать! ");
} else if ($int_mode) {
    $scripts_chain = array('00_initialize.php', '001_db_get_keys.php', '002_download.php', '003_wp_import.php', '004_wp_create_cats.php', '005_wp_fill_cats.php', '006_pending_posts.php', '007_spinner.php', '008_sql_export.php');
} else {
    $scripts_chain = array('00_initialize.php', '01_db_img_index_select.php', '02_csv_random_split.php', '03_copy_file.php', '04_generate_thumbs.php', '05_wp_import_images.php', '06_wp_check_uniq_titles.php', '07_kk_titles_choose.php', '08_choose_uniq_title.php', '09_insert_db_new_titles.php', '10_db_insert_posts.php', '11_wp_auto_suggest_category.php', '12_choose_category.php', '13_pending_posts.php', '14_spinner.php', '15_sql_export.php'); // Какой скрипт за каким следует
}

$big_res_to_split = $keyword . "_images.csv"; // Для вычленения отсюда необходимого количества позиций для 1 сайта
if ($int_mode) {
    $import_file = $keyword . "_images_" . $images_per_site . '_rand_lines_lang_' . $lang_id . '.csv';
} else {
    $import_file = $keyword . "_images_" . $images_per_site . '_rand_lines.csv';
}
//Dynamic название для Google mode
//$kk_import_file = $keyword . "_kk.csv";

$crop_width = 150; //Стандартная ширина тумба для темы нашей medhairs
$crop_height = 150;
$max_doubles = 100; //Лимит подсчета дублей заголовков картинок из базы. Если картинка (она же - тайтл) будет иметь простое название чтобы не бегать по всей базе и не счиать что количество таких картинок тысячи - это бессмысленно. Нам достаточно чтобы их было больше 10 неуникальных на самом деле для генерации/замены TITLE.
$unset_kk_doubles = true; // True = больше уникальности, меньше прессинга на ВЧ запросы. Может быть False. Если True, то из массива KK сразу удаляем все строки с тайтлами картинок, чтобы они не задублировались лишний раз. Экспериментальная функция.
$unset_all_doubles = true; // FALSE = Будем удалять только те строки которые в Title повторяются больше чем $limit_uniq раз. TRUE - Удаляем все повторы вообще тайтлов вне зависимости насколько они уникальны в пределах нашего сайта.
$limit_uniq = 3; // От скольки повторов заголовков начинать искать другой вариант. 2 значит от 2 дубля в базе, ищем замену. Чем больше число, тем быстрее работает скрипт, тем менее уникальный сайт.
$default_cat_name = 2017; //Название и URL стандартной (1) категории WP, сюда попадет все что не попало в другие категории.
$default_cat_slug = 2017; // URL категории default (1)
$before_spin_html = '<div class="text-content">'; //В эти теги будем заключать сгенерированный текст для каждого поста
$after_spin_html = '</div>';
$spin_fragments_separator = '<br>'; //Между генереными текстами разных шаблонных предложений ставим сепаратор

//Основные директории проекта и пути
$site_url = 'http://' . $site_name . '/';
$site_uploads_path = 'http://' . $site_name . '/wp-content/uploads/';
$wp_image_upload_date_prefix = '2017/09/';

//Пути и то что можно не трогать-не менять
$result_dir = $work_dir . "\\result\\";
$import_dir = $work_dir . "\\import\\";
$selects_dir = 'includes/selects';
$work_file = $import_dir . $import_file;
$img_dir = $work_dir . '/wp-content/uploads/' . $wp_image_upload_date_prefix;
$img_crop_dir = $img_dir . "crop\\"; // Более не используется.
//$kk_file = $import_dir.$kk_import_file;
//$kk_file = $selects_dir . '/' . $kk_import_file; // Пока что лежит в корне скрипта, в последствии в импорт добавлять будем
$fp_log = $result_dir . 'log.txt';

// База данных Wordpress
if ($int_mode == TRUE) {
    $db_instance = 'includes/int_instance.sql';
} else {
    $db_instance = 'includes/db_instance.sql'; // Пустая база данных с таблицами Wordpress, которая будет создаваться каждый раз для нового сайта. Лежать будет пока в папке со скриптом.
}
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
$post_guid = 130000; //Лучше не трогать. Стартовый POST_ID /?p= который будет заливаться в WP
$menu_guid = 199999; //Не трогать
$postmeta_id = 177776; // не трогать

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
//Deprecated. Перенесено в отдельный файл инклудов - unicalization_tpls.php
//$uniq_addings = array(' 2018 ', ' 2018 ', ' 2018 ', ' 2018 ', ' 2018 ', ' 2017 ', ' 2017 ', ' 2017 ', ' 2017 ', ' cute ', ' cute ', ' cute ', ' cute ', ' cute ', ' cute ', ' easy ', ' easy ', ' easy ', ' easy ', ' easy ', ' easy ', ' natural ', ' natural ', ' natural ', ' natural ', ' natural ', ' natural ', ' best ', ' best ', ' best ', ' best ', ' best ', ' best ', ' new ', ' new ', ' new ', ' cool ', ' cool ', ' cool ', ' cool ', ' quick ', ' quick ', ' quick ', ' latest ', ' latest ', ' latest ', ' formal ', ' formal ', ' formal ', ' pretty ', ' popular ', ' modern ', ' nice ', ' trendy ', ' teens ', ' elegant ', ' trending ', ' hot ', ' everyday ', ' really ', ' really quick ', ' really easy ', ' really simple ', ' really nice ', ' really cool ', ' unique ', ' fast ', ' classic ', ' young ', ' fancy ', ' stylish ', ' awesome ', ' chic ', ' romantic ', ' sexiest ', ' gorgeous ', ' red carpet ', ' celebrity red carpet ', ' lazy ', ' easy lazy ', ' cute lazy ', ' overnight ', ' coolest ', ' cutest ', ' attractive ', ' youth ');
//$uniq_addings_nch = array(' casual ', ' everyday ', ' super ', ' retro ', ' fancy ', ' mature ', ' stylish ', ' public ', ' hipster ', ' goddess ', ' perfect ', ' fifties ', ' hottest ', ' famous ', ' bohemian ', ' amazing ', ' romantic ', ' creative ', ' instagram ', ' mexican ', ' gorgeous ', ' ebony ', ' spanish ', ' sixties ', ' glamorous ', ' feminine ', ' ghetto ', ' easy lazy ', ' european ', ' glam ', ' recent ', ' gypsy ', ' universal ', ' sixteen ', 'you can afford ', ' affordable ', ' salon ', ' divine ', ' attractive ', ' the most sexy ', ' neat ', ' marvelous ', ' you desire ', ' bohemian ', ' catchy ', ' excellent ', ' naturally ', ' urban ', ' unique ', ' hottest ', ' brides ', ' romantic ', ' fabulous ', ' salon ', ' simplicity ', ' adorable ', ' convenient ', ' fashionable ', ' seductive ', ' fantastic ', ' mature ', ' graceful ', ' sweet ', ' cutest ', ' exquisite ', ' goddess ', ' favorite ', ' impressive ', ' outstanding ', ' elegance ', ' relaxed ', ' superb ', ' alluring ', ' exceptional ', ' coolest ', ' magnificent ');
//Здесь аккуратней с 2-3 буквенными словами, или придется вручную удалять категории потом, что наверное даже лучше
$year_pattern = "/(201[0-9])/"; //Находим в заголовках год, чтобы его заменить
$year_to_replace = 2017; // Год на который меняем
//todo Для INT категорий тоже надо свои слова добавить и сделать как с $uniq_tpls
$autocat_exclude_words = array($keyword, $year_to_replace, 'length', 'choose', 'when', 'youtube', 'amp', 'inspir', 'gallery', 'view', 'pic', 'about', 'your', 'idea', 'design', 'hair', 'style', 'women', 'very', 'with', 'picture', 'image', 'pinterest', 'woman', 'tumblr', 'from', 'side', 'pictures', 'ideas', 'style', 'photos'); // Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$autocat_strict_word_exclude = array('a', 'you', 'it', 'cut', 'to', 'in', 'the', 'on', 'what', 'of', 'for', 'at', 'by', 'is', 'in', 'and', 'do', 'how', 'this', 'that', 'can', 'part', 'new', 'with', 'in', 'can', 'be', 'or', 'as', 'its', 'as', 'an', 'its', 'will', 'by', 'into', 'get', 'cuts', 'over', 'life', 'bring', 'make',); //Строгое исключение данных слов в качестве категории

// Синонимы названий категорий. Важно первым элементом использовать существующую категорию из WP, иначе не сработает
$synonyms[] = array('mens', 'men', 'guy', 'boy', 'guys', 'man');
$synonyms[] = array('bob', 'lob');
$synonyms[] = array('fine', 'thick', 'thin');
$synonyms[] = array('black', 'african', 'american');
$synonyms[] = array('trend', 'latest', 'new', 'trendy', 'trends');
$synonyms[] = array('layered', 'layer', 'layers');
$synonyms[] = array('blond', 'blonded', 'blonde');
$synonyms[] = array('braid', 'braided', 'bridal', 'braids', 'braiding');
$synonyms[] = array('curls', 'curly', 'curled');
$synonyms[] = array('girl', 'girls');
$synonyms[] = array('medium', 'mid', 'shoulder');
$synonyms[] = array('updo', 'updos');
$synonyms[] = array('color', 'colors', 'colored');
$synonyms[] = array('curly', 'wavy', 'weave', 'weaves', 'curls', 'curled');

$lang = array(
    0 => 'pt',
    1 => 'es',
    2 => 'de',
    3 => 'fr',
    4 => 'it',
    5 => 'nl',
    6 => 'da',
    7 => 'sv',
    8 => 'fi',
    9 => 'cs',
//    10 => 'pl',
//    11 => 'ro',
);

function get_uniq_tpls($int_mode, $lang_id, $tpls_arr, $part)
{
    if ($int_mode) {
        if (isset($tpls_arr[$lang_id][$part])) {
            array_map('strtolower', $tpls_arr[$lang_id][$part]);
            return $tpls_arr[$lang_id][$part];
        } else {
            echo2("Проверьте уникализации для Языка $lang_id ! Выходим из программы. ");
            return false;
        }
    } else {
        array_walk($tpls_arr['default'][$part], 'strtolower');
        return $tpls_arr['default'][$part];
    }
}