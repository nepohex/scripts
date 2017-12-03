<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.11.2017
 * Time: 21:41
 * Функции для работы с DiDom
 */
use DiDom\Document;

/** Подавать на вход DiDomDocument с полным HTML страницы.
 * @param $DiDomDocument
 * @return GuzzleHttp\Client
 */
function dom_get_article_data($DiDomDocument)
{
    $elements = array('title' => 'title', 'h1' => 'h1', 'description' => 'meta[name=description]', 'keywords' => 'meta[name=keywords]');
    foreach ($elements as $key => $find) {
        if ($item = $DiDomDocument->first($find)) {
            if (in_array($key, array('description', 'keywords'))) {
                $result[$key] = $item->getAttribute('content');
            } else {
                $result[$key] = $item->text();
            }
        }
    }
    return $result;
}

/** Подавать на вход DiDom Document, уже сам элемент с контентом, а не весь HTML страницы.
 * @param $DiDomDocument
 * @param array $attributes
 * @return array|bool
 */
function dom_get_img_urls($DiDomDocument, array $attributes)
{
    if ($DiDomDocument->find('img')) {
        foreach ($DiDomDocument->find('img') as $img) {
            $imgs[] = $img->attributes($attributes);
        }
        if (is_array($imgs)) {
            foreach ($imgs as $img_sizes) {
                foreach ($img_sizes as $img) {
                    if ($img !== '') {
                        //Этот фрагмент если картинки абсолютные пути имеют, иначе добавлена не будет
                        //Нужно когда получаем у картинок сразу несколько атрибутов и там может быть что-то левое
//                        $tmp = explode(' ', $img);
//                        foreach ($tmp as $tmp2) {
//                            if (filter_var($tmp2, FILTER_VALIDATE_URL)) {
//                                $img_urls[] = $tmp2;
//                            }
//                        }
                        //Альтернативная вставка, временно
                        $img_urls[] = $img;
                    }
                }
            }
            return array_unique($img_urls);
        }
    }
    return FALSE;
}

/** Подавать на вход DiDomDocument, элемент с контентом страницы (не весь html).
 * @param $DiDomDocument
 * @param array $bad_elements
 * @return mixed
 */
function dom_cleanup_document($DiDomDocument, array $bad_elements = array('script', 'iframe', 'ins.adsbygoogle', 'div[id*=yandex]', 'div[id*=venus]', 'div[id*=SC_TBlock]', 'div[id*=rating]', 'div[id*=LC_Teaser]', '#law_changes'))
{
    foreach ($bad_elements as $selector) {
        foreach ($DiDomDocument->find($selector) as $del_elem) {
            $del_elem->remove();
        }
    }
    return $DiDomDocument;
}

/** Возвращает список ссылок в переданном DiDomDocument. Можно оставить только локальные и относительные ссылки, удалить или осавить ссылки на картинки.
 * @param $content
 * @param bool $document DiDomDocument
 * @param bool $only_local_links
 * @param $url_domain URL сайта который считать локальным
 * @param bool $no_imgs
 * @param array $bad_values
 * @return array $arr
 */
function dom_get_unique_hrefs($content, $document = FALSE, $only_local_links = FALSE, $url_domain, $no_imgs = TRUE, $bad_values = array('#', 'many', 'twitter', 'stumbl', 'digg.com', 'del.icio', 'facebook', 'linkedin.com'))
{
    if (empty($document)) {
        global $document;
    }
    $document->loadHtml($content);
    foreach ($document->find('a') as $a_elem) {
        $items[] = $a_elem->getAttribute('href');
    }
    if (is_array($items)) {
        $arr = array_unique($items);
        //Оставляем урлы которые принадлежат только этому домену или локальные урлы.
        if ($only_local_links) {
            foreach ($arr as $key => $item) {
                if (is_abs_url($item) == TRUE) {
                    if (!strstr($item, parse_url($url_domain, PHP_URL_HOST))) {
                        unset ($arr[$key]);
                    }
                }
            }
        }
        //Удаляем ссылки также и на картинки.
        if ($no_imgs) {
            $bad_values = array_merge($bad_values, array('.jpg', '.png', '.gif', '.jpeg'));
        }
        foreach ($arr as $key => $haystack) {
            foreach ($bad_values as $needle) {
                if (strstr($haystack, $needle) || $haystack == FALSE) {
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
    } else {
        return FALSE;
    }
}