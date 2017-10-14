<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.10.2017
 * Time: 18:27
 */
include "multiconf.php";
next_script(0, 1);

// Получаем категории под каждый язык.
$wp_int_cat_ids = set_int_cats($lang);
echo2("Переменная PUBLISH = $publish %, отправляем столько постов в Pending для каждого языка");
foreach ($wp_int_cat_ids as $cat) {
    list ($lang_id, $lang_name, $term_id, $term_taxonomy_id) = array_values($cat);

    //Получаем подробную инфу о каждой дочерней языковой категории
    $child_cats = dbquery("SELECT `t1`.`term_taxonomy_id` FROM `wp_term_taxonomy` as `t1` 
    LEFT JOIN `wp_terms` as `t2` ON `t1`.`term_id` = `t2`.`term_id`
    WHERE `t1`.`parent` = $term_id;", TRUE);

    if ($child_cats) {
        foreach ($child_cats as $child_term_taxonomy_id) {
            //Запускаем генератор для получения постов итерациями (шагами) по N постов чтобы не перегружать оперативку
            $generator = wp_get_posts($child_term_taxonomy_id, 1000 /* DEBUG 10, PROD 1000 */, array('ID'), TRUE);
            foreach ($generator as $post_ids) {
                shuffle($post_ids);
                $c_pending = round(count($post_ids) * $publish / 100);
                $pending_ids = array_slice($post_ids, 0, $c_pending);
                $tmp = prepare_columns_string($pending_ids);
                if ($c_pending > 0) {
                    dbquery("UPDATE `wp_posts` SET `post_status` = 'pending' WHERE `ID` IN ($tmp);");
                }
            }
        }
    }
}
next_script();

