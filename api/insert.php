<?php
require_once '../bootstrap/app.php';

use App\Controllers\AssuranceAutoController;
use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;
use App\Providers\DeliveryDestinationProvider;
use App\Providers\PartnerProvider;
use App\Providers\TimeProvider;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /**
     * provide IP ADDRESS and USER_AGENT
     */
    $ip        = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $referer   = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "Aucun referer";

    /**
     * send data to webservice DWH.
     * If this is a test, do not send the data to webservice DWH.
     */
    $ch = curl_init();

    $gender = [
        "mr"  => "M",
        "mme" => "F",
    ];
    $api_data = [
        "origine"      => "BUDGETDEVIS",
        "datecollecte" => date('Y-m-d H:i:s'),
        "ip"           => $ip,
        "urlcollecte"  => $referer,
        "email"        => $input_data['email'],
        "birthdate"    => isset($input_data['birthdate']) && ! empty($input_data['birthdate']) ? date("Y-m-d", strtotime($input_data['birthdate'])) : null,
        "mobile"       => substr(preg_replace('/[^\d]/', '', $input_data['phone']), 0, 2) == "33" ? '0' . substr(preg_replace('/[^\d]/', '', $input_data['phone']), 2) : preg_replace('/[^\d]/', '', $input_data['phone']),
        "civility"     => $gender[$input_data['civility']],
        "lastname"     => $input_data['lastname'],
        "firstname"    => $input_data['firstname'],
        "adresse1"     => $input_data['address'] ?? "",
        "zipcode"      => $input_data['zipcode'] ?? "",
        "city"         => $input_data['city'] ?? "",
        "country"      => "FR",
    ];

    $query_string = http_build_query($api_data);
    $url          = "https://api.kontikimedia.com:5007/api/collecte?" . $query_string;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: NemUO2X7D21QDwPFa2TCYeZIbDXNwsAKi8eftLN1epQULvwJE4zMo7AXBgAO1jo8SKJgu0c88EGsq7FVrzBp9CAoFNiCD6zf3PN1l3mL0qbMbJvSZE4VntoF3cyeLKbW']);
    curl_exec($ch);
    curl_close($ch);

    /**
     * provide if the lead is deliverable direclty has value
     */
    $deliverable = $input_data['deliverable'] ?? null;

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
    $lead = LeadsController::makeClassicsData($input_data, $ip, $city, $userAgent, $referer);

    //main programs
    if (
        empty($login_data['partname'])
        or empty($login_data['token'])
        or (empty($input_data['firstname']) && empty($input_data['lastname']) && empty($input_data['call_up_moment']))
        or (empty($input_data['phone']) && empty($input_data['call_up_moment']) && $input_data['call_up_moment'] !== null)
        or (empty($input_data['zipcode']) && empty($input_data['call_up_moment']))
        or (empty($input_data['email']) && empty($input_data['call_up_moment']))
    ) {
        $response = [
            "id"       => null,
            "status"   => "error",
            "success"  => false,
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "login"    => $login_data,
        ];

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == false) {
            $response = [
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "login"   => $login_data,
            ];

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            $birthdate = $input_data['birthdate'] ?? null;
            if (! empty($birthdate)) {
                $d = \DateTime::createFromFormat('Y-m-d', $birthdate);
                if ($d) {
                    $year = (int) $d->format('Y');

                    if ($year < 1000) {
                        if ($year < 10) {
                            // 3 zéros devant (ex: 0002) → 200X
                            $year = (int) ('200' . $year);
                        } else {
                            // 2 zéros devant (ex: 0095) → 19XX
                            $year = (int) ('19' . $year);
                        }

                        $birthdate = $year . '-' . $d->format('m') . '-' . $d->format('d');
                        $d         = \DateTime::createFromFormat('Y-m-d', $birthdate);
                    }

                    // vérifie age >= 18
                    if ((new \DateTime())->diff($d)->y < 18) {
                        $conn->close();
                        header("HTTP/1.1 400 Bad Request");
                        echo json_encode([
                            "status"  => "error",
                            "message" => "Invalid birthdate: age must be 18+",
                        ]);
                        exit;
                    }

                    // met à jour la birthdate corrigée
                    $input_data['birthdate'] = $birthdate;
                    $lead['birthdate']       = $birthdate;
                }
            }

            // save lead info to DB leads table
            $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
            // $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
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

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
                }
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then save defisc data to the "defiscalisation" table of the DB.
                 */
                $defisc_data = DefiscController::makeDefiscData($input_data);
                DefiscController::save_defisc($conn, $login_data, $lead, $defisc_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
                }
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead assurance DATA payload
                 * then save the payload to the "assurances" table of the DB.
                 */
                $assurances_data = AssuranceController::makeAssuranceData($input_data);
                AssuranceController::save_assurance($conn, $login_data, $lead, $assurances_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
                }
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead security DATA payload
                 * then save the payload to the "securities" table of the DB.
                 */
                $security_data = SecurityController::makeSecurityData($input_data);
                SecurityController::save_security($conn, $login_data, $lead, $security_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
                }
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formation DATA payload
                 * then save the payload to the "formations" table of the DB.
                 */
                $formation_data = FormationController::makeFormationData($input_data);
                FormationController::save_formation($conn, $login_data, $lead, $formation_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
                }
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead rachat de credits DATA payload
                 * then save the payload to the "rachat_de_credits" table of the DB.
                 */
                $rac_data = RachatCreditController::makeRacData($input_data);
                RachatCreditController::save_rac($conn, $login_data, $rac_data, $lead);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
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
            } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                /**
                 * create lead assurance_auto DATA payload
                 * then save the payload to the "assurance_auto" table of the DB.
                 */
                $assurance_auto_data = AssuranceAutoController::makeData($input_data);
                AssuranceAutoController::save($conn, $login_data, $lead, $assurance_auto_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                /**
                 * create lead assurance_sante DATA payload
                 * then save the payload to the "assurance_sante" table of the DB.
                 */

                $assurance = AssuranceSanteController::makeData($input_data);

                AssuranceSanteController::save($conn, $login_data, $lead, $assurance);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */

                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            }

            if ($last_insert_id) {

                $rawName = PartnerProvider::getName($conn, $login_data['partname']);

                if (! empty($rawName)) {
                    $thematique = str_replace(
                        ["BudgetDevis-", "-", "_"],
                        ["", " ", " "],
                        $rawName
                    );

                    // format propre (majuscule première lettre)
                    $thematique = ucfirst(trim($thematique));
                } else {
                    $thematique = "Non défini";
                }

                /**
                 * ✅ Payload email
                 */
                $emailPayload = [
                    "firstname"  => $input_data['firstname'] ?? "",
                    "lastname"   => $input_data['lastname'] ?? "",
                    "email"      => $input_data['email'] ?? "",
                    "phone"      => $input_data['phone'] ?? "",
                    "zipcode"    => $input_data['zipcode'] ?? "",
                    "thematique" => $thematique,
                    "source"     => $referer,
                ];

                /**
                 * ✅ Envoi email (non bloquant)
                 */
                if (! empty($emailPayload['email'])) {

                    // $chEmail = curl_init("https://budgetdevis.com/accueil/api/lead-confirmation");
                    $chEmail = curl_init("http://budgetdevis.local/api/lead-confirmation");

                    curl_setopt($chEmail, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chEmail, CURLOPT_POST, true);
                    curl_setopt($chEmail, CURLOPT_POSTFIELDS, json_encode($emailPayload));
                    curl_setopt($chEmail, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ]);

                    curl_setopt($chEmail, CURLOPT_TIMEOUT, 2);

                    $responseEmail = curl_exec($chEmail);

                    if (curl_errno($chEmail)) {
                        error_log("Email error: " . curl_error($chEmail));
                    }

                    curl_close($chEmail);
                }

                // close connexion
                $conn->close();

                // response
                $response = [
                    "id"      => $last_insert_id,
                    "status"  => "success",
                    "message" => "Lead registered Successfully..",
                ];

                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                // return message
                $response = [
                    "id"      => $last_insert_id,
                    "status"  => "error",
                    "message" => "Error saving lead data.. ",
                ];

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

    $response = [
        "status"  => "error",
        "message" => "Something went wrong",
    ];

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
