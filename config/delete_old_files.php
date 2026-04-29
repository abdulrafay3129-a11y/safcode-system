<?php
include(__DIR__ . "/init.php");

checkRole(['admin']);

$log_folder = __DIR__ . "/../logs/";

if(is_dir($log_folder)){

    $files = scandir($log_folder);
    $now = time();
    $deleted = 0;

    foreach($files as $file){

        if($file == '.' || $file == '..') continue;

        $file_path = $log_folder . $file;

        if(is_file($file_path)){

            $file_time = filemtime($file_path);

            if(($now - $file_time) > 15 * 24 * 60 * 60){
                unlink($file_path);
                $deleted++;
            }
        }
    }
}

header("Location: ../admin/dashboard.php?msg=deleted");
exit;
?>