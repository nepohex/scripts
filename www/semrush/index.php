<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.01.2017
 * Time: 14:05
 */
#todo дописать ограничитель для доменов. Если последние 5 запросов дали меньше N прироста к базе - дальше не парсить.
#todo сделать hardcore mode - если больше 20к запросов осталось не спаршенных - парсим по списку ключей домена до тех пор пока не получим все!
$start = microtime(true);
$debug_mode = 1; // 0 = вывод в лог, 1 - вывод сюда. Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');
$result_dir = 'result';
$fp_log = fopen($result_dir . '/log.txt', 'a');
mkdir2($result_dir, 1);
$db_name = 'image_index';
$db_pwd = '';
$db_usr = 'root';
mysqli_connect2();

//Чистим куки перед каждым запуском
if (file_exists('cookie.txt')) {
    unlink('cookie.txt');
}

function get_export_hash($domain)
{
    global $semrush_key; // Заодно и ключ-логина обновим.
    //Функция кривая потому что после каждого запроса надо переавторизовываться.
    if (file_exists('cookie.txt') == false) {
        $ch = curl_init();
        $url = 'https://ru.semrush.com/json_users/login';
        curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
        curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // сохранять куки в файл
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
        curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'email' => 'videocaa.org@gmail.com',
            'password' => 'SOsM-Q3564T7NPRt',
            'user_agent_hash' => '7d78e1d08173d6271ad8f371e14c1244',
            'event_source' => 'semrush',
        ));
        $data = curl_exec($ch);
        // в случае неудачи
        //{"user":{"email":"1aun100@gmail.com","user_agent_hash":"7d78e1d08173d6271ad8f371e14c1244","event_source":"semrush"},"errors":["\u041e\u0448\u0438\u0431\u043a\u0430! \u041d\u0435\u043f\u0440\u0430\u0432\u0438\u043b\u044c\u043d\u044b\u0439 \u043b\u043e\u0433\u0438\u043d \u0438\u043b\u0438 \u043f\u0430\u0440\u043e\u043b\u044c."]}
        // в случае удачи
        // {"redirect_url":"\/index.html?1485627979"}
    }
    if (strstr($data, 'redirect') || 'cookie.txt') {
        $ch = curl_init();
        $url = "https://ru.semrush.com/info/" . $domain . "+(by+organic)";
        curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
        curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
        $data = curl_exec($ch);
        curl_close($ch);
//        unlink('cookie.txt');
        $regexp1 = '/&export_hash=.{32}&/';
        $z = preg_match($regexp1, $data, $matches1);
        $regexp2 = '/"apiKey": ".{32}"/';
        $t = preg_match($regexp2, $data, $matches2);
        if ($matches1[0]) {
            $semrush_key = substr($matches2[0], -33, -1);
            return substr($matches1[0], -33, -1);
        } else {
            file_put_contents('result/wrong_export_hash.txt', $data);
            echo2("Не удалось получить ExportHash для домена $domain , результат ответа по запросу $url сохранен в файл result/wrong_export_hash.txt");
            unlink('cookie.txt');
            exit;
        }
    }
}

function keys_count($domain)
{
    global $country_base;
    $tmp = file_get_contents('https://' . $country_base . '.backend.semrush.com/?jsoncallback=jQuery21403911130601985613_1485539059498&key=3a9a36dd42050c5a010dd0ecafc20b4d&action=report&domain=' . $domain . '&type=domain_rank&display_hash=2540abca974071aa4111106267de104e&_=1485539059499');
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
    global $db_name, $link;
    $query = "SELECT count(*) FROM `semrush_keys`"; //Сколько было ключей в общей таблицы до слияния с текущей таблицей домена
    $was_keywords = dbquery($query);
    echo2("Начинаем сливать базу. Было строк в основной $was_keywords");
    $query = "SELECT * FROM  `$db_name`.`tmp_semrush` ;"; //Сливаем таблицу домена (tmp) с общей таблицей ключей
    $rows = dbquery($query, 1);
    foreach ($rows as $row) {
        $query = "INSERT INTO `semrush_keys` (`key_id`, `key`, `adwords`, `results`) VALUES ('', '" . addslashes($row[1]) . "', '$row[2]', '$row[3]'); ";
        dbquery($query);
    }
    $query = "SELECT count(*) FROM `semrush_keys`"; //Сколько стало после слияния строк.
    $now_keywords = dbquery($query);
    $query = "TRUNCATE TABLE `$db_name`.`tmp_semrush`"; //Чистим временную таблицу
    $result = mysqli_query($link, $query);
    if ($error = mysqli_error($link)) {
        echo2("$error");
    }
    $new_keys = $now_keywords - $was_keywords;
    echo2("Закончили сливать базы. Добавили строк к основной $new_keys");
    echo2("--------------------------------------------------");
    return $new_keys;
}

function get_big_domains()
{
    $query = "SELECT * FROM `semrush_domains`";
    $result = dbquery($query);
    $i = 0;
    foreach ($result as $domain) {
        $tmp = $domain['semrush_keys'] - $domain['results_unique'];
        if ($tmp < 20000) {
            unset($result[$i]);
        }
        $i++;
    }
    return $result;
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
 * nr_desc - количество документов по запросу
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
$semrush_key = '077e4b3b2fadf9ac02f83d3068918bd1'; //Идентификатор оплаченного логина с доступом. Обновляется функцией get_export_hash;
$export_hash = '1044760e41afeb0295a44832bde769c8'; //Пример. Меняется от сайта к сайту, надо каждый раз получать.

//Переменные уже для моего скрипта
//'therighthairstyles.com' - уже обработал
$columns = 'Ph,Nq,Nr';
$sortings1 = array('po', 'nq', 'kd', 'cp', 'tr', 'tc', 'co', 'nr');
$sortings2 = array('_desc', '_asc');
$domain_list = array('therighthairstyles.com', 'ombre-hair.info', 'hairstyleforwomen.net', '4hairstyles.com', 'hairstylesupdate.com', 'the-hairstylist.com', 'coolmenhairstyles.com', 'haircutweb.com', 'menshairstylesweb.com', 'haircolorcode.com', 'straighthairclub.com', 'styleinhair.com', 'careforhair.co.uk', 'cleverhairstyles.com', 'slickedbackhair.com', 'hairstyle-blog.com', 'hairstyle.guru', 'hairstyles123.com', 'trendy-hairstyles-for-women.com', 'babesinhairland.com', 'ukhairdressers.com', 'manbunhairstyle.net', 'hairstylecamp.com', 'cutegirlshairstyles.com', 'hairworldmag.com', 'short-hair-style.com', 'machohairstyles.com', 'ticlotel.com', 'hairstyleonpoint.com', 'cutegirlshairstyles.com', 'latest-hairstyles.com', 'hairfinder.com', 'thehairstyler.com', 'menshairstyletrends.com', 'longhairbeez.us', 'hairstylefoto.com', 'lovely-hairstyles.com', 'bob-hairstyle.com', 'haircutinspiration.com', 'short-haircut.com', 'hairfinder.com ', 'hairstylesweekly.com', 'menshairstylestoday.com', 'pophaircuts.com', 'mens-hairstylists.com', 'mens-hairstyle.com', 'hairstyle-designs.com', 'trendinghairstyles.com'); // Без HTTP и слешей
//Домены из базы которые использовались для скачивания картинок, вручную отсортированные - только прически. Нужно без пробелов и www!
$domain_db_nch = array('koojp.com', 'hairstylefoto.com', 'devahairstyles.com', 'favehairstyles.com', 'hairstylesg.com', 'mediumslengthhairs.com', 'www.blackhairdie.com', 'www.hairpediaclub.com', 'www.mens-hairstyle.com', 'www.menshairstylestoday.com', 'www.short-haircut.com', 'www.shorthairstylecool.com', 'www.mediumhaircutstyle.com', 'hairstylewomen101.com', 'hairstylesforshorthairs.com', 'hairins.com', 'shorthairstyleslong.com', 'shorthaircutforwomens.com', 'hairstylehub.com', 'myhairstyletips.com', 'www.newhairstylesidea.com', 'eleganthairstyles.net', 'haircare-clinic.com', 'yourskinandyou.net', 'fashionovert.com', 'beautifulhairstylesideas.com', 'hairstylealbum.com', 'amazing-hairstyles.com', 'www.longhairstylesandcuts.com', 'www.bestmediumhaircut.us', 'medium-hairlist.com', 'ladieshair-idea.us', 'www.hairworldmag.com', 'www.pixie-cut.com', 'www.easternag.com', 'hairstylesweekly.com', 'newbeautyshorthair.com', 'www.behairstyles.com', 'hairstylespedia101.com', 'commonhairstyles.com', 'hairstylessites.com', 'women-hair-styles.com', 'pophaircuts.com', 'modern-hairstyles.net', 'muyuela.com', 'abchairstyles.com', 'trendhaircuts.com', 'classic-hairstyles.com', 'hairstyleholic.com', 'special-hairstyles.com', 'comelyhairstyles.com', 'hairbuz.com', 'www.cuterhaircut.us', 'www.mediumhaircut99.com', 'www.hairstyleslife.com', 'celebhairstyles.net', 'www.bidentry.com', 'www.new-longhairstylepins.info', 'naturalsalon.website', 'www.styleshairs.com', 'glamour-hairstyles.net', 'hairdrome.com', 'www.sophiegee.com', 'pictureofhairstyles.net', 'www.hhairstyle.com', 'hairstyles-galaxy.com', 'great-hairstyles.net', 'www.hairstylearchives.com', 'www.hairstyleboo.com', 'womanhairstyle2016.com', 'www.hairspicture.com', 'shortlonghairstyles.net', 'www.wavygirlhairstyles.com', 'girlshairideas.com', 'hairzstyle.com', 'pretty-hairstyles.com', 'www.lovely-hairstyles.com', 'www.bob-hairstyle.com', 'www.longhairbeez.us', 'www.shorthairdie.com', 'www.eshorthairstyles.com', 'www.long-hairstyless.com', 'belliosteria.com');
$domain_list = array_map('trim', array_unique(str_replace('www.', '', array_merge($domain_list, $domain_db_nch))));
//Исключаем те домены которые уже парсили и есть записи в базе.
$query = "SELECT `domain` FROM `semrush_domains`";
$domains_parsed = dbquery($query, 1);
$domain_list = array_diff($domain_list, $domains_parsed);
$country_list = array('us', 'uk', 'au', 'ca', 'in'); //Еще не докрутил страны и базы.

function parse_words()
{
//Hardcore mode! Допарсинг уже готовых больших доменов!
    global $semrush_key, $result_dir, $columns;
    $counter_semrush_queries = 0; //Сколько раз запросили SEMRUSH
    $counter_semrush_results = 0; //Скольок кеев получили от SEMRUSH (неуник, все).
    $counter_uniq_keywords = 0; //Сколько ключей 1 ДОМЕНА записали в базу. Только уникальные ключи пишутся.
    $parse_more = get_big_domains(); // Получаем список доменов из базы которые надо еще допарсить.
//Весь этот блок надо тестить!
    if ($parse_more) {
        echo2("Выгрузили все домены которые нуждаются в дозакачке ключей, таких нашлось " . count($parse_more) . " .");
        foreach ($parse_more as $item) {
//            $item['domain'] = 'hairfinder.com'; // debug
            if (is_file($result_dir . '/' . 'words_used_' . $item['domain'] . '_.txt')) {
                $words_used = printr_to_array(file_get_contents($result_dir . '/' . 'words_used_' . $item['domain'] . '_.txt'));
                $export_hash = get_export_hash($item['domain']);
            }
            if ($words_used && $export_hash) {
                echo2("Начинаем поключевую выгрузку для домена " . $item['domain'] . ". Всего ключей есть в Semrush - " . $item['semrush_keys'] . " , мы получили уникальных " . $item['results_unique'] . " за " . $item['queries_done'] . " запросов");
                foreach ($words_used as $key => $word) {
                    $semrush_query = 'https://us.backend.semrush.com/?action=report&database=us&rnd_m=' . time() . '&key=' . $semrush_key . '&domain=' . $item['domain'] . '&type=domain_organic&display_filter=%2B%7CPh%7CCo%7C' . $key . '&display_sort=nr_asc&export_hash=' . $export_hash . '&export_decode=1&export_escape=1&currency=usd&export_columns=' . $columns . '&export=stdcsv';
                    echo2("$semrush_query");
                    $time = microtime(true);
                    $semrush_data = file_get_contents($semrush_query);
                    $time = microtime(true) - $time;
                    echo2("#$counter_semrush_queries Получили данные от SEMRUSH. Заняло времени " . number_format($time, 2) . " сек.");
                    $counter_semrush_queries++;
                    if ($semrush_data) {
                        $semrush_data = explode(PHP_EOL, $semrush_data);
                        foreach ($semrush_data as $v) {
                            $semrush_csv[] = str_getcsv($v, ';');
                        }
                        unset($semrush_data);
                        $counter_semrush_results += count($semrush_csv);
                        foreach ($semrush_csv as $str) {
                            $query = "INSERT INTO `semrush_keys` (`key_id`, `key`, `adwords`, `results`) VALUES ('', '" . addslashes($str[0]) . "', '$str[1]', '$str[2]'); ";
                            if ($z = dbquery($query, 0, 1) == 1) {
                                $counter_uniq_keywords += $z;
                                $this_time_uniq += $z;
                            }
                        }
                        $array_unique_queries[] = $this_time_uniq;
                        echo2("Новых фраз закачали $this_time_uniq из полученных " . count($semrush_csv) . " строк.");
                        unset ($semrush_csv);
                    }
                    //Если за 50 запросов в базу упало меньше 1000 уникальных ключей - переходим к следующему домену.
                    if ($counter_semrush_queries >= 50 && $counter_uniq_keywords < 1000) {
                        break;
                    }
                    //Если за последние 10 запросов упало меньше 100 запросов - переходим к следующему домену.
                    if ($counter_semrush_queries % 20 == 0) {
                        if (array_sum($array_unique_queries) < 5000) {
                            unset($array_unique_queries);
                            break;
                        }
                        unset($array_unique_queries);

                    }
                }
            }
            //Записываем обновленные результаты парсинга домена
            echo2("Закончили с доменом, новых уникальных ключей $counter_uniq_keywords за $counter_semrush_queries запросов. Обновляем данные в базе.");
            echo2("-------------------------------");
            $query = "UPDATE `semrush_domains` SET `queries_done` = `queries_done` + $counter_semrush_queries , `results_got` = `results_got` + $counter_semrush_results, `results_unique` = `results_unique` + $counter_uniq_keywords WHERE `id` = " . $item['id'];
            dbquery($query);
            $counter_semrush_queries = 0; //Сколько раз запросили SEMRUSH
            $counter_semrush_results = 0; //Скольок кеев получили от SEMRUSH (неуник, все).
            $counter_uniq_keywords = 0; //Сколько ключей 1 ДОМЕНА записали в базу. Только уникальные ключи пишутся.
            unset($last_10_queries, $tmp_last_10_queries);
        }
    }
}

//Допарсим крупные домены.
parse_words();

$counter_semrush_queries = 0; //Сколько раз запросили SEMRUSH
$counter_semrush_results = 0; //Скольок кеев получили от SEMRUSH (неуник, все).
$counter_uniq_keywords = 0; //Сколько ключей 1 ДОМЕНА записали в базу. Только уникальные ключи пишутся.
$counter_semrush_total_traffic = 0; //Сколько всего получили от SEMRUSH данных в байтах.
$counter_semrush_traffic_query = 0; //Сколько на конкретный запрос трафика получилось.
$site_iteration_count = 0;
//Основной цикл парсинга.
foreach ($domain_list as $domain) {
    $site_iteration_count++;
    $export_hash = get_export_hash($domain);
    $country_base = $country_list[0];
    $semrush_keys = keys_count($domain); //Сколько всего Organic ключей есть для домена
    $us_semrush_keys = $semrush_keys;
    if ($export_hash && $semrush_keys > 0) {
        foreach ($country_list as $country_base) {
            //Посчитаем для страны сколько есть ключей.
            if ($country_base !== 'us') {
                $semrush_keys = keys_count($domain); //Сколько всего Organic ключей есть для домена
            }
            foreach ($sortings1 as $sort) {
                foreach ($sortings2 as $ascdesc) {
                    $semrush_query = 'https://' . $country_base . '.backend.semrush.com/?action=report&database=' . $country_base . '&rnd_m=' . time() . '&key=' . $semrush_key . '&domain=' . $domain . '&type=domain_organic&display_filter=&display_sort=' . $sort . $ascdesc . '&export_hash=' . $export_hash . '&export_decode=1&export_escape=1&currency=usd&export_columns=' . $columns . '&export=stdcsv';
                    echo2($semrush_query);
                    $time = microtime(true);
                    //exmpl query https://us.backend.semrush.com/?action=report&database=us&rnd_m=1485386746&key=5c8e0eb96b3fe54582fee2128ea97257&domain=hairstylefoto.com&type=domain_organic&display_filter=&display_sort=tr_desc&export_hash=62b89bcfe05430a7607bb86a48ab0f7e&export_decode=1&export_escape=1&currency=usd&export_columns=Ph,Nq,Nr&export=stdcsv
                    $semrush_data = file_get_contents($semrush_query);
                    $time = microtime(true) - $time;
                    echo2("Получили данные от SEMRUSH. Заняло времени " . number_format($time, 2) . " сек.");
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
                            //Записывать будем только если большие сайты ключевики.
                            if ($semrush_keys > 70000) {
                                $tmp = explode(' ', $str[0]);
                                foreach ($tmp as $word) {
                                    $words_used[strtolower($word)] += 1;
                                }
                            }
                        }
                        echo2("#$counter_semrush_queries $domain Массив из Semrush получили, размером в " . convert($counter_semrush_traffic_query) . " , строк " . count($semrush_csv) . " Всего строк получили $counter_semrush_results , уникальных записей в базу $counter_uniq_keywords / $semrush_keys из возможных, трафика скачали " . convert($counter_semrush_total_traffic));
                        echo_time_wasted();
                        //Отлавливаем маленькие сайты по ключам.
                        //Если по US базе меньше 10к результатов - идем на следующий домен. Пофиг на другие страны.
                        #todo перенести в функцию этот итератор-отлавливалку с кучей глобалов
                        if (count($semrush_csv) < 10000 && $site_iteration_count == 1) {
                            unset($semrush_csv);
                            echo2("Сайт $domain по базе $country_base отдал первым же запросом меньше 10000 результатов, выходим из цикла по нему.");
                            $go_next_domain = 1;
                            break;
                            //Если по НЕ US базе меньше 10к результатов, то...берем остальные страны по 1 разу.
                        } else if (count($semrush_csv) < 10000 && $country_base !== 'us') {
                            unset($semrush_csv);
                            $only_1_time_country = 1; //Метка не больше 1 раза по маленьким странам пробежаться если какая-то из не US отдала меньше 10к результатов.
                            echo2("Сайт $domain по базе $country_base отдал первым же запросом меньше 10000 результатов. Добираем остальные страны-базы и выходим.");
                            //Если для домена (или страны) меньше 30000 ключей, делаем не больше 7 запросов, дальше выходим из цикла.
                        } else if ($semrush_keys < 30000) {
                            $country_iteration++;
                            unset($semrush_csv);
                            if ($semrush_keys < 20000 && $country_iteration == 4) {
                                $only_1_time_country = 1; //Метка не больше 1 раза по маленьким странам пробежаться если какая-то из не US отдала меньше 10к результатов.
                                echo2("Сайт $domain отдал до 20000 результатов, не ходим больше 4 раз по циклу, выходим и идем дальше.");
                            }
                            if ($country_iteration == 7) {
                                $only_1_time_country = 1; //Метка не больше 1 раза по маленьким странам пробежаться если какая-то из не US отдала меньше 10к результатов.
                                echo2("Сайт $domain отдал до 30000 результатов, не ходим больше 7 раз по циклу, выходим и идем дальше.");
                            }
                        } else {
                            unset($semrush_csv);
                        }
                        //Блок если не получили по домену данных, уже по CSV.
                    } else {
                        if ($semrush_data === false) {
                            echo2("Не получен файл от SEMRUSH. Где-то косяк в запросе, надо проверить export_hash $export_hash и semrush_keys $semrush_keys , и сам query.");
                            $go_next_domain = 1;
                            $domain_no_data = 1;
                            break;
//                            exit;
                        } else {
                            echo2("Получен пустой файл для $domain , возможно нет данных в SEMRUSH. Переходим к следующему домену");
                            $go_next_domain = 1;
                            $domain_no_data = 1;
                            break;
                        }
                    }
                    $site_iteration_count++;
                }
                if ($go_next_domain == 1 || $only_1_time_country == 1) {
                    break;
                }
            }
            // Убрал || $only_1_time_country == 1 условие, сбросил country_iter.
            $country_iteration = 0;
            if ($go_next_domain == 1) {
                break;
            }
        }
    }
    //Закончили с доменом, действия. Или не получили необходимые Export_hash и keys_count
    if ($domain_no_data == false) {
        if ($words_used) {
            $result_fp = $result_dir . '/' . 'words_used_' . $domain . '_.txt';
            arsort($words_used);
            file_put_contents($result_fp, print_r($words_used, true));
            echo2("Использованные слова записали в $result_fp");
        }
        $new_keys = added_keys();
        $query = "INSERT INTO `semrush_domains` (`id`, `domain`, `semrush_keys`, `queries_done`, `results_got`, `results_unique`,`results_new`) VALUES ('', '" . $domain . "', '$us_semrush_keys', '$counter_semrush_queries', '$counter_semrush_results', '$counter_uniq_keywords', '$new_keys');";
        dbquery($query);
    }
    //Обнуляем счетчики и метки циклов для следующего домена.
    $site_iteration_count = 0;
    $counter_semrush_queries = 0;
    $counter_uniq_keywords = 0;
    $counter_semrush_results = 0;
    $country_iteration = 0;
    unset ($go_next_domain, $domain_no_data, $only_1_time_country, $words_used);
}