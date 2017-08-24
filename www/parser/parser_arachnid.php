<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 04.02.2017
 * Time: 22:44
 * Находит очень мало ссылок, втф не знаю.
 * Использует для парсинга DOM, только для валидного кода подходит.
 */
include('../new/includes/functions.php');
require '../../vendor/autoload.php';

$debug_mode = 1 ;
$domain = 'ihouzz.ru';
$url = 'http://'.$domain;
$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain . ".txt";

$linkDepth = 3;
$options = array(
//    'curl' => array(        CURLOPT_SSL_VERIFYHOST => false,        CURLOPT_SSL_VERIFYPEER => false,    ),
    'timeout' => 30,
    'connect_timeout' => 30,
);

// Initiate crawl
$crawler = new \Arachnid\Crawler($url, $linkDepth);
$crawler->setCrawlerOptions($options);
$logger = new \Monolog\Logger('crawler logger');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('crawler.log'));
$crawler->setLogger($logger);
$crawler->traverse();

// Get link data
$links = $crawler->getLinks();
foreach ($links as $url) {
    if ($url['external_link'] == false && $url['absolute_url']) {
        $urls[] = $url['absolute_url'];
    }
}
file_put_contents($result_sitename_fp,serialize($urls));
echo_time_wasted("Записали данные в файл $result_sitename_fp, всего элементов ".count($urls));
print_r($links);