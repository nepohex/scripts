<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 25.11.2016
 * Time: 0:35
 */
ini_set('error_reporting', 0); // Нужно чтобы авторедирект работал нормально между файлами
$start = microtime(true);

//Обязательные к изменению функции от сайта к сайту
$site_name = 'rockhairstyles2.us'; // Без слешей, только домен
$keyword = "over";
$blogname = ucwords($keyword." hairstyles 2017");
$blogdescription = $blogname.". Choose your best hairstyle to rock every day and stay trendy in new year.";
$wp_conf_db_prefix = 'wtfowned_';
$wpcachehome = '/home/wtfowned/web/'.$site_name.'/public_html/cache/';

// Основные функции с которыми можно "играться" и менять от сайта к сайту
$images_per_site = 200; // Сколько картинок брать на 1 сайт (без учета их размера, еще может сильно сократиться, обычно на 20% в итоге выходит)
$gen_addings = 1; // 1 = только ВЧ популярные фразы вначале добавляются к Title (переменная $uniq_addings), 2 - только нч берутся, 3 - все.
$posts_spintext_volume = 300; // Количество символов спинтакс текста
$cats = 25; // Сколько категорий автоматом создать
$image_title_max_strlen = 85; // Максимальное количество символов в длине названии картинки, примерно 1/3 базы с очень длинными уникальными названиями которые невозмжно уникализировать или сократить cute-hairstyle-for-medium-length-hair-2016-cute-hairstyles-for-medium-length-hair-tutorial-short-haircuts-1.jpg
$image_title_min_strlen = 15;
$only_uniq_img = false; // Если True то из CSV файла выгрузки из базы картинок возьмем только те которые имеют уникальные тайтлы. Хорошо опробовать на "больших" категориях. В Short ключе например 25% отсекается сразу.
$min_img_size = 40000; // размер в байтах картинки минимальный
$seasonal_add = true; // Будем к Title дописывать год, ниже % скольки тайтлам
$seasonal_titles = 5; // Кратно этой цифре каждому тайтлу будет присвоен $year_to_replace вконце / начале. 5 = 20%, 3 = 33% и т.п.
$year_end_percent = 75; // Сколько годовых тайтлов допишется вконец. 75 = 75% в конец.
$publish = 50; // % постов от текущих PUBLISH сколько отправляем в PENDING
$multicat = true; // Каждому посту будет присвоено больше 1ой категории. Больше упор на ВЧ запросы получается из за бредкрампов.
$max_posts_per_cat = 10; //20 означает максимум 5% постов в 1 категорию. Если активна Multicat, то лучше разрешить все посты в 1 категорию. По факту, лучше не становится другим категориям от уменьшения больших.

//Диры
$work_dir = 'F:\Dumps\\'.$site_name; // Пока нигде не использовано
$start_script = 'initialize.php';
$scripts_chain = array( 'initialize.php','0_db_img_index_select.php', '1_csv_random_split.php', '2_copy_file.php','3_generate_thumbs.php','4_wp_import_images.php','5_wp_check_uniq_titles.php','6_kk_titles_choose.php', '7_choose_uniq_title.php', '8_insert_db_new_titles.php','9_db_insert_posts.php','10_wp_auto_suggest_category.php','11_choose_category.php','12_pending_posts.php','13_spinner.php','14_sql_export.php'  ); // Какой скрипт за каким следует
$big_res_to_split = $keyword."_images.csv"; // Для вычленения отсюда необходимого количества позиций для 1 сайта
$import_file = $keyword."_images_".$images_per_site.'_rand_lines.csv';
$kk_import_file = $keyword."_kk.csv";

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
$site_url = 'http://'.$site_name.'/';
$site_uploads_path = 'http://'.$site_name.'/wp-content/uploads/';
$wp_image_upload_date_prefix = '2016/12/';

//Пути и то что можно не трогать-не менять
$result_dir = $work_dir."\\result\\";
$import_dir = $work_dir."\\import\\";
$work_file = $import_dir.$import_file;
$img_dir = $work_dir.'/wp-content/uploads/'.$wp_image_upload_date_prefix;
$img_crop_dir = $img_dir."crop\\"; // Более не используется.
//$kk_file = $import_dir.$kk_import_file;
$kk_file = $kk_import_file; // Пока что лежит в корне скрипта, в последствии в импорт добавлять будем
$log_file = 'log.txt';
$fp_log = fopen($result_dir.$log_file,"a");

// База данных Wordpress
$db_instance = 'db_instance.sql'; // Пустая база данных с таблицами Wordpress, которая будет создаваться каждый раз для нового сайта. Лежать будет пока в папке со скриптом.
$db_usr = 'root';
$db_name = $site_name;
// База данных с картинками
$db_name_img = 'image_index';
$db_host = 'localhost';
$db_pwd = '';
$wp_conf_tpl = 'wp_conf_empty.txt';
$wp_conf_cache_tpl = 'wp-cache-conf_empty.txt';

// Загоняем в массив чтобы создать все диры функцией
$project_dirs = array(
    $work_dir,
    $result_dir,
    $import_dir,
    $img_dir,
);

$image_words_separator = "_"; // Между словами в названии картинок вставляем. Здесь можно любой символ персональный задать
$replace_symbols = array('.','_','+','jpg','jpeg','png','gif','-','!','-min','$', '%', '^','&', '(', ')', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '"', '  '); // Эти символы будем менять при выгрузке из базы данных с картинками и менять их на пробелы чтобы были чистые названия
$bad_symbols = array('$', '%', '^','&', '(', ')', '=', '+', '=', '`', '~', '\'', ']', '[', '{', '}', ',', '.','"', '  '); //Заменим эти символы в имени файла на пробелы
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
$filter_words = array ('hairstyles','hairstyle ',' hair ',' for ',' hairs '); // Слова которые будем заменять на регулярку при поиске, чтобы расширить семантику
$uniq_addings = array (' 2017 ',' 2017 ',' 2017 ',' 2017 ',' 2017 ',' 2017 ',' 2017 ',' 2017 ',' 2017 ',' cute ',' cute ',' cute ',' cute ',' cute ',' cute ',' easy ',' easy ',' easy ',' easy ',' easy ',' easy ',' natural ',' natural ',' natural ',' natural ',' natural ',' natural ',' best ',' best ',' best ',' best ',' best ',' best ',' new ',' new ',' new ',' cool ',' cool ',' cool ',' cool ',' quick ',' quick ',' quick ',' latest ',' latest ',' latest ',' formal ',' formal ',' formal ',' pretty ',' popular ',' modern ',' nice ',' trendy ',' teens ',' elegant ',' trending ',' hot ',' everyday ',' really ',' really quick ',' really easy ',' really simple ',' really nice ',' really cool ',' unique ',' fast ',' classic ',' young ',' fancy ',' stylish ',' awesome ',' chic ',' romantic ',' sexiest ',' gorgeous ',' red carpet ',' celebrity red carpet ',' lazy ',' easy lazy ',' cute lazy ',' overnight ',' coolest ',' cutest ',' attractive ',' youth ');
$uniq_addings_nch = array(' casual ' , ' everyday ' , ' super ' , ' retro ' , ' fancy ' , ' mature ' , ' stylish ' , ' public ' , ' hipster ' , ' goddess ' , ' perfect ' , ' fifties ' , ' hottest ' , ' famous ' , ' bohemian ' , ' amazing ' , ' romantic ' , ' creative ' , ' instagram ' , ' mexican ' , ' gorgeous ' , ' ebony ' , ' spanish ' , ' sixties ' , ' glamorous ' , ' feminine ' , ' ghetto ' , ' easy lazy ' , ' european ' , ' glam ' , ' recent ' , ' gypsy ' , ' universal ' , ' sixteen ' );
//Здесь аккуратней с 2-3 буквенными словами, или придется вручную удалять категории потом, что наверное даже лучше
$year_pattern = "/(201[0-9])/"; //Находим в заголовках год, чтобы его заменить
$year_to_replace = 2017; // Год на который меняем
$autocat_exclude_words = array($keyword, $year_to_replace, 'length', 'choose','when','youtube','amp','inspir','gallery','view','pic','about','your','idea', 'design', 'hair','style','women','very','with','picture','image','pinterest','woman','tumblr','from', 'side'); // Это слова которые будут исключены из автосоздания категорий. Исключение идет по маске!
$autocat_strict_word_exclude = array('a','cut','to','in','the','on','what','of','for','at','by','is','in','and'); //Строгое исключение данных слов в качестве категории

// Синонимы названий категорий. Важно первым элементом использовать существующую категорию из WP, иначе не сработает
$synonims[] = array ('fine','thick','thin');
$synonims[] = array  ('black','african','american');
$synonims[] = array ('trend','latest','new');
$synonims[] = array ('men','guy','boy','mens');
$synonims[] = array ('layered', 'layer','layers');
$synonims[] = array ('blond', 'blonded','blonde');
$synonims[] = array ('braid', 'braided','bridal','braids');
$synonims[] = array ('curls', 'curly','curled');
$synonims[] = array ('girl', 'girls');
$synonims[] = array ('medium', 'mid','shoulder');
$synonims[] = array ('updo','updos');
$synonims[] = array ('color','colors','colored');

$spin_tpls = array(
    'any' => array(
        '{Haircut|Hairstyle} {also|still} {suitable|{perfect|wonderful|excellent|great}|{ideal|preferred}|acceptable|{okay|fine}} for {young|older|any} {women|girl|teen|teenager|lady} with {round|oval|square|heart|long} face shape.',
    ),
    'not end' => array(
        '{{The choice|Your pick} of {haircut|hairstyle} can {really|actually|dramatically|effective} {transform|renew|convert|remake} your look and make you {shine|sparkle}.| The {right|correct|suitable|best|ideal|perfect|proper} haircut is {something|a little something|a thing} that {can|may|can certainly|will} {quickly|fast|instantly|immediately} and easily {change|modify|transform} {your whole|the whole|your entire|the entire} look for {the better|more suitable|the more effective}.}',
    ),
    'not start' => array(
        //'You spent {just|right|equal|honestly|completely|precisely} {5-20 minutes|a few moments|a couple minutes} to {create|make} a look, but {feels|seems} like much more.',
    ),
    'end' => array(
        '{Well|Properly|Clearly|Good}, {after|once} {viewing|watching|browsing} these haircuts you {should|will need to|should really|have to|need to} seriously {think about|think of} {joining|signing up for} the %post_title% club. {Sleek|Smooth} and {glossy|shiny} or shattered and slightly tousled, they {always|generally|constantly} look {fresh|new|refreshing} {and|and also|plus} {original|classic}.',
    ),
    'start' => array(
        '%post_title% haircuts {no longer|no more} need {any|any specific|almost any|just about any|any kind of} introduction, {and|and also|as well as} neither do other {hairstyles|hairdos|hair|hair styles}. {That’s|That is} {because|mainly because|simply because} {they’ve|they have} {been around|been with us|been known} {long enough|for a long time|so long|for long} to have {taken|obtained|used} on {many|countless|lots of|numerous} {creative|very creative|inspiring}, {versatile|flexible} {guises|shapes}.',
    ),
    'tip' => array (
        '{Avoid|Stop} {daily|regular} blow-drying or hair smoothing without first {applying|using} {the proper|the right} products. {It would be|It will be} {ideal|perfect} to {let|allow} the hair air-dry and use {heated|hot} rollers {only|just} {now|at this point|at this moment} {and then|and after that} to style the hair for {special|particular|specific} {occasions|events}.',
    ),
);

function convert($memory_usage)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($memory_usage/pow(1024,($i=floor(log($memory_usage,1024)))),2).' '.$unit[$i];
}

function echo_time_wasted($i = null) {
    global $start;
    $time = microtime(true) - $start;
    if ($i) {
        echo2 ("Идем по строке ".$i." Скрипт выполняется уже ".number_format($time, 2)." сек"." Памяти выделено в пике ".convert(memory_get_peak_usage(true)) );
    } else {
        echo2 ("Скрипт выполняется уже ".number_format($time, 2)." сек"." Памяти выделено в пике ".convert(memory_get_peak_usage(true)) );
    }

}

function print_r2($val) {
    echo '<pre>';
    print_r($val);
    echo  '</pre>';
    flush();
}

function echo2 ($str) {
    global $fp_log;
    fwrite($fp_log,date("d-m-Y H:i:s")." - ".$str."\n");
}

function next_script ($php_self, $fin = null) {
    global $scripts_chain;
    if ($fin == true) {
        echo2 ("Достигли конца генерации сайта, пробуем перейти на новый круг! ".$php_self);
        return header('Location: ' . array_shift($scripts_chain));
    }
    $i = 0;
    $php_self = array_pop(explode('/', $php_self));
    foreach ($scripts_chain as $script) {
        if ($script == $php_self) {
            return header('Location: ' . $scripts_chain[$i + 1]);
        }
        $i++;
    }
    echo2 ("Не можем найти следующего скрипта после ".$php_self);
}

function mkdir2 ($dir) {
    echo2 ("Пробуем создать директорию ".$dir);
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo2("Создали директорию " . $dir);
        } else {
            echo2("Директорию " . $dir . " создать не удалось и ее не существует");
        }
    } else {
        echo2("Директория " . $dir . " уже существует, все ок");
    }
}

function pwdgen( $length ) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);

}

function dbquery($queryarr)
{
    global $link;
    if (is_array($queryarr)) {
        foreach ($queryarr as $query) {
            $sqlres = mysqli_query($link, $query);
            if ($error = mysqli_error($link)) {
                echo2("Mysqli error $error в запросе $query");
            }
        }
    } else {
        mysqli_query($link, $queryarr);
        if ($error = mysqli_error($link)) {
            echo2("Mysqli error $error в запросе $queryarr");
        }
    }
}

function gen_wp_db_conf()
{
    global $site_name, $keyword, $wp_conf_db_prefix;
    global $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd;
    $tmp = strlen($wp_conf_db_prefix . $keyword);
    if ($tmp < 16) {
        $wp_conf_db_name = $wp_conf_db_prefix . $keyword . pwdgen(15 - $tmp);
        $wp_conf_db_usr = $wp_conf_db_prefix . $keyword . pwdgen(15 - $tmp);
    } elseif ($tmp >= 16) {
        $wp_conf_db_name = substr($wp_conf_db_prefix . $keyword, 0, 16);
        $wp_conf_db_usr = substr($wp_conf_db_prefix . $keyword, 0, 14) . pwdgen(2);
    }
    $wp_conf_db_pwd = pwdgen(12);
}

?>