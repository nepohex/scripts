<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 13.08.2017
 * Time: 12:12
 */
include('../new/includes/functions.php');
$fp_log = "log.txt";
$double_log = 1;

$i = new gisearch("kittens on a piano");
echo '<a href="'. $i->get_source() .'"><img src="'. $i->get_link() .'" /> '. $i->get_title() .'</a>';

/* GISearch.php
 * requires DOMDocument
 * requires php7.0-XML
 * -
 * Searches Google and returns a random image for your search string
 * -
 * Example:
 * $i = new gisearch("kittens on a piano");
 * echo '<a href="'. $i->get_source() .'"><img src="'. $i->get_link() .'" /> '. $i->get_title() .'</a>';
 */
class gisearch {
    private $SRC;
    private $LINK;
    private $TITLE;
    // $i = new gisearch(<search string>): searches Google for the image and sets internal variables
    function __construct($SEARCH) {
        // find the image
        $IMAGE = $this->select_image($SEARCH);

        // set up the internal variables
        $this->SRC = $IMAGE['src'];      // source website
        $this->LINK = $IMAGE['link'];    // url of the image
        $this->TITLE = $IMAGE['title'];  // title of the image
    }

    // get_images(<search string>): gets a list of all images from Google Image Search
    function get_images($SEARCH) {
        // set up http options
        $HTTP_OPTS = Array(
            'http' => Array(
                'method' => "GET",
                'header' => "Accept: text/html\r\n".
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36\r\n"
            )
        );
        $HTTP_CONTEXT = stream_context_create($HTTP_OPTS);

        // set up the URL (by default, this searches images of a 'Large' type)
        // &tbs=isz:m -> middle
        // tbs=isz:l -> large
        // https://www.google.ru/search?q=kitty&newwindow=1&tbas=0&tbs=isz:m&tbm=isch&source=lnt&sa=X&ved=0ahUKEwj8oOu6hdXVAhUBYpoKHR3FB10QpwUIHQ&biw=1536&bih=760&dpr=1.25
        $URL = "https://www.google.com.au/search?q=". urlencode($SEARCH) ."&source=lnms&tbm=isch&biw=1440&bih=770";

        // grab the HTML
        $HTML = file_get_contents($URL, false, $HTTP_CONTEXT);

        // create a new DOM Document
        $HTML_DOC = new DOMDocument();

        // start of get rid of errors
        $ERRORS = libxml_use_internal_errors(true);

        // load HTML into DOM Document
        $HTML_DOC->loadHTML($HTML);

        // end of get rid of errors
        libxml_use_internal_errors($ERRORS);

        // find all the <div>'s (from Google Image Search)
        $DIVS = $HTML_DOC->getElementsByTagName('div');

        // create a dummy array (so it doesn't get lost in the loop)
        $IMAGES[] = Array();
        foreach ($DIVS as $DIV) {
            // get the line from div
            $JSON = $DIV->nodeValue;

            // check if the line is actually JSON
            if ((!$this->is_json($JSON)) || (trim($JSON) == '')) {
                continue;
            }

            // convert JSON to Array
            $IMAGE = json_decode($JSON, true);

            // push the detail we need into another Array for use later
            $IMAGES[] = Array(
                'link' => $IMAGE['ou'],
                'title' => $IMAGE['s'],
                'src' => $IMAGE['ru']
            );
        }
        // output the list of images
        return $IMAGES;
    }

    function select_image($SEARCH) {
        // get the images from Google
        $IMAGES = $this->get_images($SEARCH);

        // select a random image
        $IMAGE = $IMAGES[rand(1, sizeof($IMAGES))];

        // return the image
        return $IMAGE;
    }

    function get_source() {
        return $this->SRC;
    }

    function get_link() {
        return $this->LINK;
    }

    function get_title() {
        return $this->TITLE;
    }

    // is_json(<text>): checks if string is JSON or not
    function is_json($TEXT) {
        json_decode($TEXT);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}