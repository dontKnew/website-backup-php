<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . "/config.php";
require_once __DIR__.'/Drive.php';
use w3lifer\Google\Drive;
try {

    $user = escapeshellcmd(DB_USER);
    $pass = escapeshellcmd(DB_PASSWORD);
    $host = escapeshellcmd(DB_HOST);
    $database = escapeshellcmd(DB_NAME);
    $filename = escapeshellcmd(DB_NAME . "_" . date("Y-m-d") . ".sql");
    $dir = escapeshellcmd(BACKUP_PATH . "/" . $filename);
    $command = "mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1";
    $hasBackup = false;
    if (DB_RUN_TYPE == "exec") {
        if (in_array('exec', explode(',', ini_get('disable_functions')))) {
            logs("exec function is disabled.");
            exit(1);
        }
        exec($command, $output, $return_var);
        if ($return_var !== 0) {
            logs($output);
        } else {
            $hasBackup = true;
        }
    }
    if (DB_RUN_TYPE == "shell_exec") {
        if (in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            logs("shell_exec function is disabled");
            exit(1);
        }
        $output = shell_exec($command);
        if ($output === null) {
            logs($output);
        } else {
            $hasBackup = true;
        }
    }

    if ($hasBackup) {
        if (file_exists($dir)) {
            logs("DB Backup Created : ". $filename . "(". getFileSize($dir) . ")", "INFO");
            if (empty(GOOGLE_SERVICE_ACCOUNT_JSON)) {
                logs("DB:GOOGLE_SERVICE_ACCOUNT_JSON is not set");
                exit(1);
            }
            $config = ['pathToCredentials' => GOOGLE_SERVICE_ACCOUNT_JSON];
            $drive = new Drive($config);
            $fileId = $drive->upload($dir, [GOOGLE_DRIVE_FOLDER_ID]);
            if (empty($fileId)) {
                logs("DB Upload To Google Drive Failed");
                unlink($dir);
                exit(1);
            } else {
                logs("DB Uploaded: $fileId", "INFO");
            }
        } else {
            logs("$dir not found");
        }
    }
} catch (Exception $e) {
    logs("DB :".$e->getMessage() . "- Line " . $e->getLine() . " Path " . $e->getFile());
    exit(1);
}
