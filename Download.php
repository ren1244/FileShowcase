<?php
require_once("libs.php");
$Setting=array(8,3,6,3,8,8);
$frefName="reference.txt";
$ftxtName="content.txt";
//取得FakeCode
$FakeCode=$_GET['code'];
if(strlen($FakeCode)!=$Setting[4])
	exit("code Error");
//計算ref每行字元數
$N=count($Setting);
$Len=0;
for($i=1;$i<3;++$i)
	$Len+=$Setting[0]+1+$Setting[$i]+1;
$RealPos=$Len;
$Len+=$Setting[0]+1+$Setting[$i]+1;
$FakePos=$Len;
for(++$i;$i<$N;++$i)
	$Len+=$Setting[$i]+1;
$CountPos=$Len-1-$Setting[5];
//echo "(L,R,F)=(".$Len.",".$RealPos.",".$FakePos.")<br>";
//計算共幾筆資料
$N=filesize($frefName); //檔案大小
if($N==false)
	exit("Reference Error");
$N=(int)($N/$Len);
//開啟ref及txt
$fp=fopenL($frefName,"r+",LOCK_EX,10,250);
if($fp==false)
	exit("Reference Error");
$fp2=fopenL($ftxtName,"r",LOCK_SH,10,250);
if($fp2==false)
{
	fclose($fp2);
	exit("Content Error");
}
//尋找FakeCode對應的RealRef
$RealName=0;
$OrgName=0;
for($i=0;$i<$N;++$i)
{
	fseek($fp,$i*$Len+$FakePos,SEEK_SET);
	$rd=fread($fp,$Setting[4]);
	//echo "compare #".$i.":(".$rd.",".$FakeCode.")...";
	if(strcmp($rd,$FakeCode)==0)
	{//找到！
		//echo "ok<br>";
		//取得實體檔案資訊的位置與長度
		fseek($fp,$i*$Len+$RealPos,SEEK_SET);
		$t_str=fread($fp,$Setting[0]+1+$Setting[3]);
		$t_st=(int)base_convert(substr($t_str,0,$Setting[0]),16,10);
		$t_len=(int)base_convert(substr($t_str,$Setting[0]+1),16,10);
		//取得實體檔案
		fseek($fp2,$t_st,SEEK_SET);
		$t_str=fread($fp2,$t_len);
		$RealName=mb_convert_encoding(Base64ToString($t_str),"UTF-8","UTF-16LE");
		//取得原始檔名
		fseek($fp,$i*$Len,SEEK_SET);
		$t_str=fread($fp,$Setting[0]+1+$Setting[1]);
		$t_st=(int)base_convert(substr($t_str,0,$Setting[0]),16,10);
		$t_len=(int)base_convert(substr($t_str,$Setting[0]+1),16,10);
		fseek($fp2,$t_st,SEEK_SET);
		$t_str=fread($fp2,$t_len);
		$OrgName=mb_convert_encoding(Base64ToString($t_str),"UTF-8","UTF-16LE");
		//計數+1
		fseek($fp,$i*$Len+$CountPos,SEEK_SET);
		$count=(int)base_convert(fread($fp,$Setting[5]),16,10);
		$count=base_convert($count+1,10,16);
		$t_len=strlen($count);
		if($t_len>0 && $t_len<=$Setting[5]);
		{
			fseek($fp,$i*$Len+$CountPos+$Setting[5]-$t_len,SEEK_SET);
			fwrite($fp,strtoupper($count));
		}
		break;
	}
	//else
		//echo "not this<br>";
}
//echo "real:".$RealName.' Org:'.$OrgName.'<br>';

fclose($fp);
fclose($fp2);

if($RealName)
{
	header('Content-type:application');//force-download
	//header("Content-Length:".(string)(filesize($RealName)));
	header('Content-Transfer-Encoding: Binary');
	header('Content-Disposition: attachment; filename="'.$OrgName.'"');
	readfile($RealName);
}
/*function Base64ToString(&$B64Str)
{
	$A=array();
	$AN=0;
	Base64ToBytes($B64Str,$A,$AN);
	$N=$AN>>1;
	$str="";
	for($i=0;$i<$N;++$i)
	{
		$u16c=($A[$i>>1]>>(($i&1)*16))&0xFFFF;
		//str+=String.fromCharCode();
		$str=$str.chr($u16c>>8).chr($u16c&0xFF);
	}
	//ShowArray($A);
	return mb_convert_encoding($str,"UTF-8","UTF-16");
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
}*/
?>
