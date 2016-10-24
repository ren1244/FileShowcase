<?php
require_once("libs.php");
require_once("DBAccess.php");

$DBA=new DBAccess;
$DBA->open("reference.txt","content.txt","r");
$N=$DBA->getRowCount();
for($i=0;$i<$N;++$i)
{
	$RowArr=0;
	$M=$DBA->getRow($RowArr,$i);
	if($M!=5)
		exit("err_1");
	if(ord($RowArr[3])==0x2A)
		continue;
	$server_name=mb_convert_encoding(Base64ToString($RowArr[2]),"UTF-8","UTF-16LE");
	if(!is_file($server_name))
		continue;
	$fsize=filesize($server_name);
	$fdate=filemtime($server_name);
	echo $RowArr[0].".".$fsize.".".$fdate.".".$RowArr[4].".".$RowArr[1].".".$RowArr[3]."!";
}
$DBA->close();
?>
