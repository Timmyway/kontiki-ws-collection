<?php

use App\Controllers\AssuranceAutoController;
use App\Services\SendFinancesService;
require_once('../bootstrap/app.php');

use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\LoginController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\StatisticsController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;
use App\Providers\TimeProvider;
use App\Services\SendAssuranceAutoService;
use App\Services\SendAssuranceService;
use App\Services\SendTravauxService;
use App\Services\SendDefiscService;
use App\Services\SendFormationService;
use App\Services\SendSecurityService;

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

    /** 
     * SPECIAL input_DATA => CLIENTS ID given by the Frontend.
     */
    $clientID = $input_data['clientID'] ?? null;

    //main programs
    if (empty($login_data['partname'])
        or empty($login_data['token'])
        or empty($clientID)
        or empty($lead['lead_id'])
        or (empty($lead['phone']) && empty($input_data['securities']['canal']))
        or (empty($lead['zipcode']) && empty($input_data['securities']['canal']))
        or (empty($lead['email']) && empty($input_data['securities']['canal']))
    ) {
        $response = array(
            "id"       => NULL,
            "status"   => "error",
            "success"  => FALSE,
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "login"    => $login_data
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

            /**
             * send all information to the client according to it's tags and mandatory
             * $partners variable is from bootstrap app imported in the top.
             */
            if (in_array($login_data['partname'], $partners["travaux"])) {
                /**
                 * create lead travaux DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $travaux_data    = TravauxController::makeTravauxData($input_data['travaux']);
                $lead_properties = new SendTravauxService($lead, $travaux_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
               
                $defisc_data     = DefiscController::makeDefiscData($input_data['defisc']);
                $lead_properties = new SendDefiscService($lead, $defisc_data, $clientID, TimeProvider::getTime());
                
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead insurances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                
                $insurance_data  = isset($input_data['insurances']) ? AssuranceController::makeAssuranceData($input_data['insurances']) : [];
                $lead_properties = new SendAssuranceService($lead, $insurance_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formations DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $formations_data = FormationController::makeFormationData($input_data['formations']);
                $lead_properties = new SendFormationService($lead, $formations_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead securities DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $security_data   = SecurityController::makeSecurityData($input_data['securities']);
                $lead_properties = new SendSecurityService($lead, $security_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $finances_data   = RachatCreditController::makeRacData($input_data['finances']);
                $lead_properties = new SendFinancesService($lead, $finances_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $assurance_auto_data   = AssuranceAutoController::makeData($input_data['cars']);
                $lead_properties = new SendAssuranceAutoService($lead, $assurance_auto_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $assurance_data   = AssuranceSanteController::makeData($input_data['insurances']);
                $lead_properties = new SendAssuranceService($lead, $assurance_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            }

            /**
             * SAVE CLIENTS responses into the DB after sending the lead.
             */
            LeadsController::save_leads_has_clients($conn, TimeProvider::getTime(), $login_data['partname'], $lead['lead_id'], $send_response, $clients[$clientID]);
            StatisticsController::setStat($conn, date('Y-m'));

            /**
             * Returning RESPONSE STATUS to the Frontend.
             */
            if ($send_response['status'] != 'success') {

                // close the connexion
                $conn->close();

                $response = array(
                    "id"      => $lead['lead_id'],
                    "status"  => "error",
                    "message" => 'Error when sending lead.. ' . $send_response['description']
                );

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                $response = array(
                    "id"      => $lead['lead_id'],
                    "status"  => "success",
                    "message" => "Lead sent Successfully.."
                );

                header("HTTP/1.1 202 Accepted");
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
