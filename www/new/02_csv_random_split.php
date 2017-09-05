<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.11.2016
 * Time: 19:17
 * Будем делить файл с картинками на количество картинок необходимых для 1 сайта.
 * Фильтрация по длине картинок, функционал разделения на использованные-не использованные картинки ранее.
 */
include "multiconf.php";
next_script(0, 1);

if ($google_images_mode == TRUE) {
    $fname_used = $selects_dir . '/' . $keyword . "_google_images_used.csv";
    $tmpres = str_replace(".csv", "", $big_res_to_split) . "_" . $images_per_site . "_google_rand_lines.csv";
//$take_only_unused_images = true; //debug
} else {
    $fname_used = $selects_dir . '/' . $keyword . "_images_used.csv";
    $tmpres = str_replace(".csv", "", $big_res_to_split) . "_" . $images_per_site . "_rand_lines.csv";
}

if ($take_only_unused_images && is_file($fname_used) == false) {
    $fp = fopen($selects_dir . '/' . $big_res_to_split, "r");
    while ($line = fgets($fp, 6096)) {
        $tmp = explode(";", $line);
        $ftell[] = ftell($fp);
    }
    shuffle($ftell);
    echo2("Загрузили в массив данные о концах строк файла " . $selects_dir . '/' . $big_res_to_split . " в котором " . count($ftell) . " строк с данными о картинках, будем выбирать из него случайные " . $images_per_site);

    $fp2 = fopen($import_dir . $tmpres, "w");
    $i = 0;
    $z = 0;
    foreach ($ftell as $id) {
        fseek($fp, $id);
        $rand_string = fgets($fp, 6096);
        $tmp = explode(";", $rand_string);
        $strlen = strlen($tmp[count($tmp) - 2]); // $tmp[count($tmp)-2] - здесь уже без JPG и хлама, название картинки, например "short hairstyles for round faces and thin fine hair"
        if ($strlen < $image_title_max_strlen && $strlen > $image_title_min_strlen) {
            if ($only_uniq_img == true) {
                if (!(in_array($tmp[4], $tmp_uniq_arr))) {
                    $tmp_uniq_arr[] = $tmp[count($tmp) - 2];
                    fputs($fp2, $rand_string);
                    $used_lines[] = $id;
                    $i++;
                } else {
                    $unused_lines[] = $id;
                }
            } else {
                fputs($fp2, $rand_string);
                $used_lines[] = $id;
                $i++;
            }
        } else {
            $unused_lines[] = $id;
        }
//        if ($i > 0 && $i % 1000 == 0) {
//            echo_time_wasted($i);
//        }
        if ($i == $images_per_site) {
            break;
        }
        $z++;
    }
    fclose($fp2);

//Пишем использованные картинки и не использованные в отдельные файлы
    if ($write_used_images == true && count($used_lines) > 10) {
        $fp_used = fopen($selects_dir . '/' . $keyword . "_images_used.csv", "a+");
        foreach ($used_lines as $used_id) {
            fseek($fp, $used_id);
            fputs($fp_used, fgets($fp, 6096));
        }
        fclose($fp_used);
        rewind($fp);

        $fp_unused = fopen($selects_dir . '/' . $keyword . "_images_unused.csv", "a+");
        arsort($ftell);
        arsort($used_lines);
        foreach ($ftell as $unused_id) {
            foreach ($used_lines as $used_id) {
                if ($used_id == $unused_id) {
                    $go_break = 1;
                    break;
                }
            }
            if ($go_break == 1) {
                unset($go_break);
            } else {
                fseek($fp, $unused_id);
                fputs($fp_unused, fgets($fp, 6096));
            }
        }
        fclose($fp_unused);

        fclose($fp);
    } else {
        fclose($fp);
    }
} else if ($take_only_unused_images == false) {
    $fp = fopen($selects_dir . '/' . $big_res_to_split, "r");
    while ($line = fgets($fp, 6096)) {
        $tmp = explode(";", $line);
        $ftell[] = ftell($fp);
    }
    shuffle($ftell);
    echo2("Загрузили в массив данные о концах строк файла " . $selects_dir . '/' . $big_res_to_split . " в котором " . count($ftell) . " строк с данными о картинках, будем выбирать из него случайные " . $images_per_site);

    $fp2 = fopen($import_dir . $tmpres, "w");
    $i = 0;
    $z = 0;
    foreach ($ftell as $id) {
        fseek($fp, $id);
        $rand_string = fgets($fp, 6096);
        $tmp = explode(";", $rand_string);
        $strlen = strlen($tmp[count($tmp) - 2]); // $tmp[count($tmp)-2] - здесь уже без JPG и хлама, название картинки, например "short hairstyles for round faces and thin fine hair"
        if ($strlen < $image_title_max_strlen && $strlen > $image_title_min_strlen) {
            if ($only_uniq_img == true) {
                if (!(in_array($tmp[4], $tmp_uniq_arr))) {
                    $tmp_uniq_arr[] = $tmp[count($tmp) - 2];
                    fputs($fp2, $rand_string);
                    $used_lines[] = $id;
                    $i++;
                } else {
                    $unused_lines[] = $id;
                }
            } else {
                fputs($fp2, $rand_string);
                $used_lines[] = $id;
                $i++;
            }
        } else {
            $unused_lines[] = $id;
        }
//        if ($i > 0 && $i % 1000 == 0) {
//            echo_time_wasted($i);
//        }
        if ($i == $images_per_site) {
            break;
        }
        $z++;
    }
    fclose($fp2);

//Пишем использованные картинки и не использованные в отдельные файлы
    if ($write_used_images == true && count($used_lines) > 10) {
        $fp_used = fopen($selects_dir . '/' . $keyword . "_images_used.csv", "a+");
        foreach ($used_lines as $used_id) {
            fseek($fp, $used_id);
            fputs($fp_used, fgets($fp, 6096));
        }
        fclose($fp_used);
        rewind($fp);

        arsort($ftell);
        arsort($used_lines);
        foreach ($ftell as $unused_id) {
            foreach ($used_lines as $used_id) {
                if ($used_id == $unused_id) {
                    $go_break = 1;
                    break;
                }
            }
            if ($go_break == 1) {
                unset($go_break);
            } else {
                fseek($fp, $unused_id);
                fputs($fp_unused, fgets($fp, 6096));
            }
        }
        fclose($fp_unused);

    } else {
        fclose($fp);
    }
} else if ($take_only_unused_images == true && is_file($fname_used) == true) {
    $fp = fopen($selects_dir . '/' . $keyword . "_images_unused.csv", "a+");
    while ($line = fgets($fp, 6096)) {
        $tmp = explode(";", $line);
        $ftell[] = ftell($fp);
    }
    shuffle($ftell);
    echo2("Активна функция добивки ниши по неисользованным картинкам take_only_unused_images и есть файл с записями о свободных картинках - будем исползовать только их $selects_dir . '/' . $keyword . _images_used.csv в котором " . count($ftell) . " строк с данными о картинках, будем выбирать из него случайные " . $images_per_site);

    $fp2 = fopen($import_dir . $tmpres, "w");
    $i = 0;
    $z = 0;
    foreach ($ftell as $id) {
        fseek($fp, $id);
        $rand_string = fgets($fp, 6096);
        $tmp = explode(";", $rand_string);
        $strlen = strlen($tmp[count($tmp) - 2]); // $tmp[count($tmp)-2] - здесь уже без JPG и хлама, название картинки, например "short hairstyles for round faces and thin fine hair"
        if ($strlen < $image_title_max_strlen && $strlen > $image_title_min_strlen) {
            if ($only_uniq_img == true) {
                if (!(in_array($tmp[4], $tmp_uniq_arr))) {
                    $tmp_uniq_arr[] = $tmp[count($tmp) - 2];
                    fputs($fp2, $rand_string);
                    $used_lines[] = $id;
                    $i++;
                } else {
                    $unused_image_ids[] = $tmp[0];
                    $unused_lines[] = $id;
                }
            } else {
                fputs($fp2, $rand_string);
                $used_lines[] = $id;
                $i++;
            }
        } else {
            $unused_image_ids[] = $tmp[0];
            $unused_lines[] = $id;
        }
//        if ($i > 0 && $i % 1000 == 0) {
//            echo_time_wasted($i);
//        }
        if ($i == $images_per_site) {
            break;
        }
        $z++;
    }
    fclose($fp2);

//Пишем использованные картинки и не использованные в отдельные файлы
    if ($write_used_images == true && count($used_lines) > 10) {
        $fp_used = fopen($selects_dir . '/' . $keyword . "_images_used.csv", "a+");
        foreach ($used_lines as $used_id) {
            fseek($fp, $used_id);
            fputs($fp_used, fgets($fp, 6096));
        }
        fclose($fp_used);
        rewind($fp);

        arsort($ftell);
        arsort($used_lines);
        $fp_unused = fopen($selects_dir . '/' . $keyword . "_images_unused2.csv", "a+");
        foreach ($ftell as $unused_id) {
            foreach ($used_lines as $used_id) {
                if ($used_id == $unused_id) {
                    $go_break = 1;
                    break;
                }
            }
            if ($go_break == 1) {
                unset($go_break);
            } else {
                fseek($fp, $unused_id);
                fputs($fp_unused, fgets($fp, 6096));
            }
        }
        fclose($fp_unused);
        fclose($fp);
        //Замещаем старый файл unused новым unused.
        rename($selects_dir . '/' . $keyword . "_images_unused2.csv", $selects_dir . '/' . $keyword . "_images_unused.csv");
    } else {
        fclose($fp);
    }
}

//Google Images mode
//if ($take_only_unused_images && is_file($fname_used) == false) {
//
//    //Пишем использованные картинки и не использованные в отдельные файлы
//    if ($write_used_images == true && count($used_lines) > 10) {
//
//    } else {
//        fclose($fp);
//    }
//} else if ($take_only_unused_images == false) {
//
//    //Пишем использованные картинки и не использованные в отдельные файлы
//    if ($write_used_images == true && count($used_lines) > 10) {
//
//    } else {
//        fclose($fp);
//    }
//} else if ($take_only_unused_images == true && is_file($fname_used) == true) {
//
//    //Пишем использованные картинки и не использованные в отдельные файлы
//    if ($write_used_images == true && count($used_lines) > 10) {
//
//    } else {
//        fclose($fp);
//    }
//}


echo2("Создали новый рабочий файл с картинками под названием " . $import_dir . $tmpres);
echo2("Максимальная длина названия картинки установлена в  " . $image_title_max_strlen);
echo2("Картинок в переборе было " . $z . " чтобы набрать  " . $images_per_site . " Набрали всего $i картинок.");
next_script();