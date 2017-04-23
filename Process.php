<?php
//定义ticks
declare(ticks=1);

//产生子进程分支
$pid = pcntl_fork();
if ($pid == -1) {
    die("could not fork"); //pcntl_fork返回-1标明创建子进程失败
} else if ($pid) {
    echo "p,$pid\n";
    sleep(5);
    //posix_kill($pid,9);
    posix_kill($pid, SIGUSR1);
    //exit(); //父进程中pcntl_fork返回创建的子进程进程号
} else {
    pcntl_signal(SIGUSR1, function($signo){
        echo $signo."\n";exit;
    });

    $pid=posix_getpid();
    echo "c,$pid\n";
    // 子进程pcntl_fork返回的时0
    while(true){
        echo "child\n";
        sleep(2);
    }
}