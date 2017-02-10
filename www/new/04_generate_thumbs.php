<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 29.11.2016
 * Time: 17:38
 * #2
 */
include "multiconf.php";
next_script (0,1);

function resize_crop_image($max_width, $max_height, $source_file, $dst_dir, $quality = 80) {
    $imgsize = getimagesize($source_file);
    $width = $imgsize[0];
    $height = $imgsize[1];
    $mime = $imgsize['mime'];

    switch ($mime) {
        case 'image/gif':
            $image_create = "imagecreatefromgif";
            $image = "imagegif";
            break;

        case 'image/png':
            $image_create = "imagecreatefrompng";
            $image = "imagepng";
            $quality = 7;
            break;

        case 'image/jpeg':
            $image_create = "imagecreatefromjpeg";
            $image = "imagejpeg";
            $quality = 80;
            break;

        default:
            return false;
            break;
    }

    $dst_img = imagecreatetruecolor($max_width, $max_height);
    ///////////////

    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $max_width, $max_height, $transparent);


    /////////////
    $src_img = $image_create($source_file);

    $width_new = $height * $max_width / $max_height;
    $height_new = $width * $max_height / $max_width;
    //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
    if ($width_new > $width) {
        //cut point by height
        $h_point = (($height - $height_new) / 2);
        //copy image
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        //cut point by width
        $w_point = (($width - $width_new) / 2);
        imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
    }

    $image($dst_img, $dst_dir, $quality);

    if ($dst_img)
        imagedestroy($dst_img);
    if ($src_img)
        imagedestroy($src_img);
}

$img_names = array_slice(scandir($img_dir),2);
$i = 0;
$counter_img = count($img_names);
$counter_crops_exists = 0 ; // Счетчик сколько тумбов уже было создано до прогона скрипта чтобы лишний раз не пересоздавать.
foreach ($img_names as $img_name) {
    $tmp = explode(".",$img_name);
        $cropped_img_name = $tmp[0]."-".$crop_width."x".$crop_height.".".$tmp[1];
    if (is_file($img_dir.$img_name) && !is_file($img_dir.$cropped_img_name)) {
        resize_crop_image($crop_width, $crop_height, $img_dir.$img_name, $img_dir.$cropped_img_name);
    } else {
        $counter_crops_exists++ ;
    }
    if ($i % 500 == 0 ) {
        echo_time_wasted($i);
    }
    $i++;
}
echo2 ("Создали CROP для картинок, из уже было ранее создано в папке ".$counter_crops_exists);
next_script ();