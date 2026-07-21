<?php

namespace App\Services\Assurances;

use App\Providers\CurlProvider;
use App\Services\SubServices\EuroCrmServices;
use App\Services\SubServices\FiliassurSubServices;
use App\Services\SubServices\LeadCreativeSubServices;
use App\Services\Travaux\MontEscalierServices;
use DateTime;
use Exception;

class PretServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE
     * @return array
     */
    public static function send_lead_creative($assuranceModel)
    {
        $url_send = 'https://www.devissima.fr/webservice/ws_devis_reception';

        $classics         = $assuranceModel->getClassics();
        $specifics        = $assuranceModel->getSpecifics();
        $gender_category  = parent::category_gender_transform($classics['civility'], 4);
        $birthdate_object = DateTime::createFromFormat('d/m/Y', $classics["birthdate"]);

        try {

            $data = LeadCreativeSubServices::make_assurance_pret_datas($classics, $specifics, $gender_category, $birthdate_object);
            parent::logger('../logs/assurance/leadcreative_pret_before.json', $data);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $data,
            ]);

            $output = curl_exec($curl);
            parent::logger('../logs/assurance/leadcreative_pret_after.json', json_decode($output));
            curl_close($curl);
            $json_response = json_decode($output);

            return LeadCreativeSubServices::make_assurance_pret_responses($json_response, $output);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_lead_creative_filiasure($assuranceModel, $id)
    {
        // $url_send = 'https://api-lead.filiassur.com/prospect'; // prod
        $url_send = 'https://api-lead-preprod.filiassur.com/prospect';

        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();
        // $gender_category  = parent::category_gender_transform($classics['civility'], 6);
        // $birthdate_object = DateTime::createFromFormat('d/m/Y', $classics["birthdate"]);
        if ($id === "assurance#4") {
            // Assurance emprunteur
            $username = "5186_C_EMP_EX_BUDGET_DEVIS_WEB_EMAIL";
            // $password = "99f528bc-0824-4068-84dd-abcb91dd9814"; // clé de prod
            $password = "T_80bcc25b-fc1b-4c30-aa84-3c393e8c383d"; // clé de preprod

            $logs = "emprunteur_iki_";
        } else if ($id === "assurance#5") {
            // Mutuelle Santé
            $username = "5185_C_SAN_EX_BUDGET_DEVIS_SENIOR_WEB_EMAIL";
            // $username = "5280_T_SAN_EX_LEAD_CREATIVE_SANTE_SENIOR_WEB";
            // $password = "99f528bc-0824-4068-84dd-abcb91dd9814"; // clé de prod
            $password = "T_80bcc25b-fc1b-4c30-aa84-3c393e8c383d"; // clé de preprod
            // $password = "T_6c6f6f77-33e5-46d8-a1bc-c041605dbbc8"; // clé de preprod

            $logs = "sante_iki_";
        } else {
            return [
                "status"       => "error",
                "api_response" => null,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Unknown assurance ID: " . $id,
            ];
        }

        // $data = FiliassurSubServices::make_assurance_pret_datas($classics, $specifics);
        if ($id === "assurance#4") {
            $data = FiliassurSubServices::make_assurance_pret_datas($classics, $specifics);
        } else {
            $data = FiliassurSubServices::make_mutuelle_sante_datas($classics, $specifics);
        }
        parent::logger('../logs/assurance/' . $logs . 'before.json', $data);

        $json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url_send,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => "$username:$password",
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_PRETTY_PRINT),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $output   = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $json_response = null;

        if ($output === false) {
            $error = curl_error($curl);
            curl_close($curl);

            parent::logger('../logs/assurance/' . $logs . 'error.json', [
                'error'     => $error,
                'http_code' => 0,
            ]);

            return [
                "status"       => "error",
                "api_response" => null,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "cURL error: " . $error,
            ];
        }

        curl_close($curl);


        $json_response = json_decode($output, true);

        // Logger la réponse
        parent::logger('../logs/assurance/' . $logs . 'after.json', [
            'http_code'     => $httpCode,
            'raw_output'    => $output,
            'json_response' => $json_response,
        ]);

        // ✅ VÉRIFIER SI LE DÉCODAGE JSON A ÉCHOUÉ
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Invalid JSON response from Filiassur: " . json_last_error_msg(),
            ];
        }

        // Traiter la réponse
        return FiliassurSubServices::make_assurance_pret_responses($json_response, $output);
    }
    public static function send_filiassur($assuranceModel, $id)
    {
        // $url_send = 'https://api-lead.filiassur.com/prospect'; // prod
        $url_send = 'https://api-lead-preprod.filiassur.com/prospect';

        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();
        // $gender_category  = parent::category_gender_transform($classics['civility'], 6);
        // $birthdate_object = DateTime::createFromFormat('d/m/Y', $classics["birthdate"]);
        if ($id === "assurance#4") {
            // Assurance emprunteur
            $username = "5186_C_EMP_EX_BUDGET_DEVIS_WEB_EMAIL";
            // $password = "99f528bc-0824-4068-84dd-abcb91dd9814"; // clé de prod
            $password = "T_80bcc25b-fc1b-4c30-aa84-3c393e8c383d"; // clé de preprod

            $logs = "emprunteur_iki_";
        } else if ($id === "assurance#5") {
            // Mutuelle Santé
            $username = "5185_C_SAN_EX_BUDGET_DEVIS_SENIOR_WEB_EMAIL";
            //$username = "5280_T_SAN_EX_LEAD_CREATIVE_SANTE_SENIOR_WEB";

            //$username = "5282_C_SAN_NEX_BUDGET_DEVIS_SANTE_WEB";

            // $password = "99f528bc-0824-4068-84dd-abcb91dd9814"; // clé de prod
            $password = "T_80bcc25b-fc1b-4c30-aa84-3c393e8c383d"; // clé de preprod
            //$password = "T_6c6f6f77-33e5-46d8-a1bc-c041605dbbc8";
            $logs = "sante_filiassur_";
            $classics  = $assuranceModel->getClassics();
            $specifics = $assuranceModel->getSpecifics();

            // 🔒 Blocage si âge > 80 ans
            $age = self::calculateAge($classics['birthdate'] ?? null);

            if ($age > 80) {
                return [
                    "status"       => "error",
                    "api_response" => null,
                    "id_part"      => "",
                    "ws_statut"    => "blocked",
                    "description"  => "Lead bloqué : âge supérieur à 80 ans ({$age} ans)",
                ];
            }
        } else {
            return [
                "status"       => "error",
                "api_response" => null,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Unknown assurance ID: " . $id,
            ];
        }

        $data = FiliassurSubServices::make_assurance_pret_datas($classics, $specifics);
        parent::logger('../logs/assurance/' . $logs . 'before.json', $data);

        $json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url_send,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => "$username:$password",
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_PRETTY_PRINT),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $output   = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // ✅ INITIALISER $json_response À NULL PAR DÉFAUT
        $json_response = null;

        if ($output === false) {
            $error = curl_error($curl);
            curl_close($curl);

            parent::logger('../logs/assurance/' . $logs . 'error.json', [
                'error'     => $error,
                'http_code' => 0,
            ]);

            return [
                "status"       => "error",
                "api_response" => null,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "cURL error: " . $error,
            ];
        }

        curl_close($curl);

        // ✅ DÉCODER LA RÉPONSE JSON
        $json_response = json_decode($output, true);

        // Logger la réponse
        parent::logger('../logs/assurance/' . $logs . 'after.json', [
            'http_code'     => $httpCode,
            'raw_output'    => $output,
            'json_response' => $json_response,
        ]);

        // ✅ VÉRIFIER SI LE DÉCODAGE JSON A ÉCHOUÉ
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Invalid JSON response from Filiassur: " . json_last_error_msg(),
            ];
        }

        // Traiter la réponse
        return FiliassurSubServices::make_assurance_pret_responses($json_response, $output);
    }


    public static function send_persee_media_old($assuranceModel)
    {
        $url_send = 'https://persmed.azurewebsites.net/espace_editeur/insert_lead_pm2.php';

        $classics        = $assuranceModel->getClassics();
        $specifics       = $assuranceModel->getClassics();
        $gender_category = parent::category_gender_transform($classics['civility'], 6);
        // $birthdate = DateTime::createFromFormat('Y-m-d', $classics["birthdate"]);
        $phone    = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $isMobile = preg_match('/^0[6-7]\d{8}$/', $phone);

        $data = [
            "id_unique_commande_editeur" => "665dbc91ec5ad",
            "date_collecte"              => date("d/m/Y"),
            "civilite"                   => intval($gender_category),
            "nom"                        => $classics['lastname'],
            "prenom"                     => $classics['firstname'],
            "adresse"                    => $classics['address'],
            "code_postal"                => $classics['zipcode'],
            "ville"                      => $classics['city'],
            "pays"                       => "FR",
            "email"                      => $classics['email'],
            "telephone_mobile"           => $isMobile ? $phone : null,
            "datenaissance"              => $classics["birthdate"],
            "timestamp"                  => date("d/m/Y"),
            "note"                       => $specifics['regime_social'] ?? "sécurité sociale",
        ];

        try {

            parent::logger('../logs/assurance/persee_media_before.json', $data);
            $url = $url_send . '?' . http_build_query($data);

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
            ]);

            $output = curl_exec($curl);
            if ($output === false) {
                echo 'Erreur cURL : ' . curl_error($curl);
            } else {
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($httpCode >= 400) {
                    echo 'Erreur HTTP : ' . $httpCode;
                    echo 'Response content: ' . $output;
                } else {

                    $decodedOutput = json_decode($output, true);

                    if ($decodedOutput === null) {
                        $json_response = $output;
                        $json_response = str_replace('\r\n', '', $json_response);
                        $js            = json_decode($json_response, true);
                        $logs          = json_encode($js);
                    } else {
                        $json_response = $decodedOutput;
                        $logs          = $json_response;
                    }
                }
            }

            parent::logger('../logs/assurance/persee_media_after.json', $logs);
            curl_close($curl);

            if ($logs['erreurs'] === "oui") {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads - " . $logs["message"],
                ];
            } else {
                return [
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => $logs["message"][0],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to PERSEE MEDIA",
                ];
            }
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_persee_media($assuranceModel)
    {
        $url_send = 'https://ws2.persee-media-ws.com/espace_editeur/insert_lead_pm2.php';

        $classics = $assuranceModel->getClassics();

        $gender = [
            "mr"  => "MR",
            "mme" => "MME",
        ];

        $birthdate = DateTime::createFromFormat('Y-m-d', $classics["birthdate"]);
        $phone     = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);

        $data = [
            "id_unique_commande_editeur" => "6751eff00ff0c",
            "TitreTxt"                   => $gender[$classics['civility']],
            "NomTxt"                     => $classics['lastname'],
            "PrenomTxt"                  => $classics['firstname'],
            "AdresseTxt"                 => $classics['address'],
            "CodePostalTxt"              => $classics['zipcode'],
            "VilleTxt"                   => $classics['city'],
            "EmailTxt"                   => $classics['email'],
            "TelGsmTxt"                  => $phone,
            "DateNaissanceTxt"           => $birthdate->format('d/m/Y'),
            "NombrEnfantTxt"             => "",
            "ComplementaireTxt"          => 0,
        ];

        try {

            parent::logger('../logs/assurance/persee_media_before.json', $data);
            $url = $url_send . '?' . http_build_query($data);

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
            ]);

            $output = curl_exec($curl);
            parent::logger('../logs/assurance/persee_media_after.json', json_decode($output));
            curl_close($curl);

            $json_response = json_decode($output, true);

            if ($json_response['erreurs'] === "non") {
                return [
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => $json_response["message"][0],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to PERSEE MEDIA",
                ];
            } else if ($json_response['erreurs'] === "oui") {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads - " . $json_response["message"],
                ];
            }
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_euro_crm($assuranceModel)
    {
        $url_send = 'https://ws-conciergerie.eurocrm.com/production/lead/';
        // $url_send = 'https://ws-conciergerie.eurocrm.com/recette/lead/';
        $headers = [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Accept: application/json',
            'accountId: API_KONTIKI_MEDIA',
            'apiKey: a80eb7f24fe9ec285b3860448baabef6',
        ];

        $classics        = $assuranceModel->getClassics();
        $specifics       = $assuranceModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);
        $birthdate       = new DateTime($classics['birthdate']);

        try {

            $data = EuroCrmServices::make_assurance_emprunte_datas($classics, $specifics, $gender_category, $birthdate);
            parent::logger('../logs/assurance/euro_crm_pret_before.json', $data);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => false, // ✅ Pour debug
                CURLOPT_VERBOSE        => true,  // ✅ Pour debug
            ]);

            $output     = curl_exec($curl);
            $curl_error = curl_error($curl);
            $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // ✅ Vérification des erreurs cURL
            if ($curl_error) {
                parent::logger('../logs/assurance/euro_crm_curl_error.json', [
                    'error'     => $curl_error,
                    'http_code' => $http_code,
                    'output'    => $output,
                ]);
                curl_close($curl);
                return [
                    "status"       => "error",
                    "api_response" => "cURL Error: " . $curl_error,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur de connexion: " . $curl_error,
                ];
            }

            // ✅ Vérification du code HTTP
            if ($http_code !== 200) {
                parent::logger('../logs/assurance/euro_crm_http_error.json', [
                    'http_code' => $http_code,
                    'output'    => $output,
                ]);
                curl_close($curl);
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur HTTP: " . $http_code,
                ];
            }

            parent::logger('../logs/assurance/euro_crm_pret_after.json', json_decode($output));
            curl_close($curl);

            $json_response = json_decode($output);
            return EuroCrmServices::make_assurance_emprunte_responses($json_response, $output);
        } catch (Exception $e) {
            parent::logger('../logs/assurance/euro_crm_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return parent::common_internal_server_error();
        }
    }

    public static function send_meedia_moov($assuranceModel, $type = null)
    {
        $url_send = 'https://www.media-optin.com/api/campaigns/220/contacts';
        // ✅ Headers corrigés pour JSON
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cache-Control: no-cache',
        ];

        $classics        = $assuranceModel->getClassics();
        $specifics       = $assuranceModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);

        try {
            $birthdate = new DateTime($classics['birthdate']);
            $data      = MontEscalierServices::make_mediamoov_datas($classics, $specifics, $gender_category, $birthdate);

            parent::logger('../logs/assurance/mediamoov_before.json', $data);

            $curl         = curl_init();
            $curl_options = [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                // ✅ Changement principal : JSON au lieu de form-data
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => true, // ✅ Remis à true pour la sécurité
                CURLOPT_SSL_VERIFYHOST => 2,    // ✅ Remis à 2 pour la sécurité
                CURLOPT_VERBOSE        => false,
            ];

            curl_setopt_array($curl, $curl_options);
            $output     = curl_exec($curl);
            $curl_error = curl_error($curl);
            $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($curl_error) {
                parent::logger('../logs/assurance/mediamoov_curl_error.json', [
                    'error'     => $curl_error,
                    'http_code' => $http_code,
                    'output'    => $output,
                ]);
                curl_close($curl);
                return [
                    "status"       => "error",
                    "api_response" => "cURL Error: " . $curl_error,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur de connexion: " . $curl_error,
                ];
            }

            if ($http_code !== 200) {
                parent::logger('../logs/assurance/mediamoov_after_error.json', [
                    'http_code' => $http_code,
                    'output'    => $output,
                    'data_sent' => $data, // Ajout pour debug
                ]);
                curl_close($curl);
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur HTTP: " . $http_code,
                ];
            }

            parent::logger('../logs/assurance/mediamoov_after.json', $output);
            curl_close($curl);

            return MontEscalierServices::make_mediamoov_responses($output);
        } catch (Exception $e) {
            parent::logger('../logs/assurance/mediamoov_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return parent::common_internal_server_error();
        }
    }

    public static function send_meedia_moov_emprunteur($assuranceModel, $type = null)
    {
        $url_send = 'https://www.media-optin.com/api/campaigns/212/contacts';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cache-Control: no-cache',
        ];

        $classics        = $assuranceModel->getClassics();
        $specifics       = $assuranceModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);

        try {
            $birthdate = new DateTime($classics['birthdate']);
            $data      = MontEscalierServices::make_mediamoov_emprunteur_datas(
                $classics,
                $specifics,
                $gender_category,
                $birthdate
            );

            // 🔹 LOG AVANT ENVOI
            parent::logger('../logs/assurance/mediamoov_emprunteur_before.json', $data);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_VERBOSE        => false,
            ]);

            $output     = curl_exec($curl);
            $curl_error = curl_error($curl);
            $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // 🔹 LOG DU CODE HTTP + SORTIE
            parent::logger('../logs/assurance/mediamoov_emprunteur_http_code.json', [
                'http_code' => $http_code,
                'output'    => $output,
                'data_sent' => $data,
            ]);

            // 🔹 Gestion des erreurs cURL
            if ($curl_error) {
                parent::logger('../logs/assurance/mediamoov_emprunteur_curl_error.json', [
                    'error'     => $curl_error,
                    'http_code' => $http_code,
                    'output'    => $output,
                ]);
                return [
                    "status"       => "error",
                    "api_response" => "cURL Error: " . $curl_error,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur de connexion: " . $curl_error,
                ];
            }

            // 🔹 LOG DE LA RÉPONSE BRUTE
            parent::logger('../logs/assurance/mediamoov_emprunteur_original.json', $output);

            // 🔹 Traitement via la fonction de réponse
            $final_response = MontEscalierServices::make_mediamoov_emprunte_responses($output, $http_code);

            // 🔹 LOG FINAL
            parent::logger('../logs/assurance/mediamoov_emprunteur_after.json', $final_response);

            return $final_response;
        } catch (Exception $e) {
            // 🔹 LOG D'EXCEPTION
            parent::logger('../logs/assurance/mediamoov_emprunteur_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return parent::common_internal_server_error();
        }
    }

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
            'seul'                => 'assure_principal',
            'couple'              => 'conjoint',
            'couple_avec_enfants' => 'conjoint',
            'seul_avec_enfants'   => 'enfant',
        ];
        $maritalMap = [
            'marie(e)'    => 'marie',
            'marie'       => 'marie',
            'celibataire' => 'celibataire',
            'célibataire' => 'celibataire',
            'divorce'     => 'divorce',
            'divorcé(e)'  => 'divorce',
            'divorcé'     => 'divorce',
            'divorce(e)'  => 'divorce',
            'veuf'        => 'veuf',
            'pacse'       => 'pacse',
            'union libre' => 'union-libre',
        ];
        $social = [
            'general'        => 'regime-general',
            'alsace_moselle' => 'alsace-moselle',
            'tns'            => 'travailleur-non-salarie',
        ];
        $profession = [
            'Artisan'                        => 'artisan',
            'Cadre'                          => 'cadre',
            'Chef entreprise'                => 'chef-entreprise',
            'Commercant'                     => 'commercant',
            'Employe'                        => 'employe',
            'ouvrier'                        => 'ouvrier',
            'profession-liberale'            => 'profession-liberale',
            'Retraite'                       => 'retraite',
            'retraite-fonction-territoriale' => 'retraite-fonction-territoriale',
            'autre'                          => 'autre',
        ];
        $birthdate = $classics['birthdate'] ?? null;

        // Appel correct de la méthode interne
        $birthdateFormatted = self::formatBirthdate($birthdate);

        // Calcul de l'âge et sélection du token approprié
        $age = self::calculateAge($birthdate);

        $receiveDate = $classics['receive_date'] ?? null;

        // LOG DE DÉBOGAGE - Vérifier la valeur de receive_date
        parent::logger('../logs/assurance/' . $logfile . '_receive_date_debug.json', [
            'receive_date_brut' => $receiveDate,
            'is_empty'          => empty($receiveDate),
            'classics_keys'     => array_keys($classics),
            'age'               => $age,
        ]);

        $isNightSlot = false;

        if (! empty($receiveDate)) {
            try {
                // Si tu veux forcer timezone France
                $dateLead = \DateTime::createFromFormat(
                    'd/m/Y H:i:s',
                    $receiveDate,
                    new \DateTimeZone('Europe/Paris')
                );

                if ($dateLead !== false) {
                    $hourLead    = (int) $dateLead->format('H');
                    $isNightSlot = ($hourLead >= 18 || $hourLead < 8);

                    // LOG DE DÉBOGAGE - Vérifier le parsing de la date
                    parent::logger('../logs/assurance/' . $logfile . '_hour_debug.json', [
                        'date_parsed'              => $dateLead->format('Y-m-d H:i:s'),
                        'hour_extracted'           => $hourLead,
                        'is_night_slot'            => $isNightSlot,
                        'condition_18_or_before_8' => ($hourLead >= 18 || $hourLead < 8),
                    ]);
                } else {
                    // LOG DE DÉBOGAGE - Échec du parsing
                    parent::logger('../logs/assurance/' . $logfile . '_parsing_error.json', [
                        'error'        => 'DateTime::createFromFormat returned false',
                        'receive_date' => $receiveDate,
                    ]);
                }
            } catch (\Exception $e) {
                // LOG DE DÉBOGAGE - Exception
                parent::logger('../logs/assurance/' . $logfile . '_exception.json', [
                    'exception_message' => $e->getMessage(),
                    'receive_date'      => $receiveDate,
                ]);
                $isNightSlot = false;
            }
        } else {
            // LOG DE DÉBOGAGE - receive_date vide
            parent::logger('../logs/assurance/' . $logfile . '_empty_receive_date.json', [
                'message'      => 'receive_date is empty or null',
                'receive_date' => $receiveDate,
            ]);
        }

        if ($isNightSlot) {
            // Forcer token actif
            $token       = '141e9aabbcc566f019f6d5473daa9e1ac3e8fb37';
            $ageCategory = 'actif (forcé plage horaire)';
        } else {
            // Logique normale âge
            $token = ($age < 55)
                ? '141e9aabbcc566f019f6d5473daa9e1ac3e8fb37'
                : '92112cd7c474af2797b92435e6b9847735e2daaa';

            $ageCategory = ($age < 55) ? 'actif' : 'passif';
        }

        // LOG FINAL - Résultat de la décision
        parent::logger('../logs/assurance/' . $logfile . '_final_decision.json', [
            'is_night_slot' => $isNightSlot,
            'age'           => $age,
            'age_category'  => $ageCategory,
            'token'         => $token,
        ]);

        $campaign = "CONFLUENT DIGITAL MUTUELLE_SENIOR";

        try {
            $data = [
                'url_source'         => $classics['referer'],
                'interest_area_id'   => 31,
                'token'              => $token,
                'ip'                 => $classics['ip'],
                'gender'             => $civModelId[$classics['civility']],
                'firstname'          => $classics['firstname'],
                'name'               => $classics['lastname'],
                'address'            => $classics['address'],
                'zipcode'            => $classics['zipcode'],
                'city'               => $classics['city'],
                'email'              => $classics['email'],
                'phone'              => self::formatPhone($classics['phone']),
                'birthday'           => $birthdateFormatted,
                "optin_cgu"          => 1,
                "optin_partners"     => 1,
                "complementaire"     => strtoupper($specifics["custom_field_6"]) === "OUI" ? 1 : 0,
                "profession"         => $profession[$specifics["profession"]],
                "contract_date"      => $specifics["custom_field_4"],
                "who_insured"        => $assured[$specifics["custom_field_1"]],
                "social_regime"      => $social[$specifics["custom_field_2"]],
                "marital_status"     => $maritalMap[$specifics["custom_field_5"]],
                "publisher_recorded" => $receiveDate,
            ];

            parent::logger('../logs/assurance/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);
            var_dump($responses);
            parent::logger('../logs/assurance/' . $logfile . '_after.json', $responses);

            if ($responses['status'] === "Lead correct" || $responses['status'] === "recorded") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['infos'],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign $ageCategory",

                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['infos'] ?? "error sending leads to $campaign: $ageCategory",

                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    private static function calculateAge($birthdate)
    {
        if (empty($birthdate)) {
            return 0;
        }

        try {
            $birth = new \DateTime($birthdate);
            $today = new \DateTime();
            $age   = $today->diff($birth)->y;
            return $age;
        } catch (\Exception $e) {
            return 0;
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

    private static function formatPhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        // Remplacer str_starts_with() par substr() pour compatibilité PHP < 8.0
        if (substr($phone, 0, 1) === '0') {
            return '+33' . substr($phone, 1);
        }

        return $phone;
    }

    public static function confluentDigital_animaux($assuranceModel)
    {
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();

        // $url = "https://service.comparerchanger.com/__ws/send_lead.php"
        
        $url     = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile = "confluent_digital_animaux";

        $civModelId = [
            'mr'  => "homme",
            'mme' => "femme",
        ];

        $petTypeMap = [
            'chien' => 'dog',
            'chat'  => 'cat',
            'NAC'   => 'other',
        ];

        $petChipMap = [
            'tatoue' => 'yes',
            'puce'   => 'yes',
            'rien'   => 'no',
        ];

        $token = '92112cd7c474af2797b92435e6b9847735e2daaa';

        $birthdateFormatted    = self::formatBirthdate($classics['birthdate'] ?? null);
        $petBirthdateFormatted = self::formatBirthdate($specifics['custom_field_2'] ?? null);

        try {
            $data = [
                'url_source'       => $classics['referer'] ?? '',
                'interest_area_id' => 2,
                'token'            => $token,
                'ip'               => $classics['ip'],
                'gender'           => $civModelId[$classics['civility']] ?? '',
                'firstname'        => $classics['firstname'] ?? '',
                'name'             => $classics['lastname'] ?? '',
                'address'          => $classics['address'] ?? '',
                'zipcode'          => $classics['zipcode'] ?? '',
                'city'             => $classics['city'] ?? '',
                'email'            => $classics['email'] ?? '',
                'phone'            => self::formatPhone($classics['phone'] ?? ''),
                'birthday'         => $birthdateFormatted,

                'pet_type'         => $petTypeMap[$specifics['custom_field_1']] ?? 'other',
                'pet_race'         => $specifics['custom_field_5'] ?? '',
                'pet_name'         => $specifics['custom_field_7'] ?? '',
                'pet_birthday'     => $petBirthdateFormatted,
                'pet_chip'         => $petChipMap[$specifics['custom_field_4']] ?? 'no',
                'pet_gender'       => $specifics['custom_field_8'] ?? '',     
                'optin_cgu'        => $classics['optin_cgu'] ?? 1,
                'optin_partners'   => $classics['optin_partners'] ?? 1,

               'get_params'       => json_encode([
                    'vaccins_a_jour' => $specifics['custom_field_3'] ?? '',
                    'deja_assure'    => $specifics['custom_field_6'] ?? '',
                ]),
            ];

            parent::logger('../logs/assurance/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url, [], $data);
            $responses     = json_decode($curl_response[0], true);

            parent::logger('../logs/assurance/' . $logfile . '_after.json', $responses);

            if (
                ($responses['status'] ?? null) === "recorded"
                || ($responses['status'] ?? null) === "Test Saved"
            ) {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['infos'] ?? '',
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to CONFLUENT DIGITAL ANIMAUX",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['infos'] ?? "error sending leads to CONFLUENT DIGITAL ANIMAUX",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
}
