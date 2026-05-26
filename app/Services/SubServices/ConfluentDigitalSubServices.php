<?php
namespace App\Services\Assurance;


use App\Providers\CurlProvider;

class ConfluentDigitalSubServices extends \App\Services\ApiService
{

    public static function confluentDigital_mutuel_senior($assuranceModel)
    {
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();

        // $url = "https://service.comparer-changer.com/__ws/send_lead.php";
        $url        = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile    = "confluent_digital";
        $civModelId = [
            'mr'  => "homme",
            'mme' => "femme",
        ];

        $ownerTypeModelId = [
            'Proprietaire' => "owner",
            'Locataire'    => "tenant",
            'proprietaire' => "owner",
            'locataire'    => "tenant",
        ];

        $assetTypeModelId = [
            'Appartement' => "apartment",
            'Maison'      => "home",
            'appartement' => "apartment",
            'maison'      => "home",
        ];
        $assured = [
            'assure_principal' => 'Seul',
            'conjoint'         => 'couple',
            'conjoint'         => 'couple_avec_enfants',
            'enfant'           => 'seul_avec_enfants',
        ];
        $heaterType = [
            "gaz"         => "Gaz",
            "fioul"       => "Fioul",
            "fuel"        => "Fioul",
            "electricite" => "Electrique",
            "bois"        => "Bois",
            "autre"       => "Autre",
        ];
        $birthdate = $classics['birthdate'] ?? null;

        // Appel correct de la méthode interne
        $birthdateFormatted = self::formatBirthdate($birthdate);
        $campaign           = "CONFLUENT DIGITAL MUTUELLE_SENIOR";

        try {
            $data = [
                'url_source'       => $classics['refer'],
                'interest_area_id' => 31,
                'token'            => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'               => $classics['ip'],
                'gender'           => $civModelId[$classics['civility']],
                'firstname'        => $classics['firstname'],
                'name'             => $classics['lastname'],
                'address'          => $classics['address'],
                'zipcode'          => $classics['zipcode'],
                'city'             => $classics['city'],
                'email'            => $classics['email'],
                'phone'            => $classics['phone'],
                'birthday'         => $birthdateFormatted,
                "optin_cgu"        => 1,
                "optin_partners"   => 1,
                "complementaire"   => $specifics["custom_field_6"],

                "profession"       => $specifics["situationPro"],
                "marital_status"   => $specifics["custom_field_5"],
                "contract_date"    => $specifics["custom_field_4"],
                "who_insured"      => $assured[$specifics["custom_field_3"]],
                "social_regime"    => $specifics["custom_field_1"],

            ];

            parent::logger('../logs/assurance/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);
            var_dump($responses);
            parent::logger('../logs/assurance/' . $logfile . '_after.json', $responses);

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
    public static function confluentDigital_isolation($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url     = "https://leads.oceads.com/import";
        $logfile = "confluent_digital";

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
        $campaign           = "CONFLUENT DIGITAL ITE";

        try {
            $data = [
                'deliveryId'       => 1765,
                'authKey'          => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'civilityModelId'  => $civModelId[$classics['civility']],
                'firstName'        => $classics['firstname'],
                'lastName'         => $classics['lastname'],
                'address'          => $classics['address'],
                'postalCode'       => $classics['zipcode'],
                'city'             => $classics['city'],
                'email'            => $classics['email'],
                'phoneNumber'      => $classics['phone'],
                'birthDate'        => $birthdateFormatted,
                'ownerTypeModelId' => $ownerTypeModelId[$classics['situation']],
                'assetTypeModelId' => $assetTypeModelId[$specifics['type_logement']],
                'heater_type' => $heaterType[$specifics['type_chauffage']]
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
}
