<?php
	require_once dirname(__FILE__).'/bmp2gd.php';
	
	$image = BMP2GD::createFromBMP("test.bmp");
	imagepng($image,"test.png");
	imagedestroy($image);
?>