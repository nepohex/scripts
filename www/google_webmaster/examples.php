<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 09.05.2017
 * Time: 18:46
 * https://developers.google.com/webmaster-tools/search-console-api-original/v3/searchanalytics/query
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
$debug_mode = 1;

$client = new Google_Client();
$client->setAuthConfig('auth/mfaconsole-b2354dff8c88.json');
$client->addScope(Google_Service_Webmasters::WEBMASTERS_READONLY);
// Your redirect URI can be any registered URI, but in this example
// we redirect back to this same page
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$client->setRedirectUri($redirect_uri);
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
}
//Пример 1 - получение 5000 ключей с их статистикой по поиску по картинкам
$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$request->setStartDate('2017-05-10');
$request->setEndDate('2017-05-10');
$request->setDimensions(array('query'));
$request->setSearchType('image');
$request->setRowLimit('5000');
$result = $service->searchanalytics->query('netvoine.info', $request);
//print_r($result);
//

//Пример 2 - получение ключей для планшетов только, тоже поиск по картинкам.
//$service = new Google_Service_Webmasters($client);
//$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
//$request->setStartDate('2017-05-10');
//$request->setEndDate('2017-05-10');
//$request->setDimensions(array('query'));
//$request->setSearchType('image');
//$request->setRowLimit('5000');
//$result = $service->searchanalytics->query('netvoine.info', $request);
//$filtri = new Google_Service_Webmasters_ApiDimensionFilterGroup;
//$filtro = new Google_Service_Webmasters_ApiDimensionFilter;
//$filtro->setDimension("device");
//$filtro->setOperator("equals");
//$filtro->setExpression("tablet");
//$filtri->setFilters(array( $filtro ));
//$request->setDimensionFilterGroups(array($filtri));
//$result = $service->searchanalytics->query('netvoine.info', $request);
//print_r($result);
//

//Пример 3 - получение запросов содержащих определенное слово
$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$request->setStartDate('2017-05-10');
$request->setEndDate('2017-05-10');
$request->setDimensions(array('query'));
$request->setSearchType('image');
$request->setRowLimit('5000');
$result = $service->searchanalytics->query('netvoine.info', $request);
$filtri = new Google_Service_Webmasters_ApiDimensionFilterGroup;
$filtro = new Google_Service_Webmasters_ApiDimensionFilter;
$filtro->setDimension("query");
$filtro->setOperator("contains");
$filtro->setExpression("2016");
$filtri->setFilters(array($filtro));
$request->setDimensionFilterGroups(array($filtri));
$result = $service->searchanalytics->query('netvoine.info', $request);
print_r($result);
//

$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SitesListResponse();
$result = $service->sites->listSites();
var_dump($result);