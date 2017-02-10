<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.12.2016
 * Time: 18:48
 * Загружаем тексты в базу db_instance в таблицу, если текст новый просчитываем его варианты, длину, записываем. Если старый - пропускаем.
 */
include "multiconf.php";
include("mysqli_connect.php");
echo2 ("Начинаем выполнять скрипт ".$_SERVER['SCRIPT_FILENAME']);

/**
 * Spintax - A helper class to process Spintax strings.
 * @name Spintax
 * @author Jason Davis - https://www.codedevelopr.com/
 * Tutorial: https://www.codedevelopr.com/articles/php-spintax-class/
 */
class Spintax
{
    public function process($text)
    {
        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*)\}/x',
            array($this, 'replace'),
            $text
        );
    }

    public function replace($text)
    {
        $text = $this->process($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}

/* EXAMPLE USAGE */
// $spintax = new Spintax();
// $string = '{Hello|Howdy|Hola} to you, {Mr.|Mrs.|Ms.} {Smith|Williams|Davis}!';
// echo $spintax->process($string);

/* NESTED SPINNING EXAMPLE */
// echo $spintax->process('{Hello|Howdy|Hola} to you, {Mr.|Mrs.|Ms.} {{Jason|Malina|Sara}|Williams|Davis}');

echo2 ("Проверяем какие из шаблонов Спинтакса уже есть в базе, каких нет. Тех которых нет - просчитываем и загружаем.");
$query = "SELECT `text`,`variants`,`used` FROM `my_spintax`";
$sqlres = mysqli_query($link, $query);
while ($tmp = mysqli_fetch_row($sqlres)) {
    $texts[] = $tmp[0];
    $variants += $tmp[1];
    $used += $tmp[2];
}
$spintax = new Spintax();
$i = 0;
$k = 0;
$tmp_keys = array_keys($spin_tpls);

//Просчитываем новые спины
foreach ($spin_tpls as $spin_tpl) {
    foreach ($spin_tpl as $item) {
        if (!(in_array($item, $texts))) {
            for ($z = 0; $z < 1001; $z++) {
                $spinned[$i][$z] = $spintax->process($item);
                $counter_strlen += strlen($spinned[$i][$z]);
            }
            $spinned[$i] = array_unique($spinned[$i]);
            $result[$k][$i]['text'] = $item;
            $result[$k][$i]['variants'] = count($spinned[$i]);
            $result[$k][$i]['avg_length'] = round($counter_strlen / 1001);
            $result[$k][$i]['place'] = $tmp_keys[$k];
            $new_variants += count($spinned[$i]);
            unset($counter_strlen);
            $i++;
        }
    }
    $k++;
}
//Загружаем в базу новые спины, если таковые есть
$counter_new_texts = 0;
if ($result) {
    foreach ($result as $items) {
        foreach ($items as $item) {
            $query = "INSERT INTO `my_spintax` (`id`, `text`, `place`, `variants`, `comment`, `avg_length`, `used`) VALUES ('', '" . addslashes($item['text']) . "', '" . $item['place'] . "', '" . $item['variants'] . "', '', '" . $item['avg_length'] . "', '0');";
            mysqli_query($link, $query);
            $counter_new_texts++;
        }
    }
}
//Проверяем есть ли синонимы для ключевика сайта среди синонимов, чтобы чекнуть специальные Спинтаксы под ключ (или синонимы его)
foreach ($synonyms as $synonim) {
    if (in_array($keyword,$synonim)) {
        $spin_comments[] = $keyword;
        foreach ($synonim as $item) {
            $spin_comments[] = $item;
        }
    }
}
//Начинаем выгружать из базы тексты для спинов в массив
$actually_variants = 0;
if ($spin_comments) {
    foreach ($spin_comments as $spin_comment) {
        $query = "SELECT * FROM `my_spintax` WHERE `comment` = '".$spin_comment."'";
        $sqlres = mysqli_query($link,$query);
        if ($sqlres) {
            $spin_rows[] = mysqli_fetch_assoc($sqlres);
        }
    }
} else {
    //$query = "SELECT * FROM `my_spintax` WHERE `place` = 'any'";
    $query = "SELECT * FROM `my_spintax`";
    $sqlres = mysqli_query($link,$query);
    while ($tmp = mysqli_fetch_assoc($sqlres)) {
        $spin_rows[] = $tmp;
        $actually_variants += $tmp['variants'];
    }
}
//Начинаем генерить тексты для постов
$query = "SELECT `ID`,`post_title` from `wp_posts` WHERE (`post_status` = 'publish' or `post_status` = 'pending') and `post_type` = 'post';";
$sqlres = mysqli_query($link,$query);

while ($tmp = mysqli_fetch_assoc($sqlres)) {
    $posts[] = $tmp;
}

$i = 0 ;
$counter_used_new = 0; //Сколько шаблонов использовали после обновлени текстов
function add_concat_spin_text($spec_separator)
{
    global $posts, $i, $spintax, $tmp, $spin_fragments_separator, $tmp_ind_spin_rows, $spin_rows, $counter_used_new;
    $posts[$i]['spintext'] .= $spec_separator;
    $posts[$i]['spintext'] .= $spintax->process($tmp['text']);
    $posts[$i]['spintext'] .= $spin_fragments_separator;
    $spin_rows[$tmp_ind_spin_rows]['used'] += 1;
    $counter_used_new++;
}

foreach ($posts as $post) {
    while (strlen($posts[$i]['spintext']) < $posts_spintext_volume) {
        if (!(in_array($tmp_ind_spin_rows = rand(0, count($spin_rows) - 1), $tmp_doubles_arr))) {
            $tmp_doubles_arr[] = $tmp_ind_spin_rows;
            $tmp = $spin_rows[$tmp_ind_spin_rows];
            switch ($tmp['place']) {
                case 'any':
                    add_concat_spin_text();
                    break;
                case 'tip':
                    add_concat_spin_text('<b>Hair Tip:</b>');
                    break;
                case 'not end':
                    if ((strlen($posts[$i]['spintext']) + $tmp['avg_length']) < $posts_spintext_volume) {
                        add_concat_spin_text();
                        break;
                    }
                case 'start':
                    if (strlen($posts[$i]['spintext']) == 0) {
                        add_concat_spin_text();
                        break;
                    }
                case 'end':
                    if ((strlen($posts[$i]['spintext']) + $tmp['avg_length']) > $posts_spintext_volume) {
                        add_concat_spin_text();
                        break;
                    }
                case 'not start':
                    if (((strlen($posts[$i]['spintext']) > 0) && (strlen($posts[$i]['spintext']) + $tmp['avg_length']) < $posts_spintext_volume)) {
                        add_concat_spin_text();
                        break;
                    }
                default:
                    break;
            }
        } else {
            $tmp_ind_spin_rows = rand(0, count($spin_rows) - 1);
        }
    }
    $posts[$i]['spintext'] = str_ireplace('%post_title%', $post['post_title'], $posts[$i]['spintext']);
    $posts[$i]['spintext'] = str_replace('  ', ' ', $posts[$i]['spintext']);
    $posts[$i]['spintext'] = $before_spin_html . $posts[$i]['spintext'] . $after_spin_html;
    $query = "UPDATE `wp_posts` SET `post_content` = CONCAT(`post_content`,'" . addslashes($posts[$i]['spintext']) . "') WHERE `ID` = '" . $posts[$i]['ID'] . "';";
    $sqlres = mysqli_query($link, $query);
    unset ($posts[$i], $tmp_doubles_arr);
    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
    $i++;
}
//Обновляем таблицу с данными сколько какой шаблон юзали
foreach ($spin_rows as $spin_row) {
    $query = "UPDATE `my_spintax` SET `used` = `used` + ".$spin_row['used']." WHERE `id` = '".$spin_row['id']."';";
    $sqlres = mysqli_query($link,$query);
}

echo2 ("Всего строк Спинтакса было в базе ".count($texts).", вариантов $variants, которые всего использованы $used раз. Будут использованы не все стрроки.");
echo2 ("Для генерации контента для каждой записи использовали шаблоны $counter_used_new раз, получив столько же вариантов. Всего было использовано ".count($spin_rows)." уникальных шаблонов, в которых содержится $actually_variants вариантов");
echo2 ("Новых шаблонов загрузили (если были) $counter_new_texts с вариантами $new_variants");
echo2 ("Закончили со скриптом ".$_SERVER['SCRIPT_FILENAME']." Переходим к NEXT");
next_script ($_SERVER['SCRIPT_FILENAME']);
?>