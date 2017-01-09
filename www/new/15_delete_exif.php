<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.12.2016
 * Time: 3:17
 */
$dir = 'f:\Dumps\rockhairstyles2.us\img\\';
$images = scandir($dir);
foreach ($images as $image) {
    if ( is_file( $dir . $image )) {
        $img = new Imagick($dir.$image);
        $profiles = $img->getImageProfiles("icc", true);
        if ($profiles) {
            echo "Есть ICC <br>"; flush();
            unset ($profiles);
        }
    }
}
//$path = 'F:\Dumps\rockhairstyles2.us\wp-content\uploads\2016\12\\';
//$image = 'F:\Dumps\rockhairstyles2.us\wp-content\uploads\2016\12\579_short_hairstyles_for_men_with_glasses_male_short_haircuts_style.jpg';
//$new_img_name = "_new2_".basename($image);
//$img = new Imagick($image);
//$profiles = $img->getImageProfiles("icc", true);
//print_r($profiles);
//$img->stripImage();
//if(!empty($profiles)) {
//    $img->profileImage("icc", $profiles['icc']);
//    echo "ICC was here! Added!";
//}
//$img ->contrastImage ($image);
//    $img->writeImage($path.$new_img_name);
//echo "image written! $path.$new_img_name";
?>