<?php
namespace w3lifer\Google;

use Exception;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class Drive
{
    private string $pathToCredentials;
    private Google_Client $client;
    private Google_Service_Drive $service;

    public function __construct($config)
    {
        if (
            !empty($config['pathToCredentials']) &&
            file_exists($config['pathToCredentials'])
        ) {
            $this->pathToCredentials = $config['pathToCredentials'];
        } else {
            throw new Exception('Incorrect path to credentials');
        }

        $this->client = new Google_Client();
        $this->client->setApplicationName('My Drive App');
        $this->client->setAuthConfig($this->pathToCredentials);
        $this->client->setScopes(['https://www.googleapis.com/auth/drive']);
        $this->client->useApplicationDefaultCredentials();

        $this->service = new Google_Service_Drive($this->client);
    }

    public function upload(string $pathToFile, array $folderIds = []): string
    {
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => basename($pathToFile),
            'parents' => $folderIds,
        ]);

        $result = $this->service->files->create(
            $fileMetadata,
            [
                'data' => file_get_contents($pathToFile),
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'multipart',
            ]
        );

        return $result->id;
    }

    public function createFolder(string $name, array $parents = []): string
    {
        $file = new Google_Service_Drive_DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parents
        ]);

        $result = $this->service->files->create($file);
        return $result->id;
    }

    public function getFile($fileId){
        $file = $this->service->files->get($fileId, ['fields' => 'id, name']);
        return $file;
    }
    
    public function download(string $fileId, string $saveDir): string
    {
        $file = $this->service->files->get($fileId, ['fields' => 'name']);

        $saveDir = rtrim($saveDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fullPath = $saveDir . $file->getName();
        if(file_exists($fullPath)) {
            unlink($fullPath);
        }
        $response = $this->service->files->get($fileId, ['alt' => 'media']);
        $handle = fopen($fullPath, 'w');
        while (!$response->getBody()->eof()) {
            fwrite($handle, $response->getBody()->read(1024 * 1024)); // 1MB chunks
        }
        fclose($handle);
    
        return $fullPath;
    }    

    public function downloadLastSqlFile(string $saveDir): ?string
    {
        $response = $this->service->files->listFiles([
            'q' => "name contains '.sql' and mimeType != 'application/vnd.google-apps.folder'",
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 1,
            'fields' => 'files(id, name)'
        ]);
        $files = $response->getFiles();
        if (empty($files)) {
            return null; 
        }
        $latestFile = $files[0];
        return $this->download($latestFile->getId(), $saveDir);
    }


}
