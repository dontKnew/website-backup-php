<?php 
require_once __DIR__."/config.php";

keepSomeSQLFiles(BACKUP_PATH, KEEP_LAST_SQL);
keepSomeZipFiles(BACKUP_PATH, KEEP_LAST_ZIP);

$folder_excluded = FOLDER_EXCLUDE; 
$destination_filename = ZIP_FILE_NAME . "_" . date("Y-m-d") . ".zip";
$destination =BACKUP_PATH . "/" . $destination_filename;
$file_paths =FILE_PATH; 

$response = zipDirectory($file_paths, $destination, $folder_excluded);
if($response) {
    echo $destination_filename . " Response ".$response;
} else {
    echo "Error: $response";
}

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


function deleteOldFiles($backupFolder, $extension, $keepCount) {
    $files = glob($backupFolder . '/*.' . $extension);
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $filesToDelete = array_slice($files, $keepCount);
    foreach ($filesToDelete as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "Deleted: " . basename($file) . "\n";
        }
    }
}

function keepSomeSQLFiles($backupFolder, $keepCount) {
    deleteOldFiles($backupFolder, 'sql', $keepCount);
}

function keepSomeZipFiles($backupFolder, $keepCount) {
    deleteOldFiles($backupFolder, 'zip', $keepCount);
}
?>


