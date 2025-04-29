<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . "/config.php";
require_once __DIR__ . '/Drive.php';

use w3lifer\Google\Drive;

try {
    $config = ['pathToCredentials' => GOOGLE_SERVICE_ACCOUNT_JSON];
    $drive = new Drive($config);
    createFolder(DOWNLOAD_PATH);

    $import_path = "";
    if (IMPORT_DATABASE_FROM_GOOGLE_DRIVE) {
        if (empty(GOOGLE_DRIVE_DATABASE_ID)) {
            logs("Google Drive Database ID is empty");
            exit;
        }
        $import_path =  $drive->download(GOOGLE_DRIVE_DATABASE_ID, DOWNLOAD_PATH);
        logs("Downloaded Database From Google Drive" . basename($import_path) . "(" . getFileSize($import_path) . ")" , "INFO");;
    }

    if (IMPORT_DATABASE) {
        if (!file_exists($import_path)) {
            if(!IMPORT_DATABASE_FROM_GOOGLE_DRIVE){
                $import_path = IMPORT_DATABASE_PATH;
            }else{
                logs("Database SQL file does not exists");
                exit;
            }
        }

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_errno) {
            logs("Failed to connect to MySQL: " . $mysqli->connect_error);
            exit(1);
        }
        $result = $mysqli->query("SHOW TABLES");
        $number_rows = $result->num_rows ?? 0;
        if ($number_rows !== 0) {
            logs("Database must be empty, to import database");
            $mysqli->close();
            exit;
        }
        $mysqli->close();

        $user = escapeshellcmd(DB_USER);
        $pass = escapeshellcmd(DB_PASSWORD);
        $host = escapeshellcmd(DB_HOST);
        $database = escapeshellcmd(DB_NAME);
        $dir = $import_path;
        $command = "mysql --user={$user} --password={$pass} --host={$host} {$database} < {$dir} 2>&1";
        $hasImported = false;

        if (DB_RUN_TYPE == "exec") {
            if (in_array('exec', explode(',', ini_get('disable_functions')))) {
                logs("exec function is disabled.");
                exit(1);
            }
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                logs("Import failed: " . implode("\n", $output));
            } else {
                $hasImported = true;
            }
        }

        if (DB_RUN_TYPE == "shell_exec") {
            if (in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
                logs("shell_exec function is disabled");
                exit(1);
            }
            $output = shell_exec($command);
            if ($output === null) {
                logs("Import failed or no output");
            } else {
                $hasImported = true;
            }
        }

        if ($hasImported) {
            logs("SQL import successful.", "INFO");
        }
    }


    $import_path = "";
    if (IMPORT_FILE_FROM_GOOGLE_DRIVE) {
        if (empty(GOOGLE_DRIVE_FILE_ID)) {
            logs("Google Drive File Id is empty");
            exit;
        }
        $import_path =  $drive->download(GOOGLE_DRIVE_FILE_ID, DOWNLOAD_PATH);
        logs("Downloaded File From Google Drive" . basename($import_path) . "(" . getFileSize($import_path) . ")" , "INFO");;
        exit;
    }



    if (EXTRACT_GOOGLE_DRIVE_FILE) {
        logs("Extracting Method Not Implemented");
    }
} catch (Exception $e) {
    logs("DB :" . $e->getMessage() . "- Line " . $e->getLine() . " Path " . $e->getFile());
    exit(1);
}
