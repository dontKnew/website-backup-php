<?php 
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . "/config.php";
require_once __DIR__.'/Drive.php';
use w3lifer\Google\Drive;
try {    
    if (!FILE_BACKUP) {
        logs("File Backup is disabled");
        exit(1);
    }
    $folder_excluded = FOLDER_EXCLUDE; 
    $destination_filename = ZIP_FILE_NAME . "_" . date("Y-m-d") . ".zip";
    $destination = BACKUP_PATH . "/" . $destination_filename;
    $file_paths = FILE_PATH; 
    $hasBackup = false;
    $response = zipDirectory($file_paths, $destination, $folder_excluded);
    if($response) {
        $hasBackup = true;
    } else {
        logs($response);
    }
    if ($hasBackup && EXPORT_FILE_TO_GOOGLE_DRIVE) {
        if (file_exists($destination)) {
            logs("Files Backup Created : ". $destination_filename . "(". getFileSize($destination) . ")", "INFO");
            if (empty(GOOGLE_SERVICE_ACCOUNT_JSON)) {
                logs("FileGoogleDrive-Error: GOOGLE_SERVICE_ACCOUNT_JSON is not set");
                exit(1);
            }
            $config = ['pathToCredentials' => GOOGLE_SERVICE_ACCOUNT_JSON];
            $drive = new Drive($config);
            $fileId = $drive->upload($destination, [GOOGLE_DRIVE_FOLDER_ID]);
            if (empty($fileId)) {
                logs("File Upload To Google Drive Failed");
            } else {
                logs("File Uploaded : $fileId", "INFO");
                if(LOCAL_DELETE_FILE_AFTER_EXPORT_TO_GOOGLE_DRIVE){
                    unlink($destination);
                }
            }
        } else {
            logs("$destination not found");
        }
    }

} catch (Exception $e) {
    logs($e->getMessage() . "- Line " . $e->getLine() . " Path " . $e->getFile());
    exit(1);
}

?>


