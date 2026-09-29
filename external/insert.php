<?php
require_once '../bootstrap/app.php';


use App\Controllers\AssuranceController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;

use App\Providers\CityProvider;
use App\Providers\DeliveryDestinationProvider;
use App\Providers\TimeProvider;

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


    $ip = $_SERVER['REMOTE_ADDR'];

    /**
     * provide if the lead is deliverable direclty has value
     */


    $deliverable = $input_data['lead_type'] ?? null;

    /**
     * provide CITY location
     */

    if (isset($input_data['city']) && ! empty($input_data['city'])) {
        $city = $input_data['city'];
    } else if (isset($input_data['zipcode']) && ! empty($input_data['zipcode'])) {
        $city = CityProvider::check_city($input_data['zipcode']);
    } else {
        $city = null;
    }


    /**
     * create login DATA payload
     */


    $login_data = LoginController::makeLoginData($input_data);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data, $ip, $city);



    //main programs
    if (
        empty($login_data['partname'])
        or empty($login_data['token'])
        or (empty($input_data['firstname']) && empty($input_data['lastname']))
        or (empty($input_data['phone']))
        or (empty($input_data['zipcode']))
        or (empty($input_data['email']))
        or (empty($input_data['id_base']))
        or (empty($deliverable))
    ) {
        $response = array(
            "id"       => null,
            "status"   => "error",
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "leadType" => $deliverable,
            "authData" => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == false) {
            $response = array(
                "id"       => null,
                "status"   => "error",
                "message"  => "Invalid Token or partner",
                "authData" => $login_data,
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            // save lead info to DB leads table
            $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
            // append last insert ID to the lead payload because we may need it in direct delivery mode.
            $lead["lead_id"] = $last_insert_id;

            /**
             * save lead tags specifics data to the corresponding tags table
             * $partners variable is from bootstrap app imported in the top.
             */
            if (in_array($login_data['partname'], $partners["travaux"])) {
                /**
                 * create lead travaux DATA payload
                 * then save travaux data to table travaux of the database.
                 */
                $travaux_data = TravauxController::makeTravauxData($input_data);
                TravauxController::save_travaux(
                    $conn, $login_data, $lead, $travaux_data, $partners["travaux_kontiki_ID"][$login_data['partname']]
                );
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $travaux_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then save defisc data to the "defiscalisation" table of the DB.
                 */
                $defisc_data = DefiscController::makeDefiscData($input_data);
                DefiscController::save_defisc($conn, $login_data, $lead, $defisc_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $defisc_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead assurance DATA payload
                 * then save the payload to the "assurances" table of the DB.
                 */
                $assurances_data = AssuranceController::makeAssuranceData($input_data);
                AssuranceController::save_assurance($conn, $login_data, $lead, $assurances_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $assurances_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead security DATA payload
                 * then save the payload to the "securities" table of the DB.
                 */
                $security_data = SecurityController::makeSecurityData($input_data);
                SecurityController::save_security($conn, $login_data, $lead, $security_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $security_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formation DATA payload
                 * then save the payload to the "formations" table of the DB.
                 */
                $formation_data = FormationController::makeFormationData($input_data);
                FormationController::save_formation($conn, $login_data, $lead, $formation_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $formation_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead rachat de credits DATA payload
                 * then save the payload to the "rachat_de_credits" table of the DB.
                 */
                $rac_data = RachatCreditController::makeRacData($input_data);
                RachatCreditController::save_rac($conn, $login_data, $rac_data, $lead);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $rac_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            }

            if ($last_insert_id) {

                // close the connexion
                $conn->close();

                // return message
                $response = array(
                    "id"      => $last_insert_id,
                    "status"  => "success",
                    "message" => "Lead received successfully.",
                );

                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                // return message
                $response = array(
                    "id"      => null,
                    "status"  => "error",
                    "message" => "Error on saving lead.",
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
        "id"      => null,
        "status"  => "error",
        "message" => "Something went wrong, invalid request method",
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}