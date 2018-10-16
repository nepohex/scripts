<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.08.2018
 * Time: 0:52
 */
$time = microtime(true);
include("C:/OpenServer/domains/scripts.loc/www/new/includes/functions.php");
include("C:/OpenServer/domains/scripts.loc/www/new/includes/proxy.php");
include("C:/OpenServer/domains/scripts.loc/www/parser/simpledom/simple_html_dom.php");
$debug_mode = TRUE;
define('EXCLUDE_FAIL_PROXY', TRUE); //если с данной прокси не получили результата (не важно - каптча ли, или просто прокси лежит - ее исключаем из парсинга дальше)
echo2("Многопоточная проверкп на Majestic доменов. Можно запускать много дублей скрипта = иммитация мультипоточности.");

#########
$fname_input = 'domains_my_test.txt'; //уникальное имя нужно, от него будем отталкиваться в названиях остальных
$list = file('f:\tmp\proxy_mix_hidemyname.txt', FILE_IGNORE_NEW_LINES);
$urls = file('f:\tmp/' . $fname_input, FILE_IGNORE_NEW_LINES); //список доменов без http и т.п.
$fname_result = './debug/result_majestic' . date('d-m-Y-H-i') . '_.csv';
$fname_result2 = 'f:\tmp/result_majestic' . date('d-m-Y-H-i') . '_.csv';
#########

//debug
//$url = 'medwrite.biz';
//$html = file_get_contents("./debug/$url" . ".html");
//$data = str_get_html($html);
//$tf_cf = majestic_get_TF($html);
//$languages = majestic_get_incoming_languages($data);
//$anchors = majestic_get_top_anchors($data);
//$crawled_urls = majestic_crawled_urls($data);
//$link_sources = majestic_links_sources($data);
//$row = majestic_build_csv_row($url, $tf_cf, $languages, $anchors, $crawled_urls, $link_sources);
//
//$urls = array('medwrite.biz');
//$fname_domain_res = "./debug/$url";
//file_put_contents($fname_domain_res, serialize($row));
//
//$dir = scandir("./debug");
//$res = array();
//foreach ($dir as $file) {
//    if (in_array($file, $urls)) {
//        $tmp = unserialize(file_get_contents("./debug/" . $file));
//        if (is_array($tmp)) {
//            $res[] = $tmp;
//        }
//    }
//}
//debug
echo2("Подано на проверку " . count($list) . " прокси и " . count($urls) . " доменов");
$list = tmp_synch_bad_proxy($debug['bad_proxies'], "./debug/bad_proxies.txt", $list);
echo2("Синхронизируем список плохих сохраненных прокси, удаляем их перед началом работы. Осталось прокси " . count($list));
foreach ($urls as $url) {
    $fname_domain_res = "./debug/$url";
    $fp = fopen($fname_domain_res, "a+");
    if (file_lock($fp) && fgets($fp) == FALSE) {
        while (!$data) {
            $proxy_id = rand(0, count($list));
            $rnd_proxy = $list[$proxy_id];
//            $rnd_proxy = '213.81.238.168:4145'; //debug
            $html = proxy_get_data($rnd_proxy, 'https://majestic.com', 3, TRUE, TRUE); // debug
            if ($html !== false) {
                $html = proxy_get_data($rnd_proxy, "https://majestic.com/reports/site-explorer?q=$url&oq=$url&IndexDataSource=F", 3, TRUE, TRUE);
            }
            if ($debug['query_times'] % 10 == 0) {
                $list = tmp_synch_bad_proxy($debug['bad_proxies'], "./debug/bad_proxies.txt", $list);
            }
            if (majestic_check_valid_answer($html, $url)) { // Валидный ответ выглядит вот так ./debug/DEBUG_dont_delete_vallid_answer.txt
                //debug
                file_put_contents("./debug/$url" . ".html", $html);
                echo2($url);
                echo2("Query tries " . $debug['query_times'] . " // Excluded proxies " . $debug['excluded_proxies'] . " // Bad proxies " . $debug['bad_proxy'] . " // Banned proxies " . $debug['proxy_banned']);
                unset ($debug['query_times']);
                //debug
                $data = str_get_html($html);
                $tf_cf = majestic_get_TF($html);
                $languages = majestic_get_incoming_languages($data);
                $anchors = majestic_get_top_anchors($data);
                $crawled_urls = majestic_crawled_urls($data);
                $link_sources = majestic_links_sources($data);
                $row = majestic_build_csv_row($url, $tf_cf, $languages, $anchors, $crawled_urls, $link_sources);
            } else {
                tmp_prepare_report($html);
            }
        }
        @$i++;
        if ($i % 10 == 0) {
            $list = tmp_synch_bad_proxy($debug['bad_proxy'], "./debug/bad_proxies.txt", $list);
            echo_time_wasted($i, " / " . count($urls) . " done // Total Threads Done Of Current Task = " . tmp_get_total_progress($urls) . " //  Query tries " . $debug['query_times'] . " // Excluded proxies Total " . $debug['excluded_proxies'] . " // Proxy left " . count($list) . " // Bad proxies " . $debug['bad_proxy'] . " // Banned proxies " . $debug['proxy_banned']);
        }
        file_lock($fp, 1);
        file_put_contents($fname_domain_res, serialize($row));
        unset ($data);
    }
}
echo_time_wasted();

file_lock($fp, 1);
$dir = scandir("./debug");
$res = array();
foreach ($dir as $file) {
    if (in_array($file, $urls)) {
        $tmp = unserialize(file_get_contents("./debug/" . $file));
        if (is_array($tmp)) {
            $res[] = $tmp;
        }
    }
}
majestic_write_csv($fname_result, $res);
copy($fname_result, $fname_result2);
echo2("Результат записан в файл $fname_result + $fname_result2");

function tmp_get_total_progress($urls)
{
    $i = 0;
    $dir = scandir("./debug");
    $res = array();
    foreach ($dir as $file) {
        if (in_array($file, $urls)) {
            $i++;
        }
    }
    return $i;
}

function file_lock($fp, $unlock = FALSE)
{
    if ($unlock) {
        flock($fp, LOCK_UN);
        return TRUE;
    }
    if (!flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
        if ($wouldblock) {
            // another process holds the lock
            return FALSE;
        } else {
            return FALSE;
            // couldn't lock for another reason, e.g. no such file
        }
    } else {
        return TRUE;
        // lock obtained
    }
}

/** Проверяем получили ли нормальный ответ от сервиса, или же блокировка и т.п.
 * Нормальный ответ содержит тайтл
 * <title>Plasma-Us.com WHOIS, DNS, &amp; Domain Info - DomainTools</title>
 * сразу за тайтлом идет название домена
 * @param $data
 * @param $url
 * @return bool
 */
function majestic_check_valid_answer($data, $url)
{
    if (preg_match('/<title>.*(' . $url . ').*<\/title>/i', $data)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function tmp_prepare_report($data)
{
    global $debug, $list, $proxy_id;
    if ($data == FALSE) {
        $debug['bad_proxy'] += 1;
    } else {
        $debug['proxy_banned'] += 1;
    }
    $data = FALSE;
    if (EXCLUDE_FAIL_PROXY) {
        $debug['bad_proxies'][] = $list[$proxy_id];
        unset($list[$proxy_id]);
        $list = array_values($list);
        $debug['excluded_proxies'] += 1;
    }
    $debug['query_times'] += 1;
}

function majestic_links_sources($domdocument)
{
//    <div class="wee-chart">
//		<table>
//				<tr><th>Live (<b>99.9%</b>)</th></tr>
//				<tr><td>22,698</td></tr>
//		</table>
//		<div class="bg">
//				<span style="width: 0.0748404138234647%">&nbsp;</span>
//		</div>
//		<table>
//				<tr><td>17</td></tr>
//				<tr><th class="hoverHint" title="">Deleted (<b>0.1%</b>)</th></tr>
//		</table>
//	</div>
    $data = $domdocument->find("div[class=wee-chart]");
    foreach ($data as $item) {
        $tmp = $item->find("tr");

        $column_name = preg_replace('/\W|\d/', "", $tmp[0]->plaintext);
        $res[$column_name] = preg_replace("/[^0-9]/", "", $tmp[1]->plaintext);

        $column_name = preg_replace('/\W|\d/', "", $tmp[3]->plaintext);
        $res[$column_name] = preg_replace("/[^0-9]/", "", $tmp[2]->plaintext);
    }
    return $res;
}

function majestic_crawled_urls($domdocument)
{
//    <table class="mob-1-column-table oldTable">
//		<tr>
//			<td width="250">Crawled URLs</td>
//			<td><b>35,966</a></b></td>
//		</tr>
    $tmp = $domdocument->find("table[class=mob-1-column-table oldTable]");
    $tmp = preg_replace("/[^0-9]/", "", $tmp[0]->find("td", 1)->plaintext);
    return $tmp;
}

function majestic_get_top_anchors($domdocument)
{
//    <td valign="top" class="anchorText">
//									<a href="/reports/site-explorer/anchor-text?folder=&q=medwrite.biz&oq=medwrite.biz&IndexDataSource=F&filteranchors=www.medwrite.biz" class="hoverHint anchorText" title='Anchor Text: www.medwrite.biz'>
//        www.medwrite.biz
//									</a>
//							</td>
    $anchor = $domdocument->find("td[class=anchorText]");
    foreach ($anchor as $item) {
        if ($item->children()) {
            $tmp = trim($item->children(0)->plaintext);
            if ($tmp === ".") {
                $tmp = "NO ANCHOR (IMAGE)";
            }
            $anchors[] = $tmp;
        }
    }

    //Total Это сколько ссылок всего
//    <td valign="top" align="right">923</td>
    //Deleted
//							<td class="intCell" valign="top" align="right">2</td>
    //NoFollow
//							<td class="intCell" valign="top" align="right">10</td>

    $anchor = $domdocument->find("table[class=clean-table pie-highlight pie-highlight-anchor]");
    if ($anchor) {
        $anchor = $anchor[0]->find("td[class=intCell]");
        //Будем брать только 1ый тег из набора, и получать все данные для анкора, 2ой тег - пропускать.
        foreach ($anchor as $item) {
            if ($item->prev_sibling() && $item->next_sibling()->class == "intCell") {
                $total[] = $item->prev_sibling()->plaintext; // Total
                $deleted[] = $item->plaintext; //Deleted
                $nofollow[] = $item->next_sibling()->plaintext; //Nofollow
            }
        }
        $res['anchors'] = $anchors;
        $res['total links'] = $total;
        $res['deleted'] = $deleted;
        $res['nofollow'] = $nofollow;
        return $res;
    } else {
        return FALSE;
    }
}

function majestic_get_incoming_languages($domdocument)
{
//    <div class="floatl mob-single-panel ttf-block languages " style="width:33%">
//
//		<h4>Incoming Languages</h4>
//		<p><b>22,250</b> checked backlinks</p>
    ///// Независимые фрагменты
//    <div class="floatl mob-single-panel ttf-block languages " style="width:33%">
//
//		<h4>Site Languages</h4>
//		<p><b>13,941</b> language-detected crawled URLs</p>
    $stats = $domdocument->find("div[class=floatl mob-single-panel ttf-block languages]");
    //$res['checked backlinks'] = '22,250 checked backlinks';
    $res['checked backlinks'] = preg_replace('/[^0-9]/', '', $stats[0]->children(1)->plaintext);
    $res['language-detected crawled URLs'] = preg_replace('/[^0-9]/', '', $stats[1]->children(1)->plaintext);

    //Incoming Languages (backlinks)
    $lang_backlinks = $stats[0]->find("span[class=hoverHint the_score]");
    //Разная вложенность у элементов!
    foreach ($lang_backlinks as $langs) {
        //<span class="hoverHint the_score " style="width: 88.9%; text-align: left"
        //Пробовал через дочерние элементы - лучше не стоит, там разная структура всегда!
        $links[] = preg_replace('/[^0-9\.\,]/', '', $langs->style);
        if ($langs->children()) {
            $tmp = $langs->find("span[class=language-code]", 0)->plaintext;
        } else {
            $tmp = $langs->next_sibling()->find("span[class=language-code]", 0)->plaintext;
        }
        $links[] = $tmp;
    }

    //Site Languages (language-detected crawled URLs)
    $lang_backlinks = $stats[1]->find("span[class=the_score]");
    foreach ($lang_backlinks as $langs) {
        //<span class="the_score" style="width: 100%; text-align: left">100%<span class="language-code">en</span> English</span>
        //Пробовал через дочерние элементы - лучше не стоит, там разная структура всегда!
        $pages[] = preg_replace('/[^0-9\.\,]/', '', $langs->style);
        if ($langs->children()) {
            $tmp = $langs->find("span[class=language-code]", 0)->plaintext;
        } else {
            $tmp = $langs->next_sibling()->find("span[class=language-code]", 0)->plaintext;
        }
        $pages[] = $tmp;
    }
    $res['Languages Backlinks'] = $links;
    $res['Site Languages'] = $pages;
    return $res;
}

/** На вход HTML и парсинг регуляркой, потому что находится в JS данные.
 * @param $html
 * @return null|array
 */
function majestic_get_TF($html)
{
//    var trustFlow = 16;
//    var citationFlow = 54;
    preg_match_all('/var \w+Flow = (\d+)/im', $html, $matches);
    if (@count($matches) > 0) {
        $res['tf'] = $matches[1][0];
        $res['cf'] = $matches[1][1];
        return $res;
    } else {
        return FALSE;
    }
}

function majestic_build_csv_row($url, $tf_cf, $languages, $anchors, $crawled_urls, $link_sources)
{
    //$csv_header = array('Domain', 'TF', 'CF', 'Crawled Urls', 'Links Languages', 'Main Language Links', '% Main Language Links', 'Links Languages Total %', 'Site Language-Detected Crawled URLS', 'Main Site Language Crawled Urls', 'Site Language main %', 'Site Languages Total %', 'Live Links', 'Deleted Links', '% Deleted Links', 'Deep Links', 'Homepages Links', 'Direct Links', 'Indirect Links', 'Follow Links', 'Nofollow Links', '% Nofollow Links', 'HTTP Links', 'HTTPS Links', 'Anchors');

    $row['URL'] = $url;

    $row['TF'] = $tf_cf['tf'];
    $row['CF'] = $tf_cf['cf'];

    $row['Crawled Urls'] = $crawled_urls;

    $row['Links Languages'] = $languages['checked backlinks'];

    $row['Main Language Links'] = trim($languages['Languages Backlinks'][1]);
    $row['% Main Language Links'] = str_replace('.', ',', $languages['Languages Backlinks'][0]) . '%'; //Для Экселя заменяем точки на запятые, иначе цифры типа 21.6 меняются на ДАТУ, и ничего не поделать
    $row['Links Languages Total %'] = implode($languages['Languages Backlinks'], " - ");

    $row['Site Language-Detected Crawled URLS'] = $languages['language-detected crawled URLs'];
    $row['Main Site Language Crawled Urls'] = $languages['Site Languages'][1];
    $row['Site Language Main %'] = $languages['Site Languages'][0];
    $row['Site Languages Total %'] = @implode($languages['Site Languages'], " - ");

    $row['Live Links'] = $link_sources['Live'];
    $row['Deleted Links'] = $link_sources['Deleted'];
    $row['% Deleted Links'] = @round($link_sources['Deleted'] / $link_sources['Live'] * 100) . '%';
    $row['Deep Links'] = $link_sources['FromDeepLinks'];
    $row['Homepages Links'] = $link_sources['FromHomepages'];
    $row['Direct Links'] = $link_sources['Direct'];
    $row['Indirect Links'] = $link_sources['Indirect'];
    $row['Follow Links'] = $link_sources['Follow'];
    $row['NOFollow Links'] = $link_sources['NoFollow'];
    $row['% Nofollow Links'] = @round($link_sources['NoFollow'] / $link_sources['Follow'] * 100) . '%';
    $row['HTTP Links'] = $link_sources['HTTP'];
    $row['HTTPS Links'] = $link_sources['HTTPS'];

    foreach ($anchors['anchors'] as $k => $anchor) {
        @$row_anchors .= $anchor . ' - ' . $anchors['total links'][$k] . " || ";
    }
    $row['Anchors'] = $row_anchors;

    foreach ($row as &$r) {
        if ($r == '') {
            $r = 0;
        }
    }
    array_map('trim', $row);
    return $row;
}

function majestic_write_csv($fname, $rows)
{
    $fp = fopen($fname, "w+");
    $i = 0;
    foreach ($rows as $row) {
        if ($i == 0) {
            foreach ($row as $k => $v) {
                $csv_header[] = $k;
            }
            fputcsv($fp, $csv_header, ";");
        }
        fputcsv($fp, $row, ";");
        $i++;
    }
}

function tmp_synch_bad_proxy($bad_proxy_array, $fp, $current_proxy_list)
{
    if (is_file($fp)) {
        $bad_proxy_saved = file($fp, FILE_IGNORE_NEW_LINES);
        if (@count($bad_proxy_saved) > 0) {
            //Удаляем из текущего листа нерабочие сохраненные в папке прокси.
            $list = array_diff($current_proxy_list, $bad_proxy_saved);
            $list = array_values($list);
        } else {
            $bad_proxy_saved = FALSE;
        }
    }
    if (@count($bad_proxy_array) > 0) {
        if (@is_array($bad_proxy_saved)) {
            $total_bad_proxies = array_merge($bad_proxy_saved, $bad_proxy_array);
            $list = array_diff($current_proxy_list, $total_bad_proxies);
            file_put_contents($fp, implode(PHP_EOL, $total_bad_proxies));
        } else {
            file_put_contents($fp, implode(PHP_EOL, $bad_proxy_array));
        }
    }
    if ($list == FALSE) {
        $list = $current_proxy_list;
    }
    $list = array_values($list);
    return $list;
}