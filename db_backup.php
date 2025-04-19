<?php 
require_once __DIR__."/config.php";

$user = escapeshellcmd(DB_USER);
$pass = escapeshellcmd(DB_PASSWORD);
$host = escapeshellcmd(DB_HOST);
$database = escapeshellcmd(DB_NAME);
$filename = escapeshellcmd(DB_NAME."_".date("Y-m-d").".sql");
$dir = escapeshellcmd(BACKUP_PATH."/".$filename);
$command = "mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1";
exec($command, $output, $return_var);
if($return_var !== 0) {
    echo "Error: $output\n";
} else {
    echo  $filename . "\n";
}


