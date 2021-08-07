<?php
namespace library;

class Image 
{
    
    
    /**
	* Изменяем размер изображения
	*
	* @param $pathToFile - путь до файла изображения
	* @param $width и $height - размеры нового изображения
	*
	* @return true
    */
	public function resizeImage($pathToFile = '', $width = '', $height = '')
	{
		// Создаем новое изображение
	   	if (is_file($pathToFile)) {

			// Создадим ресурс FileInfo
			$finfo = new \finfo(FILEINFO_MIME_TYPE);
			// Получим MIME-тип
			$mime = (string) $finfo->file($pathToFile);
			if (strpos($mime, 'image')  === false) die('Внимание! Это НЕ файл изображения!');

			// Возвращает информацию о пути к файлу
			$fileInfo = pathinfo($pathToFile);
			//d($fileInfo); die();
			
			switch ($fileInfo['extension']) {
				case 'jpeg':
				case 'jpg':
					$img = imagecreatefromjpeg($pathToFile);
					$get_image_width = imagesx($img);
					$get_image_height = imagesy($img);
				break;

				case 'webp':
					$img = imagecreatefromwebp($pathToFile);
					$get_image_width = imagesx($img);
					$get_image_height = imagesy($img);
				break;

				case 'png':
					$img = imagecreatefrompng($pathToFile);
					$get_image_width = imagesx($img);
					$get_image_height = imagesy($img);
				break;

				case 'gif':
					$img = imagecreatefromgif($pathToFile);
					$get_image_width = imagesx($img);
					$get_image_height = imagesy($img);
				break;

				default: return;
			}

			//if (empty($width)) $width = ceil($height / ($get_image_height / $get_image_width));
			if (empty($height)) $height = ceil($width / ($get_image_width / $get_image_height));

			$tmp = imagecreatetruecolor($width, $height);
			$bg  = imagecolorallocate($tmp, 255, 255, 255);
			imagefill($tmp, 0, 0, $bg);

			$tw = ceil($height / ($get_image_height / $get_image_width));
			$th = ceil($width / ($get_image_width / $get_image_height));
			
			if ($tw < $width) {
				imageCopyResampled($tmp, $img, ceil(($width - $tw) / 2), 0, 0, 0, $tw, $height, $get_image_width, $get_image_height);        
			} else {
				imageCopyResampled($tmp, $img, 0, ceil(($height - $th) / 2), 0, 0, $width, $th, $get_image_width, $get_image_height);    
			}

			//imagecopyresampled($tmp, $img, 0, 0, 0, 0, $w, $h, $get_image_width, $get_image_height);

			switch ($fileInfo['extension']) {
				case 'jpeg':
				case 'jpg':
					$result = imageJpeg($tmp, $fileInfo['dirname'] . '/' . $fileInfo['basename'], 100);
				break;

				case 'webp':
					$result = imageJpeg($tmp, $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.jpg', 100);
				break;

				case 'png':
					$result = imagePng($tmp, $fileInfo['dirname'] . '/' . $fileInfo['basename'], 4);
				break;

				case 'gif':
					$result = imageGif($tmp, $fileInfo['dirname'] . '/' . $fileInfo['basename']);
				break;
			}
			
			imagedestroy($tmp);

			// Удаляем файлы с расширением webp
			if ($fileInfo['extension'] == 'webp') unlink($pathToFile);
		}
  
	} // End: resizeImage


}