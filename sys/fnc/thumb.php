<?php

function resampleImage($path, $new_path, $sizew, $sizeh) {
	if (function_exists('exif_imagetype')) {
		$itype = exif_imagetype($path);
		switch ($itype) {
			case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($path); break;
			case IMAGETYPE_GIF: $img = imagecreatefromgif($path); break;
			case IMAGETYPE_PNG: $img = imagecreatefrompng($path); break;
			case IMAGETYPE_BMP: $img = imagecreatefrombmp($path); break;
			default: return false;
		}
		if(!$img) return false;
	} else if (function_exists('getimagesize')) {
		@$info = getimagesize($path);
		if (!$info || empty($info['mime'])) return false;
		switch ($info['mime']) {
			case 'image/jpeg': $img = imagecreatefromjpeg($path); break;
			case 'image/gif': $img = imagecreatefromgif($path); break;
			case 'image/png': $img = imagecreatefrompng($path); break;
			case 'image/bmp': $img = imagecreatefrombmp($path); break;
			default: return false;
		}
	} else {
		$img = imagecreatefromjpeg($path);
	}
	$w = imagesx($img);
	$h = imagesy($img);
	if ($w / $sizew < ($h / $sizeh)) {
		$nw = intval($w * $sizeh / $h);
		$nh = $sizeh;
	} else {
		$nw = $sizew;
		$nh = intval($h * $sizew / $w);
	}

	$dest = imagecreatetruecolor($nw, $nh);
	imagecopyresampled(
		$dest, $img, 0, 0, 0, 0, $nw, $nh, $w, $h
	);

	imagejpeg($dest, $new_path);
	imagedestroy($img);
	imagedestroy($dest);
	return true;
}

?>