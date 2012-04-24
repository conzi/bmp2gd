<?php
function getBit($bytes,$n)
{
	$n=1<<$n;
	return $bytes & $n;
}

function setBit($bytes,$n)
{
	$n=1<<$n;
	return $bytes | $n;
}

function printBits($bytes,$l=8,$d=0)
{
	$b='';
	for($i=$l-1;$i>-1;--$i){
		if($i!=0 && $d!=0 && $i%$d==0)$b.=' ';
		if(getBit($bytes,$i))$b.='1';
		else $b.='0';
	}
	return $b;
}

function printBitsR($bytes,$l=8,$d=0)
{
	$b='';
	for($i=0;$i<$l;++$i){
		if($i!=0 && $d!=0 && $i%$d==0)$b.=' ';
		if(getBit($bytes,$i))$b.='1';
		else $b.='0';
	}
	return $b;
}

function getBitsetInt($bytes,$bitCount,$n){
	$x=0;
	$y=$bytes;
	for($i=0;$i<$bitCount;++$i){
		$x|= (1 << $i);
	}
	
	for($i=0;$i<$n;$i++){
		$y=$y >> 1;
	}
	
	return $x & $y;
}
?>