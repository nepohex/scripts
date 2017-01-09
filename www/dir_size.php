<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 25.11.2016
 * Time: 2:50
 */
/**
Credits: BitRepository.com
URL: http://www.bitrepository.com/web-programming/php/calculate-the-size-number-of-files-folders-of-a-directory.html
*/
header('Content-Type: text/html; charset=utf-8');
$start = microtime(true);
class Directory_Calculator {
	var $size_in;
	var $decimals;
	function calculate_whole_directory($directory)
	{
		if ($handle = opendir($directory))
		{
			$size = 0;
			$folders = 0;
			$files = 0;
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					if(is_dir($directory.$file))
					{
						$array = $this->calculate_whole_directory($directory.$file.'/');
						$size += $array['size'];
						$files += $array['files'];
						$folders += $array['folders'];
					}
					else
					{
						$size += filesize($directory.$file);
						$files++;
					}
				}
			}
			closedir($handle);
		}
		$folders++;
		return array('size' => $size, 'files' => $files, 'folders' => $folders);
	}
	function size_calculator($size_in_bytes)
	{
		if($this->size_in == 'B')
		{
			$size = $size_in_bytes;
		}
		elseif($this->size_in == 'KB')
		{
			$size = (($size_in_bytes / 1024));
		}
		elseif($this->size_in == 'MB')
		{
			$size = (($size_in_bytes / 1024) / 1024);
		}
		elseif($this->size_in == 'GB')
		{
			$size = (($size_in_bytes / 1024) / 1024) / 1024;
		}
		$size = round($size, $this->decimals);
		return $size;
	}
	function size($directory)
	{
		$array = $this->calculate_whole_directory($directory);
		$bytes = $array['size'];
		$size = $this->size_calculator($bytes);
		$files = $array['files'];
		$folders = $array['folders'] - 1; // exclude the main folder
		return array('size'    => $size,
		             'files'   => $files,
		             'folders' => $folders);
	}
}

//set_time_limit(100);
$i = 0;
$z = 0;
$dir2 = 'F:/Dumps/downloaded sites/';
$down_sites_dirs = scandir ($dir2);
foreach ( $down_sites_dirs as $item ) {
	if ( is_dir( $dir2 . $item ) && $item != "." && $item != "..") {
		/* Path to Directory - IMPORTANT: with '/' at the end */
		$directory = $dir2 . $item ."/";
		/* Calculate size in: B (Bytes), KB (Kilobytes), MB (Megabytes), GB (Gigabytes) */
		$size_in = 'MB';
		/* Number of decimals to show */
		$decimals       = 2;
		$directory_size = new Directory_Calculator;
		/* Initialize Class */
		$directory_size->size_in  = $size_in;
		$directory_size->decimals = $decimals;
		$array                    = $directory_size->size( $directory ); // return an array with: size, total files & folders
		echo $i." _ The directory <em>" . $directory . "</em> has a size of " . $array['size'] . " " . $size_in . ", " . $array['files'] . " files & " . $array['folders'] . " folders.";
		$time = microtime(true) - $start;
		printf('Скрипт выполнялся %.2F сек.', $time);
		echo "<br>";
		flush();
		if ($array['size'] > 200) {
			$big_dir[$z]['dirname'] = $directory;
			$big_dir[$z]['size'] = $array['size'];
			$big_dir[$z]['files'] = $array['files'];
			$big_dir[$z]['folders'] = $array['folders'];
			$z++;
		}
		$i++;
	}
//	if ($i == 100) {
//		exit;
//	}
}

$res = 'big_dirs.txt';
$srlz_big_dirs = serialize($big_dir);
$fp = fopen($res, 'w+');
fwrite($fp,$srlz_big_dirs);

echo "<br>Результаты сохранили в файл со скриптом, ".$res3;
$time = microtime(true) - $start;
printf('Скрипт выполнялся %.2F сек.', $time);
?>

