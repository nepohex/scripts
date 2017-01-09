<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.12.2016
 * Time: 0:44
 * На основе содержимого POST TITLE проверям какие слова используются, из самых частых делаем категории.
 * Слова из которых не создавать категории в конфиге в переменной $autocat_analyse, количество категорий в конфиге $cats.
 */
include "multiconf.php";
include( "mysqli_connect.php" );
echo2 ("Начинаем выполнять скрипт ".$_SERVER['SCRIPT_FILENAME']);

$query = "Select `post_title` from `wp_posts` where `post_type` = 'post';";
$sqlres = mysqli_query($link,$query);

while ($row = mysqli_fetch_row($sqlres)) {
    $post_titles[] = $row;
}

$i= 0;
foreach ($post_titles as $p) {
    $tmp = explode(" ",$p[0]);
    foreach ($tmp as $item) {
        if (strpos(str_ireplace($autocat_exclude_words,"|",$item),"|") !== false) {
            $i = 1;
        } else {
            foreach ($autocat_strict_word_exclude as $bad_word) {
                if (strtolower($item) == strtolower($bad_word)) {
                    $i = 1;
                    break;
                }
            }
            if ($i == 0) {
                $words_used[$item] += 1;
            }
        }
        $i = 0;
    }
}
arsort($words_used);
reset($words_used);
echo2 ("Посчитали слова, записали результат в файл ".$result_dir.$autocat_analyse);
file_put_contents($result_dir.$autocat_analyse,print_r($words_used,true));
echo2 ("Начинаем создавать категории!");

for ($i = 0; $i < $cats; $i++) {
    $cat_name = key($words_used); // Получаем ключ элемента массива
    $cats_created[]= key($words_used); // Для вывода дальнейшего
$wp_terms_ids[] = $wp_postmeta_start_pos;
    $queries[] = "INSERT INTO  `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) VALUES ( ".$wp_postmeta_start_pos.",  '".ucwords($cat_name)."','".strtolower($cat_name)."',  '0' );" ;
    $queries[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (".$wp_postmeta_start_pos.", ".$wp_postmeta_start_pos.", 'category', '', '0', '0');";
    $cat_name = next($words_used);
    $wp_postmeta_start_pos++;
$menu_order_counter[] = $i;
}
dbquery($queries);

// Начинаем делать меню из этих категорий, внимание ГОВНОКОД на соплях!
$menu_order_counter[] = $i++; // Это мы добавляем количество чтобы еще стандартная 1ая категория тоже пошла по этапу, а не только вновь созданные.
$c = 77776; // wp_postmeta.meta_id стартовый (+1)
array_unshift($wp_terms_ids,1);
$menu_guid2 = $menu_guid;
$query_menu[] = "Insert into `wp_terms` ( `term_id` , `name` , `slug` , `term_group` ) values ('".$menu_guid."','Mfa_Me_nu','mfa_me_nu','0');"; //Слеша добавлены чтобы MEN категория не определялась
$query_menu[] = "INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ('".$menu_guid."','".$menu_guid."','nav_menu','','0','0');";
$query_menu[] = "INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES (".$menu_guid.", 'theme_mods_2017theme', 'a:2:{i:0;b:0;s:18:\"nav_menu_locations\";a:1:{s:7:\"primary\";i:$menu_guid;}}','yes');";
foreach ($menu_order_counter as $num) {
    $wp_posts_menu_id[] = $menu_guid++;
    $query_menu[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (" . current($wp_posts_menu_id) . ", 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', '', '', 'publish', 'closed', 'closed', ''," . current($wp_posts_menu_id) . ", '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "?p=" . current($wp_posts_menu_id) . "', " . $num . ", 'nav_menu_item', '', 0);";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_type','taxonomy');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_menu_item_parent',0);";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_object_id','".current($wp_terms_ids)."');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_object','category');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_target','');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_classes','a:1:{i:0;s:0:\"\";}');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_xfn','');";
    $query_menu[] = "INSERT INTO `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (".$c++.",".current($wp_posts_menu_id).",'_menu_item_url','');";
    $query_menu[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (".current($wp_posts_menu_id).",".$menu_guid2.",0);";
    next($menu_order_counter);
    next($wp_posts_menu_id);
    next($wp_terms_ids);
}
dbquery($query_menu);

echo2 ("Создали категории и меню из них");
echo2 (print_r($cats_created,true));
echo2 ("Закончили со скриптом ".$_SERVER['SCRIPT_FILENAME']." Переходим к NEXT");
next_script ($_SERVER['SCRIPT_FILENAME']);
?>