<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.03.2018
 * Time: 19:39
 * Импорт контента (предварительно вручную создать рубрики)
 * КОСТЫЛИ:
 * 1. http://zapdoc.ru/ekstrennaya-patologiya-organov-bryushnoj-polosti/appendiczit.-gde-on-i-kak-lechit.html
 * убрать точку! новый урл без точки, редирект в HTACCESS
 * Все остальные урлы вручную прочекал все ок.
 *
 * 2. 2 поста в базе почему то дублируют URL, видимо старая-новая версия, актуальную определил по количеству комментов к ней
 * в базе вручную выставил статус DRAFT вместо PUBLISH
 * u-obraznaya-flegmona-kisti-ili-pochemu-obshhij-analiz-krovi-berut-iz-chetvertogo-palca
 * lechenie-vrosshego-nogtya-narodnymi-sredstvami
 *
 * 3. /zadat-vopros.html > /voprosy/zadat-vopros-hirurgu.html
 * Был пост где задают вопросы, хотел сделать страницей - не получается, комменты не выводятся, только постом можно.
 * Пришлось создать отдельную рубрику и под нее пост.
 * /voprosy/zadat-vopros-hirurgu.html
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_wp_zapdoc';
mysqli_connect2('dev_modx_zapdoc');
$domain = "http://zapdoc1.ru";

$query = "SELECT `t1`.`id`,`t1`.`pagetitle`,`t1`.`alias`,`t2`.`value` AS `description`,`t1`.`content`,`t1`.`uri`,`t1`.`publishedon`, `t3`.`comments` AS `comments_count`
FROM `modx_site_content` AS `t1`
JOIN `modx_site_tmplvar_contentvalues` AS `t2`
ON `t1`.`id` = `t2`.`contentid`
LEFT JOIN `modx_tickets_threads` AS `t3` 
ON `t1`.`id` = `t3`.`resource`
WHERE 
`uri` NOT LIKE 'auth/%' 
AND `uri` NOT LIKE 'tech/%' 
AND `content` != '' 
#AND `class_key` = 'TicketsSection'
AND `template` = 4
AND `t2`.tmplvarid = 1
ORDER BY `t1`.`id` ASC;";

$res = dbquery($query);
$post_id = get_ai('wp_posts');

foreach ($res as $post) {

    $term_taxonomy_id = tmp_modx_getcat($post['uri']);
    $post_date = date('Y-m-d H:i:s', $post['publishedon']);
    if (!$term_taxonomy_id) {
        echo2("$post[id] Не найдена категория!");
        continue;
    }
    $post_content = mysqli_real_escape_string($link, $post['content']);
    $post_title = mysqli_real_escape_string($link, $post['pagetitle']);
    if ($post['comments_count'] == NULL) {
        $comment_count = 0;
    } else {
        $comment_count = $post['comments_count'];
    }
    $queries[] = "INSERT INTO `dev_wp_zapdoc`.`wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) 
VALUES ($post_id, 1, '$post_date', '$post_date','$post_content', '$post_title', '', 'publish', 'closed', 'closed', '', '$post[alias]', '', '', '$post_date', '$post_date', '', 0, '$domain/?p=$post_id', 0, 'post', '', $comment_count);";
    $queries[] = "INSERT INTO `dev_wp_zapdoc`.`wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $term_taxonomy_id, 0);";
    dbquery($queries);
    unset($queries);
    $post_id++;
}

function tmp_modx_getcat($uri)
{
    $tmp = explode("/", $uri);
    if ($tmp[1]) {
        $tmp2 = dbquery("SELECT `term_id` FROM `dev_wp_zapdoc`.`wp_terms` WHERE `slug` = '$tmp[0]';");
    } else {
        return 1;
    }
    if ($tmp2) {
        $tmp = dbquery("SELECT `term_taxonomy_id` FROM `dev_wp_zapdoc`.`wp_term_taxonomy` WHERE `term_id` = $tmp2;");
        return $tmp;
    }
    return FALSE;
}