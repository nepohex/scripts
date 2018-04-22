<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 18.04.2018
 * Time: 23:16
 */
include "../includes/functions.php";
include "../includes/proxy.php";
include 'C:\OpenServer\domains\scripts.loc\vendor\simple-html-dom\simple-html-dom\simple_html_dom.php';
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;

$urls = file('C:\OpenServer\domains\scripts.loc\www\new\celebs\inc\source.txt', FILE_IGNORE_NEW_LINES);

#### Рандомные строки для многопоточности ибо за 4 часа только 4 из 39 урлов прошло в 1 поток
shuffle($urls);
$tag = explode('/', $urls[0]);
$tag = last($tag);
for ($z = 0; $z <= 501; $z++) {
    if (!is_file('debug_data/' . $tag . '.txt')) {
        echo2("Создали файл для тега $tag, начинаем сбор");
        file_put_contents('debug_data/' . $tag . '.txt', '');
        break;
    } else {
        $z++;
        shuffle($urls);
        $tag = explode('/', $urls[0]);
        $tag = last($tag);
        if ($z == 500) {
            exit ("500 раз прочекали, есть все 39 урлов уже созданные файлы!");
        }
    }

}
####

$i = 0;
foreach ($urls as $url) {
    for ($z = 0; $z < 10000; $z++) {
        $i++;
        do {
            $proxy = proxy_get_valid($proxy_list);
            $data = proxy_get_data($proxy, $url . '/page/' . $i, 6);
        } while ($data == FALSE);
        $tag = explode('/', $url);
        $tag = last($tag);
        if ($data) {
            $tmp = str_get_html($data);
            foreach ($tmp->find('div.entry') as $element) {
                $tmp2 = $element->find('a[title]');
                $tmp3 = $tmp2[0]->plaintext;
                $name = str_replace('\'', '', trim($tmp3));

                $tmp2 = $element->find('span');
                $tmp3 = $tmp2[0]->plaintext;
                $arr[$name][0] = trim($tmp3);
                if (@!in_array($tag, $arr[$name][1])) {
                    $arr[$name][1][] = $tag;
                }
            }
            if ($i % 10 == 0) {
                echo_time_wasted($i);
            }
            //Определяем есть ли еще страницы для парсинга
            if ($tmp->find('li.next a') == FALSE) {
                echo_time_wasted(count($arr) . " собрали элементов, идем по урл $url и страница $i");
                $i = 0;
                $fin = serialize($arr);
                file_put_contents('debug_data/' . $tag . '.txt', $fin);
                exit (); //Для мультипоточности
                break;
            }
        } else {
            throw new Exception("NO data!");
        }
    }
}
$fin = serialize($arr);
file_put_contents('/debug_data/celebs_list_ser.txt', $fin);
echo "yo";



