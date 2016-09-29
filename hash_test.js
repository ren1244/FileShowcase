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
