<?php
/**
@package bmp2gd
@version 1.0
@date 2010-01-06

@author Mehmet Emin Akyuz
@email me@mehmet-emin.gen.tr
*/

require_once dirname(__FILE__).'/bitops.php';

//2012-03-31 update by conzi  ,为支持数据流传入,做了一些调整
class Bmp2gd {
	static $compressionTypes=array(0=>"RGB",1=>"RLE8",2=>"RLE4",3=>"BITFIELD",4=>"JPEG",5=>"PNG");
	
	/**
		@param $file string	filename to be converted to gd
		@return false | gdResource
	*/
	static function createFromBMP($file)
	{		
		
		$size=strlen($file);

		$offset = 0;
		
		$header=substr($file,$offset,14); 
		$offset +=14;

		$header=unpack('C2type/Lsize/vreservedA/vreservedB/Loffset',$header);


		if($header['size']!=$size){
			//return false;
			$size = $header['size'];
		}
		
		$dibHeaderSize=unpack('LdibSize',substr($file,$offset,4));
		$offset +=4;
		$dibHeaderSize=$dibHeaderSize['dibSize'];

		$dibHeader=substr($file,$offset,$dibHeaderSize-4); 
		$offset +=strlen($dibHeader);

		if($dibHeaderSize==12){
			$dibHeader=unpack("lwidth/lheight/vplaneCount/vbpp",$dibHeader);
			$dibHeader['compressType']=0;
		} else if($dibHeaderSize==40){
			$dibHeader=unpack("lwidth/lheight/vplaneCount/vbpp/LcompressType/Lbytes/lhorizontalResolution/lverticalResolution/LcolorCount/LimportantColorCount",$dibHeader);
		} else if($dibHeaderSize==56){
			$dibHeader=unpack("lwidth/lheight/vplaneCount/vbpp/LcompressType/Lbytes/lhorizontalResolution/lverticalResolution/LcolorCount/LimportantColorCount",$dibHeader);
		} else if($dibHeaderSize==108){
			$dibHeader=unpack("lwidth/lheight/vplaneCount/vbpp/LcompressType/Lbytes/lhorizontalResolution/lverticalResolution/LcolorCount/LimportantColorCount",$dibHeader);
		} else if($dibHeaderSize==124){
			$dibHeader=unpack("lwidth/lheight/vplaneCount/vbpp/LcompressType/Lbytes/lhorizontalResolution/lverticalResolution/LcolorCount/LimportantColorCount",$dibHeader);
		}

		if(BMP2GD::$compressionTypes[$dibHeader['compressType']]!="RGB"){ // only non compressed format is supported
			return false;
		}

		$rowbytes= floor(($dibHeader['width'] * $dibHeader['bpp'] - 1) / 32) * 4 + 4;
		

		$img=imagecreatetruecolor($dibHeader['width'],abs($dibHeader['height']));
		$white=imagecolorallocate($img,0xff,0xff,0xff);

		if($dibHeader['bpp']==32){
			if($dibHeader['height']>0){ // for bottom to top bitmaps
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cblue/Cgreen/Cred/',substr($file,$offset,4));
						$offset +=4;
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
				}
			} else { // for top to bottom bitmaps
				for($y=0;$y<$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 4));
						$offset +=4;

						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
				}
			}
		} else if($dibHeader['bpp']==24){
			if($dibHeader['height']>0){
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 3));
						$offset += 3;
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
				}
			} else {
			//	echo 'get color';
				for($y=0;$y< -$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 3));
						//print_r($color);
						$offset += 3;
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
				}
			}
		} else if($dibHeader['bpp']==16){ // not fully supported but windows paint is not using it anyway
			if($dibHeader['height']>0){
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$rgb=unpack('vrgb/',substr($file, $offset , 2));
						$offset += 2;
						$rgb=$rgb['rgb'];
						$color=array(
									 'red'=>getBitsetInt($rgb,5,10)<<3,
									 'green'=>getBitsetInt($rgb,5,5)<<3,
									 'blue'=>getBitsetInt($rgb,5,0)<<3);
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
				}
			} else {
				for($y=1;$y<= -$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$rgb=unpack('vrgb/',substr($file, $offset , 2));
						$offset += 2;
						$rgb=$rgb['rgb']<<3;
						$color=array(
									 'red'=>getBitsetInt($rgb,5,10)<<3,
									 'green'=>getBitsetInt($rgb,5,5)<<3,
									 'blue'=>getBitsetInt($rgb,5,0)<<3);
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
				}
			}
		} else if($dibHeader['bpp']==8){
			$colorIndex=array();
			
			for($i=0;$i<256;++$i){
				$colorIndex[]=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 4));
						$offset += 4;
			}
			
			if($dibHeader['height']>0){
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cindex/',substr($file, $offset , 1));
						$offset += 1;
						$color=$colorIndex[$color['index']];
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width']*8 - 1)/8);
					if($skip){
						$offset += $skip;
					}
				}
			} else {
				for($y=0;$y< -$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cindex/',substr($file, $offset , 1));
						$offset += 1;
						$color=$colorIndex[$color['index']];
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width']*8 - 1)/8);
					if($skip){
						$offset += $skip;
					}
				}
			}
		} else if($dibHeader['bpp']==4){
			$colorIndex=array();
			
			for($i=0;$i<16;++$i){
				$colorIndex[]=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 4));
						$offset += 4;
			}
			
			$f4=true;
			if($dibHeader['height']>0){
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						if($f4){
							$index=unpack('Cindex/',substr($file, $offset , 1));
						$offset += 1;
							$f4=false;
							$index=$index['index'];
							$uindex=getBitsetInt($index,4,4);
							$color=$colorIndex[$uindex];
						} else {
							$f4=true;
							$uindex=getBitsetInt($index,4,0);
							$color=$colorIndex[$uindex];
						}
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width']*4 - 1)/8);
					if($skip){
						$offset += $skip;
					}
				}
			} else {
				for($y=0;$y<-$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						$color=unpack('Cindex/',substr($file, $offset , 1));
						$offset += 1;
						$color=$colorIndex[$color['index']];
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width']*4 - 1)/8);
					if($skip){
						$offset +=$skip;
					}
				}
			}
		} else if($dibHeader['bpp']==1){
			$colorIndex=array();
			
			for($i=0;$i<2;++$i){
				$colorIndex[]=unpack('Cblue/Cgreen/Cred/',substr($file, $offset , 4));
						$offset += 4;
			}
			
			$f8=0;
			if($dibHeader['height']>0){
				for($y=1;$y<=$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						if($f8==0){
							$index=unpack('Cindex/',substr($file, $offset , 1));
							$offset += 1;
							$index=$index['index'];
							$uindex=getBit($index,$f8);
							$color=$colorIndex[$uindex?1:0];
						} else {
							$uindex=getBit($index,$f8);
							$color=$colorIndex[$uindex?1:0];
						}
						
						++$f8;
						$f8%=8;
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$dibHeader['height']-$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width'] - 1)/8);
					if($skip){
						//fread($f, $skip);
						$offset +=$skip;
					}
				}
			} else {
				for($y=0;$y<-$dibHeader['height'];++$y){
					for($x=0;$x<$dibHeader['width'];++$x){
						if($f8==0){
							$index=unpack('Cindex/',substr($file, $offset , 1));
							$offset += 1;
							$index=$index['index'];
							$uindex=getBit($index,$f8);
							$color=$colorIndex[$uindex?1:0];
						} else {
							$uindex=getBit($index,$f8);
							$color=$colorIndex[$uindex?1:0];
						}
						
						++$f8;
						$f8%=8;
						$color=imagecolorallocate($img,$color['red'],$color['green'],$color['blue']);
						imagesetpixel($img,$x,$y,$color);
					}
					$skip = $rowbytes -1 - floor(($dibHeader['width'] - 1)/8);
					if($skip){
						$offset +=$skip;
					}
				}
			}
		} else {
			return false;
		}

		return $img;
	}
}
?>