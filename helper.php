<?php 
function zipDirectory($source, $destination, $excludedFolders = []) {
    if (!extension_loaded('zip')) {
        return 'Zip extension is not loaded.';
    }
    if (!file_exists($source)) {
        return $source.' - directory does not exist.';
    }
    if(file_exists($destination)){
        if (!unlink($destination)) {
            return "Unable to delete the old zip file.";
        }
    }
    $zip = new ZipArchive();
    if ($zip->open($destination, ZipArchive::CREATE) !== true) {
        return 'Unable to create zip file at the destination path.';
    }

    $source = realpath($source);
    $excludedFolders = array_filter(array_map('realpath', $excludedFolders));

    // Helper: check if a path is in excluded list
    $isExcluded = function ($path) use ($excludedFolders) {
        foreach ($excludedFolders as $excluded) {
            if ($excluded && strpos($path, $excluded) === 0) {
                return true;
            }
        }
        return false;
    };

    if (is_dir($source)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $fullPath = $file->getPathname();

            // Skip symlinks or unreadable paths
            if (!$file->isReadable() || is_link($fullPath)) {
                continue;
            }

            $realPath = realpath($fullPath);
            if (!$realPath || $isExcluded($realPath)) {
                continue;
            }

            $relativePath = ltrim(str_replace($source, '', $realPath), DIRECTORY_SEPARATOR);

            if ($file->isDir()) {
                if (!$zip->addEmptyDir($relativePath)) {
                    return "Unable to add directory: $realPath Relative Path: $relativePath";
                }
            } elseif ($file->isFile()) {
                if (!$zip->addFile($realPath, $relativePath)) {
                    return "Unable to add file: $realPath";
                }
            }
        }
    } elseif (is_file($source)) {
        if (!$zip->addFile($source, basename($source))) {
            return 'Unable to add file: ' . $source;
        }
    }

    if (!$zip->close()) {
        return 'Unable to close the zip file properly.';
    }
    return true;
}


function deleteOldFiles($backupFolder, $extension, $daysToKeep) {
    // $files = glob($backupFolder . '/*.' . $extension);
    // $now = time();
    // $secondsToKeep = $daysToKeep * 86400; 
    // $fileToDelete = [];
    // foreach ($files as $file) {
    //     if (is_file($file)) {
    //         $filemtime = filemtime($file);

    //         if (($now - $filemtime) > $secondsToKeep) {
    //             $fileToDelete[] = $file;
    //         }
    //     }
    // }    
    // exit;
}

function keepSomeSQLFiles($backupFolder, $keepCount) {
    deleteOldFiles($backupFolder, 'sql', $keepCount);
}

function keepSomeZipFiles($backupFolder, $keepCount) {
    deleteOldFiles($backupFolder, 'zip', $keepCount);
}

function logs($message, $type="Error",  $arr=[]){
    $logFile = __DIR__.'/backup.log';
    $date = date('Y-m-d H:i:s');
    $message = "$date [$type] : $message\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    if($arr) {
        file_put_contents($logFile, print_r($arr, true), FILE_APPEND);
    }
}
function humanFileSize($bytes, $decimals = 2) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
}

function getFileSize($file_path) {
    if (file_exists($file_path)) {
        return humanFileSize(filesize($file_path));
    }
    return 0;
}

function createFolder($backup_path){
    if (!file_exists($backup_path)) {
        mkdir($backup_path, 0777, true);  
    }
}