<?php
require __DIR__ . '/vendor/autoload.php';
class GoogleDriveAPI
{
    private $client;
    private $service;
    private $tokenPath;
    public function __construct($credentialsPath, $tokenPath)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Drive API PHP');
        $this->client->setScopes(Google_Service_Drive::DRIVE);
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->tokenPath = $tokenPath;

        // Carregar token se existir
        $this->loadToken();
    }
    public function loadToken()
    {
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);

            // Refresh the token if expired
            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
            }
        }
    }
    public function getAuthUrl()
    {
        $url = $this->client->createAuthUrl();
        return $url;
        var_dump($url);
        exit;
    }
    public function listAllFolders()
    {
        $this->service = new Google_Service_Drive($this->client);
        $query = "mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        $response = $this->service->files->listFiles(['q' => $query]);

        $folders = [];
        foreach ($response->getFiles() as $folder) {
            $folders[] = ['name' => $folder->getName(), 'id' => $folder->getId()];
        }

        return $folders;
    }
    public function authenticate($authCode)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        $this->client->setAccessToken($accessToken);
        file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
    }

    public function listFilesInFolder($folderId)
    {
        $this->service = new Google_Service_Drive($this->client);
        $query = "'$folderId' in parents and trashed = false";
        $response = $this->service->files->listFiles(['q' => $query]);

        $files = [];
        foreach ($response->getFiles() as $file) {
            $files[] = ['name' => $file->getName(), 'id' => $file->getId()];
        }

        return $files;
    }

    // Função para fazer o upload do arquivo no Google Drive
    public function uploadFileToFolder($fileContent, $fileExtension, $folderId)
    {
        $this->service = new Google_Service_Drive($this->client);
        $fileName = $fileExtension;
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $fileName,
            'parents' => [$folderId]
        ]);
        $file = $this->service->files->create($fileMetadata, [
            'data' => $fileContent,
            'mimeType' => $this->getMimeTypeFromContent($fileContent),
            'uploadType' => 'multipart'
        ]);

        return $file->getId();
    }
    public function getMimeTypeFromContent($fileContent) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent); // Obtém o tipo MIME a partir do conteúdo binário
        return $mimeType;
    }
    public function deleteFile($fileId)
    {
        $this->service = new Google_Service_Drive($this->client);
        $this->service->files->delete($fileId);
    }
}
