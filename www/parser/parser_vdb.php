<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.02.2017
 * Time: 23:04
 * ДОхера функций, не работает на нужном сайте и не понятно как сраный список урлов получить
 */

use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use Symfony\Component\EventDispatcher\Event;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\StatsHandler;
use VDB\Spider\Spider;
include('../new/includes/functions.php');
require '../../vendor/autoload.php';

$domain = 'ihouzz.ru';
$url = 'http://'.$domain;
$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain . ".txt";

// Create Spider
$spider = new Spider($url);
// Add a URI discoverer. Without it, the spider does nothing. In this case, we want <a> tags from a certain <div>
$spider->getDiscovererSet()->set(new XPathExpressionDiscoverer("//a"));
// Set some sane options for this example. In this case, we only get the first 10 items from the start page.
$spider->getDiscovererSet()->maxDepth = 7;
$spider->getQueueManager()->maxQueueSize = 10;
// Let's add something to enable us to stop the script
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_USER_STOPPED,
    function (Event $event) {
        echo "\nCrawl aborted by user.\n";
        exit();
    }
);
// Add a listener to collect stats to the Spider and the QueueMananger.
// There are more components that dispatch events you can use.
$statsHandler = new StatsHandler();
$spider->getQueueManager()->getDispatcher()->addSubscriber($statsHandler);
$spider->getDispatcher()->addSubscriber($statsHandler);
// Execute crawl
$spider->crawl();
// Build a report
echo "\n  ENQUEUED:  " . count($statsHandler->getQueued());
echo "\n  SKIPPED:   " . count($statsHandler->getFiltered());
echo "\n  FAILED:    " . count($statsHandler->getFailed());
echo "\n  PERSISTED:    " . count($statsHandler->getPersisted());
// Finally we could do some processing on the downloaded resources
// In this example, we will echo the title of all resources
echo "\n\nDOWNLOADED RESOURCES: ";
$i = 0;
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    $i++;
//    echo "\n - " . $resource->getCrawler()->filterXpath('//title')->text();
    echo "\n - #$i " . $resource->getUri();
}
file_put_contents($result_sitename_fp,serialize($urls));
echo_time_wasted("Записали данные в файл $result_sitename_fp, всего элементов ".count($urls));