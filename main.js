var LTS;
function init()
{
	var t=new Date;
	LTS=t.getTimezoneOffset();
	call_php("Read.php","",ShowData);
}

function ShowData(d_str)
{
	var d=document .getElementById("show");
	var i,t,tb,tr,td,c;
	//var str="";
	
	d .appendChild(tb=document .createElement("table"));
	tb .appendChild(tr=document .createElement("tr"));
	tr .appendChild(td=document .createElement("th"));td.innerHTML="上傳日期";
	tr .appendChild(td=document .createElement("th"));td.innerHTML="檔案名稱";
	tr .appendChild(td=document .createElement("th"));td.innerHTML="檔案大小(byte)";
	tr .appendChild(td=document .createElement("th"));td.innerHTML="下載次數";
	tr .appendChild(td=document .createElement("th"));td.innerHTML="詳細資料";
	tr .appendChild(td=document .createElement("th"));td.innerHTML=" ";
	d_str=d_str.split('!');
	for(i=0;i<d_str.length;++i)
	{
		if((t=d_str[i].trim()).length==0)
			continue;
		t=t.split('.');
		//str+=Base64ToString(t[0])+"(code:"+t[5]+") size:"+t[1]+"bytes time:"+t[2]+" downloaded:"+t[3]+"\n";
		//str+="detail:"+Base64ToString(t[4])+"\n";
		tb .appendChild(tr=document .createElement("tr"));
		tr .appendChild(td=document .createElement("td")); //日期
			td.innerHTML=toDate(parseInt(t[2]));
		tr .appendChild(td=document .createElement("td")); //檔名
			td .appendChild(c=document .createElement("a"));
			c .innerHTML=Base64ToString(t[0]);
			c .setAttribute("href","Download.php?code="+t[5]);
		tr .appendChild(td=document .createElement("td")); //大小
			td.innerHTML=t[1];
		tr .appendChild(td=document .createElement("td")); //下載次數
			td.innerHTML=parseInt(t[3],16);
		tr .appendChild(td=document .createElement("td")); //詳細資料
			td.innerHTML=Base64ToString(t[4]);
		tr .appendChild(td=document .createElement("td")); //刪除
			td .appendChild(c=document .createElement("button"));
			c .innerHTML="刪除";
			c .setAttribute("onclick","toDelete(this)");
			c .code=t[5];
	}
}
var gtmp;
function toDelete(button_obj)
{
	gtmp=button_obj;
	call_php("Delete.php","init=true",toDeleteCbk,0);
}
function toDeleteCbk(str)
{
	var d=new Date;
	var t=(d.getTime()/1000|0);
	var A=[];
	var N=0;
	N=StringToBytes(gtmp.code,A);
	for(i=0;i<4;++i)
		N=push_byte(A,N,t>>>8*(i&3)&0xFF);
	var AA_N,AA;
	AA=[];
	AA_N=StringToBytes(document .getElementById("pwd").value,AA);
	AA=SHAKE128({A:AA,N:AA_N},256,1);
	for(i=0;i<32;++i)
		N=push_byte(A,N,AA[i>>>2]>>>8*(i&3)&0xFF);
	str+="<br>A="+ShowBytes(A,N);
	var h=SHAKE128({A:A,N:N},256,0);
	str+="<br>hash="+h;
	document .getElementById("log").innerHTML=str;
	call_php("Delete.php","id="+gtmp.code+"&time="+t+"&hash="+h,toDeleteCbk2,0);
}
function toDeleteCbk2(str)
{
	document .getElementById("log").innerHTML+="<br>"+str;
}
function push_byte(A,N,b)
{
	if((N&3)==0)
		A.push(b);
	else
		A[A.length-1]|=b<<(N&3)*8;
	return N+1;
}
function toDate(ut)
{
	var str="";
	ut=((ut/60|0)-LTS)/1440|0;
	ut+=365;
	var dy=(ut/1461|0)*4;
	ut%=1461;
	if((ut/365|0)>=3)
	{
		dy+=3;
		ut-=365*3;
	}
	else
	{
		dy+=(ut/365|0);
		ut%=365;
	}
	var y,m,A;
	A=[31,28,31,30,31,30,31,31,30,31,30,31];
	y=1969+dy;
	if(y%4==0)
		A[1]=29;
	for(m=0;m<12 && ut>=A[m];++m)
		ut-=A[m];
	str+=y+"."+(m+1)+"."+(ut+1);
	return str;
}
function call_php(url,params,cbk_func,ststus_div)
{
	var http = new XMLHttpRequest();
	http.open("POST", url, true);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.setRequestHeader("Content-length", params.length);
	http.setRequestHeader("Connection", "close");
	http.onreadystatechange = function() 
	{
		if(http.readyState == 4)
		{
			if(http.status == 200)
			{
				cbk_func(http.responseText);
				if(ststus_div)
					ststus_div.innerHTML="";
			}
			else if(ststus_div)
				ststus_div.innerHTML="連線失敗，請重試！";
		}
	}
	if(ststus_div)
		ststus_div.innerHTML="讀取中，請稍候...";
	http.send(params);
}
