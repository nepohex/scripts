<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 30.09.2017
 * Time: 1:15
 * Цикл поязычный.
 * 1. Выгрузить все тайтлы определенного языка.
 * 2. Получить топовые слова
 * Создать меню с названием языка
 * Создать категории по топовым словам
 * Присвоить категориям по топовым словам родителя = категория языка. Например структура станет http://test500site.com/2017/curly/ , и в настройках сайта будет очевидно какая категория какому языку принадлежит.
 * Присвоить категории в меню
 * Присвоить категориям персональное меню через плагин и добавление в wp_options данных
 * taxonomy_100 / a:3:{s:12:"pgm_location";s:7:"primary";s:8:"pgm_menu";s:6:"100000";s:12:"pgm_menulist";a:3:{i:0;s:6:"100027";i:1;s:6:"100028";i:2;s:6:"100029";}}
 * Также пришлось подшаманить код плагина Page Menu чтобы на страницах принадлежащих определенной категории тоже выводилось меню а не только в самой категории.
 */
include "multiconf.php";
next_script(0, 1);

// Получаем категории под каждый язык.
$wp_int_cat_ids = set_int_cats($lang);

foreach ($wp_int_cat_ids as $cat) {
    //В PHP 7 можно ассоциативный массивы подавать в list, а пока вот так только.
    list ($lang_id, $lang_name, $term_id, $term_taxonomy_id) = array_values($cat);
    $c_post_num = update_cat_count_items($term_taxonomy_id, TRUE);
    echo2("Начинаем обработку $lang_id / $lang_name языка, всего постов $c_post_num загружено в дефолтную категорию $term_taxonomy_id;");
    //debug - > выставить по 1000 после окончания дебуга
    for ($i = 0; $i < $c_post_num - 10; $i += 1000) {
        $post_ids = dbquery("SELECT `object_id` FROM `wp_term_relationships` WHERE `term_taxonomy_id` = $term_taxonomy_id LIMIT 1000 OFFSET $i;", TRUE);
        $tmp = implode(",", $post_ids);
        $post_titles = dbquery("SELECT `post_title` FROM `wp_posts` WHERE `post_type` = 'post' AND `ID` IN ($tmp)", TRUE);
        $words_used = words_used($words_used, $post_titles);
    }
    //Получаем добавки для каждого языка, чтобы они не участвовали в названиях категорий.
    get_int_addings($lang_id);
    create_cats_int($words_used, $cats, $lang_id, $term_id);
    $menu_guid = create_menu($lang_name, $lang_id, $term_id);
    custom_navi($menu_guid, $term_id);
    unset ($words_used);
}
echo2 ("Создали категории по топовым словам языка, создали персональные меню под каждый язык.");
next_script();

function create_cats_int($words_used, $cats = 25, $lang_id = 0, $wp_lang_term_id)
{
    global $autocat_exclude_words, $autocat_strict_word_exclude, $default_cat_name, $uniq_addings;
    $exclude_words = array_merge($autocat_strict_word_exclude, $autocat_exclude_words, $uniq_addings);
    $ai_terms = get_ai('wp_terms');
    $ai_taxonomy = get_ai('wp_term_taxonomy');
    //Счетчик итераций
    $i = 0;
    // Сколько по факту создали
    $c_created = 0;
    //Создаем дефолтную категорию для языка, первую категорию с годом
    $cat_slug = $lang_id . '_' . $default_cat_name;
    $queries[] = "INSERT INTO  `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ( " . $ai_terms . ",  '" . ucwords($default_cat_name) . "','" . strtolower($cat_slug) . "',  '$lang_id' );";
    $queries[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (" . $ai_taxonomy . ", " . $ai_terms . ", 'category', '', '$wp_lang_term_id', '0');";
    foreach ($words_used as $key => $word) {
        //Ставим $i чтобы не создавались категории из топовых 3х слов, которые как правило значат основной ключ + слово прическа.
        $i++;
        if (strlen($key) > 2 && (in_array($key, $exclude_words) === FALSE) && $i > 3) {
            $c_created++;
            $ai_taxonomy++;
            $ai_terms++;
            //URL категории должен быть в ASCII
            $cat_url = $lang_id . '_' . str_to_url($key);
            $cat_descr = $lang_id . '_' . $key;
            $queries[] = "INSERT INTO  `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ( " . $ai_terms . ",  '" . ucwords($key) . "','$cat_url',  '$lang_id' );";
            $queries[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (" . $ai_taxonomy . ", " . $ai_terms . ", 'category', '$cat_descr', '$wp_lang_term_id', '0');";
        }
        if ($c_created == $cats) {
            dbquery($queries);
            echo2("Создали $c_created категорий из перебора в $i топовых слов для языка $lang_id");
            return true;
        }
    }
    //На случай если запросим создание больше категорий чем Words Used
    dbquery($queries);
    echo2("Создали $c_created категорий из перебора в $i топовых слов для языка $lang_id");
    return true;
}

/**
 * //todo Может быть адаптирована под стандартную функцию создания меню включая англ язык, для этого его надо как $lang_id = 0 внести или дефолтом использовать.
 * wp_terms.term_id = wp_term_taxonomy.term_id = wp_term_telationships.term
 * wp_posts.ID = wp_term_relationships.object_id => какой элемент с каким связываем. Сначала постим в Wp_posts каждый элемент меню, потом эти ID с запощеными элементами привязываем к ID основного меню из таблицы wp_term_taxonomy со значением nav_menu (menu_guid)
 * @param $lang_name
 * @param $lang_id
 * @param $wp_lang_term_id
 * @return int menu_guid ( wp_terms.term_id меню )
 */
function create_menu($lang_name, $lang_id, $wp_lang_term_id)
{
    $ai_terms = get_ai('wp_terms');
    $ai_taxonomy = get_ai('wp_term_taxonomy');
    $ai_posts = get_ai('wp_posts');
//    Если вдруг нужно будет получить ID категории под определенный язык.
//    $created_cats = dbquery("SELECT `term_id` FROM `wp_terms` WHERE `slug` = '$lang_name' OR `name` = '$lang_name';");
    $created_cats = get_child_cats($wp_lang_term_id);
    $menu_name = $lang_name . '_int_me_nu_navigation';

    // wp_terms.term_id = wp_term_taxonomy.term_id , под ним создаем саму запись о меню.
    //создаем меню
    $menu_guid = $ai_terms;
    $menu_term_taxonomy_id = $ai_taxonomy;
    $query_menu[] = "INSERT INTO `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ('$menu_guid','$menu_name','$menu_name','$lang_id');"; //Слеша добавлены чтобы MEN категория не определялась
    $query_menu[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ($menu_term_taxonomy_id, $menu_guid ,'nav_menu','Menu for $lang_name Language $lang_id','0','0');";

    //подвязываем созданные ранее категории в это меню
    $menu_order = 1;
    foreach ($created_cats as $cat_id) {
        // Для наглядности называем post какой элемент постим, совсем необязательно.
        $postname = $lang_name . "_" . $menu_guid . "_" . $cat_id;

        $query_menu[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES 
($ai_posts, 1, '2017-08-10 00:05:53', '2017-08-10 21:05:53','', '', '', 'publish', 'closed', 'closed', '','$postname', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', $menu_guid, '/?p=$ai_posts', $menu_order, 'nav_menu_item', '', 0);";
        $query_menu[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES 
($ai_posts,$ai_taxonomy,$lang_id);";

        //Обязательный хлам без которого не обойтись о каждом конкретном элементе меню.
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_type','taxonomy');";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_menu_item_parent',0);";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_object_id',$cat_id);";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_object','category');";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_target','');";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_classes','a:1:{i:0;s:0:\"\";}');";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'_menu_item_xfn','');";
        $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ('',$ai_posts,'yst_is_cornerstone','');";
        $ai_posts++;
        $menu_order++;
    }
    echo2("Создали меню с ID $menu_guid и названием $menu_name для языка $lang_id / $lang_name");
    dbquery($query_menu);
    //Не работает функция
    //update_cat_count_items($menu_term_taxonomy_id);
    return $menu_guid;
}

function words_used(&$words_used, array $strings_array)
{
    foreach ($strings_array as $item) {
        $tmp = explode(" ", $item);
        foreach ($tmp as $word) {
            if (isset($words_used[$word])) {
                $words_used[$word] += 1;
            } else {
                $words_used[$word] = 1;
            }
        }
    }
    arsort($words_used);
    return $words_used;
}

/**
 * Нужно создать запись в wp_options с примерным содержимым
 * taxonomy_100 / a:3:{s:12:"pgm_location";s:7:"primary";s:8:"pgm_menu";s:6:"100000";s:12:"pgm_menulist";a:3:{i:0;s:6:"100027";i:1;s:6:"100028";i:2;s:6:"100029";}}
 * что создает кастом меню для категорий из 100027-100028-100029 для категории 100 из меню 100000
 * @param $menu_guid int wp_terms.term_id меню
 * @param $wp_lang_term_id int
 */
function custom_navi($menu_guid, $wp_lang_term_id)
{
//    Пример того что должно быть в wp_options для персонального меню в этой категории .
    $pers_menu_exmpl = unserialize("a:3:{s:12:\"pgm_location\";s:7:\"primary\";s:8:\"pgm_menu\";s:6:\"100000\";s:12:\"pgm_menulist\";a:3:{i:0;s:6:\"100027\";i:1;s:6:\"100028\";i:2;s:6:\"100029\";}}");
    // Пример записи в wp_options , option_name = category_children. Ключ массива - term_id категории для которой идет перс меню, внутри массива term_id категорий которые должны выводиться.
    $options_arr_exmpl = unserialize("a:2:{i:200597;a:26:{i:0;i:200607;i:1;i:200608;i:2;i:200609;i:3;i:200610;i:4;i:200611;i:5;i:200612;i:6;i:200613;i:7;i:200614;i:8;i:200615;i:9;i:200616;i:10;i:200617;i:11;i:200618;i:12;i:200619;i:13;i:200620;i:14;i:200621;i:15;i:200622;i:16;i:200623;i:17;i:200624;i:18;i:200625;i:19;i:200626;i:20;i:200627;i:21;i:200628;i:22;i:200629;i:23;i:200630;i:24;i:200631;i:25;i:200632;}i:200598;a:26:{i:0;i:200634;i:1;i:200635;i:2;i:200636;i:3;i:200637;i:4;i:200638;i:5;i:200639;i:6;i:200640;i:7;i:200641;i:8;i:200642;i:9;i:200643;i:10;i:200644;i:11;i:200645;i:12;i:200646;i:13;i:200647;i:14;i:200648;i:15;i:200649;i:16;i:200650;i:17;i:200651;i:18;i:200652;i:19;i:200653;i:20;i:200654;i:21;i:200655;i:22;i:200656;i:23;i:200657;i:24;i:200658;i:25;i:200659;}}");

    $query = "SELECT `option_value` FROM `wp_options` WHERE `option_name` = 'category_children';";
    if ($tmp = dbquery($query)) {
        $upd = 1;
        $options_arr = unserialize($tmp);
    } else {
        $upd = 0;
        $options_arr = '';
    }
    $created_cats = get_child_cats($wp_lang_term_id);
    $child_menu_items = dbquery("SELECT `ID` FROM `wp_posts` WHERE `post_parent` = $menu_guid;", TRUE);

    // Подготовка массива меню
    $pers_menu['pgm_location'] = 'primary';
    $pers_menu['pgm_menu'] = $menu_guid;
    foreach ($child_menu_items as $item) {
        $pers_menu['pgm_menulist'][] = $item;
    }
    $rdy_menu = serialize($pers_menu);

    //Для родительской категории - добавляем ее в общий перебор
    array_unshift($created_cats, $wp_lang_term_id);
    //Вставляем для каждой дочерней языковой категории конкретно это меню.
    foreach ($created_cats as $cat) {
        //Для родительской категории options arr обновляем
        $options_arr[$wp_lang_term_id][] = $cat;
        //Для каждой дочерней
        $options_arr[$cat][] = $cat;
        $tmp = 'taxonomy_' . $cat;
        $queries[] = "INSERT INTO `wp_options` (`option_id`,`option_name`,`option_value`) VALUES ('','$tmp','$rdy_menu');";
    }
    $options_arr = serialize($options_arr);
    if ($upd) {
        $queries[] = "UPDATE `wp_options` SET `option_value` = '$options_arr' WHERE `option_name` = 'category_children';";
    } else {
        $queries[] = "INSERT INTO `wp_options` (`option_id`,`option_name`,`option_value`) VALUES ('','category_children','$options_arr');";
    }
    dbquery($queries);
}
