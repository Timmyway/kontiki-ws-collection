<?php
namespace App\Services\Travaux;

use App\Providers\CurlProvider;

class PanneauServices extends \App\Services\ApiService
{
    public static function send_adkomo($travauxModel)
    {
        $classics   = $travauxModel->getClassics();
        $specifics  = $travauxModel->getSpecifics();
        $url        = "https://lead.adkomo.com/import";
        $logfile    = "panneau_adkomo";
        $civModelId = [
            'mr'  => 1,
            'mme' => 2,
        ];

        $ownerTypeModelId = [
            'Proprietaire' => 2904,
            'Locataire'    => 2905,
        ];

        $assetTypeModelId = [
            'Appartement' => 2780,
            'Maison'      => 2781,
        ];

        $campaign = "Adkomo PV";

        try {
            $data = [
                'deliveryId'        => 156,
                'authKey'           => '45228918317dc3a85550c0789e7d3a0a',
                'civilityModelId'   => $civModelId[$classics['civility']],
                'firstName'         => $classics['firstname'],
                'lastName'          => $classics['lastname'],
                'email'             => $classics['email'],
                'phoneNumberMobile' => $classics['phone'],
                'city'              => $classics['city'],
                'postalCode'        => $classics['zipcode'],
                'ownerTypeModelId'  => $ownerTypeModelId[$classics['situation']],
                'assetTypeModelId'  => $assetTypeModelId[$specifics['type_logement']],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], json_encode($data));
            $responses     = json_decode($curl_response[0], true);
            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if ($responses['status'] === 1) {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['leadId'],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else if ($responses['status'] === 3) {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $responses['reason'],
                    "description"  => "error sending leads to $campaign: " . $responses['reason'],
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function send_mediamoov($travauxModel)
    {
        $classics   = $travauxModel->getClassics();
        $specifics  = $travauxModel->getSpecifics();
        $url        = "https://www.media-optin.com/api/campaigns/215/contacts";
        $logfile    = "panneau_mediamoov";
        $civModelId = [
            'mr'  => 1,
            'mme' => 2,
        ];
        $situation = [
            'Proprietaire' => 'Oui',
            'Locataire'    => 'Non',
        ];
        $campaign = "Mediamoov PV";
        try {
            $data = [
                'token_api' => '5619d7ef81f75abb86a3c529013f59931420085b',
                'gender'    => $civModelId[$classics['civility']],
                'firstname' => $classics['firstname'],
                'lastname'  => $classics['lastname'],
                'address'   => $classics['address'],
                'zipcode'   => $classics['zipcode'],
                'city'      => $classics['city'],
                'email'     => $classics['email'],
                'tel'       => $classics['phone'],
                'custom1'   => $situation[$classics['situation']],
                'custom2'   => $specifics['type_logement'],
                'custom3'   => $specifics['type_chauffage'],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $certificat = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'certificat' . DIRECTORY_SEPARATOR . 'mediamoov.crt';

            $curl_response = CurlProvider::post_requests($url, [], $data, $certificat);

            $responses = json_decode($curl_response[0]);
            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if ($responses === "OK") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $responses,
                    "description"  => "error sending leads to $campaign",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function send_persee_media($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        $url       = "https://script.google.com/macros/s/AKfycby4ACL4ewcr8SzdC_ISAlWewzasBrdnV2TgAc81eJaYzZLCrUOLcmHudMA_mP3yhiTLJw/exec";

        try {
            $data = [
                'Prénoms'                  => $classics['firstname'],
                'Nom'                      => $classics['lastname'],
                'Email'                    => $classics['email'],
                'Téléphone'                => $classics['phone'],
                'Code postal'              => $classics['zipcode'],
                'Situation'                => $classics['situation'],
                'Type de logement'         => $specifics['type_logement'],
                'Accepte d\'être contacté' => "Oui",
            ];

            parent::logger('../logs/travaux/panneau_perseemedia_before.json', $data);
            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("PERSEE MEDIA", true);
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("PERSEE MEDIA", false);
        }
    }
    public static function send_persee_media_ws($travauxModel)
    {
        $classics      = $travauxModel->getClassics();
        $specifics     = $travauxModel->getSpecifics();
        $ws            = "https://persee-media-ws.com/espace_editeur/insert_lead_pm2.php";
        $typeChauffage = [
            "gaz"         => "gaz",
            "fioul"       => "fuel",
            "fuel"        => "fuel",
            "electricite" => "électrique",
            "bois"        => "bois",
            "autre"       => "autre",
        ];
        try {
            $data = [
                'id_unique_commande_editeur' => "6758090d001f3",
                'id'                         => "4576968",
                'prenom'                     => $classics['firstname'],
                'nom'                        => $classics['lastname'],
                'adresse1'                   => $classics['address'],
                'commune'                    => $classics['city'],
                'email'                      => $classics['email'],
                'telephone'                  => $classics['phone'],
                'codePostal'                 => $classics['zipcode'],
                'chauffageType'              => $typeChauffage[$specifics['type_chauffage']],
                'factureEnergetiqueMensuel'  => 0,
            ];

            parent::logger('../logs/travaux/panneau_perseemedia_ws_before.json', $data);
            $url = $ws . '?' . http_build_query($data);

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
            ]);

            $output = curl_exec($curl);
            curl_close($curl);

            $responses = json_decode($output, true);
            parent::logger('../logs/travaux/panneau_perseemedia_ws_after.json', $output);

            if ($responses["erreurs"] === "oui") {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads to PERSEE MEDIA : " . $responses["message"],
                ];
            } else {
                return [
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to PERSEE MEDIA",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function send_lead_creative($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        /* consulter les données ici: https://docs.google.com/spreadsheets/d/18QZ28DSss31BnTmFX6vqHqWxpNGc2RCG8KbV_g2SR0s/edit?gid=0#gid=0 */
        $url = "https://script.google.com/macros/s/AKfycby2AMC16GdwdkMpuXRLAQS1o4j3Dn4TDgAis5HEbK2wyL61oFml15gz4tVmwLxHzqE/exec";

        $area = [
            "150-a-250"   => "150 à 250 m²",
            "250-a-350"   => "250 à 350 m²",
            "350-a-450"   => "350 à 450 m²",
            "plus-de-450" => "plus de 450 m²",
        ];

        try {
            $data = [
                'Surface disponible' => $area[$specifics['surface_logement']],
                'Nom'                => $classics['lastname'],
                'Prénom'             => $classics['firstname'],
                'Email'              => $classics['email'],
                'Téléphone'          => $classics['phone'],
                'Société'            => $specifics['type_client'],
                'Code Postal'        => $classics['zipcode'],
                'Adresse'            => $classics['address'],
                'Ville'              => $classics['city'],
            ];

            parent::logger('../logs/travaux/panneau_lead_creative_before.json', $data);
            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("Lead Creative", true);
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("Lead Creative", false);
        }
    }
    public static function confluentDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

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
        $campaign           = "CONFLUENT DIGITAL PV";

        try {
            $data = [
                'url_source'              => 'kontiki',
                'interest_area_id'        => 10,
                'token'                   => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'                      => $classics['ip'],
                'gender'                  => $civModelId[$classics['civility']],
                'firstname'               => $classics['firstname'],
                'name'                    => $classics['lastname'],
                'address'                 => $classics['address'],
                'zipcode'                 => $classics['zipcode'],
                'city'                    => $classics['city'],
                'email'                   => $classics['email'],
                'phone'                   => $classics['phone'],
                'birthday'                => $birthdateFormatted,
                'home_situation'          => $ownerTypeModelId[$classics['situation']],
                'home_type'               => $assetTypeModelId[$specifics['type_logement']],
                "optin_cgu"               => 1,
                "optin_partners"          => 1,
                'electricity_bill_amount' => '100-150',
                'heater_type'             => $heaterType[$specifics['type_chauffage']],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);
            var_dump($responses);
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
    public static function TedJordanSrl($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        /* https://docs.google.com/spreadsheets/d/1ws0iHd2A5UwEc8UASYJMWMN22PSid-UTc5eRYsPx5XU/edit?hl=fr&gid=0#gid=0 */
        $url = "https://script.google.com/macros/s/AKfycbzi7qdaqtGkSuffSKJcu-XrwGI8h8Hiu90wzlJUGm8Aw1GLQX3yaehEkKcIc0i3zLsI/exec";

        try {
            $data = [
                'Date'                 => date('Y-m-d'),
                'Lead Type'            => 'PV',
                'Is_Owner'             => $classics['situation'],
                'Type_logement'        => $specifics['type_logement'],
                'Postal_code'          => $classics['zipcode'],
                'Electricity_bill'     => '',
                'Full_name'            => $classics['firstname'] . ' ' . $classics['lastname'],
                'Email'                => $classics['email'],
                'Tel'                  => $classics['phone'],
                'Energie de chauffage' => $specifics['type_chauffage'],
            ];

            parent::logger('../logs/travaux/panneau_TedJordanSrl_before.json', $data);
            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("Ted Jordan Srl", true);
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("Ted Jordan Srl", false);
        }
    }
    public static function FlexyLead($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url = "https://flexlead.leadbyte.co.uk/restapi/v1.3/leads";

        $gender = [
            "mr"  => "Monsieur",
            "mme" => "Madame",
        ];

        $situation = [
            "Proprietaire" => "Propriétaire",
            "Locataire"    => "Locataire",
        ];

        try {
            $data = [
                'campid'                      => "FR--SOLAIRE",
                'sid'                         => "79",
                'email'                       => $classics['email'],
                'firstname'                   => $classics['firstname'],
                'lastname'                    => $classics['lastname'],
                'dob'                         => " 01/01/1987",
                'street1'                     => $classics['address'],
                'towncity'                    => trim(preg_replace('/\d+/', '', $classics['city'])),
                'postcode'                    => $classics['zipcode'],
                'phone1'                      => $classics['phone'],
                'Vous_êtes_?'                 => $situation[$classics['situation']],
                'vous_habitez_dans_un(e)*?'   => $specifics['type_logement'],
                'situation_professionnelle_?' => "En activité",
                'sexe_?'                      => $gender[$classics['civility']],
            ];

            parent::logger('../logs/travaux/pannsol_FlexyLead_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, ["X_KEY: 02ea2da5f2daa4d9c464dd5a7450abd2"], json_encode($data));

            $responses = json_decode($curl_response[0], true);
            parent::logger('../logs/travaux/pannsol_FlexyLead_after.json', $responses);

            if ($responses['status'] === "Success") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['results'][0]['queueId'] ?? 0,
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Flexylead",
                ];
            } else if ($responses['status'] === "Error") {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $responses['errors'][0] ?? $responses['message'],
                    "description"  => "error sending leads to Flexylead: " . $responses['errors'][0],
                ];
            }
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("FlexyLead", false);
        }
    }
}
