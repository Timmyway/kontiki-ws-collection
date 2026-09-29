<?php
// here you can find all availables
// necessary CONFIG.

$sftp = array(
    // sftp for eni/lead creative
    "eni" => array(
        "host" => $_ENV['SFTP_HOST'],
        "username" => $_ENV['SFTP_USER'],
        "password" => $_ENV['SFTP_PASS'],
        "port" => $_ENV['SFTP_PORT'],
        "upload_path" => $_ENV['SFTP_UPLOAD_PATH'],
        "download_path" => $_ENV['SFTP_DOWNLOAD_PATH'],
    )
);