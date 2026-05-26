<?php
namespace App\Services\Assurances;

use App\Providers\CurlProvider;
use App\Services\SubServices\DeltaCrmSubServices;
use App\Services\SubServices\EuroCrmServices;
use App\Services\SubServices\WebRivageSubServices;
use DateTime;
use Exception;

class SanteServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead Sante LMP.
     * @return array
     */
    public static function LMP($assuranceModel)
    {
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();
        $birthdate = DateTime::createFromFormat('Y-m-d', $classics["birthdate"]);

        try {

            $data = [
                "NOM"          => $classics['lastname'],
                "PRENOM"       => $classics['firstname'],
                "EMAIL"        => $classics['email'],
                "TEL"          => $classics['phone'],
                "DNA"          => (int) $birthdate->format('Y'),
                "DNM"          => (int) $birthdate->format('m'),
                "DNJ"          => (int) $birthdate->format('d'),
                "POIDS"        => (float) $specifics['custom_field_1'],
                "POIDSAPERDRE" => (float) $specifics['custom_field_2'],
                "COMMENT"      => "Poire",
                "DIV1"         => $classics['affiliateID'],
                "DIV2"         => "Emailing",
            ];

            $encodedData = array_map(function ($value) {
                return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            }, $data);
            $queryString = http_build_query($encodedData);

            parent::logger('../logs/assurance/sante_lmp_before.json', $data);
            $curl_response = CurlProvider::get_requests("https://formulaire.lm-nutri.fr/28ykon99/?$queryString");

            parent::logger('../logs/assurance/sante_lmp_after.json', ["email" => $classics['email'], "response" => $curl_response[0]]);

            // Parse la réponse
            $responseData = self::parseLMPResponse($curl_response[0]);

            if ($responseData['code'] == 0) {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "	lead has been send successfully to LMP Santé",
                ];
            } else {
                // Génère un message d'erreur détaillé
                $errorMessage = self::getLMPErrorMessage($responseData['code'], $responseData['details']);

                return [
                    "status"        => "error",
                    "api_response"  => $curl_response,
                    "id_part"       => "",
                    "ws_statut"     => "error",
                    "error_code"    => $responseData['code'],
                    "error_details" => $responseData['details'],
                    "description"   => $errorMessage,
                ];
            }
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    /**
     * Parse la réponse de l'API LMP
     */
    private static function parseLMPResponse($response)
    {
        $result = [
            'code'    => 0,
            'details' => [],
        ];

        if (empty($response)) {
            return $result;
        }

        // Extrait le code d'erreur
        if (preg_match('/CODERR=(\d+)/', $response, $matches)) {
            $result['code'] = (int) $matches[1];
        }

        // Extrait les détails de l'erreur (lignes suivantes)
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && strpos($line, 'CODERR=') === false) {
                $result['details'][] = $line;
            }
        }

        return $result;
    }

    /**
     * Génère un message d'erreur lisible à partir du code d'erreur LMP
     */
    private static function getLMPErrorMessage($errorCode, $details = [])
    {
        $errors = [];

        $errorMapping = [
            1    => "Invalid first name",
            2    => "Invalid last name",
            4    => "Invalid email format",
            8    => "Invalid phone number format",
            16   => "Incorrect date of birth",
            32   => "Height out of range",
            64   => "Initial weight out of range (must be between 44 and 130 kg)",
            128  => "Insufficient weight to lose (minimum 4 kg)",
            256  => "Desired weight out of range (must be above 40 kg)",
            512  => "Age out of range (must be between 30 and 75 years)",
            1024 => "Phone number not allowed (blacklisted)",
            2048 => "Phone number already exists",
            4096 => "Email already exists",
            8192 => "Phone number not assigned",
        ];

        // Décompose le code d'erreur en erreurs individuelles
        foreach ($errorMapping as $code => $message) {
            if ($errorCode & $code) {
                $errors[] = "- " . $message;
            }
        }

        // Construit le message final
        if (empty($errors)) {
            return "Erreur inconnue lors de l'envoi à LMP Santé (Code: $errorCode)";
        }

        $message = "Erreur(s) LMP Santé (Code: $errorCode) :\n" . implode("\n", $errors);

        // Ajoute les détails supplémentaires si disponibles
        if (! empty($details)) {
            $message .= "\n\nDétails :\n" . implode("\n", $details);
        }

        return $message;
    }

    /**
     * Méthode utilitaire pour afficher les erreurs de façon formatée (optionnel)
     */
    private static function getHumanReadableErrors($errorCode)
    {
        $errors = [];

        $errorMapping = [
            1    => ["field" => "prenom", "message" => "Prénom non conforme"],
            2    => ["field" => "nom", "message" => "Nom non conforme"],
            4    => ["field" => "email", "message" => "Format email non conforme"],
            8    => ["field" => "tel", "message" => "Format du numéro de téléphone non conforme"],
            16   => ["field" => "birthdate", "message" => "Date de naissance incorrecte"],
            32   => ["field" => "taille", "message" => "Taille hors plage"],
            64   => ["field" => "poids", "message" => "Poids initial hors plage (44-130 kg)"],
            128  => ["field" => "poidsaperdre", "message" => "Poids à perdre insuffisant (≥4 kg)"],
            256  => ["field" => "poidssouhaite", "message" => "Poids souhaité trop faible (>40 kg)"],
            512  => ["field" => "age", "message" => "Âge hors plage (30-75 ans)"],
            1024 => ["field" => "tel", "message" => "Numéro blacklisté"],
            2048 => ["field" => "tel", "message" => "Numéro déjà existant"],
            4096 => ["field" => "email", "message" => "Email déjà existant"],
            8192 => ["field" => "tel", "message" => "Numéro non attribué"],
        ];

        foreach ($errorMapping as $code => $error) {
            if ($errorCode & $code) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Method to send lead Sante Animale Vie Pro to DELTA CRM.
     * @return array
     */
    public static function DeltaCRM($assuranceModel)
    {
        $url_send = 'http://wstestdeltacrmcef.deltain.net/api/opp';
        $classics = $assuranceModel->getClassics();

        try {

            $data = DeltaCrmSubServices::make_data($classics);
            parent::logger('../logs/formation/deltacrm_before.json', $data);

            $json_data      = json_encode($data);
            $content_length = strlen($json_data);
            $token          = 'ZUu8vM\13S1sUµ2S1Pg*V=$ctY}i997A3Dn+JJ8kY}8_nXdspaRz#;';

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . $content_length,
                'x-token: ' . $token,
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json_data,
            ]);

            $output     = curl_exec($curl);
            $curl_error = curl_error($curl);
            $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($curl_error) {
                parent::logger('../logs/formation/deltacrm_error.json', [
                    'error'     => $curl_error,
                    'http_code' => $http_code,
                    'output'    => $output,
                    'data_sent' => $data,
                ]);

                curl_close($curl);
                return [
                    "status"       => "error",
                    "api_response" => "curl Error: " . $curl_error,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur de connexion: " . $curl_error,
                ];
            }

            if ($http_code === 302) {
                curl_close($curl);

                $url_send = 'https://wstestdeltacrmcef.deltain.net/api/opp';

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $url_send,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $json_data,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);

                $output     = curl_exec($curl);
                $curl_error = curl_error($curl);
                $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($curl_error) {
                    parent::logger('../logs/formation/deltacrm_error.json', [
                        'error'     => $curl_error,
                        'http_code' => $http_code,
                        'output'    => $output,
                        'data_sent' => $data,
                    ]);

                    curl_close($curl);
                    return [
                        "status"       => "error",
                        "api_response" => "curl Error (HTTPS): " . $curl_error,
                        "id_part"      => "",
                        "ws_statut"    => "error",
                        "description"  => "Erreur de connexion HTTPS: " . $curl_error,
                    ];
                }
            }

            if ($http_code !== 200) {
                parent::logger('../logs/formation/deltacrm_http_error.json', [
                    'http_code' => $http_code,
                    'output'    => $output,
                    'data_sent' => $data,
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

            curl_close($curl);

            $json_response = json_decode($output);

            parent::logger('../logs/formation/deltacrm_after.json', [
                'http_code'     => $http_code,
                'raw_output'    => $output,
                'json_response' => $json_response,
                'data_sent'     => $data,
            ]);

            return DeltaCrmSubServices::response($json_response, $output);
        } catch (Exception $e) {
            parent::logger('../logs/formation/deltacrm_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return parent::common_internal_server_error();
        }
    }
    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE
     * @return array
     */
    public static function send_euro_crm($assuranceModel)
    {
        $url_send = 'https://ws-mutuelle-sante-senior.eurocrm.com/production/lead';
        // $url_send = 'https://ws-mutuelle-sante-senior.eurocrm.com/recette/lead';
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

            $data = EuroCrmServices::make_assurance_mutuel_senior_datas($classics, $specifics, $gender_category, $birthdate);
            parent::logger('../logs/assurance/euro_crm_mutuel_senior_before.json', $data);

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
            ]);

            $output = curl_exec($curl);
            parent::logger('../logs/assurance/euro_crm_mutuel_senior_after.json', json_decode($output));
            curl_close($curl);
            $json_response = json_decode($output);

            return EuroCrmServices::make_assurance_mutuel_senior_responses($json_response, $output);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_WebRivage($assuranceModel)
    {
        $url_send = 'https://publisher.api.optincollect.com/collect/parser/json/supportlocation/5578/ad/66978';
        $headers  = [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Accept: application/json',
            'accountId: API_KONTIKI_MEDIA',
            'X-Authorization-ApiKey: 8e639ae93222310a871d5923d9683e24',
        ];

        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();
        $birthdate = new DateTime($classics['birthdate']);

        try {

            $data = WebRivageSubServices::make_data($classics, $specifics, $birthdate);
            parent::logger('../logs/assurance/webrivage_mutuel_senior_before.json', $data);

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
            ]);

            $output    = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // LOG IMPORTANT : Vérifier le code HTTP
            parent::logger('../logs/assurance/webrivage_http_code.json', [
                'http_code' => $http_code,
                'output'    => $output,
            ]);

            $json_response = json_decode($output);

            parent::logger('../logs/assurance/webrivage_original.json', $json_response);

            $final_response = WebRivageSubServices::make_response($json_response, $output, $http_code);

            parent::logger('../logs/assurance/webrivage_senior_after.json', $final_response);

            return $final_response;
        } catch (Exception $e) {
            // LOG ERREUR
            parent::logger('../logs/assurance/webrivage_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return parent::common_internal_server_error();
        }
    }

    private static function mapRegimeSocial($regime_social)
    {
        // Mapping selon les valeurs LOV de la documentation
        $mapping = [
            'Regime Generale'         => "general",
            'Regime General'          => "general",
            'TNS'                     => "TNS",
            'Travailleur Non Salarie' => "TNS",
            'Alsace Moselle'          => "Alsace-Moselle",
            'Alsace-Moselle'          => "Alsace-Moselle",
            'Agricole TNS'            => "Agricole",
            'Regime Agricole'         => "Agricole",
        ];
        return isset($mapping[$regime_social]) ? $mapping[$regime_social] : 'general';
    }

    public static function FlexyLead($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        $birthdate = DateTime::createFromFormat('Y-m-d', $classics["birthdate"]);

        $url = "https://flexlead.leadbyte.co.uk/restapi/v1.3/leads";

        $gender = [
            "mr"  => "Monsieur",
            "mme" => "Madame",
        ];
        $typeHeating = [
            "bois"        => "Bois",
            "electricite" => "Electricité",
            "fuel"        => "Fioul",
            "gaz"         => "Gaz",
            "autre"       => "Autre",
        ];

        try {
            $data = [
                'campid'              => "MUTUELLE-SENIOR-EXCL",
                'sid'                 => "79",
                
                'Email'               => $classics['email'],
                'Prenom'              => $classics['firstname'],
                'Nom'                 => $classics['lastname'],
                'Date_de_naissance'   => $birthdate->format('Y-m-d'),
                'Adresse'             => $classics['address'],
                'Code_Postal'         => $classics['zipcode'],
                'Numéro_de_téléphone' => $classics['phone'],
                'profession'          => $specifics['profession'],
                'Vous_êtes_?'         => $gender[$classics['civility']],
                'regime'              => ! empty($specifics['custom_field_2']) ?
                self::mapRegimeSocial($specifics['custom_field_2']) : "general",

            ];

            parent::logger('../logs/assurance/mutuel_senior_FlexyLead_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, ["X_KEY: 02ea2da5f2daa4d9c464dd5a7450abd2"], json_encode($data));

            $responses = json_decode($curl_response[0], true);
            parent::logger('../logs/assurance/mutuel_senior_FlexyLead_after.json', $responses);

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
