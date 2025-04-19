<?php 
require_once __DIR__."/config.php";

$user = escapeshellcmd(DB_USER);
$pass = escapeshellcmd(DB_PASSWORD);
$host = escapeshellcmd(DB_HOST);
$database = escapeshellcmd(DB_NAME);
$filename = escapeshellcmd(DB_NAME."_".date("Y-m-d").".sql");
$dir = escapeshellcmd(BACKUP_PATH."/".$filename);
$command = "mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1";
if(DB_RUN_TYPE=="exec"){
    // is disable function 
    if (in_array('exec', explode(',', ini_get('disable_functions')))) {
        echo "Error: exec function is disabled.\n";
        exit(1);
    }
    exec($command, $output, $return_var);
    if($return_var !== 0) {
        echo "Error: $output\n";
    } else {
        echo  $filename . "\n";
    }
}
if(DB_RUN_TYPE=="shell_exec"){
    if (in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        echo "Error: shell_exec function is disabled.\n";
        exit(1);
    }
    $output = shell_exec($command);
    if($output === null) {
        echo "Error: $output\n";
    } else {
        echo  $filename . "\n";
    }
}



