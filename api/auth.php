<?php
require_once(__DIR__.'/../bootstrap/app.php');

use App\Controllers\AuthController;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    // login params
    $login = $input_data['email'] ?? null;
    $password = $input_data['password'] ?? null;

    //main programs
    if (empty($login)) {
        $response = array(
            "user" => NULL,
            "status" => "error",
            "message" => "Authentication error, missing login",
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } 
    elseif (empty($password)) {
        $response = array(
            "user" => NULL,
            "status" => "error",
            "message" => "Authentication error, missing passowrd",
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        $encrypt_mdp = base64_encode($password);
        //launch mode
        $loggedIn = AuthController::auth($login, $encrypt_mdp, $conn);
        if (empty($loggedIn)) {
            $response = array(
                "user" => NULL,
                "status" => "error",
                "message" => "Authentification failed, no matching credentials"
            );
            
            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            $response = array(
                "user" => $loggedIn,
                "status" => "success",
                "message" => "Authentification success"
            );
            
            header("HTTP/1.1 202 Accepted");
            echo json_encode($response);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";
            
    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "user" => NULL,
        "status" => "error",
        "message" => "Not authorized"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
