<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.01.2017
 * Time: 20:20
 */
// RDS Api ключ пользователя
$api_key = "327bddc3-8986-4a2b-911e-b08dda098e47";

// XML для инициализации сессии
$put_data =
    '<InitSession>
    <Parameters>
        <TaskVariant>GoogleImage</TaskVariant>
    </Parameters>
    <DomainNames>
        <string>ticlotel.com</string>
    </DomainNames>
    <Refresh>true</Refresh>
</InitSession>';

// Инициализируем запрос для создания сессии
$ch = curl_init("http://recipdonor.com:977/api/session/new?format=xml");
// Передаем API ключ
curl_setopt($ch, CURLOPT_USERPWD, "{$api_key}:x");
// Указываем возврат результата передачи
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// При инициализации сессии используется метод PUT
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
// Указываем необходимые заголовки
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: text/xml; charset=utf-8",
    "Content-Length: ".strlen($put_data)));
// Передаем данные для инициализации
curl_setopt($ch, CURLOPT_POSTFIELDS, $put_data);

// Посылаем запрос
$initSessionData = curl_exec($ch);
$responseInfo = curl_getinfo($ch);
curl_close($ch);

if($responseInfo['http_code'] != 200) exit('Wrong request!');

// Обрабатываем пришедший ответ
$sesssionInitData = new SimpleXMLElement($initSessionData);
// Достаем идентификатор созданой сессии
$sessionId = $sesssionInitData->Id;

$sessionData = null;
do{
    // Небольшая задержка перед получением данных
    sleep(2);
    // Инициализируем запрос для получения данных сессии
    $ch = curl_init("http://recipdonor.com:977/api/session/get/{$sessionId}?format=xml");
    // Передаем API ключ
    curl_setopt($ch, CURLOPT_USERPWD, "{$api_key}:x");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8"));

    // Получаем и обрабатываем пришедший ответ
    $response = curl_exec($ch);
    $responseInfo = curl_getinfo($ch);
    curl_close($ch);

    if($responseInfo['http_code'] != 200) exit('Wrong request!');

    $sessionData = new SimpleXMLElement($response);

    // Повоторяем процедуру пока сессия не будет завершена
}while($sessionData->SessionStatus == 'AtChecking');

// Выводим пришедшие данные
foreach($sessionData->Domains->DomainData as $domain)
{
    // Доменное имя
    echo $domain->DomainName."<br/>";

    foreach($domain->Values->Data as $data)
    {
        echo $data->Parameter."<br/>";
        echo $data->Value."<br/>";
    }
    echo "<br/>";
}