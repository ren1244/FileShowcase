<?php
/*

## B64Str->Bytes->String ##

function Base64ToBytes(&$B64str,&$A,&$N)
	Base64字串轉Byte陣列
function BytesToString(&$A,&$N)
	Byte陣列轉一般字串
function Base64ToString(&$B64Str)
	Base64字串轉一般字串

## String->Bytes->B64Str ##

function StringToBytes(&$str,&$A)
	字串轉Byte陣列
function BytesToBase64(&$A,$N)
	Byte陣列轉Base64
function StringToBase64(&$str)
	字串轉Base64字串

function PushByte($b,&$A,&$N)
	推入一個Byte到A
function B64Idx(&$B64str,$idx)
	取得 B64str[idx] 所對應的整數
function fopenL($fname,$rw,$L,$N,$ms)
	開啟檔案用
function ShowBytes(&$A,$N)
	顯示Byte陣列

*/
function ShowBytes(&$A,$N)
{
	//$str="typeof(A)=".gettype($A)." typeof(N)".gettype($N)." A.length=".count($A)." N=".$N;
	$str="";
	for($i=0;$i<$N;++$i)
	{
		$t=$A[$i>>2]>>8*($i&3)&0xFF;
		$str=$str.($t>15?"":"0").strtoupper(base_convert($t,10,16))." ";
	}
	return $str;
}
function StringToBytes(&$str,&$A)
{
	$len=0;
	$n=strlen($str);
	$i=0;
	for($i=0;$i<$n;++$i)
	{
		/*$uc16=ord(substr($str,$i,1));
		if($i&1)//單數
			$A[$i>>1]|=$uc16<<16;
		else//雙數
			array_push($A,$uc16);*/
		if($i&3)//單數
			$A[$i>>2]|=(ord(substr($str,$i,1))&0xFF)<<8*($i&3);
		else//雙數
			array_push($A,ord(substr($str,$i,1))&0xFF);
	}
	return $n;
}
function StringToBase64(&$str)
{
	$A=array();
	$N=StringToBytes($str,$A);
	return BytesToBase64($A,$N);
}
function Base64ToString(&$B64Str)
{
	$A=array();
	$AN=0;
	Base64ToBytes($B64Str,$A,$AN);
	return BytesToString($A,$AN);
	/*$N=$AN>>1;
	$str="";
	for($i=0;$i<$N;++$i)
	{
		$u16c=($A[$i>>1]>>(($i&1)*16))&0xFFFF;
		//str+=String.fromCharCode();
		$str=$str.chr($u16c>>8).chr($u16c&0xFF);
	}
	//ShowArray($A);
	return mb_convert_encoding($str,"UTF-8","UTF-16");*/
}
function BytesToString(&$A,&$N)
{
	$str="";
	for($i=0;$i<$N;++$i)
		$str.=chr($A[$i>>2]>>8*($i&3)&0xFF);
	return $str;
}
function Base64ToBytes(&$B64str,&$A,&$N)
{
	$SN=strlen($B64str);
	for($i=$t=0;$i<$SN;++$i)
	{
		$t=$t<<6|B64Idx($B64str,$i);
		$s=$i&3;
		//echo "i=".$i." t=".$t." s=".$s."<br>";
		if($s>0)
		{
			//echo "push ".($t>>6-$s*2)."<br>";
			PushByte($t>>6-$s*2,$A,$N);
			$t=$t&0x3F>>$s*2;
		}
	}
	if($s<3)
	{
		//echo "push ".($t>>6-$s*2)."<br>";
		PushByte($t<<$s*2+2,$A,$N);
	}
}
function PushByte($b,&$A,&$N)
{
	if(($N&3)==0)
		array_push($A,$b);
	else
		$A[count($A)-1]|=$b<<($N&3)*8;
	++$N;
	//echo $N." ";
	//ShowArray($A);
}
function B64Idx(&$B64str,$idx)
{
	$a=ord(substr($B64str,$idx,1));
	if(0x41<=$a && $a<=0x5A)
		return $a-0x41;
	if(0x61<=$a && $a<=0x7A)
		return $a-0x61+26;
	if(0x30<=$a && $a<=0x39)
		return $a-0x30+52;
	return $a==0x2D?62:($a==0x5F?63:-1);
}
function fopenL($fname,$rw,$L,$N,$ms)
{
	if(($fp=fopen($fname,$rw))==false)
		return false;
	for($i=0;$i<$N && !flock($fp,$L);++$i)
		usleep($ms*1000);
	if($i<$N)
		return $fp;
	fclose($fp);
	return false;
}
function BytesToBase64(&$A,$N)
{
	$B64="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
	$str="";
	for($i=0;$i<$N;++$i)
	{
		$b=$A[$i>>2]>>($i&3)*8&0xFF;
		$s=$i%3*2;
		$t=$t<<6-$s&0xFFFFFFFF|$b>>$s+2&0x3F>>$s;
		$str.=substr($B64,$t,1);
		$t=$b&(0x3F>>4-$s);
		if($s==4)
		{
			$t=$b&0x3F;
			$str.=substr($B64,$t,1);
			$t=0;
		}
	}
	if($s<4)
		$str.=substr($B64,$t<<4-$s&0xFFFFFFFF,1);
	return $str;
}
?>
