<?php

$shm_key = ftok(__FILE__, 't');
$shm_id = shmop_open($shm_key, "c", 0644, 100);
if (!$shm_id)  echo "Couldn't create shared memory segment\n";
$shm_size = shmop_size($shm_id); echo "SHM Block Size: " . $shm_size . " has been created.\n";

$shm_bytes_written = shmop_write($shm_id, "", 0);
//if ($shm_bytes_written != strlen("")) {
//    echo "Couldn't write the entire length of data\n";
//}

// Now lets read the string back
$my_string = shmop_read($shm_id, 0, $shm_size);
//if (!$my_string) {
//    echo "Couldn't read from shared memory block\n";
//}
echo "The data inside shared memory was: " . $my_string . "\n";exit;

$pid = pcntl_fork();
if ($pid == -1) {
    exit("could not fork"); //pcntl_fork返回-1标明创建子进程失败
} else if ($pid) {// 父进程;
    while(true){
        $input=fgets(STDIN);
        $shm_bytes_written = shmop_write($shm_id, $input, 0);echo "parenIn:$input\n";
        if ($shm_bytes_written != strlen("my shared memory block")) {
            echo "Couldn't write the entire length of data\n";
        }
    }
    //$this->listenTerminal();
    posix_kill($pid,9);
} else {
    while(true){
        $my_string = shmop_read($shm_id, 0, $shm_size);
        if (!$my_string) {
            echo "Couldn't read from shared memory block\n";break;
        }else{
            echo "childOut:$my_string\n";
        }
        sleep(3);
    }
}


//Now lets delete the block and close the shared memory segment
if (!shmop_delete($shm_id)) {
    echo "Couldn't mark shared memory block for deletion.";
}
shmop_close($shm_id);