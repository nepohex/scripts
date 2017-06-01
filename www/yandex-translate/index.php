<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.05.2017
 * Time: 23:30
 */
require '../../vendor/autoload.php';
use Beeyev\YaTranslate\Translate;

try {
    $tr = new Translate('trnsl.1.1.20170503T103019Z.b160dfdfa5e3b13c.c68030c8a3da1d6056f347b7d4fab95648032016');
    $result = $tr->translate("Hey baby, what are you doing tonight?", 'fr');

    echo $result;                           // Hey bébé, tu fais quoi ce soir?
    echo $result->sourceText();             // Hey baby, what are you doing tonight?
    echo $result->translationDirection();   // en-fr

    var_dump($result->translation());       // array (size=1)
    // 0 => string 'Hey bébé, tu fais quoi ce soir?'
} catch (\Beeyev\YaTranslate\TranslateException $e) {
    //Handle exception
}