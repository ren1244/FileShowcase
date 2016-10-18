#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
/*介面：
SHA3(filename);
	output: sha3 hash
	return value:
		(0)success 
		(1)argument error 
		(2)file open error 
*/

uint32_t RC[][2]={
{0x00000000,0x00000001},{0x00000000,0x00008082},{0x80000000,0x0000808A},
{0x80000000,0x80008000},{0x00000000,0x0000808B},{0x00000000,0x80000001},
{0x80000000,0x80008081},{0x80000000,0x00008009},{0x00000000,0x0000008A},
{0x00000000,0x00000088},{0x00000000,0x80008009},{0x00000000,0x8000000A},
{0x00000000,0x8000808B},{0x80000000,0x0000008B},{0x80000000,0x00008089},
{0x80000000,0x00008003},{0x80000000,0x00008002},{0x80000000,0x00000080},
{0x00000000,0x0000800A},{0x80000000,0x8000000A},{0x80000000,0x80008081},
{0x80000000,0x00008080},{0x00000000,0x80000001},{0x80000000,0x80008008}};

void ShowByteStream(FILE *fp)
{
	unsigned char x;
	fseek(fp,0,SEEK_SET);
	while(fread(&x,1,1,fp))
		printf("%02X ",x);
}

void keccak_p_1600_24(uint32_t *S) // uint32_t S[400]
{
	int i,j,k,t_x,t_y,off,pos;
	uint32_t D,H,L;
	for(j=0;j<24;++j)
	{
		//f1();
		for(i=0;i<10;++i)
			S[50+i]=S[i]^S[i+10]^S[i+20]^S[i+30]^S[i+40];
		for(i=0;i<10;++i)
		{
			D=S[50+(i+2)%10]<<1|S[50+(i+3-(i<<1&2))%10]>>31;
			D^=S[50+(i+8)%10];
			for(k=0;k<5;++k)
				S[i+10*k]^=D;
		}
		//f2();
		S[50]=S[0];S[51]=S[1];
		t_x=1;t_y=0;
		for(i=0;i<24;++i)
		{
			off=((i+1)*(i+2)>>1&0x3F);
			pos=t_x*2+t_y*10;
			H=off>=32?S[pos]:S[pos+1];
			L=off>=32?S[pos+1]:S[pos];		
			off=(off&0x1F);
			S[pos+50]=L<<off|H>>32-off;
			S[pos+51]=H<<off|L>>32-off;
			pos=t_y;
			t_y=(2*t_x+3*t_y)%5;
			t_x=pos;
		}
		//f3();
		for(i=0;i<5;++i)
		{
			for(k=0;k<5;++k)
			{
				t_x=i*2+k*10;
				t_y=(i+3*k)%5*2+i*10+50;
				S[t_x]=S[t_y];
				S[t_x+1]=S[t_y+1];
			}
		}
		//f4();
		for(i=0;i<5;++i)
		{
			for(k=0;k<5;++k)
			{
				t_x=i*2+k*10;
				t_y=(i+1)%5*2+k*10;
				pos=(i+2)%5*2+k*10;
				S[t_x+50]=S[t_x]^((~S[t_y])&S[pos]);
				S[t_x+51]=S[t_x+1]^(~S[t_y+1]&S[pos+1]);
			}
		}
		//f5(i);
		for(i=0;i<5;++i)
		{
			for(k=0;k<5;++k)
			{
				t_x=i*2+k*10;
				S[t_x]=S[t_x+50];
				S[t_x+1]=S[t_x+51];
			}
		}
		S[0]^=RC[j][1];
		S[1]^=RC[j][0];
	}
}
void SHAKE128(FILE *fp)
{
	int i,j,n,N;
	uint32_t S[100],t,b;
	memset(S,0,400);
	fseek(fp,0,SEEK_END);
	N=ftell(fp);
	fseek(fp,0,SEEK_SET);
	//吸收
	//printf("N=%d\n",N);
	i=0;
	for(j=3;j<N;j+=4) //完整4的倍數
	{
		//printf("fread j=%d\n",j);
		fread(&t,1,4,fp);
		S[i++]^=t;
		if(i==42)
		{
			keccak_p_1600_24(S);
			i=0;
		}
	}
	t=0;n=N%4;
	for(j=0;j<n;++j)
	{
		//printf("\tfread j=%d\n",j);
		fread(&b,1,1,fp);
		t|=(b&0xFF)<<8*j;
	}
	if(N%168==167)
	{
		//printf("N%168==167\n");
		t|=0x9F000000;
		S[i++]^=t;
		keccak_p_1600_24(S);
	}
	else
	{
		//printf("N%168!=167\n");
		if(i==41)
			S[i++]^=0x1F<<8*(N%4)|0x80000000|t;
		else
		{
			t|=0x1F<<8*(N%4);
			S[i++]^=t;
			while(i<41)
				S[i++]^=0;
			S[i++]^=0x80000000;
		}
		keccak_p_1600_24(S);
	}
	//擠出
	//printf("for(i=0;i<168;++i)\n");
	for(i=0;i<32;++i)
		printf("%02X",S[i/4]>>8*(i&3)&0xFF);
	/*keccak_p_1600_24(S);
	printf("for(i=0;i<88;++i)\n");
	for(i=0;i<88;++i)
		printf("%02X",S[i/4]>>8*(i&3)&0xFF);*/
}

int main(int argc,char *argv[])
{
	FILE* fp;
	unsigned char *p;
	int n;
	
	if(argc!=2)
		return 1;
	fp=fopen(argv[1],"rb");
	if(fp==NULL)
		return 2;
	fseek(fp,0,SEEK_END);
	//printf("%d bytes\n",n=ftell(fp));
	//ShowByteStream(fp);
	SHAKE128(fp);
	fclose(fp);
	return 0;
}
