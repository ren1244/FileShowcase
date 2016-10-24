<?php
require_once("libs.php");
require_once("DBAccess.php");
if(isset($_GET['id']) && isset($_GET['time']))
{
	$DBA=new DBAccess;
	$DBA->open("reference.txt","content.txt","r");
	$N=$DBA->getCol($Arr,3);
	$id=$_GET['id'];
	for($i=0;$i<$N && $Arr[$i]!=$id;++$i);
	if($i<$N)
	{
		$DBA->getCol($Arr,2);
		$fname=Base64ToString($Arr[$i]);
		$fname=str_replace("\0","",$fname);
		
		if(!file_exists($fname))
			echo "$fname 無檔案";
		else
			echo $fname."有";
		echo touch($fname,$_GET['time'])?"成功":"失敗";
	}
	else
		echo "id錯誤";
	$DBA->close();
}
else
	echo "參數錯誤";
?>
