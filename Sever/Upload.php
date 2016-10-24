<?php
/*輸入：
	$_FILES['upload']->檔名
	$_POST['discription'] as Base64String->描述
	$_POST['hash'] as HexString
	$_POST['time'] as IntString
	
	實體位置->Path/真檔名
	代碼->亂數產生,確認無重複
	下載次數->0
輸出：
	0:成功
	其他:錯誤
*/
//SHAKE-128("test",256);
session_start();
include_once('libs.php');
require_once('Verify.php');
require_once('DBAccess.php');

if(isset($_POST['init']) && strcmp($_POST['init'],"true")==0)
{
	echo ($_SESSION['time']=time());
	exit;
}

$path='filedata/';
$rand_try_max=0xFF; //亂數嘗試限制

//傳入參數驗證
if(!isset($_FILES['upload']))
	exit("NO FILE");
if(!isset($_POST['cript']))
	exit("NO SCRIPT");
if(!isset($_POST['time']))
	exit("NO TIME");
if(!isset($_POST['hash']))
	exit("NO HASH");

//echo "pure name:".$_FILES['upload']['name']."<br>";

$UF=$_FILES['upload']['name'];
$DC=$_POST['cript'];
$TS=(int)$_POST['time'];
$HS=$_POST['hash'];
//echo $UF." ".$DC." ".$TS." ".$HS."<br>";

//檔案檢查
$fs=&$_FILES['upload'];
if($fs['error']>0)
	exit("file error coed ".$fs['error']);

//合法性驗證

$shahash=strtoupper(hash_file('sha256',$fs['tmp_name']));
$str=mb_convert_encoding($UF.$shahash,"UTF-16LE","UTF-8");
$A=array();
$A_N=StringToBytes($str,$A);//檔名->Bytes

$vfy=VerifyDTP($A,$A_N,$TS,$HS);
if($vfy==-1)
	exit("ProgErr!");
else if($vfy==1)
	exit("Timestamp Error!");
else if($vfy==2)
	exit("Verify Error!");

//取得不重複檔名$RF
$RF=$path.base_convert($TS,10,16).base_convert(rand(0,0xFFFF),10,16);
while(is_file($RF))
	$RF=$RF.base_convert(rand(0,0xFFFF),10,16);

//取得CodeTable表格
$DBA=new DBAccess;
if($DBA->open("reference.txt","content.txt","r+")==false)
	exit("DBA_OPEN_ERR");

$CodeTable=0;
$N=$DBA->getCol($CodeTable,3);
if($N<0)
	exit("PROG_ERR");
for($i=0;$i<$N && ord($CodeTable[$i])!=0x2A;++$i);
$writePos=$i==$N?-1:$i;
//產生不重複的code
$code="";
$count=0;
$B64="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
do{
	$code="";
	for($i=0;$i<8;++$i)
		$code=$code.substr($B64,rand(0,63),1);
	//echo "#$count code=$code<br>";
	for($i=0;$i<$N && strcmp($code,$CodeTable[$i])!=0;++$i);
}while($i<$N && $count++ <$rand_try_max);
//echo "code=$code<br>";
if($i<$N)
	exit("can't generate code");

//寫入檔案
move_uploaded_file($fs['tmp_name'],$RF);

//增加一筆資料到資料庫中
$str=mb_convert_encoding($UF,"UTF-16LE","UTF-8");
$UF=StringToBase64($str);//這邊的str是之前轉成UTF-16LE的$UF
$str=mb_convert_encoding($RF,"UTF-16LE","UTF-8");
$RF=StringToBase64($str);
$A=array($UF,$DC,$RF,$code,sprintf("%08d",0));
$K=$DBA->writeRow($A,$writePos);
/*if($K<count($A))
{
	echo "writeRow=$K with row=$writePos<br>";
	for($i=0;$i<count($A);++$i)
		echo "[".$A[$i]."]";
	echo "<br>";
}
else
	echo "ok";*/
$DBA->close();
echo "success";
?>

