<?php

namespace App\Services\Travaux;

use App\Providers\CurlProvider;
use App\Providers\PhoneNumberProvider;
use App\Services\SubServices\LeadCreativeSubServices;

class DoucheServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method for sending leads "Douche senior" information to LEAD CREATIVE.
     * @param mixed $travauxModel
     * @return array
     */
    public static function send_lead_creative($travauxModel)
    {
        $auth_data = array(
            "client_id"     => "e01fa10c-29a6-421e-a87d-88396c0efa22",
            "client_secret" => "64H8Q~l1O4O~~6fBH-vsY6FYvrEut~jk~_5U3cRz",
            "grant_type"    => "client_credentials",
            "scope"         => "https://dbs.crm4.dynamics.com/.default"
        );

        $auth_header = array("Content-Type: application/x-www-form-urlencoded");

        $auth_url = "https://login.microsoftonline.com/6a05316b-b8d5-40b4-8d79-52785ca0ccb8/oauth2/v2.0/token";

        $send_url = "https://dbs.crm4.dynamics.com/api/data/v9.2/leads";

        $classics              = $travauxModel->getClassics();
        $specifics             = $travauxModel->getSpecifics();
        $situation             = parent::category_situation_transform($classics['situation'], 0);
        $correct_number_format = PhoneNumberProvider::remove_plus_33(PhoneNumberProvider::remove_whitespace($classics["phone"]));
        try {
            // try authentication
            $auth_response = CurlProvider::post_requests($auth_url, $auth_header, http_build_query($auth_data));
            $access_token  = json_decode($auth_response[0])->access_token;

            if ($access_token !== "") {
                file_put_contents("../logs/travaux/test_access.txt", $access_token);

                $headers = array(
                    "Authorization: Bearer $access_token",
                    "OData-Version: 4.0",
                    "OData-MaxVersion: 4.0",
                    "Content-Type: application/json"
                );

                $data = LeadCreativeSubServices::make_douche_senior_datas($classics, $specifics, $situation, $correct_number_format);
                parent::logger("../logs/travaux/douche_lead_creative_before.json", $data);

                $curl_response = CurlProvider::post_requests($send_url, $headers, json_encode($data));

                $json_response = json_decode($curl_response[0]);
                parent::logger("../logs/travaux/douche_lead_creative_after.json", $json_response);

                return LeadCreativeSubServices::make_douche_senior_responses($json_response, $curl_response);
            }
            return array(
                "status"       => "error",
                "api_response" => "",
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Lead_creative web service authentification error"
            );

        } catch (\Throwable $th) {
            //throw $th;
            return parent::common_internal_server_error();
        }


    }

    public static function confluentDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        // $url = "https://service.comparer-changer.com/__ws/send_lead.php";
        $url = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile    = "douche_confluent_digital";
        $civModelId = [
            'mr'  => "homme",
            'mrs' => "homme",
            'm'   => "homme",
            'mme' => "femme",
            'F'   => "femme",
        ];

        $ownerTypeModelId = [
            'Proprietaire'  => "owner",
            'Proprietaires' => "owner",
            'Locataire'     => "tenant",
            'Locataires'    => "tenant",
        ];

        $assetTypeModelId = [
            'Appartement' => "apartment",
            'appartement' => "apartment",
            'Maison'      => "home",
            'maison'      => "home",
        ];

        $heaterType = [
            "gaz"         => "Gaz",
            "fioul"       => "Fioul",
            "fuel"        => "Fioul",
            "electricite" => "Electrique",
            "bois"        => "Bois",
            "autre"       => "Autre",
        ];
        $projectDate = [
            "immediatement" => "0",
            "moins-de-1-mois" => "1",
            "moins-de-3-mois" => "2-3",
            "plus-de-3-mois" => "3-6",
            "plus-de-6-mois" => ">6"
        ];
        $projecttype = [
            "Existant" => 99,
            "Nouveau" => 100,
            "PMR" => 251
        ];
        // $phoneFormat = [
        //     "/^06(\d{8})$/" => "+336$1",
        // ];

        $birthdate = $classics['birthdate'] ?? null;
        // $birthdateFormatted = self::formatBirthdate($birthdate);
        if (empty($birthdate) || ! self::formatBirthdate($birthdate)) {
            $birthdateFormatted = "1951-12-12";
        } else {
            $birthdateFormatted = self::formatBirthdate($birthdate);
        }
        $campaign = "CONFLUENT DIGITAL DOUCHE";

        try {
            $data = [
                'url_source'           => $classics["referer"],
                'interest_area_id'     => 19,
                'token'                => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'                   => $classics['ip'],
                'gender'               => $civModelId[$classics['civility']],
                'firstname'            => $classics['firstname'],
                'name'                 => $classics['lastname'],
                'address'              => $classics['address'],
                'zipcode'              => $classics['zipcode'],
                'city'                 => $classics['city'],
                'email'                => $classics['email'],
                'birthday'             => $birthdateFormatted,
                'phone'                => preg_replace("/^06(\d{8})$/", "+336$1", preg_replace("/[^0-9]/", "", $classics['phone'])),
                'home_situation'       => $ownerTypeModelId[$classics['situation']],
                'home_information'     => $assetTypeModelId[$specifics['type_logement']],
                "optin_cgu"            => 1,
                "optin_partners"       => 1,
                "projecttype"          => $projecttype[$specifics['custom_field_2']],
                'project_date'        => $projectDate[$specifics['date_start']],
                "project_description"  => $specifics['custom_field_1']
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
}