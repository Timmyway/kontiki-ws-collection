<?php 
require_once('../bootstrap/app.php');

use App\Providers\SftpUploaderProvider;

function upload($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path)
{
    try {
        $sftp_uploader = new SftpUploaderProvider($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path);
        $local_path = '../storages/app/energy/eni/';
        // $date = date('d-m-Y', strtotime('-1 day'));
        $date = date('d-m-Y');
        // $date = date('d-m-Y');
        $local_file = 'eni_leads_' . $date . ".xlsx";
        if (file_exists($local_path.$local_file)) {
            $sftp_uploader->uploadFile($local_path, $local_file);
            return "File uploaded successfully.";
        } else {
            return "No such file to upload today.";
        }
    } catch (\Throwable $th) {
        return "Error: " . $th;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $eni_sftp_host = $sftp["eni"]["host"];
    $eni_sftp_username = $sftp["eni"]["username"];
    $eni_sftp_password = $sftp["eni"]["password"];
    $eni_sftp_port = $sftp["eni"]["port"];
    $eni_sftp_upload_path = $sftp["eni"]["upload_path"];
    
    $eni_log = upload($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path);

    // log schedule
    $logPath = '../logs/eni/eni_logs_' . date('d-m-Y') . '.txt';
    if (file_exists($logPath)) {
        file_put_contents($logPath, $eni_log. " " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    } else {
        file_put_contents($logPath, $eni_log. " " . date('Y-m-d H:i:s') . "\n");
    }

    echo "Operation complete...\n$eni_log";
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";
            
    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status" => "error",
        "message" => "Something went wrong"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}