<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 19.01.2018
 * Time: 19:45
 */
//todo merge synonims и разные словоформы
//todo get multiple acc
include('../../new/includes/functions.php');
require('../../../vendor/autoload.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;

error_reporting('E_ERROR');
//$console_mode = 1;
$fp_log = __DIR__ . '/debug_data/' . time() . '_log.txt';
$debug_mode = 1;
$double_log = 1;
$db_pwd = '';
$db_usr = 'root';
$db_name = 'image_index';
$tname = 'instagram';
mysqli_connect2($db_name);
$pin_acc = 'inga.tarpavina.89@mail.ru';
$pin_pwd = 'xmi0aJByoB';
prepare_dir(dirname($fp_log));

//$descs = array(
//    3 => 'Ponytail Styles',
//    5 => 'hairstyle for long hair updo hairstyle',
//    19 => 'Tuck and Cover Half Looped French Braid',
//    23 => ' ',
//    24 => NULL,
//    25 => NULL,
//    26 => NULL,
//    27 => 'Twisted Low Ponytail',
//    49 => 'awesome kapsels opgestoken beste fotos',
//);
//$valid_descs = pin_filter_descs($descs, $allowed_words);
//$words_count = count_words($valid_descs);
//$z = words_weight($words_count, $valid_descs);

### Settings ###
$get_related_images = 100; //сколько похожих фоток доставать, по стандарту идет 50, из них получается 5-6 валидных описаний всего.
$get_max_description = 50; //максимальная длина описания фотки (со всеми спец символами) которую будем использовать для определения тематики
$base_img_dir = 'f:\Dumps\instagram\all_inst\\';

//done
//Взял топовые 5к фраз с 5 сайтов из консоли google webmaster, отсортировал по словам которые употребляются чаще всего
$allowed_words = file(__DIR__ . '/debug_data/good_words.txt', FILE_IGNORE_NEW_LINES);

//$pin_acc = get_pin_acc();
$login_success = pinterest_login_until();
//pinterest_local_login('b.toy@mail.ru','xom94ok');
$board_id = get_board_id($bot);

$i = 0;

while ($files = get_files_to_post(110)) {
    foreach ($files as $key => $file) {
        list($instagram_login, $img_id, $img_name) = $file;
        $i++;
        if (!$file['posted']) {
            $fname = $base_img_dir . $instagram_login . '/thumb/' . $img_name;
            if (is_file($fname)) {
                $pinInfo = $bot->pins->create($fname, $board_id, '');
                if ($pinid = $pinInfo['id']) {
                    $files[$key]['posted'] = 1;
                    $c_pins_created += 1;
                    dbquery("INSERT INTO `$db_name`.`$tname` (`pinid`, `img_id`) VALUES ('$pinid', '$img_id');");
                    dbquery("UPDATE `image_index`.`instagram_images` SET `posted` = '1' WHERE `id` = '$img_id';");
                    foreach ($bot->pins->related($pinid, $get_related_images) as $pin) {
                        if ($desc = pin_get_desc($pin, $get_max_description)) {
                            $descs[] = $desc;
                        }
                    }
                    if (!is_array($descs)) {
                        //Ничего не выводит, просто нет похожих пинов.
//                echo2($bot->getLastError());
                    } else {
                        $valid_descs = pin_filter_descs($descs, $allowed_words);
                        if (is_array($valid_descs)) {
                            $tmp = serialize(array_map('addslashes', $descs));
                            $words_count = count_words($valid_descs);
                            $auto_name = words_weight($words_count, $valid_descs);
                            if ($auto_name) {
                                $c_pins_named += 1;
                                dbquery("UPDATE `$db_name`.`$tname` SET `related_descriptions` = '$tmp', `auto_name` = '$auto_name' WHERE `img_id` = $img_id;");
                            } else {
                                dbquery("UPDATE `$db_name`.`$tname` SET `related_descriptions` = '$tmp' WHERE `img_id` = $img_id;");
                            }
                        }
                    }
                } else {
                    $c_accs += 1;
                    echo2($bot->getLastError());
                    echo2("#$c_accs $pin_acc[0]:$pin_acc[1] Бан на час, в час 100 пинов можно только загрузить.");
                    get_pin_acc(1, $pin_acc);
                    $login_success = pinterest_login_until();
                    $board_id = get_board_id($bot);
                }
            } else {
                dbquery("UPDATE `image_index`.`instagram_images` SET `deleted` = 1 WHERE `id` = $img_id;");
            }
            unset($descs);
            if ($i % 10 == 0) {
                echo_time_wasted($i, "Создали всего пинов $c_pins_created , подписали $c_pins_named");
            }
        }
    }
}
get_pin_acc(1, $pin_acc);

//Визаульная похожесть работает хуже чем просто похожие картинки (related)
//$valid_descs = pin_filter_descs($descs, $allowed_words);
//foreach ($bot->boards->pins('786300484880316405') as $pin) {
//    $similarData = $bot->pins->visualSimilar($pin['id'], 50);
//    $similarArr = $similarData->toArray();
//    $descs = pin_get_desc($similarArr);
//    $valid_descs = pin_filter_descs($descs, $allowed_words);
//    file_put_contents('../debug_data/descs_' . $pin['id'] . '.txt', serialize($descs));
//    file_put_contents('../debug_data/descs_valid_' . $pin['id'] . '.txt', serialize($valid_descs));
//    $words_count = count_words($valid_descs);
//    print_r($words_count);
//}

function get_files_to_post($count = 50)
{
//    $last_posted = dbquery("SELECT `id`,`sourceid`,`fname` FROM `image_index`.`instagram_images` WHERE `posted` = 0 ORDER BY `id` DESC LIMIT $count;", TRUE);
    $last_posted = dbquery("SELECT `t2`.`url`, `t1`.`id`, `t1`.`fname` FROM `instagram_images` AS `t1` JOIN `instagram_sources` AS `t2`
ON `t1`.sourceid = `t2`.`id` WHERE `t2`.`clean` = 1 AND `t1`.`posted` = 0 AND `t1`.`deleted` = 0 ORDER BY `t1`.`id` ASC LIMIT $count;", TRUE);
    if ($last_posted) {
        foreach ($last_posted as $item) {
            $ids[] = $item[1];
        }
        $id = implode(",", $ids);
        dbquery("UPDATE `image_index`.`instagram_images` SET `posted` = '2' WHERE `id` IN ($id);");
        return $last_posted;
    }
}

function get_board_id($bot)
{
    $boards = $bot->boards->forMe();
    foreach ($boards as $board) {
        if ($board['name'] == 'Private Instagram Hair') {
            return $board['id'];
        }
    }
    $private = $bot->boards->createPrivate('Private Instagram Hair', '');
    $boards = $bot->boards->forMe();
    if ($private) {
        foreach ($boards as $board) {
            if ($board['name'] == 'Private Instagram Hair') {
                return $board['id'];
            }
        }
    } else {
        return FALSE;
    }
}


function get_pin_acc($finish = FALSE, $login_data = FALSE, $bad_acc = FALSE)
{
    // used 0 = можно юзать, 1 - в процессе, 2 - не трогать акк (Даши сайты), 3 - не залогинились
    if ($finish == TRUE && $login_data == TRUE) {
        $login_data = implode(":", $login_data);
        dbquery("UPDATE `pinterest`.`proxy2` SET `used` = 0, `finish_time` = NOW() WHERE `pin_acc` = '$login_data';");
        return TRUE;
    }
    if ($bad_acc) {
        $login_data = implode(":", $login_data);
        $tmp = dbquery("SELECT `used` FROM `pinterest`.`proxy2` WHERE `pin_acc` = '$login_data';");
        echo2("=== Аккаунт $login_data сменил статус $tmp => 3 (не логинится!)");
        dbquery("UPDATE `pinterest`.`proxy2` SET `used` = 3, `finish_time` = NOW() WHERE `pin_acc` = '$login_data';");
        return FALSE;
    }
    $time = time() - 60 * 60;
    $res = dbquery("SELECT `id`,`pin_acc` FROM `pinterest`.`proxy2` WHERE `used` = 0 and `finish_time` > $time LIMIT 1;");
    if ($res) {
        $id = $res[0]['id'];
        dbquery("UPDATE `pinterest`.`proxy2` SET `used` = 1, `start_time` = NOW() WHERE `id` = $id;");
        $login_data = explode(':', $res[0]['pin_acc']);
        return $login_data;
    }
    echo2("Нет больше логин-паролей незанятых!");
    exit();
}

function arr_start_pos($array, $start_value)
{
    foreach ($array as $key => $item) {
        if ($start_value == $item) {
            unset($array[$key]);
            return $array;
        }
        unset($array[$key]);
    }
}

function pin_get_desc($similarDataArr, $max_len = 50)
{
    if (is_array($similarDataArr)) {
        // Это если Related Pins , перебор по каждому отдельному пину на вход идет пин
        if ($similarDataArr['description'] && (strlen($similarDataArr['description']) < $max_len)) {
            $descriptions = $similarDataArr['description'];
            return $descriptions;
        }
        //Это если на вход идет массив похожих пинов визуально
        foreach ($similarDataArr as $item) {
            if (is_array($item)) {
                foreach ($item as $simitem) {
                    if (is_array($simitem)) {
                        if ($simitem['description'] && (strlen($simitem['description']) < $max_len)) {
                            $descriptions[] = $simitem['description'];
                        }
                    }
                }
            }
        }
    } else {
        return FALSE;
    }
    return $descriptions;
}

function pin_filter_descs(array $descs, array $allowed_words)
{
    foreach ($descs as $desc) {
        $desc = preg_replace(array('/\W/', '/\s+/', '/\d/'), ' ', $desc);
//        $desc = preg_replace('/\s+/', ' ', $desc);
        $desc = trim($desc);
        $tmp = explode(' ', $desc);
        $tmp = array_map('strtolower', $tmp);
        if ($accords = array_intersect($tmp, $allowed_words)) {
            $valid_descs[] = $desc;
        }
    }
    if ($valid_descs) {
        return $valid_descs;
    } else {
        return FALSE;
    }
}

function count_words($data)
{
    $words_used = array();
    if (is_array($data)) {
        foreach ($data as $item) {
            $words = explode(' ', $item);
            foreach ($words as $word) {
                $tmp = strtolower($word);
                if ($words_used[$tmp]) {
                    $words_used[$tmp]++;
                } else if ($tmp !== FALSE) {
                    $words_used[$tmp] = 1;
                }
            }
        }
    } else {
        $words = explode(' ', $data);
        foreach ($words as $word) {
            $tmp = strtolower($word);
            if ($words_used[$tmp]) {
                $words_used[$tmp]++;
            } else if ($tmp !== FALSE) {
                $words_used[$tmp] = 1;
            }
        }
    }
    arsort($words_used);
    return $words_used;
}

function pinterest_local_login($pin_acc, $pin_pwd)
{
    global $bot;
    $bot = PinterestBot::create();
    $bot->auth->login($pin_acc, $pin_pwd, FALSE);
//    if ($bot->user->isBanned()) {
//        echo2("BANNED! Local IP and $pin_acc:$pin_pwd");
//    }
    if ($bot->auth->isLoggedIn() && $bot->getLastError() == FALSE) {
        echo2("login success! Local IP and $pin_acc:$pin_pwd");
        return TRUE;
    } else {
        echo2("login failed! $pin_acc:$pin_pwd");
        return FALSE;
    }
}

//вариантов функции можно много продумать, от нее все зависит как будет определяться имя!
function words_weight(array $words_count, array $array_variants)
{
    #algoritm 1: Брать слова более 1 раза использованные, вес фразе добавлять в размере употребленного количества раз на все варианты
    $words_count = array_filter($words_count, 'minimum_word_count');
    if (count($words_count) < 3) {
        return FALSE;
    }
    $array_variants = array_map('strtolower', $array_variants);
    $array_variants = array_map('trim', $array_variants);
    foreach ($array_variants as $variant) {
        $tmp[$variant] = 0;
        foreach ($words_count as $word => $value) {
            if (substr_count($variant, $word)) {
                $tmp[$variant] += $value;
            }
        }
    }
    arsort($tmp);
    return key($tmp);
    ### /algoritm 1
}

function minimum_word_count($value, $num = 1)
{
    if ($value > $num) {
        return $num;
    } else {
        return FALSE;
    }
}

function pinterest_login_until()
{
    do {
        $c_login_attempts += 1;
        $pin_acc = get_pin_acc();
        $login_success = pinterest_local_login($pin_acc[0], $pin_acc[1]);
        if ($c_login_attempts == 10) {
            echo2("Не можем залогиниться уже в 10ый акк подрдяд, что-то не так!");
            echo2($bot->getLastError());
        }
        if ($login_success == FALSE) {
            get_pin_acc(0, $pin_acc, 1);
        }
    } while ($login_success == FALSE);
}

function pins_get_sourceid($source_url)
{
    if ($id = dbquery("SELECT `id` FROM `image_index`.`instagram_sources` WHERE `url` = '$source_url';")) {
        return $id;
    } else {
        dbquery("INSERT INTO `image_index`.`instagram_sources` (`url`) VALUES ('$source_url');");
        echo2("Вставили в базу `image_index`.`instagram_sources` новый источник картинок $source_url , вручную надо пометить количество фоток всего и сколько взяли из них ");
        return dbquery("SELECT `id` FROM `image_index`.`instagram_sources` WHERE `url` = '$source_url';");
    }
}