/*
用法：
	sha256(A,N,mode)
*/

function sha256(A,N)
{
	var i,m,t,w,t2;
	var H0,H1,H2,H3,H4,H5,H6,H7;
	var a,b,c,d,e,f,g,h;
	var cst=[
0x428a2f98,0x71374491,0xb5c0fbcf,0xe9b5dba5,0x3956c25b,0x59f111f1,0x923f82a4,0xab1c5ed5,
0xd807aa98,0x12835b01,0x243185be,0x550c7dc3,0x72be5d74,0x80deb1fe,0x9bdc06a7,0xc19bf174,
0xe49b69c1,0xefbe4786,0x0fc19dc6,0x240ca1cc,0x2de92c6f,0x4a7484aa,0x5cb0a9dc,0x76f988da,
0x983e5152,0xa831c66d,0xb00327c8,0xbf597fc7,0xc6e00bf3,0xd5a79147,0x06ca6351,0x14292967,
0x27b70a85,0x2e1b2138,0x4d2c6dfc,0x53380d13,0x650a7354,0x766a0abb,0x81c2c92e,0x92722c85,
0xa2bfe8a1,0xa81a664b,0xc24b8b70,0xc76c51a3,0xd192e819,0xd6990624,0xf40e3585,0x106aa070,
0x19a4c116,0x1e376c08,0x2748774c,0x34b0bcb5,0x391c0cb3,0x4ed8aa4a,0x5b9cca4f,0x682e6ff3,
0x748f82ee,0x78a5636f,0x84c87814,0x8cc70208,0x90befffa,0xa4506ceb,0xbef9a3f7,0xc67178f2];
	//initial hash value
	H0=0x6a09e667;
	H1=0xbb67ae85;
	H2=0x3c6ef372;
	H3=0xa54ff53a;
	H4=0x510e527f;
	H5=0x9b05688c;
	H6=0x1f83d9ab;
	H7=0x5be0cd19;
	//Parsing the Message
	t=N*8;
	bpush(128);
	m=N%64;
	m=m>56?56+64-m:56-m;
	for(i=0;i<m;++i)
		bpush(0);
	bpush(0);bpush(0);bpush(0);bpush(0); //因為t存不到超過32位元
	for(i=0;i<4;++i)
		bpush(t>>>8*(3-i)&0xFF);
	//
	w=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0
	,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0
	,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0
	,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];

	for(m=0;m<N;m+=64)
	{
		//Prepare the message schedule
		for(i=0;i<16;++i)
		{
			t=A[(m>>>2)+i];
			w[i]=t>>>24&0xFF|t>>>8&0xFF00|t<<8&0xFF0000|t<<24&0xFF000000;
		}
		for(;i<64;++i)
			w[i]=u32add(u32add(Sig1(w[i-2]),w[i-7]),u32add(Sig0(w[i-15]),w[i-16]));
		//Initialize the eight working variables
		a=H0;b=H1;c=H2;d=H3;e=H4;f=H5;g=H6;h=H7;
		//Loop
		for(i=0;i<64;++i)
		{
			t=u32add(u32add(h,Sum1(e)),u32add(u32add(Ch(e,f,g),cst[i]),w[i]));
			t2=u32add(Sum0(a),Maj(a,b,c));
			h=g;g=f;f=e;e=u32add(d,t);
			d=c;c=b;b=a;a=u32add(t,t2);
		}
		//hash value H
		H0=u32add(a,H0);H1=u32add(b,H1);H2=u32add(c,H2);H3=u32add(d,H3);
		H4=u32add(e,H4);H5=u32add(f,H5);H6=u32add(g,H6);H7=u32add(h,H7);
	}
	return showint(H0)+showint(H1)+showint(H2)+showint(H3)+showint(H4)+showint(H5)+showint(H6)+showint(H7);
	
	function showint(x)
	{
		var i,str,t;
		str="";
		for(i=0;i<4;++i)
		{
			t=x>>>8*(3-i)&0xFF;
			str+=(t<16?"0":"")+t.toString(16).toUpperCase();
		}
		return str;
	}
	function bpush(val)
	{
		if(N&3)
			A[N>>>2]|=(val&0xFF)<<((N&3)<<3);
		else
			A.push(val&0xFF);
		++N;
	}
	function u32add(x,y)
	{
		var tL,tH;
		tL=(x&0xFFFF)+(y&0xFFFF);
		tH=(x>>>16)+(y>>>16)+(tL>>>16);
		return (tH&0xFFFF)<<16|tL&0xFFFF;
	}
	function Ch(x,y,z)
	{
		return (x&y)^(~x&z)
	}
	function Maj(x,y,z)
	{
		return (x&y)^(x&z)^(y&z);
	}
	function Sum0(x)
	{
		return (x>>>2|x<<30)^(x>>>13|x<<19)^(x>>>22|x<<10);
	}
	function Sum1(x)
	{
		return (x>>>6|x<<26)^(x>>>11|x<<21)^(x>>>25|x<<7);
	}
	function Sig0(x)
	{
		return (x>>>7|x<<25)^(x>>>18|x<<14)^(x>>>3);
	}
	function Sig1(x)
	{
		return (x>>>17|x<<15)^(x>>>19|x<<13)^(x>>>10);
	}
}
