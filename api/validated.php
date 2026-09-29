<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Controllers\LeadsController;



if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($_GET);
    /**
     * get page limit
     */
    $page_limit = isset($_GET['limit']) && $_GET['limit'] !== 'undefined' ? $_GET['limit'] : 50;
    /**
     * get page offset
     */
    $page_offset = isset($_GET['offset']) && $_GET['offset'] !== 'undefined' ? $_GET['offset'] : 0;

    if (empty($login_data['partname']) or empty($login_data['token'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => $login_data
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
            $res = LeadsController::get_leads_has_clients($login_data['partname'], $page_offset, $page_limit, $conn);
            if (! $res) {
                header("HTTP/1.1 200 OK");
                echo "Get Nothing, maybe there is an errors.. try again";
            } else {
                header("HTTP/1.1 200 OK");
                echo $res;
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