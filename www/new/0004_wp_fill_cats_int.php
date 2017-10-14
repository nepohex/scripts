<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 11.10.2017
 * Time: 22:28
 */
include "multiconf.php";
next_script(0, 1);

// Получаем категории под каждый язык.
$wp_int_cat_ids = set_int_cats($lang);

foreach ($wp_int_cat_ids as $cat) {
    list ($lang_id, $lang_name, $term_id, $term_taxonomy_id) = array_values($cat);

    //Получаем подробную инфу о каждой дочерней языковой категории
    $child_cats = dbquery("SELECT `t1`.`term_taxonomy_id`, `t2`.`name` FROM `wp_term_taxonomy` as `t1` 
    LEFT JOIN `wp_terms` as `t2` ON `t1`.`term_id` = `t2`.`term_id`
    WHERE `t1`.`parent` = $term_id;", TRUE);

    $z = 0;
    //Если ранее сбивался скрипт, и перезапускали например чтобы не гонять по новой и не получать варнинги и нотисы Из за пустых родительских категорий.
    $c_posts_parent = update_cat_count_items($term_taxonomy_id, TRUE, TRUE);
    if ($c_posts_parent) {
        //Запускаем генератор для получения постов итерациями (шагами) по N постов чтобы не перегружать оперативку
        $generator = wp_get_posts($term_taxonomy_id, 1000 /* DEBUG 10, PROD 1000 */, array('ID', 'post_title'));
        foreach ($generator as $post_titles) {
            //DEBUG
//            $z += 1000;
//            echo_time_wasted("Получили $z / $c_posts_parent строк для языка $lang_name");
            //
            foreach ($post_titles as $post_title) {
                //Получаем Term_taxonomy_id первой сезонной категории языка.
                if (empty($season_taxonomy_id)) {
                    $season_taxonomy_id = $child_cats[0][0];
                }
                // Мешаем категории чтобы равномерно намазать.
                shuffle($child_cats);

                $ii = 0; //Счетчик чтобы определять посты которым не нашлось категорий.
                $got_cat = FALSE; //Счетчик чтобы определять посты которым не нашлось категорий.
                //Определяем посту категорию
                foreach ($child_cats as $cat_) {
                    $ii++;
                    if (stristr($post_title[1], $cat_[1]) !== FALSE) {
                        $cats_arr[$cat_[0]][] = $post_title[0];
                        $got_cat = TRUE;
                    }
                    if ($ii == count($child_cats) && $got_cat == FALSE) {
                        $no_cat_posts[] = $post_title[0];
                    }
                }
            }
            //Если ниодному из постов не нашлась категория
            if (isset($cats_arr)) {
                //Записываем новые категории из массива где ключ - term_taxonomy_id категории, значения - post_id. Привязка идет к term_taxonomy_id категории!
                foreach ($cats_arr as $cat_term_taxonomy_id => $post_ids) {
                    //Делаем Update т.к. у нас ранее все посты были приписаны к родительской категории языка!
                    wp_update_taxonomy_relation($cat_term_taxonomy_id, $post_ids);
                }
                unset ($cats_arr, $post_ids);
            }
            //Добиваем остатки постов которым не подобрали категории, закидываем в первую дочернюю (сезонная).
            if (isset($no_cat_posts)) {
                wp_update_taxonomy_relation($season_taxonomy_id, $no_cat_posts);
            }
            unset ($no_cat_posts);
        }
        //Обновляем количество постов в родительской категории языка.
        update_cat_count_items($term_taxonomy_id);
    }
    unset ($posts_ids, $post_ids, $post_title, $post_titles, $cats_arr, $season_taxonomy_id);
    echo_time_wasted($lang_id, "Обработали язык $lang_name ");
}

function wp_update_taxonomy_relation($term_taxonomy_id, $post_ids)
{
    if (is_array($post_ids)) {
        $post_ids = prepare_columns_string($post_ids);
    }
    dbquery("UPDATE `wp_term_relationships` SET `term_taxonomy_id` = $term_taxonomy_id WHERE `object_id` IN ($post_ids);");
    update_cat_count_items($term_taxonomy_id);
}

echo2("Присвоили INT посты по категориям по контексту заголовка. Остатки что не получилось закинуть в категории закинули в первую категорию языка, сезонную.");
next_script();