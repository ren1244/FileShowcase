"use strict";
var DB;
function init()
{
	document .getElementById("refresh") .setAttribute("onclick",'DB.read(RefreshAll);');
	document .getElementById("admin_run") .setAttribute("onclick",'upld()');
	document .getElementById("menu") .setAttribute("onchange",'changePanel()');
	document .getElementById("menu_flod") .setAttribute("onclick","changeAdminDisplay()");
	
	DB=new FileList("");
	DB.read(RefreshAll);
	
	createDomTree(document .getElementById("run_panel"),[
		"div",null,"class","dev",
		["input",null,"type","text","id","timestamp"],
		["button",null,"onclick","modifyT()",["Text",null,"修改時間"]],
		["br",null]
	]);
}
function modifyT()
{
	var del_radio=document .getElementById("del_panel").elements;
	var i,n,code;
	n=del_radio.length;
	for(i=0;i<n && !del_radio[i].checked;++i);
	if(i<n)
	{
		var t=document .getElementById("timestamp").value;
		t=t.split(' ');
		if(t.length<=0)
			return;
		t[0]=t[0].split('/');
		if(t[0].length!=3)
			return;
		var DT=new Date;
		DT.setFullYear(t[0][0]);
		DT.setMonth(t[0][1]-1);
		DT.setDate(t[0][2]);
		if(t.length==2)
		{
			t[1]=t[1].split(':');
			if(t[1].length!=3)
				return;
			DT.setHours(t[1][0]);
			DT.setMinutes(t[1][1]);
			DT.setSeconds(t[1][2]);
		}
		alert(DT.toLocaleString());
		DB.modifyTime(i,DT.getTime()/1000|0,function(){DB.read(RefreshAll);});
	}
}
function upld()
{
	DB.upload(document .getElementById("inp_file"),
		document .getElementById("pwd").value,
		document .getElementById("txt").value,
		function(reTxt){DB.read(RefreshAll);});
}
function toDelete()
{
	var del_radio=document .getElementById("del_panel").elements;
	var i,n,code;
	n=del_radio.length;
	for(i=0;i<n && !del_radio[i].checked;++i);
	if(i<n)
		DB.del(document .getElementById("pwd").value,i,function(){DB.read(RefreshAll);});
}
function changePanel()
{
	var radios=document .getElementById("menu").elements;
	var btn=document .getElementById("admin_run");
	var p=[document .getElementById("up_panel"),document .getElementById("del_panel")];
	var i,n,k;
	n=radios.length;
	for(i=0;i<n;++i)
	{
		if(radios[i].checked)
		{
			p[i] .removeAttribute("style");
			k=i;
		}
		else
			p[i].setAttribute("style","display:none");
	}
	btn .setAttribute("onclick",k==0?"upld()":"toDelete()");
	btn.innerHTML= k==0?"上傳":"刪除";
}
function RefreshAll()
{
	var d=document .getElementById("show");
	var del_d=document .getElementById("del_panel");
	
	var t,i,DT,HT,tt;
	
	d.innerHTML="";
	del_d.innerHTML="";
	
	DB.sortBy(['time','fileName'],-1);
	HT=0; //標題時間，紀錄日期，以便判斷更新
	
	for(i=0;i<DB.list.length;++i)
	{
		t=DB.list[i];
		DT=new Date(t.time*1000);
		
		createDomTree(del_d,[
			["input",null,"type","radio","name","del_radio","value",t.fileCode],
			["Text",null,t.fileName],
			["br",null]
		]);
		
		tt=DT.toLocaleDateString();
		if(HT!=tt)
		{
			HT=tt;
			createDomTree(d,["div",null,"class","date_header",["Text",null,DT.toLocaleDateString()]]);
		}
		
		createDomTree(d,["div",null,"class","file_data",
			["div",null,"class","data_header",
				["a",null,"href","Download.php?code="+t.fileCode,['Text',null,t.fileName]],
				["span",null,"onclick","changeDisplay("+i+")",["Text",null,"詳細資料"]]
			],
			["div",null,"class","data_content",
				["div",null,
					["Text",null,getDataSize(t.fileSize)],
					["Text",null," 下載次數："+t.dlCount],
					["span",null,["Text",null,DT.toLocaleTimeString()]]
				],
				["pre",null,"class","content_scpt",["Text",null,t.script]]
			],
			["hr",null]
		]);
		
	}
	function getDataSize(sz)
	{
		var unit=[" B"," KB"," MB"];
		var i;
		for(i=0;i<unit.length-1 && sz>=1000;++i)
			sz/=1000;
		return (sz>10?Math.floor(sz).toString():sz.toString().substr(0,4))+unit[i];
	}
	
}

function changeDisplay(idx)
{
	var ele=document .getElementsByClassName("file_data")[idx].children[1];
	if(ele.getAttribute('style'))
		ele .removeAttribute('style');
	else
		ele .setAttribute('style',"display:block");
	//alert();
}
function changeAdminDisplay()
{
	var ele=document .getElementById("admin");
	if(ele.getAttribute('style'))
		ele .removeAttribute('style');
	else
		ele .setAttribute('style',"display:none;");
	//alert();
}
function createDomTree(p,DataArr)
{
/*根據陣列資料，建立整個DOM
	參數：
		p:掛載目的地，DOM Ref。
		DataArr:陣列，內容規定如下。
			
			[tagNmae,namespace,Attr1,Val1,Attr2,Val2,...,[ChildrenElement]]
			
			tagNmae:標籤名稱 如div p 等
			namespace:如果是一般html元素，填null，否則為xml namespace
			Attr:屬性名稱
			Val:屬性數值
			ChildrenElement:陣列，此tagDOM的代表子物件
*/
	var t,ty,i;
	if(typeof(DataArr[0])!='string')
	{
		for(i=0;i<DataArr.length;++i)
			if(typeof(DataArr[i])!='string')
				createDomTree(p,DataArr[i]);
		return;
	}
	if(DataArr[1])
	{
		p.appendChild(t=document.createElementNS(DataArr[1],DataArr[0]));
		for(i=2;i<DataArr.length;++i)
			if(typeof(DataArr[i])=='string')
			{
				t.setAttributeNS(null,DataArr[i],DataArr[i+1]);
				++i;
			}
			else
				createDomTree(t,DataArr[i]);
	}
	else
	{
		if(DataArr[0]=='Text')
			p.appendChild(t=document.createTextNode(DataArr.slice(2).toString()));
		else
		{
			p.appendChild(t=document.createElement(DataArr[0]));
			for(i=2;i<DataArr.length;++i)
				if(typeof(DataArr[i])=='string')
				{
					if(DataArr[i]=='innerHTML')
						t.innerHTML=DataArr[i+1];
					else
						t.setAttribute(DataArr[i],DataArr[i+1]);
					++i;
				}
				else
					createDomTree(t,DataArr[i]);
		}
	}
}
