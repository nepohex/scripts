<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 20.11.2016
 * Time: 1:03
 * Категории нужно создать сначала самому!
 * Содержит только SELECT / UPDATE , поэтому можно перезапускать без вреда.
 * Скрипт:
 * 1. Получает категории из DB WP (которые нужно предварительно создать через админку WP, на основе анализа слов из KK)
 * 2. Получает посты из wp_posts
 * 3. На основе заголовка post_title присваивает в случайном порядке одну из массива категорий. Также доступна функция синонимов (заполняется вручную $synonyms) для лучшего покрытия.
 * 4. Обновляем таблицу wp_term_relationships и присываиваем постам новую категорию. При этом 1 посту возможна только 1 категория.
 * 5. Обновляем wp_term_taxonomy , вставляем количество постов в поле Count для каждой категории, чтобы в админке на странице Рубрик были актуальные данные по количеству постов в рубриках.
 */
include "multiconf.php";
mysqli_connect2();
next_script(0, 1);

// Получаем список категорий
$query = "SELECT * FROM `wp_terms`;";
$sqlres = mysqli_query($link, $query, MYSQLI_USE_RESULT);
while ($row = mysqli_fetch_assoc($sqlres)) {
    foreach ($synonyms as $synonim) {
        if (stripos($row['name'], $synonim[0]) !== false) {
            if (stripos($synonim[0], "men") !== false) { // ХУК HOOK чтобы улавливать мужские стрижки и не превращать в женские, это пиздец гавно уебанское но ничего не могу поделать
                $synonim[0] = ' men';
            }
            $row['synonim'] = $synonim;
        }
    }
    if (stripos($row['name'], "men") !== false) { // ХУК HOOK чтобы улавливать мужские стрижки и не превращать в женские
        $row['name'] = " men";
    }
    $wp_terms[] = $row;
}

$query = "SELECT `ID`,`post_title` FROM `wp_posts` WHERE `post_type` = 'post';";
$wp_posts = dbquery($query);

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
//13.12 потратил 2 часа написал этот фрагмент ненужный :(
//$i = 0;
//foreach ($wp_terms as $wp_term) {
//    for ($k = 0; $k < count($wp_new_term); $k++) {
//        if ($wp_term['name'] == key($wp_new_term)) {
//            $wp_terms[$i]['count'] = current($wp_new_term);
//            break;
//        }
//        next($wp_new_term);
//    }
//    reset($wp_new_term);
//    $i++;
//}
//// Добавляем добивку категорий в которых меньше 2% записей получается
//$i = 0;
//foreach ($wp_terms as $term) {
//    if ($term['count'] < count($wp_posts)/50) {
//        $i = 0;
//        foreach ($wp_posts as $item) {
//            if ($item['new_term'] == false) {
//                if(stripos($item['post_title'],$term['name'])) {
//                    $wp_posts[$i]['new_term'] = $term['term_id'];
//                    $wp_new_term[$term['name']] +=1;
//                    $z++;
//                    break;
//                } else if ($term['synonim']) {
//                    foreach ($term['synonim'] as $syn) {
//                        if(stripos($item['post_title'],$syn)) {
//                            $wp_posts[$i]['new_term'] = $term['term_id'];
//                            $wp_new_term[$term['name']] +=1;
//                            $z++;
//                            break;
//                        }
//                    }
//                }
//            }
//            $i++;
//        }
//    }
//}
arsort($wp_new_term);
echo2(print_r($wp_new_term, true));
echo2("Нашли новые категории, итого распределили по новым категориям _" . $z . " / " . count($wp_posts) . " _ записей! Начинаем обновлять базу:");

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
$query = "SELECT `term_taxonomy_id`, count(*) AS `term_count` FROM `wp_term_relationships` GROUP BY `term_taxonomy_id`;";
$wp_terms_count = dbquery($query);
foreach ($wp_terms_count as $item) {
    // Создаем меню из получившихся категорий
    $query = "UPDATE `wp_term_taxonomy` SET `count` = '" . $item['term_count'] . "' WHERE `term_taxonomy_id` = " . $item['term_taxonomy_id'] . ";";
    dbquery($query);
}
next_script();