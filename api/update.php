<?php
require_once('../bootstrap/app.php');

use App\Controllers\AssuranceAutoController;
use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\LoginController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }
    
    /** 
     * provide IP ADDRESS
     */
    $ip = $input_data['classics']['ip'];
    $userAgent = $input_data['classics']['userAgent'] ?? null;
    $referer = $input_data['classics']['referer'] ?? null;
    /** 
     * provide CITY location
     */
    $city = (isset($input_data['classics']['city']) && ! empty($input_data['classics']['city']))
        ? $input_data['classics']['city']
        : CityProvider::check_city($input_data['classics']['zipcode']);

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data['login']);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data['classics'], $ip, $city, $userAgent, $referer);

    //main programs
    if (empty($login_data['partname']) or empty($login_data['token']) or empty($lead['lead_id'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => array(
                "login"   => $login_data['partname'],
                "token"   => $login_data['token'],
                "lead_id" => $lead['lead_id'],
            )
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
            // set lead call status to not callable
            $success_update_lead = LeadsController::update_lead($conn, $lead);

            if (! $success_update_lead) {
                $response = array(
                    "status"  => "error",
                    "message" => "Error when updating lead.. "
                );

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            } else {

                /**
                 * update lead tags specifics data to the corresponding tags table
                 * $partners variable is from bootstrap app imported in the top.
                 */
                if (in_array($login_data['partname'], $partners["travaux"])) {
                    $travaux_data        = TravauxController::makeTravauxData($input_data['travaux']);
                    $success_update_tags = TravauxController::update_travaux($conn, $lead, $travaux_data);
                    /**
                     * leads tag travaux updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                    $defisc_data         = DefiscController::makeDefiscData($input_data['defisc']);
                    $success_update_tags = DefiscController::update_defisc($conn, $lead, $defisc_data);
                    /**
                     * leads tag defisc updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                    $assurance_data      = isset($input_data['insurances']) ? AssuranceController::makeAssuranceData($input_data['insurances']) : [];
                    $success_update_tags = AssuranceController::update_assurance($conn, $lead, $assurance_data);
                    /**
                     * leads tag insurances updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["formations"])) {
                    $formations_data     = FormationController::makeFormationData($input_data['formations']);
                    $success_update_tags = FormationController::update_formation($conn, $lead, $formations_data);
                    /**
                     * leads tag formations updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["securities"])) {
                    $security_data       = SecurityController::makeSecurityData($input_data['securities']);
                    $success_update_tags = SecurityController::update_security($conn, $lead, $security_data);
                    /**
                     * leads tag securities updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["finances"])) {
                    $rac_data            = RachatCreditController::makeRacData($input_data['finances']);
                    $success_update_tags = RachatCreditController::update_rac($conn, $lead, $rac_data);
                    /**
                     * leads tag finances updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                    $assurance_auto_data = AssuranceAutoController::makeData($input_data['cars']);
                    $success_update_tags = AssuranceAutoController::update($conn, $lead, $assurance_auto_data);
                    /**
                     * leads tag assurance_auto updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                    
                    $assurance_data = AssuranceSanteController::makeData($input_data['insurances']);
                    $success_update_tags = AssuranceSanteController::update($conn, $lead, $assurance_data);
                    /**
                     * leads tag assurance updated.
                     */
                }


                if (! $success_update_tags) {

                    // close database connexion
                    mysqli_close($conn);

                    // return response
                    $response = array(
                        "status"  => "error",
                        "message" => "Error when updating table travaux data.. "
                    );

                    header("HTTP/1.1 406 Not Acceptable");
                    echo json_encode($response);
                } else {

                    // close database connexion
                    mysqli_close($conn);

                    // return response
                    $response = array(
                        "status"  => "success",
                        "message" => "Discard lead Success.."
                    );

                    header("HTTP/1.1 202 Accepted");
                    echo json_encode($response);
                }
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