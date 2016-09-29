/*
StringToBytes(str,A)
	將str轉換為A
BytesToString(A,N)
	將A轉換為str
StringToBase64(str)
	將str轉換為Base64字串
Base64ToString(B64Str)
	將B64Str(Base64字串)轉換為原本的字串
BytesToBase64(Arr,Byte_N)
	將數字陣列Arr轉換為Base64字串，以Byte為單位，共轉換Byte_N Byte
Base64ToBytes(B64str) //回傳{A,N}
	將B64Str(Base64字串)轉換為原本的Arr及Byte_N
*/
function ShowBytes(A,N)
{
	var i,t,str;
	str="";
	for(i=0;i<N;++i)
	{
		t=A[i>>>2]>>>8*(i&3)&0xFF;
		str+=(t>15?t.toString(16):"0"+t.toString(16)).toUpperCase()+" ";
	}
	return str;
}
function StringToBytes(str,A)
{
	var i;
	A.length=0;
	for(i=0;i<str.length;++i)
	{
		if(i&1)//單數
			A[i>>>1]|=str.charCodeAt(i)<<16;
		else//雙數
			A.push(str.charCodeAt(i));
	}
	return i*2;
}
function BytesToString(A,N)
{
	var i;
	var str="";
	N>>>=1;
	for(i=0;i<N;++i)
		str+=String.fromCharCode(A[i>>>1]>>>(i&1)*16&0xFFFF);
	return str;
}
function StringToBase64(str)
{
	var i;
	var A=[];
	i=StringToBytes(str,A);
	return BytesToBase64(A,i);
}
function Base64ToString(B64Str)
{
	var T=Base64ToBytes(B64Str);
	return BytesToString(T.A,T.N);
}
function BytesToBase64(Arr,Byte_N)
{
	var B64="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";
	var i,t,b,s,str;
	str="";
	for(i=0;i<Byte_N;++i)
	{
		b=Arr[i>>>2]>>>i%4*8&0xFF;
		s=i%3*2;
		t=t<<6-s|b>>>s+2&0x3F>>>s;
		str+=B64[t];
		t=b&(0x3F>>>4-s);
		if(s==4)
		{
			t=b&0x3F;
			str+=B64[t];
			t=0;
		}
	}
	if(s<4)
		str+=B64[t<<4-s];
	return str;
}
function Base64ToBytes(B64str) //回傳{A,N}
{
	var r={A:[],N:0};
	var i,t,s;
	for(i=t=0;i<B64str.length;++i)
	{
		t=t<<6|B64Idx();
		s=i&3;
		if(s>0)
		{
			PushByte(t>>>6-s*2);
			t=t&0x3F>>>s*2;
		}
	}
	if(s<3)
		PushByte(t<<s*2+2);
	function PushByte(b)
	{
		if((r.N&3)==0)
			r.A.push(b);
		else
			r.A[r.A.length-1]|=b<<(r.N&3)*8;
		++r.N;
	}
	function B64Idx()
	{
		var a=B64str.charCodeAt(i);
		if(0x41<=a && a<=0x5A)
			return a-0x41;
		if(0x61<=a && a<=0x7A)
			return a-0x61+26;
		if(0x30<=a && a<=0x39)
			return a-0x30+52;
		return a==0x2D?62:(a==0x5F?63:-1);
	}
	return r;
}
