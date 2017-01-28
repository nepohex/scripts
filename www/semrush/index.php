<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.01.2017
 * Time: 14:05
 */
#todo дописать ограничитель на au/uk домены если первые запросы меньше 10к сразу выходить из цикла.
#todo дописать ограничитель для доменов. Если последние 5 запросов дали меньше N прироста к базе - дальше не парсить.
#todo если 1ый же запрос меньше 10к строк - выходить из цикла.
$start = microtime(true);
//$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');
$result_dir = 'result';
$fp_log = fopen($result_dir . '/log.txt', 'a');
mkdir2($result_dir, 1);
$db_name = 'image_index';
$db_pwd = '';
$db_usr = 'root';
mysqli_connect2();

function get_export_hash($domain)
{
    $query = 'https://ru.semrush.com/info/' . $domain . '+(by+organic)';
    $tmp = file_get_contents($query);
    $regexp = '/"current","exportHash":"\w{32}"/';
    $z = preg_match($regexp, $tmp, $matches);
    if ($matches[0]) {
        return substr($matches[0], -33, -1);
    } else {
        file_put_contents('result/wrong_export_hash.txt', $tmp);
        echo2("Не удалсь получить ExportHash для домена $domain , результат ответа по запросу $query сохранен в файл result/wrong_export_hash.txt");
        exit;
    }
}

function keys_count($domain)
{
    $tmp = file_get_contents('https://us.backend.semrush.com/?jsoncallback=jQuery21403911130601985613_1485539059498&key=3a9a36dd42050c5a010dd0ecafc20b4d&action=report&domain=' . $domain . '&type=domain_rank&display_hash=2540abca974071aa4111106267de104e&_=1485539059499');
    $regexp = '/", "Or": "[0-9]{1,8}"/';
    $z = preg_match($regexp, $tmp, $matches);
    if ($matches[0]) {
        $z = preg_match('/\d+/', $matches[0], $match);
        return $match[0];
    } else {
        file_put_contents('result/wrong_json_response.txt', $tmp);
        echo2("Не удалсь получить ExportHash для домена $domain , результат ответа по запросу $query сохранен в файл result/wrong_json_response.txt");
        return 0;
    }
}

function added_keys()
{
    $query = "SELECT count(*) FROM `semrush_keys`"; //Сколько было ключей в общей таблицы до слияния с текущей таблицей домена
    $was_keywords = dbquery($query);
    // SET SQL_MODE =  'NO_AUTO_VALUE_ON_ZERO';  ???
    $query = "INSERT INTO  `image_index`.`semrush_keys` SELECT * FROM  `image_index`.`tmp_semrush` ;"; //Сливаем таблицу домена (tmp) с общей таблицей ключей
    mysqli_query($link, $query);
    if ($error = mysqli_error($link)) {
        echo2("$error");
    }
    $query = "SELECT count(*) FROM `semrush_keys`"; //Сколько стало после слияния строк.
    $now_keywords = dbquery($query);
    $query = "TRUNCATE TABLE `tmp_semrush`"; //Чистим временную таблицу
    mysqli_query($link, $query);
    if ($error = mysqli_error($link)) {
        echo2("$error");
    }
    return $now_keywords - $was_keywords;
}

/**
 * Пример запроса:
 * https://us.backend.semrush.com/?action=report&database=us&rnd_m=1485386746&key=5c8e0eb96b3fe54582fee2128ea97257&domain=hairstylefoto.com&type=domain_organic&display_filter=&display_sort=tr_desc&export_hash=62b89bcfe05430a7607bb86a48ab0f7e&export_decode=1&export_escape=1&currency=usd&export_columns=Ph,Nq,Kd,Cp,Ur,Tc,Co,Nr,Td&export=stdcsv
 * &key=5c8e0eb96b3fe54582fee2128ea97257 - это переменная аккаунта залогиненного и оплаченного, самое важное.
 *
 * &type=domain_organic - тип отчета, поисковые фразы
 *
 * &export_columns= - Варианты сортировки кеев в отчете (можно получить больше если asc-desc по каждой колонке сделать):
 * po_desc - позиция
 * nq_desc - volume (частота google adwords)
 * kd_desc - keyword density. Чем выше тем сложнее кей (по мнению semrush)
 * cp_desc - cpc
 * tr_desc - трафик (стандарт)
 * tc_desc - trafic cost, стоимость трафика (клика?)
 * co_desc - competitive density - уровень конкуренции (чем выше тем сложнее)
 * nr_desr - количество документов по запросу
 *
 * Домены: us,uk,au
 *
 * &export_columns= - колонки. Keyword;Position;Previous Position;Search Volume;Keyword Difficulty Index;CPC;Url;Traffic (%);Traffic Cost (%);Competition;Number of Results;Trends;Timestamp
 * Ph,Po,Pp,Nq,Kd,Cp,Ur,Tr,Tc,Co,Nr,Td,Ts
 * Ph,Nq,Nr - колонки без лишнего.
 *
 * &export=stdcsv - csv с разделителями ;
 *
 * https://us.backend.semrush.com/?action=report&database=us&rnd_m=1485453511&key=5c8e0eb96b3fe54582fee2128ea97257&domain=bob-hairstyle.com&type=domain_organic&display_filter=%2B%7CPh%7CCo%7Cbob&display_sort=co_desc&export_hash=5e70221ea35c5de124a897e2039351f6&export_decode=1&export_escape=1&currency=usd&export_columns=Ph,Po,Pp,Nq,Kd,Cp,Ur,Tr,Tc,Co,Nr,Td,Ts&export=stdcsv
 * &display_filter=%2B%7CPh%7CCo%7Cbob - фильтр по ключу bob.
 */

//Необходимо каждый раз логиниться и указывать верные переменные хешей, смотреть их в запросе при скачивании файла.
$rnd_m = '1485471591'; //Пример Меняется от запроса к запросу, timestamp
$semrush_key = '5c8e0eb96b3fe54582fee2128ea97257'; //Идентификатор оплаченного логина с доступом. Не меняется от сайта к сайту.
$export_hash = '1044760e41afeb0295a44832bde769c8'; //Пример. Меняется от сайта к сайту, надо каждый раз получать.

//Переменные уже для моего скрипта
//'therighthairstyles.com' - уже обработал
$columns = 'Ph,Nq,Nr';
$sortings1 = array('po', 'nq', 'kd', 'cp', 'tr', 'tc', 'co', 'nr');
$sortings2 = array('_desc', '_asc');
$domain_list = array('ticlotel.com','hairstyleonpoint.com', 'cutegirlshairstyles.com', 'latest-hairstyles.com', 'hairfinder.com', 'thehairstyler.com', 'menshairstyletrends.com', 'longhairbeez.us', 'hairstylefoto.com', 'lovely-hairstyles.com', 'bob-hairstyle.com', 'haircutinspiration.com', 'short-haircut.com', 'hairfinder.com ', 'hairstylesweekly.com', 'menshairstylestoday.com', 'pophaircuts.com', 'mens-hairstylists.com', 'mens-hairstyle.com', 'hairstyle-designs.com', 'trendinghairstyles.com'); // Без HTTP и слешей

$counter_semrush_queries = 0; //Сколько раз запросили SEMRUSH
$counter_semrush_results = 0; //Скольок кеев получили от SEMRUSH (неуник, все).
$counter_uniq_keywords = 0; //Сколько ключей записали в базу. Только уникальные ключи пишутся.
$counter_semrush_total_traffic = 0; //Сколько всего получили от SEMRUSH данных в байтах.
$counter_semrush_traffic_query = 0; //Сколько на конкретный запрос трафика получилось.
$site_iteration_count = 0;

foreach ($domain_list as $domain) {
    $site_iteration_count++;
    $export_hash = get_export_hash($domain);
    $semrush_keys = keys_count($domain); //Сколько всего Organic ключей есть для домена
    foreach ($sortings1 as $sort) {
        foreach ($sortings2 as $ascdesc) {
            $semrush_query = 'https://us.backend.semrush.com/?action=report&database=us&rnd_m=' . time() . '&key=' . $semrush_key . '&domain=' . $domain . '&type=domain_organic&display_filter=&display_sort=' . $sort . $ascdesc . '&export_hash=' . $export_hash . '&export_decode=1&export_escape=1&currency=usd&export_columns=' . $columns . '&export=stdcsv';
            echo2($semrush_query);
            //exmpl query https://us.backend.semrush.com/?action=report&database=us&rnd_m=1485386746&key=5c8e0eb96b3fe54582fee2128ea97257&domain=hairstylefoto.com&type=domain_organic&display_filter=&display_sort=tr_desc&export_hash=62b89bcfe05430a7607bb86a48ab0f7e&export_decode=1&export_escape=1&currency=usd&export_columns=Ph,Nq,Nr&export=stdcsv
            $semrush_data = file_get_contents($semrush_query);
            $counter_semrush_queries++;
            if ($semrush_data) {
                $counter_semrush_traffic_query = strlen($semrush_data);
                $counter_semrush_total_traffic += $counter_semrush_traffic_query;
                $semrush_data = explode(PHP_EOL, $semrush_data);
                $i = 0;
                foreach ($semrush_data as $v) {
                    $semrush_csv[$i] = str_getcsv($v, ';');
                    $i++;
                }
                unset($semrush_data);
                $counter_semrush_results += count($semrush_csv);
                foreach ($semrush_csv as $str) {
                    //            INSERT INTO `image_index`.`tmp_semrush` (`key_id`, `key`, `adwords`, `results`) VALUES (NULL, 'word', '500', '1000');
                    $query = "INSERT INTO `tmp_semrush` (`key_id`, `key`, `adwords`, `results`) VALUES ('', '" . addslashes($str[0]) . "', '$str[1]', '$str[2]'); ";
                    if ($z = dbquery($query, 0, 1) == 1) {
                        $counter_uniq_keywords += $z;
                    }
                    $tmp = explode(' ', $str[0]);
                    foreach ($tmp as $word) {
                        $words_used[strtolower($word)] += 1;
                    }
                }
                echo2("#$counter_semrush_queries $domain Массив из Semrush получили, размером в " . convert($counter_semrush_traffic_query) . " , строк " . count($semrush_csv) . " Всего строк получили $counter_semrush_results , уникальных записей в базу $counter_uniq_keywords / $semrush_domain_organic_keys из возможных, трафика скачали " . convert($counter_semrush_total_traffic));
                echo_time_wasted();
                //Отлавливаем маленькие сайты по ключам.
                if (count($semrush_csv) < 10000 && $site_iteration_count == 1) {
                    unset($semrush_csv);
                    echo2("Сайт $domain отдал первым же запросом меньше 10000 результатов, выходим из цикла по нему.");
                    $go_next_domain = 1;
                    break;
                } else {
                    unset($semrush_csv);
                }
            } else {
                echo2("Не получен файл от SEMRUSH");
            }
        }
        if ($go_next_domain == 1) {
            break;
        }
    }
    $result_fp = $result_dir . '/' . 'words_used_' . $domain . '_.txt';
    file_put_contents($result_fp, print_r($words_used, true));
    echo2("Использованные слова записали в $result_fp");
    $new_keys = added_keys();
    $query = "INSERT INTO `semrush_domains` (`id`, `domain`, `semrush_keys`, `queries_done`, `results_got`, `results_unique`,`results_new`) VALUES ('', '" . $domain . "', '$semrush_keys', '$counter_semrush_queries', '$counter_semrush_results', '$counter_uniq_keywords', '$new_keys');";
    $site_iteration_count = 0;
    $counter_semrush_queries = 0;
    unset ($go_next_domain);
}