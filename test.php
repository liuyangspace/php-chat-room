<?php
//定义ticks
declare(ticks=1);

//产生子进程分支
$pid = pcntl_fork();
if ($pid == -1) {
    die("could not fork"); //pcntl_fork返回-1标明创建子进程失败
} else if ($pid) {
    echo "childId:$pid\n";
    if($in=fgets(STDIN)){
        echo "parent:$in";
        posix_kill($pid,SIGTERM);echo "sendSig\n";
        exit();
    }
    //父进程中pcntl_fork返回创建的子进程进程号
} else {
    // 子进程pcntl_fork返回的时0
    // 安装信号处理器
    $sig_handler=function($signo)
    {

        switch ($signo) {
            case SIGTERM:
                // 处理中断信号
                echo "getSig!\n";
                exit;
                break;
            case SIGHUP:
                // 处理重启信号
                break;
            default:
                // 处理所有其他信号
        }

    };
    pcntl_signal(SIGTERM, $sig_handler);
    pcntl_signal(SIGHUP, $sig_handler);

// 执行无限循环任务
    echo 'sessionId:'.posix_getpid()."\n";
    while (1) {
        echo "child run\n";
        // do something interesting here
        sleep(2);

    }


}

// 从当前终端分离
//if (posix_setsid() == -1) {
//    die("could not detach from terminal");
//}




