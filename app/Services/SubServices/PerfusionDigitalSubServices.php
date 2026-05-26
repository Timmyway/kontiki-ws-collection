<?php
namespace App\Services\SubServices;

use App\Services\ApiService;

class PerfusionDigitalSubServices extends \App\Services\ApiService
{
    private static $campaignCategories = [
        'PV'  => 21, // Panneaux photovoltaïques
        'PAC' => 20, // Isolation
        'ITE' => 23,
    ];
    
    public static function make_PerfusionDigital($classics, $specifics, $campagne)
    {
        $civModelId = [
            'mr'  => "1",
            'mrs' => "1",
            'm'   => "1",
            'mme' => "2",
            'F'   => "2",
        ];

        $ownerTypeModelId = [
            'Proprietaire'  => "1",
            'Proprietaires' => "1",
            'Locataire'     => "2",
            'Locataires'    => "2",
        ];

        $assetTypeModelId = [
            'Appartement' => "2",
            'appartement' => "2",
            'Maison'      => "1",
            'maison'      => "1",
        ];
        
        $delay = [
            'urgent'        => "1",
            '1 an'          => "5",
            '6 mois'        => "4",
            'dans 6 mois'   => "4",
            'plus d\'un an' => "4",
        ];

        $campaign = self::$campaignCategories[$campagne] ?? 0;

        $data = [
            'category_id'       => $campaign,
            'tracker1'          => $campagne,
            'partner_reference' => $classics['affiliateID'],
            'lead_data'         => [
                'email'           => $classics['email'],
                'firstname'       => $classics['firstname'],
                'lastname'        => $classics['lastname'],
                'civility'        => $civModelId[$classics['civility']] ?? null,
                'cellphone'       => preg_replace(
                    "/^06(\d{8})$/",
                    "+336$1",
                    preg_replace("/[^0-9]/", "", $classics['phone'])
                ),
                'city'            => $classics['city'],
                'zipcode'         => $classics['zipcode'],
                'isowner'         => $ownerTypeModelId[$classics['situation']] ?? null,
                'workdescription' => $classics['custom_field_5'] ?? "renovation",
                'housingtype'     => $assetTypeModelId[$specifics['type_logement']] ?? null,
                'delay'           => $delay[$specifics['custom_field_3']] ?? null,
            ],
        ];
        
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }
    
    // ✅ CORRECTION: Cette méthode retourne maintenant un TABLEAU au lieu d'une STRING JSON
    public static function response_by_audition($output)
    {
        $data = json_decode($output, true);
        
        // Si ce n'est PAS un tableau → réponse invalide
        if ($data === null) {
            return [
                "status"       => "error",
                "api_response" => [],
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API invalide (non JSON). Raw: " . substr($output, 0, 200),
            ];
        }

        // Cas erreur avec clé "error" (format d'erreur de l'API)
        if (isset($data['error'])) {
            return [
                "status"       => "error",
                "api_response" => $data,
                "id_part"      => $data['submission_id'] ?? "",
                "ws_statut"    => "rejected",
                "description"  => $data['error'],
            ];
        }

        // Cas succès (success = true)
        if (isset($data['success']) && $data['success'] === true) {
            return [
                "status"       => "success",
                "api_response" => $data,
                "id_part"      => $data['submission_id'] ?? "",
                "ws_statut"    => $data['status'] ?? "ACCEPTED",
                "description"  => $data['message'] ?? "Lead accepté avec succès",
            ];
        }

        // ✅ NOUVEAU: Cas refus (success = false, status = REFUSED)
        if (isset($data['success']) && $data['success'] === false) {
            return [
                "status"       => "error",
                "api_response" => $data,
                "id_part"      => $data['submission_id'] ?? "",
                "ws_statut"    => $data['status'] ?? "REFUSED",
                "description"  => $data['message'] ?? "Lead refusé",
            ];
        }

        // Cas inattendu (ne devrait plus arriver)
        return [
            "status"       => "error",
            "api_response" => $data,
            "id_part"      => "",
            "ws_statut"    => "unknown",
            "description"  => "Réponse API inattendue: " . json_encode($data),
        ];
    }

    public static function logger($file, $new_data)
    {
        $data = [];

        if (file_exists($file)) {
            $json_data = file_get_contents($file);
            $data      = json_decode($json_data, true) ?? [];
        }

        $data[] = $new_data;

        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($file, $json_data);
    }
    
    private static function decodeApiResponse($output)
    {
        // Essayer JSON d'abord
        $data = json_decode($output, true);
        if ($data !== null) {
            return $data;
        }

        // Essayer unserialize (ancien format)
        $data = @unserialize($output);
        if ($data !== false) {
            return $data;
        }

        return null;
    }
    
    public static function send($model, $campagne)
    {
        // Vérifier que la campagne est supportée
        if (! isset(self::$campaignCategories[$campagne])) {
            return [
                "status"       => "error",
                "api_response" => [],
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Campagne '$campagne' non supportée par PerfusionDigital",
            ];
        }

        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        $url = "https://leads.perfusiondigital.com/api/leads/submit";
        $data = self::make_PerfusionDigital($classics, $specifics, $campagne);
        $logfile = "travaux/perfusiondigital_{$campagne}";

        try {
            // Log avant envoi
            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_before.json',
                ['data' => $data]
            );

            // Initialiser cURL
            $curl = curl_init($url);
            $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_USERAGENT      => "partenaire-apivud-" . ($data['key'] ?? 'unknown'),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-API-Key: dd6c2930229472157203ddd814d1ef73b95b38fb887f66eb08feadca0024e3a4',
                    'X-API-Environment: TEST',
                    'Content-Length: ' . strlen($payload),
                ],
            ]);

            $output    = curl_exec($curl);
            $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            // Vérifier les erreurs cURL
            if ($output === false) {
                throw new \Exception("Erreur cURL: {$curlError}");
            }

            if ($httpCode !== 200) {
                throw new \Exception("Code HTTP incorrect: {$httpCode}");
            }

            // Décoder la réponse pour les logs
            $apiResponseDecoded = self::decodeApiResponse($output);

            // Log après envoi
            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_after.json',
                [
                    'campagne'     => $campagne,
                    'api_response' => $apiResponseDecoded ?? $output,
                    'http_code'    => $httpCode,
                    'date'         => date('Y-m-d H:i:s'),
                ]
            );

            // ✅ RETOURNE UN TABLEAU (pas une string JSON)
            return self::response_by_audition($output);

        } catch (\Exception $e) {
            error_log("PerfusiondigitalSubServices::send() - Campagne: {$campagne} - Erreur: " . $e->getMessage());

            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_error.json',
                [
                    'campagne' => $campagne,
                    'error'    => $e->getMessage(),
                    'date'     => date('Y-m-d H:i:s'),
                ]
            );

            return ApiService::common_internal_server_error();
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
    
    public static function getSupportedCampaigns()
    {
        return array_keys(self::$campaignCategories);
    }
    
    public static function getCategoryIdForCampaign($campagne)
    {
        return self::$campaignCategories[$campagne] ?? null;
    }
}