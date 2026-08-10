<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class DevisPlusSubService extends \App\Services\ApiService
{
    private static $campaignCategories = [
        'PV'  => 'P12', // Panneaux Photovoltaïques
        'ITE' => 'I04', // Isolation Extérieure
        'PAC' => 'P02', // Pompe à chaleur
    ];

    private const URL_RECETTE = 'https://infos.devis-plus.com/DEVISPLUS/devis-plus_Tests_Fourn.nsf/';
    private const URL_PROD    = 'https://infos.devis-plus.com/devis-plus.nsf/';

    private const NOM_PARTENAIRE = 'Scup';
    private const CLE_ACCES      = 'Scup17072026';

    /**
     * Prépare les données du lead au format Devis+
     * @param array $classics
     * @param array $specifics
     * @param string $campagne
     * @return array
     */
    public static function makedata($classics, $specifics, $campagne)
    {
        $codeMetier = self::$campaignCategories[$campagne] ?? null;

        $data = [
            'nomPartenaire' => self::NOM_PARTENAIRE,
            'cleAcces'      => self::CLE_ACCES,
            'codeMetier'    => $codeMetier,
            'nom'           => substr($classics['lastname'] ?? '', 0, 255),
            'prenom'        => substr($classics['firstname'] ?? '', 0, 255),
            'adresse'       => substr($classics['address'] ?? '', 0, 255),
            'codePostal'    => substr($classics['zipcode'] ?? '', 0, 5),
            'ville'         => substr($classics['city'] ?? '', 0, 255),
            'telephone'     => self::formatPhoneNumber($classics['phone'] ?? ''),
            'eMail'         => substr($classics['email'] ?? '', 0, 255),
            'Profil'        => 'Particulier', // toujours "Particulier" selon la doc

           
            'civilite'      => self::mapCivilite($classics['civility'] ?? null),

           
            'Situation'     => self::mapSituation($classics['situation'] ?? null),

            'typeDhabitat'  => self::mapTypeDhabitat($specifics['type_logement'] ?? null),

           
            'typeTravaux'   => self::mapTypeTravaux($specifics['custom_field_5'] ?? null),

           
            'delai'         => self::mapDelai($specifics['custom_field_3'] ?? null),

            'AccOffre'      => '', 
            'descProjet'    => $specifics['custom_field_5'] ?? '',

            'idfiche'       => $classics['lead_id'] ?? null,
        ];

        if ($codeMetier === 'P02') {
            $data['modediff'] = self::mapModediff($specifics['mode_diffusion'] ?? null);
        }

        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private static function mapCivilite($value)
    {
        $mapping = [
            'monsieur'     => 'M',
            'm'            => 'M',
            'mr'           => 'M',
            'madame'       => 'Mme',
            'mme'          => 'Mme',
            'mademoiselle' => 'Mlle',
        ];
        return $mapping[strtolower($value ?? '')] ?? 'M'; // défaut M
    }

    private static function mapSituation($value)
    {
        $mapping = [
            'proprietaire'   => 'Proprietaire',
            'Proprietaire'   => 'Proprietaire',
            'locataire'      => 'Locataire',
            'Locataire'      => 'Locataire',
            'administrateur' => 'Architecte ou syndicat',
            'syndicat'       => 'Architecte ou syndicat',
        ];
        return $mapping[strtolower($value ?? '')] ?? 'Proprietaire';
    }

    private static function mapTypeDhabitat($value)
    {
        $mapping = [
            'maison'            => 'Maison / Pavillon',
            'Maison'            => 'Maison / Pavillon',
            'appartement'       => 'Appartement',
            'Appartement'       => 'Appartement',
            'immeuble'          => 'Immeuble',
            'bureau'            => 'Bureaux',
            'local commercial'  => 'Local commercial',
            'local industriel'  => 'Local industriel',
        ];
        return $mapping[strtolower($value ?? '')] ?? 'Maison / Pavillon';
    }

    private static function mapTypeTravaux($value)
    {
        $mapping = [
            'renovation' => 'Rénovation',
            'Renovation' => 'Rénovation',
            'rénovation' => 'Rénovation',
            'Rénovation complète' => 'Rénovation',
            'Remplacement d’un équipement' => 'Rénovation',
            
            'Installation complète'       => 'Neuf',
            'neuf'       => 'Neuf',

            'ancien'     => 'Ancien',
            'Réparation partielle' => 'Ancien',
            'Maintenance régulière'     => 'Ancien',
            'Diagnostic ou devis'     => 'Ancien',
        ];
        return $mapping[strtolower($value ?? '')] ?? 'Rénovation';
    }

    private static function mapDelai($value)
    {
        $mapping = [
            'urgent'        => 'Immédiat',
            '2 mois'        => '2 à 3 mois',
            '3 mois'        => '2 à 3 mois',
            '6 mois'        => '3 à 6 mois',
            '1 an'          => 'Plus de 6 mois',
            'plus d\'un an' => 'Plus de 6 mois',
        ];
        return $mapping[strtolower($value ?? '')] ?? '2 à 3 mois';
    }

    private static function mapModediff($value)
    {
        $mapping = [
            'radiateur' => 'radiateur',
            'plancher'  => 'plancher',
        ];
        return $mapping[strtolower($value ?? '')] ?? null;
    }

    private static function formatPhoneNumber($phone)
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');
        return substr($digits, 0, 10);
    }

    /**
     * Interprète la réponse texte brute de Devis+ (pas de JSON pour l'envoi du lead)
     * @param string $output
     * @return array
     */
    public static function responsedata($output)
    {
        $trimmed = trim($output ?? '');

        if (stripos($trimmed, 'devis transmise') !== false) {
            return [
                'status'       => 'success',
                'api_response' => $trimmed,
                'ws_statut'    => 'ok',
                'description'  => 'Lead enregistré avec succès (devis transmise)',
            ];
        }

        return [
            'status'       => 'error',
            'api_response' => $trimmed,
            'ws_statut'    => 'error',
            'description'  => $trimmed ?: 'Réponse vide ou inattendue de Devis+',
        ];
    }

    /**
     * Envoie un lead à l'API Devis+
     * @param mixed $model
     * @param string $campagne
     * @return array
     */
    public static function send($model, $campagne)
    {
        if (!isset(self::$campaignCategories[$campagne])) {
            return [
                'status'       => 'error',
                'api_response' => [],
                'ws_statut'    => 'error',
                'description'  => "Campagne '$campagne' non supportée par Devis+",
            ];
        }

        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        $data = self::makedata($classics, $specifics, $campagne);

        $url = self::URL_RECETTE . 'DevisPost?openagent&' . http_build_query($data);
        // $url = self::URL_PROD . 'DevisPost?openagent&' . http_build_query($data);

        $logfile = "travaux/devisplus_{$campagne}";

        try {
            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_before.json',
                [
                    'campagne'   => $campagne,
                    'codeMetier' => $data['codeMetier'] ?? '',
                    'email'      => $data['eMail'] ?? '',
                    'data'       => $data,
                    'date'       => date('Y-m-d H:i:s'),
                ]
            );

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
            ]);

            $output    = curl_exec($curl);
            $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($output === false) {
                throw new \Exception("Erreur cURL: {$curlError}");
            }

            if ($httpCode !== 200) {
                throw new \Exception("Code HTTP incorrect: {$httpCode}");
            }

            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_after.json',
                [
                    'campagne'     => $campagne,
                    'codeMetier'   => $data['codeMetier'] ?? '',
                    'email'        => $data['eMail'] ?? '',
                    'api_response' => $output,
                    'http_code'    => $httpCode,
                    'date'         => date('Y-m-d H:i:s'),
                ]
            );

            return self::responsedata($output);

        } catch (\Exception $e) {
            error_log("DevisPlusSubService::send() - Campagne: {$campagne} - Erreur: " . $e->getMessage());

            self::logger(
                dirname(__DIR__, 3) . '/logs/' . $logfile . '_error.json',
                [
                    'campagne' => $campagne,
                    'email'    => $data['eMail'] ?? 'unknown',
                    'error'    => $e->getMessage(),
                    'date'     => date('Y-m-d H:i:s'),
                ]
            );

            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Interroge l'état d'un lead déjà envoyé (verificationEtatLeadV2)
     * @param string $idfiche
     * @return array
     */
    public static function getEtatLead($idfiche)
    {
        $params = [
            'login'         => self::NOM_PARTENAIRE,
            'password'      => self::CLE_ACCES,
            'nomPartenaire' => self::NOM_PARTENAIRE,
            'lead_id'       => $idfiche,
        ];

        $url = self::URL_RECETTE . 'verificationEtatLeadV2?openAgent?' . http_build_query($params);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $output = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($output, true);

        if ($data === null || ($data['result'] ?? '') === 'failure') {
            return [
                'status'       => 'error',
                'api_response' => $data ?? $output,
                'description'  => 'Erreur lors de la vérification de l\'état du lead',
            ];
        }

        return [
            'status'       => 'success',
            'api_response' => $data,
            'etat'         => $data['message']['etat'] ?? null,
            'commentaire'  => $data['message']['status_comment'] ?? null,
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

    public static function getSupportedCampaigns()
    {
        return array_keys(self::$campaignCategories);
    }

    public static function getCodeMetierForCampaign($campagne)
    {
        return self::$campaignCategories[$campagne] ?? null;
    }
}