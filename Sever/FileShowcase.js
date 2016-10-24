/*

========FileList物件說明========

	FileList是一個與伺服器溝通的接口，提供read,del,upload等方法
	伺服器端需要有Read.php,Delete.php,Upload.php等接口
	(放在建構函數url資料夾中)
	
	相依函式庫：
		StringBase64.js
		SHA3.js
		SHA256.js

========建構========
FileList(url)
	參數：
		url:伺服器網址(最後要有"/"，如果是同資料夾，可為空字串)
	回傳值:
		無
========屬性========
list
	用read方法後，存放更新後的資料
	**del和upload方法不會自動更新**
	這是一個物件陣列，物件內容如下：
	{
		fileName:檔案名稱
		fileSize:檔案大小(in byte)
		time:上傳時間(unixtime in sec)
		dlCount:下載次數
		script:檔案描述字串
		fileCode:檔案代碼(給del用參數，可以不管它)
	}
sever_url
	服器網址(最後要有"/"，如果是同資料夾，可為空字串)
	可動態修改
========方法========
read(cbk)
	參數：
		cbk:讀取完畢，伺服器會呼叫的callback
	回傳值:
		無
del(pwdstr,idx,cbk)
	參數：
		pwdstr:密碼字串
		idx:要刪除list中的哪筆資料
		cbk:讀取完畢，伺服器會呼叫的callback
	回傳值:
		無
upload(oEleFile,pwdstr,script,cbk)
	參數：
		oEleFile:<input type='file'>物件
		pwdstr:密碼字串
		script:此檔案的描述文字字串
	回傳值:
		無
sortBy(arr,mode)
	對list依照指定的欄位做排序
	參數：
		arr:陣列，其元素要比較的欄位字串。如:['time','fileSize']會先比較時間再比較檔案大小。
		mode:非數字或數字>=0為升序排列，<0為降序排列
	回傳值:
		無
*/
function FileList(url)
{
	this.list=[]; //儲存資料的陣列
	this.sever_url=url;
	
	this.push_byte=function(A,N,b)
	{
		if((N&3)==0)
			A.push(b);
		else
			A[A.length-1]|=b<<(N&3)*8;
		return N+1;
	};
}
FileList.prototype.read=function(cbk)
{
	var xhr = new XMLHttpRequest();
	var r;
	this.list=r=[];
	xhr.open("POST",this.sever_url+"Read.php");
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.onreadystatechange=function() 
	{
		if(xhr.readyState==4 && xhr.status==200)
		{
			var A=xhr.responseText.split('!');
			var i,n,t;
			for(i=0,n=A.length;i<n;++i)
			{
				if((t=A[i].trim()).length==0)
					continue;
				t=t.split('.');
				r.push({
					fileName:Base64ToString(t[0]),
					fileSize:t[1],
					time:parseInt(t[2]),
					dlCount:parseInt(t[3],16),
					script:Base64ToString(t[4]),
					fileCode:t[5]
				});
			}
			cbk(xhr.responseText);
		}
	};
	xhr.send();
}
FileList.prototype.del=function(pwdstr,idx,cbk)
{
	var str="";
	var org_obj=this;
	var delete_file=function()
	{
		//計算雜湊
		var t=(Date.now()/1000|0);
		var A=[];
		var i,N=0;
		N=StringToBytes(org_obj.list[idx].fileCode,A);
		for(i=0;i<4;++i)
			N=org_obj.push_byte(A,N,t>>>8*(i&3)&0xFF);
		var AA_N,AA;
		AA=[];
		AA_N=StringToBytes(pwdstr,AA);
		AA=SHAKE128({A:AA,N:AA_N},256,1);
		for(i=0;i<32;++i)
			N=org_obj.push_byte(A,N,AA[i>>>2]>>>8*(i&3)&0xFF);
		var h=SHAKE128({A:A,N:N},256,0);
		//傳送訊息
		var xhr = new XMLHttpRequest();
		xhr.open("POST",org_obj.sever_url+"Delete.php");
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange=function() 
		{
			if(xhr.readyState==4 && xhr.status==200 && cbk)
				cbk(xhr.responseText);
		}
		xhr.send("id="+org_obj.list[idx].fileCode+"&time="+t+"&hash="+h);
	};
	//更新時間戳
	var xhr = new XMLHttpRequest();
	xhr.open("POST",this.sever_url+"Delete.php");
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.onreadystatechange=function() 
	{
		if(xhr.readyState==4 && xhr.status==200)
			delete_file();
	};
	xhr.send("init=true");
}
FileList.prototype.upload=function(oEleFile,pwdstr,script,cbk)
{
	if(!oEleFile || oEleFile.files.length<=0)
		return;
	var UF,HS,TS,DC;
	var dbg="";
	var org_obj=this;
	UF=oEleFile.files[0].name;
	TS=(Date.now()/1000|0);
	DC=StringToBase64(script);
	var reader=new FileReader();
	reader.onload=function(e)
	{
		var i,A,AA,A_N,AA_N;
		var a=new Uint8Array(e.target.result);
		A=[];A_N=0;
		for(i=0;i<a.length;++i)
			A_N=org_obj.push_byte(A,A_N,a[i]);
		//計算檔案雜湊值
		HS=sha256(A,A_N);
		//放入檔案名稱+檔案雜湊值
		A=[];
		A_N=StringToBytes(UF+HS,A);
		//A加入時間
		for(i=0;i<4;++i)
			A_N=org_obj.push_byte(A,A_N,TS>>>8*(i&3)&0xFF);
		//計算pwd的Hash於AA
		AA=[];
		AA_N=StringToBytes(pwdstr,AA);
		AA=SHAKE128({A:AA,N:AA_N},256,1);
		//將pwd的Hash加到A後計算Hash
		for(i=0;i<32;++i)
			A_N=org_obj.push_byte(A,A_N,AA[i>>>2]>>>8*(i&3)&0xFF);
		HS=SHAKE128({A:A,N:A_N},256,0);
		refresh_timestemp();
	}
	reader.readAsArrayBuffer(oEleFile.files[0]);
	function refresh_timestemp()
	{
		var xhr = new XMLHttpRequest();
		xhr.open("POST",org_obj.sever_url+"Upload.php");
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange=function() 
		{
			if(xhr.readyState==4 && xhr.status==200)
				submit();
		};
		xhr.send("init=true");
	}
	function submit()
	{		
		var xhr = new XMLHttpRequest();
		xhr.open("POST",org_obj.sever_url+"Upload.php");
		var FD = new FormData();
		FD.append('upload',oEleFile.files[0],oEleFile.files[0].name);
		FD.append('time',TS);
		FD.append('hash',HS);
		FD.append('cript',DC);
		xhr.onreadystatechange=function() 
		{
			if(xhr.readyState==4 && xhr.status==200 && cbk)
				cbk(xhr.responseText);
		};
		xhr.send(FD);
	} 
}
FileList.prototype.modifyTime=function(idx,timeStamp,cbk)
{
	var xhr = new XMLHttpRequest();
	xhr.open("GET","MyModify.php?id="+this.list[idx].fileCode+"&time="+timeStamp);
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.onreadystatechange=function() 
	{
		if(xhr.readyState==4 && xhr.status==200)
			cbk(xhr.responseText);
	};
	xhr.send();
}
FileList.prototype.sortBy=function(arr,mode)
{
	var n=arr.length;
	
	if(typeof(mode)!='number' || mode==0)
		mode=1;
	
	this.list.sort(cmp);
	function cmp(a,b)
	{
		var i;
		for(i=0;i<n;++i)
			if(a[arr[i]]!=b[arr[i]])
				return a[arr[i]]<b[arr[i]]?-mode:mode;
		return 0;
	}
}
