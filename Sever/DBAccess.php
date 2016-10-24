<?php

class DBAccess
{
	private $fp1;
	private $fp2;
	private $lock=LOCK_UN;
	
	static $reflen=8;
	static $rowlen=60;
	static $cfg=array(-3,-6,-3,8,8);
	static $pos=array(0,13,29,42,51);
	
	public function getRow(&$StrArr,$row)
	{
		if($this->lock==LOCK_UN)
			return -1;
		$n=$this->getRowCount();
		$m=$this->getColCount();
		if($row<0 || $row>=$n)
			return -2;
		$StrArr=array();
		fseek($this->fp1,$row*DBAccess::$rowlen,SEEK_SET);
		$str=fread($this->fp1,DBAccess::$rowlen);
		for($i=0;$i<$m;++$i)
		{
			$len=DBAccess::$cfg[$i];
			$st=DBAccess::$pos[$i];
			if($len<0)
			{
				$st2=(int)base_convert(substr($str,$st,DBAccess::$reflen),16,10);
				$len2=(int)base_convert(substr($str,$st+DBAccess::$reflen+1,-$len),16,10);
				fseek($this->fp2,$st2,SEEK_SET);
				array_push($StrArr,$len2>0?fread($this->fp2,$len2):"");
			}
			else
				array_push($StrArr,substr($str,$st,$len));
		}
		return $m;
	}
	
	public function getRowCount()
	{
		fseek($this->fp1,0,SEEK_END);
		return (int)(ftell($this->fp1)/DBAccess::$rowlen);
	}
	public function getColCount()
	{
		return count(DBAccess::$cfg);
	}
	public function getCol(&$StrArr,$col) //回傳整數。-1:開啟權限不足或尚未開啟 -2:col超出範圍 >=0:寫入StrArr個數
	{
		if($this->lock==LOCK_UN)
			return -1;
		if($col<0 || $col>=count(DBAccess::$cfg))
			return -2;
		$StrArr=array();
		fseek($this->fp1,0,SEEK_END);
		$n=(int)(ftell($this->fp1)/DBAccess::$rowlen);
		fseek($this->fp1,0,SEEK_SET);
		$len=DBAccess::$cfg[$col];
		$st=DBAccess::$pos[$col];
		if($len<0)
		{
			$len=-$len;
			for($i=0;$i<$n;++$i)
			{
				$str=fread($this->fp1,DBAccess::$rowlen);
				$st2=(int)base_convert(substr($str,$st,DBAccess::$reflen),16,10);
				$len2=(int)base_convert(substr($str,$st+DBAccess::$reflen+1,$len),16,10);
				fseek($this->fp2,$st2,SEEK_SET);
				array_push($StrArr,fread($this->fp2,$len2));
			}
		}
		else
		{
			for($i=0;$i<$n;++$i)
			{
				$str=fread($this->fp1,DBAccess::$rowlen);
				array_push($StrArr,substr($str,$st,$len));
			}
		}
		return $n;
	}
	public function writeRow(&$StrArr,$row) //回傳整數。-1:開啟權限不足或尚未開啟 ==count(&$StrArr):成功 0<return<count(&$StrArr):失敗於陣列元素
	{
		//檢查
		if($this->lock!=LOCK_EX)
			return -1;
		$n=count($StrArr);
		$m=count(DBAccess::$cfg);
		for($i=0;$i<$m && $i<$n && (DBAccess::$cfg[$i]<0 || strlen($StrArr[$i])==DBAccess::$cfg[$i]);++$i);
		if($i<$m)
			return $i;
		//將指標移到row 
		fseek($this->fp1,0,SEEK_END);
		fseek($this->fp2,0,SEEK_END);
		$st=ftell($this->fp2);
		$len=0;
		$end=ftell($this->fp1);
		if(0<=$row*DBAccess::$rowlen && $row*DBAccess::$rowlen<$end)
			fseek($this->fp1,$row*DBAccess::$rowlen,SEEK_SET);
		//寫入資料
		for($i=0;$i<$m;++$i)
		{
			if(DBAccess::$cfg[$i]<0)
			{
				$len=strlen($StrArr[$i]);
				fwrite($this->fp1,sprintf("%0".DBAccess::$reflen."X %0".(-DBAccess::$cfg[$i])."X",$st,$len).($i==$m-1?"\n":" "));
				fwrite($this->fp2,$StrArr[$i]);
				$st+=$len;
			}
			else
				fwrite($this->fp1,$StrArr[$i].($i==$m-1?"\n":" "));
		}
		return $m;
	}
	public function open($filename_ref,$filename_txt,$mode)
	{
		if(strcmp($mode,"r")==0)
			$L=LOCK_SH;
		else if(strcmp($mode,"r+")==0)
			$L=LOCK_EX;
		else
			return false;
		$this->fp1=fopenL($filename_ref,$mode,$L,10,250);
		if($this->fp1==false)
			return false;
		$this->fp2=fopenL($filename_txt,$mode,$L,10,250);
		if($this->fp2==false)
		{
			fclose($this->fp1);
			return false;
		}
		$this->lock=$L;
		return true;
	}
	public function close()
	{
		$this->lock=LOCK_UN;
		fclose($this->fp1);
		fclose($this->fp2);
	}
	private function fopenL($fname,$rw,$L,$N,$ms)
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
}

?>
