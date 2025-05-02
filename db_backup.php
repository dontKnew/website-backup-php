<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . "/config.php";
require_once __DIR__.'/Drive.php';
use w3lifer\Google\Drive;
try {
    if (!DATABASE_BACKUP) {
        logs("DB Backup is disabled");
        exit(1);
    }

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
            logs("Command failed to execute.");
        } elseif (stripos($output, 'error') !== false || stripos($output, 'failed') !== false) {
            logs("Command ran but returned an error: " . $output);
        } else {
            $hasBackup = true;
        }
    }

    if ($hasBackup && EXPORT_DATABASE_TO_GOOGLE_DRIVE) {
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
                exit(1);
            } else {
                logs("DB Uploaded: $fileId", "INFO");
                if(LOCAL_DELETE_DATABASE_AFTER_EXPORT_TO_GOOGLE_DRIVE){
                    unlink($dir);
                }
            }
        } else {
            logs("$dir not found");
        }
    }
} catch (Exception $e) {
    logs("DB :".$e->getMessage() . "- Line " . $e->getLine() . " Path " . $e->getFile());
    exit(1);
}
