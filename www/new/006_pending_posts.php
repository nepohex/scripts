<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 20.11.2016
 * Time: 20:39
 * Определенное количество постов нужно отправить в запланированные для дальнейшей публикации плагином Auto Post Scheduler
 * Можно прогонять несколько раз, каждый раз при данных $publish = 30 будет 30% от опубликованных записей присваиваться статус pending
 */
include "multiconf.php";
next_script(0,1);
mysqli_connect2();
//$fp = fopen($result_dir.$log_file,"a");

$query = "SELECT `ID` FROM `wp_posts` WHERE `post_status` = 'publish' AND `post_type` = 'post';";
$post_ids = dbquery($query,1);
shuffle($post_ids);

$i=0;
foreach ($post_ids as $ids) {
    $query = "UPDATE `wp_posts` SET `post_status` = 'pending' WHERE `ID` ='".$ids."';";
    dbquery($query);
    if ($i>(count($post_ids)*$publish/100)) {
        break;
    }
    $i++;
}
echo2 ("Поставили статус PENDING для _ ".(count($post_ids)*$publish/100)." _ / ".count($post_ids)." PUBLISH строк из wp_posts. Если мало, можно запустить еще раз и отправить еще такой же % от текущих PUBLISH в PENDING.");
next_script();