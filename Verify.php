<?php
require_once('libs.php');
require_once('SHA3.php');
//must use session start before

function VerifyDTP($DataArr,$N,$StampTime,$Hash)
{
	static $pwd="7E8F9DAFE3C950B73F87994C74A7674C666E0FF775F35D1498437D65049438D0";
	if(!isset($_SESSION['time']))
		return -1;
	$x=$_SESSION['time']-$StampTime;
	if($x<-1 || $x>15)
	{
		echo " tdiff[".$x."] ";
		return 1;
	}
	for($i=0;$i<4;++$i)//時間->Bytes
		PushByte($StampTime>>8*($i&3)&0xFF,$DataArr,$N);
	for($i=0;$i<32;++$i)//pwd->Bytes
		PushByte((int)base_convert(substr($pwd,$i*2,2),16,10),$DataArr,$N);
	echo "<br>A=".ShowBytes($DataArr,$N)."<br>";
	$CalHash=SHAKE128($DataArr,$N,256,0);//get hash
	if(strcmp($Hash,$CalHash)!=0)
	{
		echo " hash[$CalHash] ";
		return 2;
	}
	return 0;
}
?>
