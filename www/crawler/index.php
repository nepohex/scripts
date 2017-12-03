<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.11.2017
 * Time: 18:14
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;

$client = new Client();
// Initiate each request but do not block
$promises = [
    'image' => $client->getAsync('/image'),
    'png'   => $client->getAsync('/image/png'),
    'jpeg'  => $client->getAsync('/image/jpeg'),
    'webp'  => $client->getAsync('/image/webp')
];

$requests = function() {
    for ($i = 0; $i < 10; ++$i) {
        yield new Request('GET', 'http://localhost:8008/');
    }
};
$pool = new Pool($client, $requests(), [
    'concurrency' => 5,
    'fulfilled' => function(ResponseInterface $response, $index) {
        echo $response->getBody() . PHP_EOL;
    },
]);
$promise = $pool->promise();
$promise->wait();

$promise = $client->requestAsync('GET', 'http://jsonplaceholder.typicode.com/posts/1');
$promise->then(
    function (Response $resp) {
        echo $resp->getBody();
    },
    function (RequestException $e) {
        echo $e->getMessage();
    }
);
$response_async = $promise->wait();
echo $response_async->getBody();
print_r($response_async->getHeaders());
// Create a client with a base URI
$client = new GuzzleHttp\Client(['base_uri' => 'http://jsonplaceholder.typicode.com/']);
// Send a request to https://foo.com/api/test
$response = $client->request('GET', '/posts');
// Send a request to https://foo.com/root
$response = $client->request('GET', '/users');

// Check if a header exists.
if ($response->hasHeader('Content-Length')) {
    echo "It exists";
}

// Get a header from the response.
echo $response->getHeader('Content-Length');

// Get all of the response headers.
foreach ($response->getHeaders() as $name => $values) {
    echo $name . ': ' . implode(', ', $values) . "\r\n";
}

$body = $response->getBody();
// Implicitly cast the body to a string and echo it
echo $body;
// Explicitly cast the body to a string
$stringBody = (string)$body;
// Read 10 bytes from the body
$tenBytes = $body->read(10);
// Read the remaining contents of the body as a string
$remainingBytes = $body->getContents();


echo "FIN!";

