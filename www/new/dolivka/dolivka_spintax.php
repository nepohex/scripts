<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.01.2018
 * Time: 17:13
 */

include "../includes/functions.php";
//error_reporting('E_ERROR');
//$console_mode = 1;
$fp_log = 'log.txt';
$debug_mode = 1;
$db_host = 'localhost';
$db_pwd = '';
$db_usr = 'root';
$db_name = 'dev_wp_dolivka';
$tname = 'instagram';
$db_name_spin = 'hair_spin';
$spintax = new Spintax();

/* Переменные настроек */
$post_status_before = 'private'; //Статус поста когда только залили в WP и еще нет текстов
$post_status_after = 'publish'; //Статус поста после добавления текста
$posts_spintext_volume = 300; // Минимальное Количество символов спинтакс текста
$spin_fragments_separator = '<br>'; //Между генереными текстами разных шаблонных предложений ставим сепаратор
/* ^^/Взятые из стандартного скрипта данные */
$site_name = 'tvoigorod.org';
$result_dir = 'f:\Dumps\instagram\\' . $site_name;
prepare_dir($result_dir);
$mega_spin = TRUE;
$predicted_words = file('c:\OpenServer\domains\scripts.loc\www\pinterest\debug_data\good_words.txt', FILE_IGNORE_NEW_LINES);

//Реконнект к основной базе сайта.
mysqli_connect2($db_name);
echo2("Сохраняем базу данных до спинтакса в файл $result_dir dump_before_spin.sql чтобы в случае чего продожить с этого шага");
//Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = '/dump_before_spin.sql', $result_dir);
//Получаем список постов из основной базы.

$query = "SELECT `ID`,`post_title` FROM `$db_name`.`wp_posts` WHERE (`post_status` = '$post_status_before') AND `post_type` = 'post';";
$posts = dbquery($query);

foreach ($posts as $post) {
    //Получаем слова по которым будем брать мегаспины
    $megaspin_words = dolivka_know_megaspin_words($post['post_title'], $predicted_words);

    //Темплейты мегаспина
    if ($megaspin_tpls = dolivka_get_megaspin_tpls($megaspin_words)) {
        $c_megaspin_posts += 1;
        shuffle($megaspin_tpls);
        $megaspin_item = array_shift($megaspin_tpls);
        $add_to_post = dolivka_gen_spin($spintax, $post, $megaspin_item);
        dbquery("UPDATE `hair_spin`.`data` SET `used` = `used` + 1 WHERE `id` = $megaspin_item[id];");
    } else {
        do {
            $spintax_tpl = dbquery("SELECT `id`, `text`, `place` FROM `hair_spin`.`my_spintax` WHERE `place` IN ('any','tip') ORDER BY RAND() LIMIT 1;", 0, 0, 0, 0, 1);
            switch ($spintax_tpl['place']) {
                case 'any':
                    $add_to_post = dolivka_gen_spin($spintax, $post, $spintax_tpl, '');
                    break;
                case 'tip':
                    $add_to_post = dolivka_gen_spin($spintax, $post, $spintax_tpl, '<b>Hair Tip:</b>');
                    break;
                default:
                    break;
            }
            dbquery("UPDATE `hair_spin`.`my_spintax` SET `used` = `used` + 1 WHERE `id` = $spintax_tpl[id];");
        } while (@strlen($add_to_post) < $posts_spintext_volume);
        $add_to_post = $before_spin_html . $add_to_post . $after_spin_html;
        $c_spin_posts += 1;
    }
    dbquery("UPDATE `$db_name`.`wp_posts` SET `post_content` = CONCAT(`post_content`,'" . addslashes($add_to_post) . "'), `post_status` = '$post_status_after' WHERE `ID` = '" . $post['ID'] . "';");
    unset($add_to_post);
}
echo2("Подали на вход " . count($posts) . " постов, нашли для них megaspin/spin шаблонов использовано $c_megaspin_posts/$c_spin_posts ");


/** На вход можно подать шаблон и MegaSpin и просто Spin
 * @param $spintax
 * @param array $post
 * @param array $megaspin_item
 * @param string $before_spin_html
 * @param string $after_spin_html
 * @return mixed|string
 */
function dolivka_gen_spin($spintax, array $post, array $megaspin_item, $before_spin_html = '<div class="text-content">', $after_spin_html = '</div>')
{
    if ($megaspin_item['text_template']) {
        $spintext = $spintax->process($megaspin_item['text_template']);
        $spintext = str_ireplace('%post_title%', $post['post_title'], $spintext);
        $spintext = str_replace('  ', ' ', $spintext);
        $spintext = $before_spin_html . $spintext . $after_spin_html;
    } else {
        $spintext = $before_spin_html;
        $spintext .= $spintax->process($megaspin_item['text']);
        $spintext = str_ireplace('%post_title%', $post['post_title'], $spintext);
        $spintext = str_replace('  ', ' ', $spintext);
    }
    return $spintext;
}

function dolivka_get_megaspin_tpls(array $words)
{
    $res = array();
    foreach ($words as $word) {
        $query = "SELECT `id`,`h3`,`text_template`, `img_alt`,`avg_len` FROM `hair_spin`.`data` WHERE `h3` LIKE '%$word%' OR `img_alt` LIKE '%$word%' ORDER BY `used` ASC LIMIT 10;";
        $tmp = dbquery($query);
        $res = @array_merge($res, $tmp);
    }
    if (count($res) > 0) {
        return $res;
    } else {
        return FALSE;
    }
}

function dolivka_know_megaspin_words($post_title, array $predicted_words, array $exclude_words = array('hair', 'cut', 'style'))
{
    $tmp = explode(" ", $post_title);
    $tmp = array_map('strtolower', $tmp);
    //Исключаем из массива возможных основных ключевых слов те слова которые слишком масштабные и не подойдут для подбора спинтакса
    $good_words = array_diff($predicted_words, $exclude_words);
    $megaspin_words = array_intersect($tmp, $good_words);
    return $megaspin_words;
}