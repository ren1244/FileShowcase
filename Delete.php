<?php
session_start();
require_once('Verify.php');
require_once('SHA3.php');
require_once('DBAccess.php');
require_once('libs.php');

if(isset($_POST['init']) && strcmp($_POST['init'],"true")==0)
{
	echo ($_SESSION['time']=time());
	exit;
}

//檢查參數
$A=array('id','hash','time');
$A_N=count($A);
for($i=0;$i<$A_N;++$i)
	if(!isset($_POST[$A[$i]]))
	{
		echo 'prameter err:'.$A[$i];
		exit;
	}
//驗證
$str=mb_convert_encoding($_POST['id'],"UTF-16LE","UTF-8");
$A=array();
$A_N=StringToBytes($str,$A);
$t=VerifyDTP($A,$A_N,(int)($_POST['time']),$_POST['hash']);
if($t)
{
	echo "Verify_ERR code $t";
	exit;
}
//開啟資料庫，找尋
$DBA=new DBAccess;
$t=$DBA->open("reference.txt","content.txt","r+");
if($t==false)
	exit('DB_OPEN_ERR');
$A=array();
$A_N=$DBA->getCol($A,3);
if($A_N<0)
{
	$DBA->close();
	exit('DB_READ_ERR');
}
$str=$_POST['id'];
for($i=0;$i<$A_N && strcmp($str,$A[$i])!=0;++$i);
if($i<$A_N)
{
	$n=$DBA->getRow($t,$i);
	$t[3]='********';
	echo "writeRow(t,$i) t=";
	for($j=0;$j<$n;++$j)
		echo $t[$j]." ";
	$tmpRow=0;
	$tmpRowN=$DBA->getRow($tmpRow,$i);
	$t=$DBA->writeRow($t,$i);
	if($t<$n)
		echo "ERR:$t<$n";
	$sever_fname=mb_convert_encoding( Base64ToString($tmpRow[2]),"UTF-8","UTF-16LE");
	if(is_file($sever_fname))
		unlink($sever_fname);
	else
		echo "No $sever_fname";
}
$DBA->close();
?>
