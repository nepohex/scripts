<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 24.11.2017
 * Time: 19:02
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';

ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$db_usr = 'root';
$db_pwd = '';
$db_name = 'dev_wp_parser';
$fp_log = 'log.txt';

$wp_domain = 'http://www.sitoweb.biz';

$mysqli = new mysqli("localhost", $db_usr, $db_pwd, $db_name);

/* проверка подключения */
if (mysqli_connect_errno()) {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
}

$post_id = dbquery("SELECT MAX(`ID`) FROM `dev_gz`.`wp_posts4`;");
$meta_id = dbquery("SELECT MAX(`meta_id`) FROM `dev_gz`.`wp_postmeta4`;");
$cat_ids = dbquery("SELECT `term_id`,`slug` FROM `dev_gz`.`wp_terms`;");
$menu_order = 200;
///////////////////SWAP fin posts////////////////////////
$query = "SELECT * FROM `dev_wp_parser`.`content`;";
if ($result = $mysqli->query($query)) {

    /* выборка данных и помещение их в массив */
    while ($row = $result->fetch_row()) {
        /* подготавливаемый запрос, первая стадия: подготовка */
        if (!($stmt = $mysqli->prepare("INSERT INTO `dev_gz`.`wp_posts4` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(?, 1, '2017-11-22 18:30:54', '2017-11-22 15:30:54', ?, ?, '', 'publish', 'closed', 'closed', '', ?, '', '', '2017-11-22 18:30:54', '2017-11-22 15:30:54', '', 0, ?, ?, 'post', '', 0)"))
        ) {
            echo "Не удалось подготовить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
        }
        $post_id++;
        $menu_order++;
        $content = $row[1];
        $title = $row[2];
        $post_name = tmp_gen_post_name($row[3]);
        $guid = $wp_domain . '/?p=' . $post_id;
        $meta = unserialize($row[4]);
//http://www.php.su/mysqli_stmt_bind_param
        if (!$stmt->bind_param('issssi', $post_id, $content, $title, $post_name, $guid, $menu_order)) {
            echo "Не удалось привязать параметры: (" . $stmt->errno . ") " . $stmt->error;
        }
        /* execute prepared statement */
        $stmt->execute();
        $term_taxonomy_id = tmp_get_taxonomy_id($cat_ids, $meta['cat']);
        dbquery("INSERT INTO `dev_gz`.`wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ($post_id, $term_taxonomy_id, 0)");

        $seo_title = mysqli_real_escape_string($link, $meta['title']);
        $meta_description = mysqli_real_escape_string($link, $meta['description']);
//        $meta_keywords = mysqli_real_escape_string($link, $meta['']);
        $meta_id++;
        dbquery("INSERT INTO `dev_gz`.`wp_postmeta4` (`meta_id`,`post_id`,`meta_key`,`meta_value`) VALUES ($meta_id, $post_id, '_yoast_wpseo_title', '$seo_title')");
        $meta_id++;
        dbquery("INSERT INTO `dev_gz`.`wp_postmeta4` (`meta_id`,`post_id`,`meta_key`,`meta_value`) VALUES ($meta_id, $post_id, '_yoast_wpseo_metadesc', '$meta_description')");
        //если есть keywords
//        $meta_id++;
//        dbquery("INSERT INTO `dev_gz`.`wp_postmeta4` (`meta_id`,`post_id`,`meta_key`,`meta_value`) VALUES ($meta_id, $post_id, '_yoast_wpseo_focuskw', '$meta_description')");
    }
}

function tmp_gen_post_name($url)
{
    $tmp = explode('/', $url);
    $tmp2 = explode('.', last($tmp));
    $tmp3 = array_first($tmp2);
    return $tmp3;
}

function tmp_get_taxonomy_id(array $wp_terms, $needle)
{
    foreach ($wp_terms as $value) {
        if ($needle == $value['slug']) {
            return $value['term_id'];
        }
    }
}