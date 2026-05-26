<?php

namespace App\Providers;

use phpseclib3\Net\SFTP;

class SftpUploaderProvider
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $remotePath;

    public function __construct($host, $port, $username, $password, $remotePath)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->remotePath = $remotePath;
    }

    public function uploadFile($localPath, $fileName)
    {
        $sftp = new SFTP($this->host, $this->port);
        
        if (!$sftp->login($this->username, $this->password)) {
            throw new \Exception("SFTP login failed.");
        }
        
        $remoteFile = $this->remotePath . '/' . $fileName;
        
        if ($sftp->file_exists($remoteFile)) {
            // File already exists, delete it before uploading
            $sftp->delete($remoteFile);
        }
        
        if (!$sftp->put($remoteFile, $localPath.$fileName, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \Exception("File upload failed.");
        }
    }
}
