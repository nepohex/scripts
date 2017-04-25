<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.03.2017
 * Time: 21:57
 */
//todo Дописан только минимальный функционал, осталось чекать Релатед картинки и заливать их тоже.
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

//$console_mode = 1;
$debug_mode = 1;
$double_log = 1;
$fp_log = __DIR__.'/extractor/log.txt';

$start_time = time();

$domains = file(__DIR__ . '/extractor/small_domains.txt', FILE_IGNORE_NEW_LINES);
foreach ($domains as $domain) {
//Данные для инсталлера на хостинг , пути для кеша
    $installer_db_host = 'localhost';
    $installer_db_usr = 'root';
    $installer_db_pwd = 'RABCkgt0rKhF';
    //Идет в wp-config
    $wpcachehome = '/home/wtfowned/web/' . $domain . '/public_html/wp-content/cache/';
    //Идет в /wp-content/wp-cache-config.php
    $wp_super_cachehome = '/home/wtfowned/web/' . $domain . '/public_html/wp-content/plugins/wp-super-cache/';

//Localhost
    $db_name = $domain;
    $db_host = 'localhost';
    $db_pwd = '';
    $db_usr = 'root';
    $db_instance = __DIR__ . '/extractor/_instance/extractor_instance_updated.sql'; // Пустая база данных с таблицами Wordpress, которая будет создаваться каждый раз для нового сайта. Лежать будет пока в папке со скриптом.
//$table_name = 'my_domains';
//$table_result = 'extractor_instance';
//mysqli_connect2($db_name);
    $wp_conf_db_prefix = 'wtfowned_';
    $wp_conf_tpl = 'wp_conf_empty.txt';
    $wp_conf_cache_tpl = 'wp_cache_conf_tpl.txt';

    $domain_dir = __DIR__ . '/extractor/' . $domain;
    $wp_dir = $domain_dir . '/wp-content/uploads/2017/04';

    $site_name = $domain;
    $site_url = 'http://' . $site_name . '/';
    $blogname = $domain;
    $blogdescription = '';
    $default_cat_name = 2017;
    $default_cat_slug = 2017;

    $pin_acc = 'inga.tarpavina.89@mail.ru';
    $pin_pwd = 'xmi0aJByoB';

    pinterest_local_login($pin_acc, $pin_pwd);

    gen_wp_db_conf($site_name, $wp_conf_db_prefix);
    import_db_instance();
    mk_site_dir($wp_dir);
    copy(__DIR__ . '/pin_inst.zip', $domain_dir . '/pin_inst.zip');

    $tmp = file_get_contents(__DIR__ . '/extractor/_instance/' . $wp_conf_tpl);
    file_put_contents($domain_dir . '/wp-config.php', '<?php' . PHP_EOL . 'define (\'WPCACHEHOME\', \'' . $wp_super_cachehome . '\');' . PHP_EOL . 'define(\'DB_NAME\', \'' . $wp_conf_db_name . '\');' . PHP_EOL . 'define(\'DB_USER\', \'' . $wp_conf_db_usr . '\');' . PHP_EOL . 'define(\'DB_PASSWORD\', \'' . $wp_conf_db_pwd . '\');' . PHP_EOL . $tmp);
    echo2("Сгенерили wp-config для нового сайта, записали в $domain_dir содержимое. Данные для сайта : db_name , db_user , db_pwd : $wp_conf_db_name $wp_conf_db_usr $wp_conf_db_pwd");

    $tmp = file_get_contents(__DIR__ . '/extractor/_instance/' . $wp_conf_cache_tpl);
    file_put_contents($domain_dir . '/wp-content/wp-cache-config.php', '<?php' . PHP_EOL . '$cache_path = \'' . $wpcachehome . '\';' . PHP_EOL . $tmp);

    gen_installer(__DIR__ . '/extractor/_instance/installer_instance.txt', $domain_dir . '/installer.php', $installer_db_host, $installer_db_usr, $installer_db_pwd, $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd, 'dump.sql');
// --
    $pins = $bot->pins->fromSource($domain, 500)->toArray();
    echo2("Получили " . count($pins) . " пинов");
    file_put_contents($domain_dir . '_pins.txt', serialize($pins));
    $pins = unserialize(file_get_contents($domain_dir . '_pins.txt'));
    $pins_unique = extract_unique_pins();
    get_imgs($pins_unique, $wp_dir);
    mysqli_connect2($db_name);
    pins_wp_insert($pins_unique, $domain, $wp_image_upload_date_prefix);
    Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = 'dump.sql', $domain_dir . '/');
}

function pins_wp_insert($arr, $domain, $wp_image_upload_date_prefix)
{
    $wp_image_upload_date_prefix = '2017/04/';
    $site_url = 'http://' . $domain;
    $site_uploads_path = $site_url . '/wp-content/uploads/';
    //Offsets
    $meta_id = 100;
    $image_id = 20000;
    $post_id = 40000;
// пример того что падает в wp_postmeta массив с данными о картинке
    $exmpl = unserialize('a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}');
//Это сам запрос для инсерта
//INSERT INTO  `sh_parse1`.`wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES ( '4',  '4',  '_wp_attachment_metadata', 'a:5:{s:5:"width";i:239;s:6:"height";i:239;s:4:"file";s:18:"2016/11/podves.jpg";s:5:"sizes";a:1:{s:9:"thumbnail";a:4:{s:4:"file";s:18:"podves-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}' );
    $i = 0;
    $z = 0; //Сколько по факту добавили картинок в посты
    $urls = array();
    foreach ($arr as $item) {
        $i++;
        if ($item['actions'] > 2) {
            $z++;
            $img_name = basename($item['image']['url']);
            $tmp = explode(".", $img_name);
            $cropped_img_name = $tmp[0] . "-150x150." . $tmp[1];
            $img_fullpath = $site_uploads_path . $wp_image_upload_date_prefix . $img_name;
            if (($img_title = $item['title']) == false) {
                $img_title = $item['description'];
                if ($img_title == false) {
                    $img_title = $item['id'];
                }
            }
            $img_title = remove_none_word_chars($img_title);

            //Images Insert
            $array_to_postmeta['width'] = $item['image']['width'];
            $array_to_postmeta['height'] = $item['image']['height'];
            $array_to_postmeta['file'] = $wp_image_upload_date_prefix . $img_name;
            $array_to_postmeta['sizes']['thumbnail']['file'] = $cropped_img_name;
            $array_to_postmeta['sizes']['thumbnail']['width'] = 150;
            $array_to_postmeta['sizes']['thumbnail']['height'] = 150;
            $array_to_postmeta['sizes']['thumbnail']['mime_type'] = 'image/jpeg'; //todo Hardcode
            $array_to_postmeta['image_meta'] = $exmpl['image_meta'];
            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attached_file','" . $array_to_postmeta['file'] . "');";
            $meta_id++;
            $queries[] = "INSERT INTO  `wp_postmeta` ( `meta_id` , `post_id` , `meta_key` , `meta_value` ) VALUES (" . $meta_id . "," . $image_id . ",  '_wp_attachment_metadata','" . addslashes(serialize($array_to_postmeta)) . "');";
            $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($image_id, 1, '2017-04-07 00:05:53', '2016-04-07 21:05:53','', '" . $img_name . "', '', 'inherit', 'closed', 'closed', '', '" . $img_name . "', '', '', '2017-04-07 00:05:53', '2017-04-07 21:05:53', '', 0, '" . $site_uploads_path . $wp_image_upload_date_prefix . $img_name . "', 0, 'attachment', '" . $array_to_postmeta['sizes']['thumbnail']['mime_type'] . "', 0);";
            if (in_array($item['link'], $urls) == false) {
                $urls[] = $item['link'];
                $index[$post_id]['link'] = $item['link'];
                $index[$post_id]['id'] = $post_id;
                //Posts insert
                // Готовим содержимое будущего поста, пример:
                // <img src="http://mh_parse.loc/wp-content/uploads/2016/11/5230_medium-length-hair-styles-layered-hair-styles-55deb08e15624.jpg" alt="Medium Length Hairstyles 2017" title="Medium Length Hairstyles 2017" width="1024" height="1137" class="alignnone size-full wp-image-5124" />
                $post_content = "<img src=\"" . $img_fullpath . "\" alt=\"" . $img_title . "\" title=\"" . $img_title . "\" width=\"" . $item['image']['width'] . "\" height=\"" . $item['image']['height'] . "\" class=\"alignnone size-full wp-image-" . $item['id'] . "\" /><br /><br />";
                // Должно получиться нечто : INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (10000, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', 'Medium Length Layered Haircut With Bangs', '', 'publish', 'closed', 'closed', '', '5126_medium-length-layered-haircut-with-bangs', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, 'http://mh_parse.loc/?p=10000', 0, 'post', '', 0);
                $queries[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ($post_id, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','" . $post_content . "', '" . $img_title . "', '', 'publish', 'closed', 'closed', '', $post_id, '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, '" . $site_url . "/?p=" . $post_id . "', 0, 'post', '', 0);";

                //INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (10000, 1, 0); - Для нулевого сайта, в стандартную категорию.
                $queries[] = "INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES (" . $post_id . ", 1, 0);";

                //Update post parent для Attachment.
                $queries[] = "UPDATE `wp_posts` SET `post_parent` = $post_id WHERE `id` = $image_id;";
            } else {
                //
                $tmp_post_id = $post_id;
                foreach ($index as $value) {
                    if ($value['link'] == $item['link']) {
                        $post_id = $value['id'];
                        break;
                    }
                }
                $index[$post_id]['finished'] = 1;
                if ($index[$post_id]['concat'] == 1) {
                    $post_content = addslashes("<br><img src=\"" . $img_fullpath . "\" alt=\"" . $img_title . "\" title=\"" . $img_title . "\" width=\"" . $item['image']['width'] . "\" height=\"" . $item['image']['height'] . "\" class=\"alignnone size-full wp-image-" . $item['id'] . "\" />");
                } else {
                    $index[$post_id]['concat'] = 1;
                    $post_content = addslashes("<a href=\"javascript:void(0)\" onclick=\"growDiv()\" class=\"button\"><span>Expand Photos</span></a><div id=\"grow\"><div class=\"measuringWrapper\"><img src=\"" . $img_fullpath . "\" alt=\"" . $img_title . "\" title=\"" . $img_title . "\" width=\"" . $item['image']['width'] . "\" height=\"" . $item['image']['height'] . "\" class=\"alignnone size-full wp-image-" . $item['id'] . "\" />");
                }
                //Дописываем пост.
//            <a href="javascript:void(0)" onclick="growDiv()" class="button"><span>Expand Photos</span></a><div id="grow"><div class="measuringWrapper">
                // <img src="http://mh_parse.loc/wp-content/uploads/2016/11/5230_medium-length-hair-styles-layered-hair-styles-55deb08e15624.jpg" alt="Medium Length Hairstyles 2017" title="Medium Length Hairstyles 2017" width="1024" height="1137" class="alignnone size-full wp-image-5124" />
//                $post_content = addslashes("<a href=\"javascript:void(0)\" onclick=\"growDiv()\" class=\"button\"><span>Expand Photos</span></a><div id=\"grow\"><div class=\"measuringWrapper\"><img src=\"" . $img_fullpath . "\" alt=\"" . $img_title . "\" title=\"" . $img_title . "\" width=\"" . $item['image']['width'] . "\" height=\"" . $item['image']['height'] . "\" class=\"alignnone size-full wp-image-" . $item['id'] . "\" />");
                // Должно получиться нечто : INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (10000, 1, '2016-11-19 00:05:53', '2016-11-18 21:05:53','', 'Medium Length Layered Haircut With Bangs', '', 'publish', 'closed', 'closed', '', '5126_medium-length-layered-haircut-with-bangs', '', '', '2016-11-19 00:05:53', '2016-11-18 21:05:53', '', 0, 'http://mh_parse.loc/?p=10000', 0, 'post', '', 0);
                $queries[] = "UPDATE `wp_posts` SET `post_content` = CONCAT(`post_content`,'$post_content') WHERE `id` = $post_id;";

                //Update post parent для Attachment.
                $queries[] = "UPDATE `wp_posts` SET `post_parent` = $post_id WHERE `id` = $image_id;";
                $post_id = $tmp_post_id;
            }
            dbquery($queries);
            unset($queries);
        }
        $image_id++;
        $post_id++;
        $meta_id++;
    }
    $post_content = "</div></div>";
    foreach ($index as $item) {
        if ($item['finished']) {
            //Закрываем див со слайдером
            $queries[] = "UPDATE `wp_posts` SET `post_content` = CONCAT (`post_content`, '$post_content') WHERE `id` = " . $item['id'] . ";";
        }
        //Заполняем таблицу редиректов плагина Redirections
        //INSERT INTO `wp_redirection_items` (`id`, `url`, `regex`, `position`, `last_count`, `last_access`, `group_id`, `status`, `action_type`, `action_code`, `action_data`, `match_type`, `title`) VALUES (NULL, '/2016/01/pictures-of-hair-braids-styles.html?m=1&utm_content=buffer6334b&utm_medium=social&utm_source=pinterest1', '0', '2', '1', '2017-04-11 23:06:44', '1', 'enabled', 'url', '301', 'http://heartshapeface.top/shape/1', 'url', NULL);
        $tmp = parse_url($item['link']);
        if ($tmp['query']) {
            $redir_url = $tmp['path'] . '?' . $tmp['query'];
        } else {
            $redir_url = $tmp['path'];
        }
        $queries[] = "INSERT INTO `wp_redirection_items` (`id`, `url`, `regex`, `position`, `last_count`, `last_access`, `group_id`, `status`, `action_type`, `action_code`, `action_data`, `match_type`, `title`) VALUES (NULL, '$redir_url', '0', '2', '1', '2017-04-11 23:06:44', '1', 'enabled', 'url', '301', '" . $item['id'] . "', 'url', NULL);";
    }
    dbquery($queries);
    echo2("Добавили всего " . count($urls) . " URL , на них приходится $z картинок которые легли в эти посты.");
    return $index;
}

exit();

//todo 2ая часть скрипта еще не дописана, запись похожих пинов в базу
//Нужны для функций - не менять!
$my_images = 0; //Сколько уникальных картинок из наших пинов
$similar_images = 0; //Похожих картинок извлекли для наших пинов и для related пинов.
$boards = 0; //Сколько тегов для картинок наших и similar + related
$related_images = 0; //Похожие картинки к пинам у которых больше 500 сигналов.
$unique_urls = 0; // Сколько уникальных URL для уникальных картинок.

$pins_boards = get_pins_deep($pins_unique);
file_put_contents($domain_dir . '_pins_deep.txt', serialize($pins_boards));
$pins_boards = unserialize(file_get_contents($domain_dir . '_pins_deep.txt'));
$pins_related = get_top_related($pins_boards, 500);
file_put_contents($domain_dir . '_pins_related.txt', serialize($pins_related));
$pins_related = unserialize(file_get_contents($domain_dir . '_pins_related.txt'));
$pins_minimum = minimum_tags($pins_related, $boards, 500);
file_put_contents($domain_dir . '_pins_minimum_tags.txt', serialize($pins_minimum));
$i = 0;
echo2("$domain => my images $my_images / similar images $similar_images / tags $boards / related images $related_images ");

function pinterest_local_login($pin_acc, $pin_pwd)
{
    global $bot;
    $bot = PinterestBot::create();
    $bot->auth->login($pin_acc, $pin_pwd);
    if ($bot->auth->isLoggedIn()) {
        echo2("login success! Local IP and $pin_acc:$pin_pwd");
    } else {
        echo2("login failed!");
        exit();
    }
}

function clean_url($url)
{
    //для medhair много урлов с решеткой вконце (addthis), приравниваем
    if (strpos($url, "#") == true) {
        $url = stristr($url, "#", true);
    }
    //https приравниваем к http
    if (preg_match("/https.*/", $url)) {
        $url = 'http' . substr($url, 5);
    }
    return $url;
}

function extract_unique_pins()
{
    global $pins, $domain, $my_images, $unique_urls;
    $tmp_top_pins = array();
    foreach ($pins as $pin) {
        if (preg_match('/.*' . $domain . '.*/i', $pin['domain'])) {
            $sign = $pin['image_signature'];
            $actions = $pin['repin_count'] + $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
            if ($tmp_top_pins[$sign][0] < $actions) {
                $tmp_top_pins[$sign]['id'] = $pin['id'];
                $tmp_top_pins[$sign]['actions'] = $actions;
                $tmp_top_pins[$sign]['link'] = clean_url($pin['link']);
                $tmp_top_pins[$sign]['description'] = $pin['description'];
                $tmp_top_pins[$sign]['title'] = $pin['title'];
                $tmp_top_pins[$sign]['image'] = $pin['images']['736x'];
                $tmp_arr[] = clean_url($pin['link']);
            }
        }
    }
    $arr2 = array_msort($tmp_top_pins, array('actions' => SORT_DESC));
    $my_images = count($arr2);
    $unique_urls = array_unique($tmp_arr);
    return $arr2;
}

function get_pins_deep($arr)
{
    global $bot, $similar_images, $boards;
    echo_time_wasted(null, "Собираем визуально похожие для уникальных " . count($arr) . " картинок.");
    $z = 0;
    foreach ($arr as $key => $item) {
        $similar = $bot->pins->visualSimilar($item['id']);
        if (count($similar['result_pins']) > 0) {
            $arr[$key]['boards'] = $similar['annotations'];
            $boards += count($similar['annotations']);
            $i = 0;
            foreach ($similar['result_pins'] as $other_pins) {
                $arr[$key]['similar'][$i]['id'] = $other_pins['id'];
                $arr[$key]['similar'][$i]['actions'] = $other_pins['like_count'] + $other_pins['repin_count'];
                $arr[$key]['similar'][$i]['link'] = $other_pins['link'];
                $arr[$key]['similar'][$i]['description'] = $other_pins['description'];
                $arr[$key]['similar'][$i]['image'] = $other_pins['images']['736x']['url'];
                $i++;
            }
            $z++;
            $similar_images += $i;
            echo_time_wasted($z, "Досок(тегов) $boards");
        }
    }
    return $arr;
}

function get_top_related($arr, $minimum_actions = 500, $step = 100)
{
    global $bot, $related_images;
    echo_time_wasted(null, "Собираем related для топовых пинов где больше 500 экшенов " . count($arr) . " пинов.");
    foreach ($arr as $key => $item) {
        if ($item['actions'] > $minimum_actions) {
            echo2("Нашелся pin с > 500 Actions, парсим $step Related картинок");
            $related = $bot->pins->related($item['id'], $step);
            $i = 0;
            foreach ($related as $pin) {
                $arr[$key]['related'][$i]['id'] = $pin['id'];
                $arr[$key]['related'][$i]['actions'] = $pin['repin_count'] + $pin['aggregated_pin_data']['aggregated_stats']['saves'] + $pin['aggregated_pin_data']['aggregated_stats']['likes'];
                $arr[$key]['related'][$i]['link'] = clean_url($pin['link']);
                $arr[$key]['related'][$i]['description'] = $pin['description'];
                $arr[$key]['related'][$i]['image'] = $pin['images']['736x']['url'];
                $i++;
                echo_time_wasted($i);
            }
            $related_images += count($arr[$key]['related']);
        }
    }
    return $arr;
}


function minimum_tags($arr, $boards, $minimum_tags = 1000)
{
    global $bot, $similar_images, $boards;
    echo2("Производим добивку до количества тегов минимум $minimum_tags , парсим Related картинки ищем Similar");
    if ($boards > $minimum_tags) {
        echo2("Количество тегов больше необходимого минимума в $minimum_tags , все ок");
        return $arr;
    }
    foreach ($arr as $key => $item) {
        if ($item['related'] == true) {
            $z = 0;
            foreach ($item['related'] as $key_r => $value_r) {
                $z++;
                $similar = $bot->pins->visualSimilar($value_r['id']);
                if (count($similar['result_pins']) > 0) {
                    $arr[$key]['related'][$key_r]['boards'] = $similar['annotations'];
                    $boards += count($similar['annotations']);
                    $i = 0;
                    foreach ($similar['result_pins'] as $other_pins) {
                        $arr[$key]['related'][$key_r]['similar'][$i]['id'] = $other_pins['id'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['actions'] = $other_pins['like_count'] + $other_pins['repin_count'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['link'] = $other_pins['link'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['description'] = $other_pins['description'];
                        $arr[$key]['related'][$key_r]['similar'][$i]['image'] = $other_pins['images']['736x']['url'];
                        $i++;
                    }
                    $similar_images += $i;
                    echo_time_wasted($z, "Досок(тегов) всего $boards");
                    if ($boards > $minimum_tags) {
                        echo2("Количество тегов больше необходимого минимума в $minimum_tags , все ок");
                        return $arr;
                    }
                }
            }
        }
    }
    echo2("Не набрали минимум тегов в $minimum_tags , используем те что есть $boards");
    return $arr;
}

function check_max($written, $attempt_to_write)
{
    if ($written < $attempt_to_write) {
        $written = $attempt_to_write;
    }
    if ($written === null) {
        $written = 0;
    }
    return $written;
}

function mk_site_dir($domain_dir)
{
    if (is_dir($domain_dir)) {
        echo2("Создали рабочую директрию $domain_dir");
        return true;
    } else {
        mkdir2($domain_dir);
    }
}

function get_imgs($arr, $domain_dir)
{
    $counter_img_filesize_total = 0;
    $i = 0;
    $z = 0; //success download
    echo2("Начинаем качать и кропать " . count($arr) . " картинок. 1 минута ~ 50 картинок.");
    foreach ($arr as $item) {
        $i++;
        $image_name = basename($item['image']['url']);
        $image_local_path = $domain_dir . '/' . $image_name;
        $tmp = explode(".", $image_name);
        $cropped_img_name = $domain_dir . '/' . $tmp[0] . "-150x150." . $tmp[1];
        if ($item['image'] == true && is_file($image_local_path) == false) {
            file_put_contents($image_local_path, file_get_contents($item['image']['url']));
            if (is_file($image_local_path) == true) {
                $z++;
                $counter_img_filesize_total += filesize($image_local_path);
            }
        } else {
            $z++;
        }
        if (is_file($image_local_path) == true && is_file($cropped_img_name) == false) {
            resize_crop_image(150, 150, $image_local_path, $cropped_img_name);
        }
    }
    $counter_img_filesize_total = $counter_img_filesize_total / 1024 / 1024; // Размер в MB картинок
    echo_time_wasted(false, "Скачали $z/$i картинок, общим размером $counter_img_filesize_total MB");
}

function resize_crop_image($max_width, $max_height, $source_file, $dst_dir, $quality = 80)
{
    $imgsize = getimagesize($source_file);
    $width = $imgsize[0];
    $height = $imgsize[1];
    $mime = $imgsize['mime'];

    switch ($mime) {
        case 'image/gif':
            $image_create = "imagecreatefromgif";
            $image = "imagegif";
            break;

        case 'image/png':
            $image_create = "imagecreatefrompng";
            $image = "imagepng";
            $quality = 7;
            break;

        case 'image/jpeg':
            $image_create = "imagecreatefromjpeg";
            $image = "imagejpeg";
            $quality = 80;
            break;

        default:
            return false;
            break;
    }

    $dst_img = imagecreatetruecolor($max_width, $max_height);
    ///////////////

    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $max_width, $max_height, $transparent);


    /////////////
    $src_img = $image_create($source_file);

    $width_new = $height * $max_width / $max_height;
    $height_new = $width * $max_height / $max_width;
    //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
    if ($width_new > $width) {
        //cut point by height
        $h_point = (($height - $height_new) / 2);
        //copy image
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        //cut point by width
        $w_point = (($width - $width_new) / 2);
        imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
    }

    $image($dst_img, $dst_dir, $quality);

    if ($dst_img)
        imagedestroy($dst_img);
    if ($src_img)
        imagedestroy($src_img);
}

function import_db_instance()
{

    global $db_usr;
    global $db_pwd;
    global $db_host;
    global $db_instance;
    global $db_name;
    global $site_url;
    global $blogname;
    global $blogdescription;
    global $default_cat_name;
    global $site_name;
    global $default_cat_slug;

    $link = mysqli_init();

// Соединяемся с базой 1ый раз, создаем ее
    if (mysqli_real_connect($link, $db_host, $db_usr, $db_pwd, $db_name)) {
        echo2("База уже есть, больше ее не трогаем, идем создавать папки ");
    } else {
        mysqli_real_connect($link, $db_host, $db_usr, $db_pwd);
        $query = "CREATE DATABASE `" . $db_name . "`"; // Не знаю почему но почему-то выводит ошибку что уже создана база данных, как ни крути
        if (mysqli_query($link, $query)) {
            echo2("Создали базу данных для работы, теперь ее заполняем " . $db_name);
            mysqli_query($link, "USE `" . $db_name . "`;");
            $templine = '';
            $lines = file($db_instance);
            if ($lines == false) {
                echo2("Не можем найти файл с импортом для таблицы, или пустой файл!");
            }
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || $line == '\n')
                    continue;
                $line = str_replace("\n", "", $line);
                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    mysqli_query($link, $templine) or echo2('Ошибка выполнения запроса' . $templine . ': ' . mysqli_error($link));
                    $templine = '';
                }
            }
            $tmp_pwd = pwdgen(14);
            echo2("Пароль для нового сайта $tmp_pwd");
            $tmp_pwd = md5($tmp_pwd);
            $queries_prepare[] = "UPDATE  `wp_terms` SET  `name` =  '" . $default_cat_name . "', `slug` =  '" . $default_cat_slug . "' WHERE  `wp_terms`.`term_id` =1;";
            $queries_prepare[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (NULL, '1', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '[kwayy-sitemap]', 'Sitemap', '', 'publish', 'closed', 'closed', '', 'sitemap', '', '', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '', '0', '" . $site_url . "?page_id=50', '0', 'page', '', '0');";
            $queries_prepare[] = "UPDATE `wp_users` SET `user_pass` ='" . $tmp_pwd . "' , `user_login` = 'wtfowned' WHERE `id` = 1;";
            $queries_prepare[] = 'UPDATE `wp_options` SET `option_value` =\'' . $site_url . '\' WHERE `option_id` = 1 OR `option_id` = 2';
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogname . "' WHERE `option_name` = 'blogname';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogdescription . "' WHERE `option_name` = 'blogdescription';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='http://" . $site_name . "/' WHERE `option_name` = 'ossdl_off_cdn_url';";
            $queries_prepare[] = "SELECT `option_value` FROM `wp_options` WHERE `option_name` = 'wpseo'";
            foreach ($queries_prepare as $query_pre) {
                $sqlres = mysqli_query($link, $query_pre);
            }
            $tmp = mysqli_fetch_row($sqlres);
            $ggf = unserialize($tmp[0]);
            $ggf['website_name'] = $blogname;
            $query = "UPDATE `wp_options` SET `option_value` = '" . addslashes(serialize($ggf)) . "' WHERE `option_name` = 'wpseo';";
            mysqli_query($link, $query);
            return echo2("Таблицы в базе данных заполнили!");
        } else {
            echo2("Создать базу данных " . $db_name . " не получилось, либо уже существует " . mysqli_error($link));
            echo2("Чтобы заполнить таблицу, ее нужно удалить и заново запустить скрипт.");
        }
    }
}