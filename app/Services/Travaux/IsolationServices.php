<?php
namespace App\Services\Travaux;

use App\Providers\CurlProvider;
use DateTime;
use Exception;

class IsolationServices extends \App\Services\ApiService
{

    public static function send_isolation_mokhtar_sheets($model, $logfile, $url, $client_name)
    {
        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        try {
            $data = [
                "Référence"           => $classics['lead_id'],
                "Date de validation"  => date('d-m-Y H:i:s'),
                "Civilité"            => $classics['civility'],
                "Nom"                 => $classics['lastname'],
                "Prénom"              => $classics['firstname'],
                "Email"               => $classics['email'],
                "Numéro de téléphone" => $classics['phone'],
                "Code postal"         => $classics['zipcode'],
                "Addresse"            => $classics['address'],
                "Situation"           => $classics['situation'],
                "Type de chauffage"   => $specifics['type_chauffage'],
                "Type de logement"    => $specifics['type_logement'],
            ];
            parent::logger('../logs/travaux/' . $logfile, $data);

            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("$client_name Isolation Extérieure", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name Isolation Extérieure", false);
        }
    }

    public static function send_isolation_societeMooner_sheets($model)
    {
        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();
        /* voici le lien de l sheet: https://docs.google.com/spreadsheets/d/1Hr_EBDcjaR5Xj68_amfALGV303leAo5wOVUcbxCYzq8/edit?hl=fr&gid=0#gid=0*/
        $url         = "https://script.google.com/macros/s/AKfycbzdXZ22WLmeWjEZUdTtVqywdnc8wbzl0jTmAsKyUlbIg-0jW5Qd73EMiTvZ7iFDPhAsPQ/exec";
        $client_name = "SH CONSEIL"; // ex SOCIETE MOONER (06/11/2024 à 15h27)

        try {
            $data = [
                "Receive date"   => date('d-m-Y H:i:s'),
                "civilité"       => $classics['civility'],
                "nom"            => $classics['lastname'],
                "prénom"         => $classics['firstname'],
                "mail"           => $classics['email'],
                "phone"          => $classics['phone'],
                "zipcode"        => $classics['zipcode'],
                "city"           => $classics['zipcode'],
                "adresse"        => $classics['address'],
                "situation"      => $classics['situation'],
                "Type chauffage" => $specifics['type_chauffage'],
                "Type logement"  => $specifics['type_logement'],
            ];

            parent::logger('../logs/travaux/isolation_exterieure_shconseil_before.json', $data);

            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("$client_name Isolation Extérieure", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name Isolation Extérieure", false);
        }
    }

    public static function confluentDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url = "https://service.comparer-changer.com/__ws/send_lead.php";
        // $url = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile    = "iso_confluent_digital";
        $civModelId = [
            'mr'  => "homme",
            'mme' => "femme",
        ];

        $ownerTypeModelId = [
            'Proprietaire' => "owner",
            'Locataire'    => "tenant",
        ];

        $assetTypeModelId = [
            'Appartement' => "apartment",
            'Maison'      => "home",
        ];

        $heaterType = [
            "gaz"         => "Gaz",
            "fioul"       => "Fioul",
            "fuel"        => "Fioul",
            "electricite" => "Electrique",
            "bois"        => "Bois",
            "autre"       => "Autre",
        ];

        $campaign = "CONFLUENT DIGITAL ITE";

        try {
            $data = [
                'url_source'       => 'kontiki',
                'interest_area_id' => 120,
                'token'            => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'               => '127.0.0.1',
                'gender'           => $civModelId[$classics['civility']],
                'firstname'        => $classics['firstname'],
                'name'             => $classics['lastname'],
                'address'          => $classics['address'],
                'zipcode'          => $classics['zipcode'],
                'city'             => $classics['city'],
                'email'            => $classics['email'],
                'phone'            => $classics['phone'],
                'home_situation'   => $ownerTypeModelId[$classics['situation']],
                'home_information' => $assetTypeModelId[$specifics['type_logement']],
                "optin_cgu"        => 1,
                "optin_partners"   => 1,
                'heater_type'      => $heaterType[$specifics['type_chauffage']],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);

            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if ($responses['status'] === "recorded" || $responses['status'] === "Lead correct") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['infos'],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['infos'] ?? "error sending leads to $campaign: ",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function AstonGroup($travauxModel, $campagne = "")
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url = "https://script.google.com/macros/s/AKfycbwWQgegxDVIf58Bcvf7XL0ZjSEtCRWUWZV_W8qpymnfG96cdw9uxDyDMaCXqM0HHUxM/exec";

        try {
            $data = [
                'sheet'           => $campagne,
                'Date'            => date('Y-m-d'),
                'Civilité'        => $classics['civility'],
                'Nom'             => $classics['lastname'],
                'Prénom'          => $classics['firstname'],
                'Adresse postale' => $classics['address'],
                'CP'              => $classics['zipcode'],
                'Ville'           => $classics['city'],
                'Tel'             => $classics['phone'],
                'Email'           => $classics['email'],
            ];

            switch ($campagne) {
                case "PV":
                    $log   = "travaux/pv";
                    $data += [
                        'Situation'         => $classics['situation'],
                        'Type de logement'  => $specifics['type_logement'],
                        'Type de chauffage' => $specifics['type_chauffage'],
                    ];
                    break;
                case "ITE":
                    $log   = "travaux/isolation";
                    $data += [
                        'Situation'         => $classics['situation'],
                        'Type de logement'  => $specifics['type_logement'],
                        'Type de chauffage' => $specifics['type_chauffage'],
                    ];
                    break;
                case "PAC":
                    $log   = "travaux/pac";
                    $data += [
                        'Situation'         => $classics['situation'],
                        'Type de logement'  => $specifics['type_logement'],
                        'Type de chauffage' => $specifics['type_chauffage'],
                    ];
                    break;
                case "MUTUELLE SENIOR":
                    $log   = "assurance/mutuelle_sante";
                    $data += [
                        "Birthdate"     => $classics['birthdate'],
                        "Profession"    => $specifics['profession'],
                        "Assuré"        => $specifics['custom_field_1'],
                        "Regime social" => $specifics['custom_field_2'],
                    ];
                    break;
                case "ALARME IDF":
                    $log  = "security/alarm_idf";
                    break;
                case "ALARME NATIO":
                    $log  = "security/alarm_idf";
                    break;
                case "FENETRE DE TOIT ET VERRIERE":
                    $log   = "travaux/fenetre";
                    $data += [
                        'Situation'        => $classics['situation'],
                        'Type de logement' => $specifics['type_logement'],
                        'Projet'           => $specifics['custom_field_1'],
                        'Type de matériau' => $specifics['custom_field_2'],
                    ];
                    break;
                case "ASSURANCE AUTO":
                    $log   = "assurance/auto";
                    $data += [
                        "Birthdate"   => $classics['birthdate'],
                        'Marque'      => $specifics['brand'],
                        'bonus-malus' => $specifics['custom_field_1'],
                    ];
                    break;

            }

            parent::logger('../logs/' . $log . '_aston_group_before.json', $data);
            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("Aston Group", true);
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("Aston Group", false);
        }
    }

    public static function oceads_isolation($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url     = "https://leads.oceads.com/import";
        $logfile = "oceads_ite";

        $civModelId = [
            'mr'       => "3638",
            'monsieur' => "3638",
            'Monsieur' => "3638",
            'mme'      => "3639",
            'madame'   => "3639",
            'Madame'   => "3639",
        ];

        $ownerTypeModelId = [
            'Proprietaire' => "3657",
            'Locataire'    => "3658",
            'proprietaire' => "3657",
            'locataire'    => "3658",
        ];

        $assetTypeModelId = [
            'Appartement' => "3656",
            'Maison'      => "3655",
            'appartement' => "3656",
            'maison'      => "3655",
        ];
        $heaterType = [
            "gaz"         => "3652",
            "Gaz"         => "3652",
            "fioul"       => "3651",
            "Fioul"       => "3651",
            "fuel"        => "3651",
            "Fuel"        => "3651",
            "electricite" => "3650",
            "Electricite" => "3650",
            "bois"        => "3653",
            "Bois"        => "3653",
            "autre"       => "3654",
            "Autre"       => "3654",
        ];

        $birthdate          = $classics['birthdate'] ?? null;
        $birthdateFormatted = self::formatBirthdate($birthdate);
        $campaign           = "OCEADS ITE";

        try {
            $data = [
                'deliveryId'         => 1765,
                "authKey"            => "67db34b6f44aee5d24cc239b7742538a",
                'civilityModelId'    => $civModelId[$classics['civility']],
                'firstName'          => $classics['firstname'],
                'lastName'           => $classics['lastname'],
                'address'            => $classics['address'],
                'postalCode'         => $classics['zipcode'],
                'city'               => $classics['city'],
                'email'              => $classics['email'],
                'phoneNumber'        => $classics['phone'],
                'birthDate'          => $birthdateFormatted,
                'ownerTypeModelId'   => $ownerTypeModelId[$classics['situation']],
                'assetTypeModelId'   => $assetTypeModelId[$specifics['type_logement']],
                'heatingTypeModelId' => $heaterType[$specifics['type_chauffage']],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);
            var_dump($responses);
            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if (isset($responses['status']) && $responses['status'] === 1 && $responses['statusText'] === "ok") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['leadId'] ?? '',
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['reason'] ?? "error sending leads to $campaign",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    private static function formatBirthdate($birthdate)
    {
        if (empty($birthdate)) {
            return null;
        }

        try {
            $date = new \DateTime($birthdate);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function FlexyLead($travauxModel)
    {
        $classics = $travauxModel->getClassics();

        $baseUrl = "https://flexlead.leadbyte.co.uk/api/submit.php";

        $birthdate = $classics['birthdate'] ?? "22/01/1980";

        $date = DateTime::createFromFormat('d/m/Y', $birthdate);

        // IMPORTANT : format demandé par FlexyLead
        $birthdateFormatted = $date
            ? $date->format('d/m/Y')
            : '22/01/1980';

        try {

            $params = [
                'returnjson' => 'yes',
                'campid'     => 'FR---ITE',
                'sid'        => '79',
                'email'      => $classics['email'],
                'firstname'  => $classics['firstname'],
                'lastname'   => $classics['lastname'],
                'dob'        => $birthdateFormatted,
                'street1'    => $classics['address'],
                'towncity'   => $classics['city'],
                'postcode'   => $classics['zipcode'],
                'phone1'     => $classics['phone'],
            ];

            // construit automatiquement :
            // ?returnjson=yes&campid=...
            $url = $baseUrl . '?' . http_build_query($params);

            parent::logger('../logs/travaux/ite_FlexyLead_before.json', $url);

            // appel GET
            $curl_response = CurlProvider::get_requests($url);

            $responses = json_decode($curl_response[0], true);

            parent::logger('../logs/travaux/ite_FlexyLead_after.json', $responses);

            if (isset($responses['code']) && $responses['code'] == 1) {

                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['leadId'] ?? 0,
                    "ws_statut"    => "ok",
                    "description"  => "lead sent successfully to Flexylead",
                ];

            } else {

                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $responses['response'] ?? 'Unknown error',
                    "description"  => "error sending lead to Flexylead",
                ];
            }

        } catch (\Exception $e) {

            return parent::common_spreadsheets_responses("FlexyLead", false);
        }
    }

}
