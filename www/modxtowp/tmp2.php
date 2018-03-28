<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 18.03.2018
 * Time: 15:16
 * IMPORT COMMENTS - с костылями
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_wp_zapdoc';
mysqli_connect2('dev_modx_zapdoc');
$domain = "http://zapdoc1.ru";

$query = "SELECT `t1`.`id`,`t1`.`alias` FROM `dev_modx_zapdoc`.`modx_site_content` AS `t1`
#JOIN `modx_site_tmplvar_contentvalues` AS `t2`
#ON `t1`.`id` = `t2`.`contentid`
WHERE 
`uri` NOT LIKE 'auth/%' 
AND `uri` NOT LIKE 'tech/%' 
AND `content` != '' 
#AND `class_key` = 'TicketsSection'
AND `template` IN (4,6) #6 это страница с zadat-vopros.html на которой 600 комментов
#AND `t2`.tmplvarid = 1
ORDER BY `t1`.`id` ASC;";

$res = dbquery($query, 1, 0, 0, 0, 1);
foreach ($res as $item) {
    $tmp = dbquery("SELECT `id` FROM `dev_modx_zapdoc`.`modx_tickets_threads` WHERE `resource` = $item[0];");
    if (!$tmp) {
        echo2("ID $item[0] - NO COMMENTS FOR POST $item[1]");
    } else {
        if ($tmp = dbquery("SELECT * FROM `dev_modx_zapdoc`.`modx_tickets_comments` WHERE `thread` = $tmp AND `published` = 1;")) {
            //comment_post_ID (wp_posts.ID)
            $tmp2 = dbquery("SELECT `id` FROM `dev_wp_zapdoc`.`wp_posts` WHERE `post_name` = '$item[1]' AND `post_status` = 'publish';");
            foreach ($tmp as $comment) {
                if (dbquery("SELECT `comment_ID` FROM `dev_wp_zapdoc`.`wp_comments` WHERE `comment_author` = '$comment[name]' AND `comment_author_email` = '$comment[email]' AND `comment_author_IP` = '$comment[ip]' AND `comment_date` = '$comment[createdon]';", 0, 0, 0, 1, 1)) {
                    continue;
                }
                $comment_text = mysqli_real_escape_string($link, $comment['text']);


                if ($comment['email'] == 'info@zapdoc.ru') {
                    $comment_author = 1;
                } else {
                    $comment_author = 0;
                }


                if ($comment['parent'] == 0) {
                    $comment_parent = 0;
                    dbquery("INSERT INTO `dev_wp_zapdoc`.`wp_comments` VALUES ('', $tmp2, '$comment[name]', '$comment[email]', '', '$comment[ip]', '$comment[createdon]', '$comment[createdon]', '$comment_text', 0, '1', '', '', $comment_parent, $comment_author);");
                } else {
                    $tmp3 = dbquery("SELECT `name`,`email`,`ip`,`createdon` FROM `dev_modx_zapdoc`.`modx_tickets_comments` WHERE `id` = $comment[parent];", 0, 0, 0, 0, 1);
                    if ($tmp3) {
                        $tmp4 = dbquery("SELECT `comment_ID` FROM `dev_wp_zapdoc`.`wp_comments` WHERE `comment_author` = '$tmp3[name]' AND `comment_author_email` = '$tmp3[email]' AND `comment_author_IP` = '$tmp3[ip]' AND `comment_date` = '$tmp3[createdon]';");
                        if ($tmp4) {
                            $comment_parent = $tmp4;
                            dbquery("INSERT INTO `dev_wp_zapdoc`.`wp_comments` VALUES ('', $tmp2, '$comment[name]', '$comment[email]', '', '$comment[ip]', '$comment[createdon]', '$comment[createdon]', '$comment_text', 0, '1', '', '', $comment_parent, $comment_author);");
                        }
                    } else {
                        echo2("$comment[id] - parent not found");
                    }
                }
                unset($tmp3, $tmp4);
            }
        }
    }
    unset($tmp, $tmp2, $queries);
}
echo2("hello!");