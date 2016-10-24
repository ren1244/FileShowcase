<?php
require_once('libs.php');
require_once('SHA3.php');
//must use session start before

function VerifyDTP($DataArr,$N,$StampTime,$Hash)
{
	//這是密碼產生的雜湊字串，請將 first_use.html 產生的字串取代這邊 7F9C...EF26 的區域
	//(若沒更改，密碼就是空字串，也就是不用輸入)
	static $pwd="7F9C2BA4E88F827D616045507605853ED73B8093F6EFBC88EB1A6EACFA66EF26";
	//=====================================
	if(!isset($_SESSION['time']))
		return -1;
	$x=$StampTime-$_SESSION['time'];
	if($x<-1 || $x>15)
	{
		echo " tdiff[".$x."] ";
		return 1;
	}
	for($i=0;$i<4;++$i)//時間->Bytes
		PushByte($StampTime>>8*($i&3)&0xFF,$DataArr,$N);
	for($i=0;$i<32;++$i)//pwd->Bytes
		PushByte((int)base_convert(substr($pwd,$i*2,2),16,10),$DataArr,$N);
	//echo "<br>A=".ShowBytes($DataArr,$N)."<br>";
	$CalHash=SHAKE128($DataArr,$N,256,0);//get hash
	if(strcmp($Hash,$CalHash)!=0)
	{
		echo " hash[$CalHash] ";
		return 2;
	}
	return 0;
}
?>
