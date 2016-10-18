function push_byte(A,N,b)
{
	if((N&3)==0)
		A.push(b);
	else
		A[A.length-1]|=b<<(N&3)*8;
	return N+1;
}
function upld()
{
	var d=new Date;
	var f=document .getElementById("inp_file").files;
	
	if(f.length==0)
		return;
	
	var UF=f[0].name;
	var DC=StringToBase64(document .getElementById("txt").value);
	var TS=(d.getTime()/1000|0); //TimeStamp
	var HS;
	
	var A,A_N,AA,AA_N,i,hash,dt;
	var dbg="";
	A=[];AA=[];A_N=AA_N=0;
	//A加入檔案
	if(f.length>0)
	{
		var reader=new FileReader();
		reader.onload=function(e)
		{
			var i;
			var a=new Uint8Array(e.target.result);
			for(i=0;i<a.length;++i)
				A_N=push_byte(A,A_N,a[i]);
			//計算檔案雜湊值
			HS=sha256(A,A_N);
			//放入檔案名稱+檔案雜湊值
			A=[];
			A_N=StringToBytes(UF+HS,A); 
			//A加入時間
			for(i=0;i<4;++i)
				A_N=push_byte(A,A_N,TS>>>8*(i&3)&0xFF);
			AA_N=StringToBytes(document .getElementById("pwd").value,AA);
			AA=SHAKE128({A:AA,N:AA_N},256,1);
			dbg+="B64(FileName)="+StringToBase64(UF)+"\n";
			dbg+="hash(Bytes(pwd))="+ShowBytes(AA,32);
			for(i=0;i<32;++i)
				A_N=push_byte(A,A_N,AA[i>>>2]>>>8*(i&3)&0xFF);
			dbg+="\nA="+ShowBytes(A,A_N);
			HS=SHAKE128({A:A,N:A_N},256,0);
			dbg+="\nHash(A)="+HS;
			
			document .getElementById("inp_hash").value=HS;
			document .getElementById("inp_cript").value=DC;
			document .getElementById("inp_time").value=TS;
			
			dbg+="\n"+UF+" "+DC+" "+TS+" "+HS;
			
			//document .getElementById("dbg").innerHTML=dbg;
			call_php("Upload.php","init=true",RealSubmit,false);
		};
		reader.readAsArrayBuffer(f[0]);
	}
}
function RealSubmit(str)
{
	//alert(str);
	document .getElementById("up_form").submit();
}
function getHash()
{
	var org=document .getElementById("txt1").value;
	var A=[];
	var N=StringToArray(org,A);
	document .getElementById("log1").innerHTML=SHAKE128({A:A,N:N},256,0);
	call_php("SHA3.php","str="+org,cbk);
}
function cbk(str)
{
	document .getElementById("log2").innerHTML=str;//php結果顯示
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
