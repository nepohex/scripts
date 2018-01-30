<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.12.2017
 * Time: 0:16
 */
require '../../vendor/autoload.php';
include('../new/includes/functions.php');
//$console_mode = 1;
$fp_log = __DIR__ . '/debug/log.txt';
$debug_mode = 1;
$double_log = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'image_index';
$tname = 'instagram';
mysqli_connect2($db_name);

### Settings ###
//например https://www.instagram.com/rapunzel_ekb/ (http:// вначале и / слеш на концце)
//Удалил эту часть т.к.  если логин с точкой неверно определяет.
//$source_url = 'https://www.instagram.com/talia.stylist/'; //Будет добавлен как новый источник, надо вручную править потом количество
//$login = pathinfo($source_url, PATHINFO_FILENAME);

$get_images = 3000; //Сколько фоток получить из аккаунта
$sleep = 0; //В миллисекнудах запросы к инсте
$logins = array('stepanova_ek', 'kontierparis.tsvetnoy', 'strigasalon', 'alexkontier', 'victor.nadolsky', 'archi_svaryan_', 'allata_hair', 'dmitriy.oskin', 'kawaicat_salon', 'sheremetyevanton', 'toniandguy_moscow', 'polyninaluba', 'viktor__goncharenko', 'bugaevandrey', 'jul.bo__hair', 'studio_vg', 'steve_spb', 'mmtrfmv',  'hairgurunn', 'ryabchik.moscow','alena__famina','kalganovanasy','lenabogucharskaya','makeup_anme','talia.stylist','artstreetmoscow', 'timurbegichevstudio', 'gulevich.vladimir', '_yunusova_ekaterina', 'apropomakeup', 'matildainozemtseva', 'wazhmulya', 'spletnitca_com'
, 'anastasy_d', 'alexeeva_victoria', 'afro.style.dp', 'hair_stylist_l.mostovich', 'ssellenna', 'naida_style', 'vlasova_elena_guru', 'olesy88', 'schwarzkopfproru', 'xenia_stylist', 'ayvazzovsky'
, 'annapudra', 'nastya_mokk', 'marianna_hair_stylist', 'dyadkinaira', 'nastty_lisicina', 'bogdanovich.studio', 'elstile.models', 'eksnagustenko', 'olganova_mua','elstilespb');

$instagram = new \InstagramScraper\Instagram();

foreach ($logins as $login) {
    $img_dir = 'f:\Dumps\instagram\all_inst/' . $login . '/';
    prepare_dir($img_dir);

###Получаем основные переменные для работы ###
    $maxid = return_maxid($login); //Получаем ID последней фотки которую выгружали чтобы продолжить с того же места а не сначала
    $acc_stats = ig_get_stats($instagram, $login); //Нужно перед SourceID получением
    $sourceid = pins_get_sourceid($login, $acc_stats);

    if (check_need_dl($sourceid, $get_images) == FALSE) {
        echo2 ("Пропускаем аккаунт $login - закачано сколько нужно");
        continue;
    }

    echo2("$login - Medias $acc_stats[0] - Followers $acc_stats[1]");
    $result = $instagram->getPaginateMedias($login, $maxid);
    do {
        if ($sleep) {
            usleep($sleep);
        }
        if (count($result['medias']) > 0) {
            $c_got_medias += count($result['medias']);
            ig_wrapper($result['medias'], $sourceid, $img_dir, 1);
        } else {
            echo2("Выгрузили всего $c_got_medias/$get_images запрошенных фоток. Больше фоток не отдает.");
        }

        if ($result['hasNextPage'] === true) {
            if ($c_got_medias % 60 == 0) { //Запись maxid для докачки, чтобы не каждый запрос писать, а например кратные N.
                return_maxid($login, 1, $result['maxId']);
                echo_time_wasted("$c_got_medias/$get_images");
            }
            $result = $instagram->getPaginateMedias($login, $result['maxId']);
        } else {
            echo2("Выгрузили всего $c_got_medias/$get_images запрошенных фоток. Последняя страница, больше фоток нет.");
            break;
        }
    } while ($c_got_medias < $get_images);

    dbquery("UPDATE `image_index`.`instagram_sources` SET `images_took` = $c_got_medias WHERE `id` = $sourceid;");
    echo2("Выгрузили всего $c_got_medias/$get_images запрошенных фоток.");
    unset ($c_got_medias);
}
exit ();
//Ошибка после 1000-2000 запросов, и продолжить невозможно с того же места если не переписывать функцию чужую.
//$medias = $instagram->getMedias($login, 3000, 1610975502871811746);

function check_need_dl($sourceid, $get_images)
{
    $images_took = dbquery("SELECT `images_took` FROM `image_index`.`instagram_sources` WHERE `id` = $sourceid;");
    $images_total = dbquery("SELECT `images_total` FROM `image_index`.`instagram_sources` WHERE `id` = $sourceid;");
    if ($images_took < $get_images && $images_total > $get_images) {
        return TRUE;
    } else if ($images_took == FALSE) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function ig_get_stats($class, $login)
{
    $account = $class->getAccount($login);
    $res[] = $account->getMediaCount();
    $res[] = $account->getFollowedByCount();
    return $res;
}

function return_maxid($login, $save = FALSE, $maxid = '')
{
    $path = __DIR__ . '/debug/' . $login . '.txt';
    if ($save) {
        file_put_contents($path, $maxid);
        return TRUE;
    }
    if (is_file($path)) {
        return file_get_contents($path);
    } else {
        return '';
    }
}

function ig_wrapper($medias, $sourceid, $img_dir, $arraymode = 1)
{
    if ($arraymode) {
        foreach ($medias as $media) {
            if (ig_dl_img($media, $img_dir)) {
                ig_db_insert_img($media, $sourceid);
            }
        }
    } else {
        if (ig_dl_img($medias, $img_dir)) {
            ig_db_insert_img($medias, $sourceid);
        }
    }
}

function ig_dl_img($media, $save_dir)
{
    if (($media->getType() == 'image' | $media->getType() == 'sidecar') && $media->isAd() == FALSE) {
        $file = $media->getImageHighResolutionUrl();
        $fname = basename($file);
        if (!is_file($save_dir . $fname)) {
            $fdata = file_get_contents($file);
            return file_put_contents($save_dir . $fname, $fdata);
        }
    }
    return FALSE;
}

function ig_db_insert_img($media, $sourceid)
{
    $file = $media->getImageHighResolutionUrl();
    $fname = basename($file);
    $timestamp = date("Y-m-d H:i:s", $media->getCreatedTime());
    $link = $media->getShortCode();
    dbquery("INSERT INTO `image_index`.`instagram_images`  (`sourceid`, `fname`, `link`, `timestamp`) VALUES ($sourceid, '$fname', '$link', '$timestamp');");
}

function get_medias($class, $login, $n, $maxid = '', $return_medias = 1, $sleep = 100)
{
    $all_photos = array();
    $result = $class->getPaginateMedias($login, $maxid);
    do {
        if ($sleep) {
            usleep($sleep);
        }
        if (count($result['medias']) > 0) {
            $all_photos = array_merge($all_photos, $result['medias']);
        } else {
            echo2("Выгрузили всего " . count($all_photos) . "/$n запрошенных фоток.");
            return $all_photos;
        }

        if ($result['hasNextPage'] === true) {
            $result = $class->getPaginateMedias($login, $result['maxId']);
        } else {
            echo2("Выгрузили всего " . count($all_photos) . "/$n запрошенных фоток.");
            return $all_photos;
        }
    } while (count($all_photos) < $n);
    echo2("Выгрузили всего " . count($all_photos) . "/$n запрошенных фоток.");
    return $all_photos;
}

function pins_get_sourceid($source_url, $acc_stats = FALSE)
{
    if ($id = dbquery("SELECT `id` FROM `image_index`.`instagram_sources` WHERE `url` = '$source_url';")) {
        return $id;
    } else if ($acc_stats) {
        dbquery("INSERT INTO `image_index`.`instagram_sources` (`url`, `images_total`, `followers`) VALUES ('$source_url', $acc_stats[0], $acc_stats[1]);");
    } else {
        dbquery("INSERT INTO `image_index`.`instagram_sources` (`url`) VALUES ('$source_url');");
    }
//    echo2("Вставили в базу `image_index`.`instagram_sources` новый источник картинок $source_url , вручную надо пометить количество фоток всего и сколько взяли из них ");
    return dbquery("SELECT `id` FROM `image_index`.`instagram_sources` WHERE `url` = '$source_url';");
}
