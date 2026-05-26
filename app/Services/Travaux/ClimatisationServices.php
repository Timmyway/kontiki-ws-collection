<?php
namespace App\Services\Travaux;

use App\Providers\CurlProvider;

class ClimatisationServices extends \App\Services\ApiService
{
    public static function confluentDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        // $url = "https://service.comparer-changer.com/__ws/send_lead.php";
        $url        = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile    = "climatisation_confluent_digital";
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
            "immediatement"   => "0",
            "1_3_mois" => "1",
            
            "3_6_mois"  => "3-6",
            "plus_6_mois"  => ">6",
        ];
        $home_work_type = [
            "Existant" => "renovation",
            "Nouveau"  => "new",
            "Reparer"      => "repair",
        ];
        
        $birthdate = $classics['birthdate'] ?? null;
        if (empty($birthdate) || ! self::formatBirthdate($birthdate)) {
            $birthdateFormatted = "1951-12-12";
        } else {
            $birthdateFormatted = self::formatBirthdate($birthdate);
        }
        $campaign = "CONFLUENT DIGITAL DOUCHE";

        try {
            $data = [
                'url_source'          => $classics["referer"],
                'interest_area_id'    => 22,
                'token'               => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'                  => $classics['ip'],
                'gender'              => $civModelId[$classics['civility']],
                'firstname'           => $classics['firstname'],
                'name'                => $classics['lastname'],
                'address'             => $classics['address'],
                'zipcode'             => $classics['zipcode'],
                'city'                => $classics['city'],
                'email'               => $classics['email'],
                'birthday'            => $birthdateFormatted,
                'phone'               => preg_replace("/^06(\d{8})$/", "+336$1", preg_replace("/[^0-9]/", "", $classics['phone'])),
                'home_situation'      => $ownerTypeModelId[$classics['situation']],
                'home_information'    => $assetTypeModelId[$specifics['type_logement']],
                "optin_cgu"           => 1,
                "optin_partners"      => 1,
                "home_work_type"      => $home_work_type[$specifics['custom_field_3']],
                'project_date'        => $projectDate[$specifics['custom_field_2']],
                "project_description" => $specifics['custom_field_1'],
                "heater_type"          => $heaterType[$specifics['type_chauffage']],
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
