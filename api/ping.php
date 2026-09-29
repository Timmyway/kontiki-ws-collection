<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Services\PingService;

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

    /** 
    * create ping DATA payload
    */
    $ping_data = array(
        "zipcode" => $input_data['zipcode'] ?? null,
        "city" => $input_data['city'] ?? null
    );
    
    /** 
    * SPECIAL input_DATA => CLIENTS ID given by the Frontend.
    */
    $clientID = $input_data['clientID'] ?? null;

    //main programs
    if (empty($clientID) or empty($login_data['partname']) or empty($login_data['token']) or empty($ping_data['zipcode']) or empty($ping_data['city'])) {
        $response = array(
            "id" => NULL,
            "status" => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data" => $input_data
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status" => "error",
                "message" => "No maching 'fournisseurs'",
                "data" => $login_data
            );
            
            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            // ping response response
            $ping_properties = new PingService($ping_data, $clientID);
            $ping_status = $ping_properties->ping();

            if($ping_status){
                $response = array(
                    "ping" => true,
                    "status" => "success",
                    "message" => "pre-matching success.."
                );
                
                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {
                $response = array(
                    "ping" => false,
                    "status" => "error",
                    "message" => 'pre-matching error, departement not allowed at this time'
                );
                
                header("HTTP/1.1 406 Not Acceptable");
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
        "status" => "error",
        "message" => "Something went wrong"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}