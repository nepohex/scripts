<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.03.2017
 * Time: 1:36
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');

$debug_mode = 1 ;

use WebSocket\Client;

$client = new Client("wss://dasproxy.eurodns.com/");
$client->send("domainNameList=massivholzdielen-infos.de%0Alacasa-amarilla.es%0Aamazing.clothe%0Abdavisdesigner.com%0Aafrugalteacher.com%0Asfentona.gr%0Asisustusjasepustus.eu%0Acakepoprecipes.org%0Afishtastic.net%0Aignite3sixty.com%0Aallthingsbaking.org%0Agalaxyfec.com%0Aanimalhealthfoundation.net%0Abaldeagleservice.com%0Adreamystitches.com%0Ainspic.net%0Afinehomeideas.tk%0Adqdgnwz.tk%0Awonderwool.it%0Afba.pt%0Adsgnyes.top%0Amaui.cc%0Astringer.az%0Alinnea-keittiot.fi%0Amyrtlebeachhotels.sc%0Aaiindy.com%0Amommymccrafty.com%0Aphormasrl.it%0Atiddlerandfox.com%0Aaughwickfurniture.com%0Abluering.co%0Aclultimateflagging.com%0Awholefoodsonabudget.com%0Aantispamsoftwaremac.info%0Alabellehomestaging.com%0Adinneratourplace365.com%0Acatherinethomsvintage.com%0Aeaglelinegear.com%0Amoffatmn.com%0Ajabadoo.nl%0A36hourweigtloss.tk%0Anetworkeliteclub.de%0Aproductcorpeagle11store.com%0Abakonyihirmondo.hu%0Afranchiseserbia.rs%0Atingbo.no%0Aabouthomeequityloans.info%0Apaintnetwork.es%0Agroupsglobal.es%0Abloglovin.comundefined%0A&type=all");

echo $client->receive(); // Will output 'Hello WebSocket.org!'

$ch = curl_init();
$domains = urlencode(implode(',', $res));
$url = 'https://my.eurodns.com/das/searchresult/bulk';
curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
if ($proxy_ip) {
    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_login);
}
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__."/cookie.txt"); // сохранять куки в файл
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__."/cookie.txt");
curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
curl_setopt($ch, CURLOPT_POSTFIELDS, 'domainNameList=massivholzdielen-infos.de%0Alacasa-amarilla.es%0Aamazing.clothe%0Abdavisdesigner.com%0Aafrugalteacher.com%0Asfentona.gr%0Asisustusjasepustus.eu%0Acakepoprecipes.org%0Afishtastic.net%0Aignite3sixty.com%0Aallthingsbaking.org%0Agalaxyfec.com%0Aanimalhealthfoundation.net%0Abaldeagleservice.com%0Adreamystitches.com%0Ainspic.net%0Afinehomeideas.tk%0Adqdgnwz.tk%0Awonderwool.it%0Afba.pt%0Adsgnyes.top%0Amaui.cc%0Astringer.az%0Alinnea-keittiot.fi%0Amyrtlebeachhotels.sc%0Aaiindy.com%0Amommymccrafty.com%0Aphormasrl.it%0Atiddlerandfox.com%0Aaughwickfurniture.com%0Abluering.co%0Aclultimateflagging.com%0Awholefoodsonabudget.com%0Aantispamsoftwaremac.info%0Alabellehomestaging.com%0Adinneratourplace365.com%0Acatherinethomsvintage.com%0Aeaglelinegear.com%0Amoffatmn.com%0Ajabadoo.nl%0A36hourweigtloss.tk%0Anetworkeliteclub.de%0Aproductcorpeagle11store.com%0Abakonyihirmondo.hu%0Afranchiseserbia.rs%0Atingbo.no%0Aabouthomeequityloans.info%0Apaintnetwork.es%0Agroupsglobal.es%0Abloglovin.comundefined%0A&type=all');
$data = curl_exec($ch);
curl_close($ch);
unlink(__DIR__."/cookie.txt");