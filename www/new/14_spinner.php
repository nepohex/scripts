<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 15.01.2017
 * Time: 0:50
 */
include "multiconf.php";
mysqli_connect2($db_name_spin);
next_script(0, 1);

echo2("Проверяем какие из шаблонов Спинтакса уже есть в базе, каких нет. Тех которых нет - просчитываем и загружаем.");
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
            dbquery($query);
            $counter_new_texts++;
        }
    }
}
//Проверяем есть ли синонимы для ключевика сайта среди синонимов, чтобы чекнуть специальные Спинтаксы под ключ (или синонимы его)
foreach ($synonyms as $synonim) {
    if (in_array($keyword, $synonim)) {
//        $spin_comments[] = $keyword;
        foreach ($synonim as $item) {
//            $spin_comments[] = $item;
            $spin_synonims[] = $item;
        }
    }
}
//Начинаем выгружать из базы тексты для спинов в массив
//не работает пока что функционал т.к. нет комментов.
$actually_variants = 0;
if ($spin_comments) {
    foreach ($spin_comments as $spin_comment) {
        $query = "SELECT * FROM `my_spintax` WHERE `comment` = '" . $spin_comment . "'";
        $sqlres = mysqli_query($link, $query);
        if ($sqlres) {
            $spin_rows[] = mysqli_fetch_assoc($sqlres);
        }
    }
} else {
    //$query = "SELECT * FROM `my_spintax` WHERE `place` = 'any'";
    $query = "SELECT * FROM `my_spintax`";
    $sqlres = mysqli_query($link, $query);
    while ($tmp = mysqli_fetch_assoc($sqlres)) {
        $spin_rows[] = $tmp;
        $actually_variants += $tmp['variants'];
    }
}

//Реконнект к основной базе сайта.
mysqli_connect2();
echo2 ("Сохраняем базу данных до спинтакса в файл $result_dir dump_before_spin.sql чтобы в случае чего продожить с этого шага");
Export_Database($db_host, $db_usr, $db_pwd, $db_name, $tables = false, $backup_name = 'dump_before_spin.sql', $result_dir);
//Получаем список постов из основной базы.
$query = "SELECT `ID`,`post_title` FROM `wp_posts` WHERE (`post_status` = 'publish' OR `post_status` = 'pending') AND `post_type` = 'post';";
$posts = dbquery($query);

//Если есть Mega Spin - спин по текстам к картинкам с других сайтов.
if ($mega_spin == true) {
    mysqli_connect2($db_name_spin);
    $mega_spin_variants = 0; // Сколько у нас всего вариантов под этот ключевик в базе для Mega Spin.
    if ($spin_synonims) {
        $tmp = ' ' . implode('| ', $spin_synonims);
        //Костыль для men/woman
        if (in_array('men', $spin_synonims)) {
            $query = "SELECT `id`,`h3`,`img_alt`,`avg_len` from `data` WHERE (`h3` REGEXP '.*$tmp.*' OR `img_alt` REGEXP '.*$tmp.*') and `h3` NOT LIKE '%wom%' AND `img_alt` NOT LIKE '%wom%';";
        } else {
            $query = "SELECT `id`,`h3`,`img_alt`,`avg_len` from `data` WHERE `h3` REGEXP '.*$tmp.*' OR `img_alt` REGEXP '.*$tmp.*';";
        }
    } else {
        $query = "SELECT `id`,`h3`,`img_alt`,`avg_len` from `data` WHERE `h3` LIKE '%$keyword%' OR `img_alt` LIKE '%$keyword%';";
    }
    $sqlres = mysqli_query($link, $query);
    while ($tmp = mysqli_fetch_assoc($sqlres)) {
        $mega_spin_tpls[] = $tmp;
        $mega_spin_variants += $tmp['avg_len'];
    }

    echo2("Mega Spin в базе $db_name_spin нашлось " . count($mega_spin_tpls) . " шаблонов текста подходящих под ключ $keyword, общий объем текстов $mega_spin_variants. Начинаем подбор шаблонов по PREG для Title постов.");

    if (count($mega_spin_tpls) > 0) {
        shuffle($mega_spin_tpls);
        // Готовим слова для замены в названии картинок на шаблон для поиска текстов под них.
        $excluded_spin_words = array_merge($filter_words, $uniq_addings, $uniq_addings_nch, $autocat_exclude_words);
        $excluded_spin_words = array_map('trim', $excluded_spin_words);
        $excluded_spin_words = array_unique($excluded_spin_words);
        foreach ($autocat_strict_word_exclude as $tmp) {
            $predlogi[] = ' ' . $tmp . ' ';
        }

        // Определяем шаблон PREG like под шаблон текста картинки.
        $i = 0;
        $counter_mega_tpls_matched = 0; //Сколько постов получили шаблон генерации Mega tpl.
        $preg_tpls_replace = array('  ', ' .* .* ', ' .* ', ' .*', '.* ', '.*.*');
        //todo Проработать цикл, на 1000 записей работает более 30 минут.
        foreach ($posts as $post) {
            $tmp = str_ireplace($excluded_spin_words, ".*", $post['post_title']); //Удаляем основные слова, меняем на шаблон.
            $tmp = str_ireplace($predlogi, ".*", $tmp); //Предлоги и прочая хрень.
            $tmp = str_ireplace($preg_tpls_replace, '.*', $tmp);
            $posts[$i]['tpl'] = '/(.)*' . $tmp . '(.)*/i';
            // Начинаем поиск постов подходящих под регулярку.
            shuffle($mega_spin_tpls); //Чтобы всем по-ровну досталось. Эффективность на 50% выше с рандомом!
            foreach ($mega_spin_tpls as $mega_spin_tpl) {
                //if else if сделан для ускорения регулярки чтобы сразу 2 раза не пробегать.
                if (preg_match($posts[$i]['tpl'], $mega_spin_tpl['h3'])) {
                    $mega_spin_used_ids[] = $mega_spin_tpl['id'];
                    $posts[$i]['mega_tpl_id'] = $mega_spin_tpl['id'];
                    $posts[$i]['mega_tpl_img_alt'] = $mega_spin_tpl['img_alt'];
                    unset ($posts[$i]['tpl']);
                    $counter_mega_tpls_matched++;
                    break;
                } else if (preg_match($posts[$i]['tpl'], $mega_spin_tpl['img_alt'])) {
                    $mega_spin_used_ids[] = $mega_spin_tpl['id'];
                    $posts[$i]['mega_tpl_id'] = $mega_spin_tpl['id'];
                    $posts[$i]['mega_tpl_img_alt'] = $mega_spin_tpl['img_alt'];
                    unset ($posts[$i]['tpl']);
                    $counter_mega_tpls_matched++;
                    break;
                }
            }
            $i++;
            if ($i % 500 == 0) {
                echo_time_wasted($i);
//                break; // debug
            }
        }
        unset($mega_spin_tpls, $excluded_spin_words);
        echo2("Mega Spin нашлись для $counter_mega_tpls_matched / " . count($posts) . " постов. ");
        $mega_spin_used_ids = array_unique($mega_spin_used_ids);
        echo2("Сохраняем данные для MegaSpin в $result_dir : посты с ID в массиве Posts, Megaspin tpl ids.");

        file_put_contents($result_dir . "mega_spin_used_ids.txt", serialize($mega_spin_used_ids));
        file_put_contents($result_dir . "posts_spin_data.txt", serialize($posts));

        mysqli_connect2($db_name_spin);
        // Обновляем данные в базе по количеству использований шаблонов.
        $counter_used_megaspin_ids = array_count_values($mega_spin_used_ids);
        foreach ($counter_used_megaspin_ids as $idkey => $tmp) {
            $query = "UPDATE `data` SET `used` = `used` + " . $tmp . " WHERE `id` = '" . $idkey . "';";
            dbquery($query);
        }

        //Пробуем сделать длинный запрос с большим количеством ID в нем.
        $query = "SELECT `id`,`text_template` FROM `data` WHERE `id` IN (" . implode(',', $mega_spin_used_ids) . ");";
        $mega_spin_tpl_texts = dbquery($query);

        //Генерируем mega spin text для тех шаблонов что подошли постам.
        $i = 0;
        foreach ($posts as $post) {
            if ($post['mega_tpl_id']) {
                foreach ($mega_spin_tpl_texts as $mega_spin_tpl_text) {
                    if ($post['mega_tpl_id'] == $mega_spin_tpl_text['id']) {
                        $posts[$i]['spintext'] = $spintax->process($mega_spin_tpl_text['text_template']);
                        $posts[$i]['spintext'] = str_ireplace('%post_title%', $post['post_title'], $posts[$i]['spintext']);
                        $posts[$i]['spintext'] = str_replace('  ', ' ', $posts[$i]['spintext']);
                        $posts[$i]['spintext'] = $before_spin_html . $posts[$i]['spintext'] . $after_spin_html;
                        break;
                    }
                }
            }
            $i++;
        }
        echo2("Mega Spin сгенерили");
    }
}

//Начинаем генерить тексты для постов
$i = 0;
$counter_used_new = 0; //Сколько шаблонов использовали после обновлени текстов

mysqli_connect2();
foreach ($posts as $post) {
    if ($posts[$i]['spintext'] == false) { //Если megaspin уже сделали, то дальше не трогаем данный пост.
        while (strlen($posts[$i]['spintext']) < $posts_spintext_volume) {
            $tmp_doubles_arr = array('99999'); // Костыль чтобы не было ошибки
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
    }
    $query = "UPDATE `wp_posts` SET `post_content` = CONCAT(`post_content`,'" . addslashes($posts[$i]['spintext']) . "') WHERE `ID` = '" . $posts[$i]['ID'] . "';";
    dbquery($query);
    unset ($posts[$i], $tmp_doubles_arr);
//    if ($i % 1000 == 0) {
//        echo_time_wasted($i);
//    }
    $i++;
}

//Обновляем таблицу с данными сколько какой шаблон юзали
mysqli_connect2($db_name_spin);
foreach ($spin_rows as $spin_row) {
    $query = "UPDATE `my_spintax` SET `used` = `used` + " . $spin_row['used'] . " WHERE `id` = '" . $spin_row['id'] . "';";
    dbquery($query);
}

function add_concat_spin_text($spec_separator = '')
{
    global $posts, $i, $spintax, $tmp, $spin_fragments_separator, $tmp_ind_spin_rows, $spin_rows, $counter_used_new;
    $posts[$i]['spintext'] .= $spec_separator;
    $posts[$i]['spintext'] .= $spintax->process($tmp['text']);
    $posts[$i]['spintext'] .= $spin_fragments_separator;
    $spin_rows[$tmp_ind_spin_rows]['used'] += 1;
    $counter_used_new++;
}

echo2("Всего строк Спинтакса было в базе " . count($texts) . ", вариантов $variants, которые всего использованы $used раз. Будут использованы не все стрроки.");
echo2("Для генерации контента для каждой записи использовали шаблоны $counter_used_new раз, получив столько же вариантов.");
echo2("Новых шаблонов загрузили (если были) $counter_new_texts с вариантами $new_variants");
next_script();