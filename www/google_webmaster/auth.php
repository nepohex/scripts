<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 09.05.2017
 * Time: 18:46
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
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
$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$request->setStartDate('2017-04-01');
$request->setEndDate('2017-05-08');
$request->setDimensions(array('query'));
$result = $service->searchanalytics->query('netvoine.info', $request);
print_r($result);

$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SitesListResponse();
$result = $service->sites->listSites();
var_dump($result);
foreach ($result as )