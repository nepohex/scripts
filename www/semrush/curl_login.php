<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.01.2017
 * Time: 18:55
 * exmpl SEMRUSH.COM
 */
// Тест через прокси
    $ch = curl_init();
    $url = 'http://whoer.net';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_PROXY, '31.184.198.58:1033');
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'dostup:6t92ic29c5T');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
//    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
$data = curl_exec($ch);

//О том, что мы авторизовались будем судить по наличию формы logout
function isAuth( $data ){
    return preg_match('#<form[^>]+id="logout"#Usi',$data);
}
$ch = curl_init();
$url = 'https://ru.semrush.com/json_users/login';
curl_setopt($ch, CURLOPT_URL, $url ); // отправляем на
curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // сохранять куки в файл
curl_setopt($ch, CURLOPT_COOKIEFILE,  'cookie.txt');
curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'email'=>'gaun100@gmail.com',
    'password' =>	'46LPQNoR7p*O5KM3',
    'user_agent_hash' =>	'7d78e1d08173d6271ad8f371e14c1244',
    'event_source'	=> 'semrush',
));
$data = curl_exec($ch);
//echo $data;
//curl_close($ch);

//А теперь используем эти куки.
$ch = curl_init();
$url = "https://ru.semrush.com/info/ticlotel.com+(by+organic)?sort=pos_desc";
curl_setopt($ch, CURLOPT_URL, $url ); // отправляем на
curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
curl_setopt($ch, CURLOPT_COOKIEFILE,  'cookie.txt');
$data = curl_exec($ch);
echo $data;
curl_close($ch);