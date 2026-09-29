<?php

use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Providers\ExcelCreatorProvider;
use App\Providers\ExcelWriterProvider;

require_once('../bootstrap/app.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data);

    //main programs
    if (empty($login_data['partname'])
        or empty($login_data['token'])
        or empty($input_data['export_criteria'])
        or empty($input_data['export_reason'])
        or empty($input_data['export_label'])
    ) {
        $response = array(
            "id"              => NULL,
            "status"          => "error",
            "success"         => FALSE,
            "message"         => "Missing parameters",
            "export_reason"   => $input_data['export_reason'],
            "export_criteria" => $input_data['export_criteria'],
            "login"           => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            // Provide filepath.
            $filepath_main_title = $input_data['export_reason'] . '_leads_' . $input_data['export_label'] . '_';
            if ($input_data['export_criteria']["monthly"] === true) {
                $filepath_second_part = $input_data['export_criteria']["month_rank"] . '_' . $input_data['export_criteria']["year"] . ".xlsx";
            } else {
                $filepath_second_part = $input_data['export_criteria']["start_date"] . '_' . $input_data['export_criteria']["end_date"] . ".xlsx";
            }
            $filepath = '../storages/app/exports/' . $filepath_main_title . $filepath_second_part;

            // check if file already exist then delete it and re-download it.
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            // Proceed with the export.
            $export_data = LeadsController::downloadable_leads($login_data['partname'], $input_data['export_reason'], $input_data['export_criteria'], $conn);
            if ($export_data['rows']) {
                $ExcelCreatorProvider = new ExcelCreatorProvider($filepath, $export_data['headers']);
                $ExcelCreatorProvider->createFile();

                $ExcelWriterProvider = new ExcelWriterProvider($filepath);
                foreach ($export_data['rows'] as $row) {
                    $ExcelWriterProvider->appendRow(array_values($row));
                    $ExcelWriterProvider->save();
                }

                header("HTTP/1.1 202 Accepted");
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filepath_main_title . $filepath_second_part . '"');
                $ExcelWriterProvider->download();
            } else {
                header("HTTP/1.1 406 Not Acceptable");
                $response = array(
                    "status"  => "error",
                    "message" => "No such data to export.."
                );
                echo json_encode($response);
            }
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}