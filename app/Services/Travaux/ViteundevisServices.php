<?php
namespace App\Services\Travaux;

use App\Services\ApiService;

class ViteundevisServices extends \App\Services\ApiService
{
    private static $campaignCategories = [
        'pannsol#20' => 37,  // Panneaux photovoltaïques
        'iso#8'      => 12,  // Isolation
        'pac#20'     => 36,  // Pompe à chaleur
        'douche#3'   => 160, // Douche sénior
        'fenetre#3'  => 72,  // Fenêtre
    ];

    /**
     * Prépare les données du lead au format ViteUnDevis API
     * @param array $classics
     * @param array $specifics
     * @param string $campagne
     * @return array
     */
    public static function make_viteundevis_emprunteur_datas($classics, $specifics, $campagne)
    {
        $key   = '176157443468ff7e2292fb968ff7e2292ff5';
        $catId = self::$campaignCategories[$campagne] ?? 0;

        // Mapping du type de personne
        $typePersonne = 1; // Par défaut: Particulier
        if (isset($specifics['custom_field_4'])) {
            $mapping = [
                'particulier'   => 1,
                'professionnel' => 2,
                'syndicat'      => 3,
                'autre'         => 4,
            ];
            $typePersonne = $mapping[strtolower($specifics['custom_field_4'])] ?? 1;
        }

        // Mapping du délai
        $delais = 3; // Par défaut: Dans l'année
        if (isset($specifics['custom_field_3'])) {
            $mapping = [
                'urgent'        => 1,
                '6 mois'        => 2,
                '1 an'          => 3,
                'plus d\'un an' => 4,
            ];
            $delais = $mapping[strtolower($specifics['custom_field_3'])] ?? 3;
        }

        // Mapping du type de bien
        $typeBien = 2; // Par défaut: Maison
        if (isset($specifics['type_logement'])) {
            $mapping = [
                'appartement' => 1,
                'maison'      => 2,
                'immeuble'    => 3,
                'bureau'      => 4,
                'terrain'     => 5,
                'autre'       => 6,
            ];
            $typeBien = $mapping[strtolower($specifics['type_logement'])] ?? 2;
        }

        // Mapping de la situation
        $situation = 1; // Par défaut: Propriétaire
        if (isset($specifics['situation_immo'])) {
            $mapping = [
                'proprietaire'       => 1,
                'futur propriétaire' => 1,
                'locataire'          => 2,
                'futur locataire'    => 2,
                'administrateur'     => 3,
                'autre'              => 4,
            ];
            $situation = $mapping[strtolower($specifics['situation_immo'])] ?? 1;
        }

        $data = [
            "nom"           => substr($classics['lastname'], 0, 255),
            "prenom"        => substr($classics['firstname'], 0, 255),
            "email"         => substr($classics['email'], 0, 255),
            "adresse1"      => substr($classics['address'] ?? '', 0, 255),
            "cp"            => substr($classics['zipcode'], 0, 5),
            "ville"         => substr($classics['city'], 0, 255),
            "cp_projet"     => substr($classics['zipcode'], 0, 5),
            "ville_projet"  => substr($classics['city'], 0, 255),
            "tp"            => $typePersonne,
            "delais"        => $delais,
            "key"           => $key,
            "tel"           => self::formatPhoneNumber($classics['phone']),
            "cat_id"        => $catId,
            "type_bien"     => $typeBien,
            "situation"     => $situation,
            "description"   => $specifics["custom_field_5"] ?? 'Demande de devis',
            "format_return" => 'json', // Demander JSON (mais l'API peut retourner serialize)
        ];

        // Nettoyer les valeurs null
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Formate un numéro de téléphone
     * @param string $phone
     * @return string
     */
    private static function formatPhoneNumber($phone)
    {
        $digits = preg_replace('/\D/', '', $phone);
        return substr($digits, 0, 10);
    }

    /**
     * Décode la réponse de l'API (supporte JSON et serialize)
     * @param string $output
     * @return array|null
     */
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

    /**
     * Interprète la réponse de l'API ViteUnDevis selon la documentation officielle
     * @param string $output
     * @return array
     */
    public static function response_by_audition($output)
    {
        $data = self::decodeApiResponse($output);

        // Si le décodage échoue complètement
        if ($data === null) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API invalide (ni JSON ni serialize)",
                "details"      => [
                    "accept"     => 0,
                    "recommande" => 0,
                    "buyers"     => 0,
                    "cpl"        => 0,
                    "ecpl"       => 0,
                    "error"      => 1,
                ],
            ];
        }

        // Gestion des anciennes réponses (format serialize avec code_retour)
        if (isset($data['code_retour'])) {
            return self::handleLegacyResponse($data);
        }

        // Nouvelle API: vérifier si c'est une erreur
        if (isset($data['error']) && $data['error'] == 1) {
            return [
                "status"       => "error",
                "api_response" => $data,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => $data['text'] ?? 'Erreur API non spécifiée',
                "details"      => [
                    "accept"     => $data['accept'] ?? 0,
                    "recommande" => $data['recommande'] ?? 0,
                    "buyers"     => $data['buyers'] ?? 0,
                    "cpl"        => $data['cpl'] ?? 0,
                    "ecpl"       => $data['ecpl'] ?? 0,
                    "error"      => 1,
                ],
            ];
        }

        // Lead accepté par un acheteur direct
        if (isset($data['accept']) && $data['accept'] == 1) {
            return [
                "status"       => "success",
                "api_response" => $data,
                "id_part"      => $data['devis_id'] ?? "",
                "ws_statut"    => "accepted",
                "description"  => $data['text'] ?? "Lead accepté - Acheteur trouvé",
                "details"      => [
                    "accept"     => 1,
                    "recommande" => $data['recommande'] ?? 1,
                    "buyers"     => $data['buyers'] ?? 1,
                    "cpl"        => $data['cpl'] ?? 0,
                    "ecpl"       => $data['ecpl'] ?? 0,
                    "error"      => 0,
                ],
            ];
        }

        // Lead recommandé (pas d'acheteur direct mais potentiel sur réseaux secondaires)
        if (isset($data['recommande']) && $data['recommande'] == 1) {
            return [
                "status"       => "success",
                "api_response" => $data,
                "id_part"      => $data['devis_id'] ?? "",
                "ws_statut"    => "recommended",
                "description"  => $data['text'] ?? "Lead recommandé - Acheteur secondaire potentiel",
                "details"      => [
                    "accept"     => 0,
                    "recommande" => 1,
                    "buyers"     => $data['buyers'] ?? 0,
                    "cpl"        => $data['cpl'] ?? 0,
                    "ecpl"       => $data['ecpl'] ?? 0,
                    "error"      => 0,
                ],
            ];
        }

        // Lead envoyé sans garantie
        return [
            "status"       => "success",
            "api_response" => $data,
            "id_part"      => $data['devis_id'] ?? "",
            "ws_statut"    => "pending",
            "description"  => $data['text'] ?? "Lead envoyé - Recherche d'acheteur en cours",
            "details"      => [
                "accept"     => 0,
                "recommande" => 0,
                "buyers"     => $data['buyers'] ?? 0,
                "cpl"        => $data['cpl'] ?? 0,
                "ecpl"       => $data['ecpl'] ?? 0,
                "error"      => 0,
            ],
        ];
    }

    /**
     * Gère les anciennes réponses API (format avec code_retour)
     * @param array $data
     * @return array
     */
    private static function handleLegacyResponse($data)
    {
        // Code 200 = succès
        if (isset($data['code_retour'][0]['code']) && $data['code_retour'][0]['code'] == 200) {
            return [
                "status"       => "success",
                "api_response" => $data,
                "id_part"      => $data['devis_data']['devis_id'] ?? "",
                "ws_statut"    => "ok",
                "description"  => "Lead enregistré avec succès",
                "details"      => [
                    "devis_id"          => $data['devis_data']['devis_id'] ?? "",
                    "devis_reversement" => $data['devis_data']['devis_reversement'] ?? 0,
                    "devis_hash"        => $data['devis_data']['devis_hash'] ?? "",
                    "devis_montant_attention" => $data['devis_data']['devis_montant_attention'] ?? "",
                ],
            ];
        }

        // Collecte des erreurs
        $errors = [];
        if (isset($data['code_retour']) && is_array($data['code_retour'])) {
            foreach ($data['code_retour'] as $error) {
                $errors[] = "[Code {$error['code']}] {$error['code_texte']}";
            }
        }

        return [
            "status"       => "error",
            "api_response" => $data,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => implode(' | ', $errors) ?: 'Erreur inconnue',
            "details"      => [
                "errors" => $errors,
            ],
        ];
    }

    /**
     * Enregistre les logs dans un fichier JSON
     * @param string $file
     * @param array $new_data
     * @return void
     */
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

    /**
     * Envoie un lead à l'API ViteUnDevis
     * @param mixed $model
     * @param string $campagne (ex: 'pannsol#20', 'iso#8', etc.)
     * @return array
     */
    public static function send($model, $campagne)
    {
        // Vérifier que la campagne est supportée
        if (!isset(self::$campaignCategories[$campagne])) {
            return [
                "status"       => "error",
                "api_response" => [],
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Campagne '$campagne' non supportée par ViteUnDevis",
            ];
        }

        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        // URL de test - décommenter pour production
        $url = "https://www.viteundevis.com/api/get.php?test=1";
        // $url = "https://www.viteundevis.com/api/get.php";

        $data = self::make_viteundevis_emprunteur_datas($classics, $specifics, $campagne);

        // Logs avec nom de campagne
        $logfile = "travaux/viteundevis_{$campagne}";

        try {
            // Log avant envoi
            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_before.json',
                [
                    'campagne'    => $campagne,
                    'category_id' => $data['cat_id'] ?? 0,
                    'email'       => $data['email'],
                    'data'        => $data,
                    'date'        => date('Y-m-d H:i:s'),
                ]
            );

            // Initialiser cURL
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($data),
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_USERAGENT      => "partenaire-apivud-" . ($data['key'] ?? 'unknown'),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/x-www-form-urlencoded',
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

            // Log après envoi avec réponse décodée
            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_after.json',
                [
                    'campagne'     => $campagne,
                    'category_id'  => $data['cat_id'] ?? 0,
                    'email'        => $data['email'],
                    'api_response' => $apiResponseDecoded ?? $output,
                    'http_code'    => $httpCode,
                    'date'         => date('Y-m-d H:i:s'),
                ]
            );

            return self::response_by_audition($output);

        } catch (\Exception $e) {
            // Log de l'erreur
            error_log("ViteundevisServices::send() - Campagne: {$campagne} - Erreur: " . $e->getMessage());

            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_error.json',
                [
                    'campagne' => $campagne,
                    'email'    => $data['email'] ?? 'unknown',
                    'error'    => $e->getMessage(),
                    'date'     => date('Y-m-d H:i:s'),
                ]
            );

            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Récupère les campagnes supportées
     * @return array
     */
    public static function getSupportedCampaigns()
    {
        return array_keys(self::$campaignCategories);
    }

    /**
     * Récupère l'ID de catégorie pour une campagne
     * @param string $campagne
     * @return int|null
     */
    public static function getCategoryIdForCampaign($campagne)
    {
        return self::$campaignCategories[$campagne] ?? null;
    }
}