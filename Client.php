<?php

/**
 * 客户端
 * 基于 posix,pcntl,sockets,shmop 的简易聊天室
 */

class Client
{
    protected $clientSocket=null;
    const SESSION_END=':q'.PHP_EOL;

    public function run($ip,$port)
    {
        $this->initSocket($ip,$port);
        $this->process();
        socket_close($this->clientSocket);
    }

    //
    public function initSocket($ip,$port)
    {
        $socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        if(socket_connect($socket,$ip,(int)$port)===false) exit("connect fail");;
        socket_set_nonblock($socket);
        $this->clientSocket=$socket;
    }

    //
    public function process()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            exit("could not fork"); //pcntl_fork返回-1标明创建子进程失败
        } else if ($pid) {// 父进程;
            $this->listenTerminal();
            posix_kill($pid,9);
        } else {
            $this->listenSocket();
        }
    }

    //
    public function listenTerminal()
    {
        while(true){
            $input=fgets(STDIN);
            socket_write($this->clientSocket,$input,strlen($input));
            if($input==self::SESSION_END) {
                break;
            }
        }
    }

    //
    public function listenSocket()
    {
        while(true){
            $inputStr = $this->socketReadAll($this->clientSocket);
            if($inputStr) echo "$inputStr";
            usleep(100000);
        }
    }

    //
    public function socketReadAll($socket){
        $inputStr=NULL;
        $bufferSize=128;
        $inputChar = socket_read($socket,$bufferSize);
        while(strlen($inputChar)>0){
            $inputStr.=$inputChar;
            $inputChar = socket_read($socket,$bufferSize);
        }
        return $inputStr;
    }

}

$wc=new Client();
//echo "please input [ip:port] : ";
//$in=fgets(STDIN);
//echo "Client Start: ( input \":q\" to quit ) \n";
//$in=explode(':',$in);
$ip='127.0.0.1';
$port=10010;
$wc->run($ip,$port);
