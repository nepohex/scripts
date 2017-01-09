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
include ("mysqli_connect.php");
echo2 ("Начинаем выполнять скрипт ".$_SERVER['SCRIPT_FILENAME']);

$fp = fopen($result_dir.$log_file,"a");

$query = "Select `ID` from `wp_posts` where `post_status` = 'publish' and `post_type` = 'post';";
$sqlres = mysqli_query($link,$query);
while ($result = mysqli_fetch_row($sqlres)) {
    $post_ids[] = $result[0];
}
shuffle($post_ids);

$i=0;
foreach ($post_ids as $ids) {
    $query = "UPDATE `wp_posts` SET `post_status` = 'pending' WHERE `ID` ='".$ids."';";
    $sqlres = mysqli_query($link,$query);
    if ($i>(count($post_ids)*$publish/100)) {
        break;
    }
    $i++;
}
echo2 ("Поставили статус PENDING для _ ".(count($post_ids)*$publish/100)." _ / ".count($post_ids)." PUBLISH строк из wp_posts. Если мало, можно запустить еще раз и отправить еще такой же % от текущих PUBLISH в PENDING.");
echo2 ("Закончили со скриптом ".$_SERVER['SCRIPT_FILENAME']." Переходим к NEXT");
next_script ($_SERVER['SCRIPT_FILENAME']);
?>
