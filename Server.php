<?php

/**
 * 服务端
 * 基于 posix,pcntl,sockets,shmop 的简易聊天室
 */
class Server
{
    public $clientSocketPool=[];
    public $hostSocket=null;
    public $clientKey=1000;
    public $shmId=null;
    public $shmSize=null;
    public $shareMemoryInitString='';
    public $shareMemoryInitStringSize=1024;
    const SESSION_END=':q'.PHP_EOL;
    const CHECK_TIME=100;


    public function run($ip,$port)
    {
        $this->initShareMemory();
        $this->initServerSocket($ip,$port);
        $this->process();
    }

    public function initShareMemory()
    {
        $this->shareMemoryInitString=str_pad('',$this->shareMemoryInitStringSize,' ');

        $shmKey = ftok(__FILE__, 't');
        $oldShmId = shmop_open($shmKey, "w", 0644, strlen($this->shareMemoryInitString));
        if($oldShmId){
            shmop_delete($oldShmId);
            shmop_close($oldShmId);
        }
        $this->shmId = shmop_open($shmKey, "c", 0644, strlen($this->shareMemoryInitString));
        shmop_write($this->shmId, $this->shareMemoryInitString, 0);
    }

    //
    public function initServerSocket( $ip, $port )
    {
        $hostSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!$hostSocket)
            die("Failed of socket_create: ". socket_strerror(socket_last_error())."\n");

        if(! socket_set_option($hostSocket, SOL_SOCKET, SO_REUSEADDR, 1) )
            die("Failed of socket_set_option: ". socket_strerror(socket_last_error())."\n");

        if(! socket_bind($hostSocket,$ip,(integer)$port) )
            die("Failed of socket_bind: ". socket_strerror(socket_last_error())."\n");

        if(! socket_listen($hostSocket,10) )
            die("Failed of socket_listen: ".socket_strerror(socket_last_error())."\n");

        socket_set_nonblock($hostSocket);

        $this->hostSocket = $hostSocket;
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
            if($input==self::SESSION_END) {
                shmop_write($this->shmId, "[Server Over]\n", 0);
                break;
            }
            echo $input="[Server]:$input";
            shmop_write($this->shmId,$input.'*', 0);
        }
    }

    //
    public function listenSocket()
    {
        $write=[];
        while (true) {
            $readFds = array_merge($this->clientSocketPool,[$this->hostSocket]);
            $ready = socket_select($readFds, $write, $except = NULL, 0);
            if( $ready > 0 ){
                $this->checkNewClient();
                $this->checkOldClient();
            }
            $this->serverMessage();
            usleep(self::CHECK_TIME);
        }
    }

    //
    public function checkNewClient()
    {
        $newClientSocket=socket_accept($this->hostSocket);
        if(is_resource($newClientSocket)){
            socket_set_nonblock($newClientSocket);
            $this->clientSocketPool[$this->clientKey]=$newClientSocket;
            echo $serverMessage="[$this->clientKey]: [Session In]\n";
            $this->clientBroadcast($serverMessage);
            $this->clientKey++;
        }
    }

    //
    public function checkOldClient()
    {
        foreach($this->clientSocketPool as $key=>$readSocket){
            $inputStr=$this->socketReadAll($readSocket);
            if($inputStr){
                if($inputStr===self::SESSION_END){
                    $inputStr="[Session Out]\n";
                    socket_close($this->clientSocketPool[$key]);
                    unset($this->clientSocketPool[$key]);
                }
                echo $serverMessage="[$key]: $inputStr";
                $this->clientBroadcast($serverMessage);
            }
        }
    }

    //
    public function serverMessage()
    {
        $serverMessage = shmop_read($this->shmId, 0, $this->shmSize);
        if($serverMessage!=$this->shareMemoryInitString){
            shmop_write($this->shmId, $this->shareMemoryInitString, 0);
            $serverMessage=explode('*',$serverMessage);
            array_pop($serverMessage);
            $serverMessage=implode('*',$serverMessage);
            $this->clientBroadcast($serverMessage);
        }
    }

    // 客户端广播
    public function clientBroadcast($inputStr,$except=null)
    {
        foreach($this->clientSocketPool as $writeSocket){
            if($writeSocket!=$except){
                socket_write($writeSocket, $inputStr,strlen($inputStr));
            }
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

    //
    public function closeServer()
    {
        socket_close($this->hostSocket);
        unset($this->clientSocketPool);
    }

}

//error_reporting(E_ERROR | E_WARNING | E_PARSE);

$ws=new Server();
//echo "please input [ip:port] : ";
//$in=fgets(STDIN);
//echo "Server Start: ( input \":q\" to quit ) \n";
//$in=explode(':',$in);
$ip='127.0.0.1';
$port=10010;
$ws->run($ip,$port);