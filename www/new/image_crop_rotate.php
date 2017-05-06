<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.04.2017
 * Time: 22:35
 */
//Угол поворота, важно в минусовом значении.
$degrees = -3;
$filename = 'scr.jpg';
$filename2 = 'scr_new.jpg';
$filename3 = 'scr_new3.jpg';

function rotate_and_crop ($filename, $filename_new, $degrees = -3) {
    //find the original image size
    $image_info = getimagesize($filename);
    $original_width = $image_info[0];
    $original_height = $image_info[1];

    $image_source = imagecreatefromjpeg($filename);

//rotate
    $rotate = imagerotate($image_source, $degrees, 0);
    $rotated_width = imagesx($rotate);
    $rotated_height = imagesy($rotate);

//Координаты смещения относительно x - y
    $x_pos = $original_width - $rotated_width;
    $y_pos = $original_height - $rotated_height;

//Новые размеры изображения
    $new_width = $original_width - ($rotated_width - $original_width);
    $new_height = $original_height - ($rotated_height - $original_height);

    $new_image = imagecreatetruecolor($new_width, $new_height);

    imagecopyresampled($new_image, $rotate, $x_pos, $y_pos, 0, 0, $rotated_width, $rotated_height, $rotated_width, $rotated_height);

//save over the new image.
    imagejpeg($new_image, $filename_new);
}

//find the original image size
$image_info = getimagesize($filename);
$original_width = $image_info[0];
$original_height = $image_info[1];

$image_source = imagecreatefromjpeg($filename);

//rotate
$rotate = imagerotate($image_source, $degrees, 0);
$rotated_width = imagesx($rotate);
$rotated_height = imagesy($rotate);

//Координаты смещения относительно x - y
$x_pos = $original_width - $rotated_width;
$y_pos = $original_height - $rotated_height;

//Новые размеры изображения
$new_width = $original_width - ($rotated_width - $original_width);
$new_height = $original_height - ($rotated_height - $original_height);

$new_image = imagecreatetruecolor($new_width, $new_height);

imagecopyresampled($new_image, $rotate, $x_pos, $y_pos, 0, 0, $rotated_width, $rotated_height, $rotated_width, $rotated_height);

//save over the new image.
imagejpeg($new_image, $filename3);