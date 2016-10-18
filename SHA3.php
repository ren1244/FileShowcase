<?php

require_once('libs.php');
//Stat is uint32_t A[400]; (1600 bit Array)

function run(&$Stat)
{
	for($i=0;$i<24;++$i)
	{
		f1($Stat);
		f2($Stat);
		f3($Stat);
		f4($Stat);
		f5($Stat,$i);
	}
}
/*function check_over(&$Stat)
{
	$n=count($Stat);
	for($i=0;$i<$n*4;++$i)
		if($Stat[$i]>>32)
			echo "[".$i."]";
}*/
function f1(&$Stat)
{
	for($i=0;$i<10;++$i)
		$Stat[50+$i]=$Stat[$i]^$Stat[$i+10]^$Stat[$i+20]^$Stat[$i+30]^$Stat[$i+40];
	for($i=0;$i<10;++$i)
	{
		$D=($Stat[50+($i+2)%10]<<1&0xFFFFFFFF|$Stat[50+($i+3-($i<<1&2))%10]>>31&0x1);
		$D=$D^$Stat[50+($i+8)%10];
		for($z=0;$z<5;++$z)
			$Stat[$i+10*$z]=$Stat[$i+10*$z]^$D;
	}
	//echo "f1<br>";
	//ShowState($Stat);
}
function f2(&$Stat)
{
	$Stat[50]=$Stat[0];
	$Stat[51]=$Stat[1];
	$t_x=1;
	$t_y=0;
	for($t=0;$t<24;++$t)
	{
		$off=(($t+1)*($t+2)>>1)%64;
		$pos=$t_x*2+$t_y*10;		
		$H=$off>=32?$Stat[$pos]:$Stat[$pos+1];
		$L=$off>=32?$Stat[$pos+1]:$Stat[$pos];		
		$off=$off%32;
		$Stat[$pos+50]=$L<<$off&0xFFFFFFFF|($off<32?($H>>1&0x7FFFFFFF)>>31-$off:0);
		$Stat[$pos+51]=$H<<$off&0xFFFFFFFF|($off<32?($L>>1&0x7FFFFFFF)>>31-$off:0);
		
		$pos=$t_y;
		$t_y=(2*$t_x+3*$t_y)%5;
		$t_x=$pos;
	}
	
	//echo "f2<br>";
	//ShowState($Stat);
}
function f3(&$Stat)
{
	for($i=0;$i<5;++$i)
	{
		for($j=0;$j<5;++$j)
		{
			$p1=$i*2+$j*10;
			$p2=($i+3*$j)%5*2+$i*10+50;
			$Stat[$p1]=$Stat[$p2];
			$Stat[$p1+1]=$Stat[$p2+1];
		}
	}
	//echo "f3<br>";
	//ShowState($Stat);
}
function f4(&$Stat) //A[S|S']
{
	for($i=0;$i<5;++$i)
	{
		for($j=0;$j<5;++$j)
		{
			$p1=$i*2+$j*10;
			$p2=($i+1)%5*2+$j*10;
			$p3=($i+2)%5*2+$j*10;
			$Stat[$p1+50]=$Stat[$p1]^((~$Stat[$p2])&$Stat[$p3]);
			$Stat[$p1+51]=$Stat[$p1+1]^(~$Stat[$p2+1]&$Stat[$p3+1]);
		}
	}
	//echo "f4<br>";
	//ShowState($Stat);
}
function f5(&$Stat,$ir) //A[S'|S]
{
	static $RCTable=array(
array(0x00000000,0x00000001),array(0x00000000,0x00008082),array(0x80000000,0x0000808A),
array(0x80000000,0x80008000),array(0x00000000,0x0000808B),array(0x00000000,0x80000001),
array(0x80000000,0x80008081),array(0x80000000,0x00008009),array(0x00000000,0x0000008A),
array(0x00000000,0x00000088),array(0x00000000,0x80008009),array(0x00000000,0x8000000A),
array(0x00000000,0x8000808B),array(0x80000000,0x0000008B),array(0x80000000,0x00008089),
array(0x80000000,0x00008003),array(0x80000000,0x00008002),array(0x80000000,0x00000080),
array(0x00000000,0x0000800A),array(0x80000000,0x8000000A),array(0x80000000,0x80008081),
array(0x80000000,0x00008080),array(0x00000000,0x80000001),array(0x80000000,0x80008008));
	for($i=0;$i<5;++$i)
	{
		for($j=0;$j<5;++$j)
		{
			$p1=$i*2+$j*10;
			$Stat[$p1]=$Stat[$p1+50];
			$Stat[$p1+1]=$Stat[$p1+51];
		}
	}
	$Stat[0]^=$RCTable[$ir][1];
	$Stat[1]^=$RCTable[$ir][0];
	//echo "f5! ".gettype($RCTable)." !".$RCTable[$ir][1]." !".$RCTable[$ir][0]."<br>";
	//ShowState($Stat);
}
function push_byte_macro(&$A,&$N,$val)
{
	$val&=0xFF;
	if(($N&3)==0)
		array_push($A,$val);
	else
		$A[($N-1>>2)]|=$val<<8*($N%4);
	++$N;
}
/*function ShowState(&$Stat)
{
	$n=count($Stat);
	check_over($Stat);
	for($i=0;$i<$n*4;++$i)
	{
		$x=$Stat[$i>>2]>>8*($i&3)&0xFF;
		echo ($x<16?("0".strtoupper(base_convert($x,10,16))):strtoupper(base_convert($x,10,16)))." ";
		if($i%16==($i<200?15:7))
			echo "<br>";
		if($i%200==199)
			echo "<br>";
	}
	echo "<br>";
}*/
function SHAKE128($P_A,$P_N,$d,$ret_type)
{
	//製造P=copy(M),S={A:[0,0,...],N:400}
	$S_A=array();
	$S_N=400;
	for($i=0;$i<100;++$i)
		array_push($S_A,0);
	//填滿P為168的整數倍
	if($P_N%168==167)
		push_byte_macro($P_A,$P_N,0x9F);
	else
	{
		push_byte_macro($P_A,$P_N,0x1F);
		for(;$P_N%168!=167;)
			push_byte_macro($P_A,$P_N,0x00);
		push_byte_macro($P_A,$P_N,0x80);
	}
	//吸收
	$n=(int)($P_N/168);
	$str="";
	for($i=0;$i<$n;++$i)
	{
		for($j=0;$j<42;++$j)
			$S_A[$j]^=$P_A[$i*42+$j];
		//echo "#".$i.":<br>";
		//ShowState($S_A);
		run($S_A);
	}
	//擠出
	$Z_A=array();
	$Z_N=168;
	for($k=0;$k<42;++$k)
		array_push($Z_A,$S_A[$k]);
	while($Z_N*8<$d)
	{
		run($S_A);
		for($k=0;$k<42;++$k)
			array_push($Z_A,$S_A[$k]);
		$Z_N+=168;
	}
	if(!$ret_type)
	{
		for($i=0;$i<($d>>3);++$i)
		{
			$n=($Z_A[$i>>2]>>8*($i%4)&0xFF);
			$str.=$n<16?("0".strtoupper(base_convert($n,10,16))):strtoupper(base_convert($n,10,16));
		}
		return $str;
	}
	//$Z_A.length=($d>>5);
	return $Z_A;
}
?>
