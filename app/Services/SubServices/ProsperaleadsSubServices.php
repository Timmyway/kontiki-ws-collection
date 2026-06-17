<?php

namespace App\Services\SubServices;

use App\Services\ApiService;
use App\Providers\CurlProvider;

class ProsperaleadsSubServices
{
    // =========================================================================
    // PRIVATE MAPPING METHODS
    // =========================================================================

    /**
     * @param string $phone
     * @return string
     */
    private static function formatPhone($phone)
    {
        return preg_replace(
            '/^(?:\+?33|0)/',
            '',
            preg_replace('/[^0-9]/', '', $phone)
        );
    }

    /**
     * @param string|null $situation
     * @return string
     */
    private static function mapRelationType($situation)
    {
        $mapping = [
            'Proprietaire'  => 'owner',
            'Proprietaires' => 'owner',
            'proprietaire'  => 'owner',
            'Locataire'     => 'tenant',
            'Locataires'    => 'tenant',
            'locataire'     => 'tenant',
        ];

        return $mapping[$situation] ?? 'owner';
    }

    /**
     * @param string|null $homeType
     * @return string
     */
    private static function mapHomeType($homeType)
    {
        $mapping = [
            'Appartement' => 'apartment',
            'appartement' => 'apartment',
            'Maison'      => 'house',
            'maison'      => 'house',
        ];

        return $mapping[$homeType] ?? 'house';
    }

    /**
     * @param string|null $heatingType
     * @return string
     */
    private static function mapHeatingType($heatingType)
    {
        $mapping = [
            // Gaz
            'gaz'         => 'gas',
            'Gaz'         => 'gas',
            'GAZ'         => 'gas',
            'gas'         => 'gas',

            // Électricité
            'electricite' => 'electricity',
            'Electricite' => 'electricity',
            'électricité' => 'electricity',
            'Électricité' => 'electricity',
            'electricity' => 'electricity',
            'electrique'  => 'electricity',
            'Electrique'  => 'electricity',

            // Fioul
            'fioul'       => 'oil',
            'Fioul'       => 'oil',
            'fuel'        => 'oil',
            'Fuel'        => 'oil',
            'oil'         => 'oil',

            // Bois
            'bois'        => 'wood',
            'Bois'        => 'wood',
            'wood'        => 'wood',
        ];

        return $mapping[$heatingType] ?? 'gas';
    }

    /**
     * @param string|null $jobType
     * @return string
     */
    private static function mapJobType($jobType)
    {
        $mapping = [
            // En activité
            'working'          => 'working',
            'Salarié'          => 'working',
            'salarie'          => 'working',
            'Salarie'          => 'working',
            'Cadre'            => 'working',
            'cadre'            => 'working',
            'Indépendant'      => 'working',
            'independant'      => 'working',
            'Fonctionnaire'    => 'working',
            'fonctionnaire'    => 'working',
            'Chef entreprise'  => 'working',
            'Artisan'          => 'working',
            'Commerçant'       => 'working',
            'commercant'       => 'working',

            // Sans emploi
            'jobless'          => 'jobless',
            'Demandeur emploi' => 'jobless',
            'demandeur-emploi' => 'jobless',
            'Sans emploi'      => 'jobless',
            'sans-emploi'      => 'jobless',
            'Chômeur'          => 'jobless',
            'chomeur'          => 'jobless',

            // Retraité
            'retired'          => 'retired',
            'Retraité'         => 'retired',
            'retraite'         => 'retired',
            'Retraite'         => 'retired',
        ];

        return $mapping[$jobType] ?? 'working';
    }

    /**
     * @param string|null $monthlyCost
     * @return string|null
     */
    private static function mapMonthlyCost($monthlyCost)
    {
        if (empty($monthlyCost)) {
            return null;
        }

        $allowed = ['0-100', '0-50', '50-100', '100-200', '200-x'];

        return in_array($monthlyCost, $allowed) ? $monthlyCost : null;
    }

    /**
     * @param string|null $birthdate
     * @return int|null
     */
    private static function extractBirthYear($birthdate)
    {
        if (empty($birthdate)) {
            return null;
        }

        $formats = ['Ymd', 'd/m/Y', 'd-m-Y', 'Y-m-d'];

        foreach ($formats as $format) {
            $dateObject = \DateTime::createFromFormat($format, $birthdate);
            if ($dateObject && $dateObject->format($format) === $birthdate) {
                $year = (int) $dateObject->format('Y');
                return ($year >= 1935 && $year <= 2008) ? $year : null;
            }
        }

        return null;
    }

    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    /**
     *
     * @param mixed  $classics
     * @param mixed  $specifics
     * @param string $api_key
     * @param string $cid
     * @return array
     */
    public static function make_prosperaleads_datas($classics, $specifics, $api_key, $cid)
    {
        // --- Champs obligatoires ---
        $data = [
            'api_key'        => $api_key,
            'cid'            => $cid,
            'first_name'     => $classics['firstname'],
            'last_name'      => $classics['lastname'],
            'email'          => $classics['email'],
            'phone_number'   => self::formatPhone($classics['phone']),
            'zip_code'       => $classics['zipcode'],
            'city'           => $classics['city'],
            'relation_type'  => self::mapRelationType($classics['situation'] ?? null),
            'home_type'      => self::mapHomeType($specifics['type_logement'] ?? null),
            'heating_type'   => self::mapHeatingType($specifics['type_chauffage'] ?? $specifics['heating_type'] ?? null),
            'job_type'       => self::mapJobType($classics['situationPro'] ?? $specifics['profession'] ?? null),
        ];

        // --- Champs facultatifs ---

        
        if (!empty($classics['lead_id'])) {
            $data['delivery_id'] = $classics['lead_id'];
        }

        
        if (!empty($classics['affiliateID'])) {
            $data['supplier_lid'] = $classics['affiliateID'];
        }

        
        if (!empty($classics['address'])) {
            $data['street_address'] = $classics['address'];
        }

        
        $monthlyCost = self::mapMonthlyCost($specifics['energetic_cost'] ?? null);
        if ($monthlyCost !== null) {
            $data['monthly_cost'] = $monthlyCost;
        }

        
        $birthYear = self::extractBirthYear($classics['birthdate'] ?? null);
        if ($birthYear !== null) {
            $data['birth_year'] = $birthYear;
        }

        
        if (!empty($classics['user_agent']) && strlen($classics['user_agent']) >= 50) {
            $data['user_agent'] = $classics['user_agent'];
        }

        return $data;
    }

    /**
     * @param mixed  $responses     Réponse décodée (array) de l'API
     * @param mixed  $curl_response Réponse brute cURL
     * @param string $campaign      Nom de la campagne (pour le message de description)
     * @return array
     */
    public static function make_prosperaleads_responses($responses, $curl_response, $campaign)
    {
        if (isset($responses['result']) && stripos($responses['result'], 'Waiting to validate') !== false) {
            return [
                'status'       => 'success',
                'api_response' => $curl_response,
                'id_part'      => $responses['id'] ?? '',
                'ws_statut'    => 'ok',
                'description'  => "lead has been send successfully to $campaign",
            ];
        }

       
        $errorDetail = null;
        if (!empty($responses['errors'])) {
            $errorDetail = is_array($responses['errors'])
                ? json_encode($responses['errors'], JSON_UNESCAPED_UNICODE)
                : $responses['errors'];
        } elseif (!empty($responses['message'])) {
            $errorDetail = $responses['message'];
        }

        return [
            'status'       => 'error',
            'api_response' => $curl_response,
            'id_part'      => '',
            'ws_statut'    => 'error',
            'description'  => $errorDetail ?? "error sending leads to $campaign",
        ];
    }

    /**
     * Envoie un lead (PAC / PV / ITE) à l'API Prosperaleads.
     * @param mixed  $travauxModel
     * @param string $service      "PAC", "PV" ou "ITE"
     * @return array
     */
    public static function send_prosperaleads($travauxModel, $service)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $api_key   = '0760-78eea24a-3502-b42b11-e82068bc';
        $test_mode = true; // ← passer à false pour la production

        $base_url  = $test_mode
            ? 'https://api-test.prosperalead.com/api/v1/sp/add-lead'
            : 'https://api.prosperalead.com/api/v1/sp/add-lead';

        switch ($service) {
            case 'PAC':
                $cid      = 'lf1H0Ght0';
                $campaign = 'PROSPERALEADS PAC';
                break;
            case 'PV':
                $cid      = 'EvgY2UNdp';
                $campaign = 'PROSPERALEADS PV';
                break;
            case 'ITE':
                $cid      = 'Q2LbWHqOD';
                $campaign = 'PROSPERALEADS ITE';
                break;
            default:
                return ApiService::common_internal_server_error();
        }

        $logfile = strtolower($service) . '_prosperaleads';

        try {
            $data = self::make_prosperaleads_datas($classics, $specifics, $api_key, $cid);

       
            if ($test_mode) {
                $data['response_request'] = 1;
            }

            ApiService::logger('../logs/travaux/' . $logfile . '_before.json', $data);

            $url           = $base_url . '?' . http_build_query($data);
            $curl_response = CurlProvider::get_requests($url);
            $responses     = json_decode($curl_response[0], true);

            ApiService::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            return self::make_prosperaleads_responses($responses, $curl_response, $campaign);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}