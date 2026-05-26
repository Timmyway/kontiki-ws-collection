<?php
namespace App\Services\Travaux;

use App\Services\ApiService;
use DateTime;
use Exception;

class MontEscalierServices extends ApiService
{
    /**
     * Method to provide lead data formatted for Mediamoov API
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate
     * @return array
     */
    public static function make_mediamoov_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $data = [

            "token_api"      => "5619d7ef81f75abb86a3c529013f59931420085b",

            "gender"         => self::mapCiviliteToInt($gender_category),
            "firstname"      => substr($classics['firstname'], 0, 100),      // Max 100 caractères
            "lastname"       => substr($classics['lastname'], 0, 100),       // Max 100 caractères
            "email"          => substr($classics['email'], 0, 100),          // Max 100 caractères
            "tel"            => self::formatPhoneNumber($classics['phone']), // Uniquement des chiffres, max 10
            "dateOfBirthday" => $birthdate->format('Y-m-d'),                 // Format YYYY-mm-dd
            'address'        => $classics['address'],
            "zipcode"        => substr($classics['zipcode'], 0, 100), // Max 100 caractères
            "city"           => substr($classics['city'], 0, 100),    // Max 100 caractères

            "origin"         => "Emulation",
            "source"         => "4848",
        ];

        return $data;
    }
    /**
     * Method to provide lead data formatted for Mediamoov API
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate
     * @return array
     */
    public static function make_mediamoov_emprunteur_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $department = self::extractDepartment($classics['zipcode']);
        $data       = [

            "token_api"      => "5619d7ef81f75abb86a3c529013f59931420085b",
            "gender"         => self::mapCiviliteToInt($gender_category),
            "firstname"      => substr($classics['firstname'], 0, 100),
            "lastname"       => substr($classics['lastname'], 0, 100),
            "email"          => substr($classics['email'], 0, 100),
            "tel"            => self::formatPhoneNumber($classics['phone']),
            "dateOfBirthday" => $birthdate->format('Y-m-d'),
            'address'        => $classics['address'],
            "zipcode"        => substr($classics['zipcode'], 0, 100),
            "city"           => substr($classics['city'], 0, 100),

            "management"     => $department,
            "optin"          => substr($specifics['optin'] ?? 'Oui', 0, 250),
            // "custom1"        => self::mapRegimeSocial($specifics['regime_social'] ?? ''),
            "csp"            => self::mapCSP($classics['situationPro'] ?? ''),

            "childrenCount"  => intval($specifics['custom_field_2'] ?? 0),
            "maritalStatus"  => self::mapSituationFamiliale($specifics['custom_field_5'] ?? ''),
            "custom4"        => isset($specifics['custom_field_3'])
                ? (self::normalizeDate($specifics['custom_field_3']) ?? $specifics['custom_field_3'])
                : null,

            "origin"         => "Emulation",
            "source"         => "4848",
        ];

        if (! empty($specifics['spouse_exists']) && $specifics['spouse_exists'] === true) {
            $data = array_merge($data, self::addSpouseData($specifics));
        }

        // Ajout des dates de contrat
        $data = array_merge($data, self::addContractDates($specifics));

        return $data;
    }

    public static function make_mediamoov_pac_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $situation = [
            'Proprietaire' => 'Oui',
            'Locataire'    => 'Non',
        ];
        $department = self::extractDepartment($classics['zipcode']);
        $data       = [
            "token_api"      => "5619d7ef81f75abb86a3c529013f59931420085b",
            "gender"         => self::mapCiviliteToInt($gender_category),
            "firstname"      => substr($classics['firstname'], 0, 100),
            "lastname"       => substr($classics['lastname'], 0, 100),
            "email"          => substr($classics['email'], 0, 100),
            "tel"            => self::formatPhoneNumber($classics['phone']),
            'address'        => $classics['address'],
            "zipcode"        => substr($classics['zipcode'], 0, 100),
            "city"           => substr($classics['city'], 0, 100),
            "management"     => $department,
            'custom1'   => $situation[$classics['situation']],
            'custom2'   => $specifics['type_logement'],
            'custom3'   => $specifics['type_chauffage'],
        ];

        $data = array_merge($data, self::addContractDates($specifics));

        return $data;
    }

    /**
     * Method to provide lead response data for Mediamoov API
     * @param string $output - Réponse brute de l'API
     * @return array
     */
    public static function make_mediamoov_responses(string $output)
    {

        $decoded_response = json_decode($output, true);

        // Si le décodage JSON échoue, on travaille avec la chaîne brute
        if ($decoded_response === null) {
            $response_to_check = trim($output);
        } else {
            // Si on obtient un tableau de chaînes avec guillemets, on les nettoie
            if (is_array($decoded_response)) {
                $decoded_response = array_map(
                    fn($v) => trim($v, '"'),
                    $decoded_response
                );
            }
            // Si c'est un JSON valide, on utilise la valeur décodée
            $response_to_check = $decoded_response;
        }

        if ($response_to_check === "OK") {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => (string) time(),
                "ws_statut"    => "OK",
                "description"  => "Contact est bien envoyer vers Mediamoov",
            ];
        } elseif ($response_to_check === "NOK") {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Erreur lors de l'envoi vers Mediamoov. Code NOK reçu de l'API.",
            ];
        }

        // Gère les cas où la réponse est vide ou inattendue
        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => empty($output) ? "Réponse API vide ou invalide." : "Réponse API inattendue: " . $output,
        ];
    }

    public static function make_mediamoov_emprunte_responses($output, $http_response_code)
    {
        $original_output = $output;

        // 🔹 Convertit en texte si c'est un tableau JSON unique ["OK"]
        if (is_array($output) && count($output) === 1 && isset($output[0])) {
            $output = $output[0];
        }

        // 🔹 Si le retour est du JSON, on tente de le décoder
        if (is_string($output)) {
            $decoded = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $output = $decoded;
            }
        }

        // 🔹 Cas 1 — Réponse OK (chaîne ou tableau)
        if (
            $http_response_code === 200 &&
            (
                (is_string($output) && strtoupper(trim($output, "\" \n\r\t")) === "OK") ||
                (is_array($output) && in_array("OK", array_map(fn($v) => strtoupper(trim((string) $v, "\" \n\r\t")), $output)))
            )
        ) {
            // Réponse structurée standardisée
            $structuredOutput = json_encode([
                "success"   => true,
                "message"   => "Collecte créée avec succès",
                "http_code" => 200,
            ], JSON_UNESCAPED_UNICODE);

            return [
                "status"       => "success",
                "api_response" => $structuredOutput,
                "id_part"      => "",
                "ws_statut"    => "success",
                "description"  => "Collecte créée avec succès",
            ];
        }

        // 🔹 Cas 2 — Réponse NOK explicite
        if (
            (is_string($output) && strtoupper(trim($output, "\" \n\r\t")) === "NOK") ||
            (is_array($output) && in_array("NOK", array_map(fn($v) => strtoupper(trim((string) $v, "\" \n\r\t")), $output)))
        ) {
            return [
                "status"       => "error",
                "api_response" => $original_output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Erreur d’envoi vers Mediamoov (réponse NOK).",
            ];
        }

        // 🔹 Cas 3 — Réponse HTTP 200 mais contenu vide
        if ($http_response_code === 200 && empty($output)) {
            return [
                "status"       => "success",
                "api_response" => json_encode([
                    "success"   => true,
                    "message"   => "Envoi réussi vers Mediamoov (aucune donnée retournée).",
                    "http_code" => 200,
                ], JSON_UNESCAPED_UNICODE),
                "id_part"      => "",
                "ws_statut"    => "success",
                "description"  => "Envoi réussi vers Mediamoov (aucune donnée retournée).",
            ];
        }

        // 🔹 Cas 4 — Erreur HTTP
        if ($http_response_code >= 400) {
            return [
                "status"       => "error",
                "api_response" => $original_output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Erreur HTTP {$http_response_code} lors de l’envoi vers Mediamoov.",
            ];
        }

        // 🔹 Cas 5 — Réponse inattendue
        return [
            "status"       => "error",
            "api_response" => is_string($original_output)
                ? $original_output
                : json_encode($original_output, JSON_UNESCAPED_UNICODE),
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => "Réponse inattendue reçue de Mediamoov : " .
            (is_string($original_output) ? $original_output : json_encode($original_output)),
        ];
    }
    public static function make_mediamoov_pac_responses($output, $http_response_code)
    {
        $original_output = $output;

        // 🔹 Convertit en texte si c'est un tableau JSON unique ["OK"]
        if (is_array($output) && count($output) === 1 && isset($output[0])) {
            $output = $output[0];
        }

        // 🔹 Si le retour est du JSON, on tente de le décoder
        if (is_string($output)) {
            $decoded = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $output = $decoded;
            }
        }

        // 🔹 Cas 1 — Réponse OK (chaîne ou tableau)
        if (
            $http_response_code === 200 &&
            (
                (is_string($output) && strtoupper(trim($output, "\" \n\r\t")) === "OK") ||
                (is_array($output) && in_array("OK", array_map(fn($v) => strtoupper(trim((string) $v, "\" \n\r\t")), $output)))
            )
        ) {
            // Réponse structurée standardisée
            $structuredOutput = json_encode([
                "success"   => true,
                "message"   => "Collecte créée avec succès",
                "http_code" => 200,
            ], JSON_UNESCAPED_UNICODE);

            return [
                "status"       => "success",
                "api_response" => $structuredOutput,
                "id_part"      => "",
                "ws_statut"    => "success",
                "description"  => "Collecte créée avec succès",
            ];
        }

        // 🔹 Cas 2 — Réponse NOK explicite
        if (
            (is_string($output) && strtoupper(trim($output, "\" \n\r\t")) === "NOK") ||
            (is_array($output) && in_array("NOK", array_map(fn($v) => strtoupper(trim((string) $v, "\" \n\r\t")), $output)))
        ) {
            return [
                "status"       => "error",
                "api_response" => $original_output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Erreur d’envoi vers Mediamoov (réponse NOK).",
            ];
        }

        // 🔹 Cas 3 — Réponse HTTP 200 mais contenu vide
        if ($http_response_code === 200 && empty($output)) {
            return [
                "status"       => "success",
                "api_response" => json_encode([
                    "success"   => true,
                    "message"   => "Envoi réussi vers Mediamoov (aucune donnée retournée).",
                    "http_code" => 200,
                ], JSON_UNESCAPED_UNICODE),
                "id_part"      => "",
                "ws_statut"    => "success",
                "description"  => "Envoi réussi vers Mediamoov (aucune donnée retournée).",
            ];
        }

        // 🔹 Cas 4 — Erreur HTTP
        if ($http_response_code >= 400) {
            return [
                "status"       => "error",
                "api_response" => $original_output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Erreur HTTP {$http_response_code} lors de l’envoi vers Mediamoov.",
            ];
        }

        // 🔹 Cas 5 — Réponse inattendue
        return [
            "status"       => "error",
            "api_response" => is_string($original_output)
                ? $original_output
                : json_encode($original_output, JSON_UNESCAPED_UNICODE),
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => "Réponse inattendue reçue de Mediamoov : " .
            (is_string($original_output) ? $original_output : json_encode($original_output)),
        ];
    }
    /**
     * Method to check if email exists in Mediamoov database
     * @param string $email
     * @return bool
     */
    public static function checkEmailExists($email)
    {
        $url = "https://www.media-optin.com/api/campaigns/220/checkmail?token=5619d7ef81f75abb86a3c529013f59931420085b&email=" . urlencode($email);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $output     = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            return false; // En cas d'erreur, on assume que l'email n'existe pas
        }

        return trim($output) === "OK"; // OK = email existe, NOK = email n'existe pas
    }

    /**
     * Method to check if MD5 hashed email exists in Mediamoov database
     * @param string $email
     * @return bool
     */
    public static function checkMD5EmailExists($email)
    {
        $md5Email = md5($email);
        $url      = "https://www.media-optin.com/api/campaigns/220/checkmailMD5?token=5619d7ef81f75abb86a3c529013f59931420085b&email=" . $md5Email;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $output     = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            return false;
        }

        return trim($output) === "OK";
    }

    /**
     * Format phone number to contain only digits, max 10 characters
     * @param string $phone
     * @return string
     */
    private static function formatPhoneNumber($phone)
    {
        // Supprimer tous les caractères non numériques
        $digits = preg_replace('/\D/', '', $phone);

        // Limiter à 10 chiffres maximum
        return substr($digits, 0, 10);
    }
    /**
     * Normalise une date de format YYYY-DD-MM vers YYYY-MM-DD
     * @param string $dateString
     * @return string|null
     */
    private static function normalizeDate($dateString)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateString, $matches)) {
            $year  = $matches[1];
            $day   = $matches[2];
            $month = $matches[3];
            return "$year-$month-$day";
        }

        return null;
    }

    /**
     * Map civilité texte vers entier selon API Mediamoov
     * @param string $gender_category
     * @return int
     */
    private static function mapCiviliteToInt($gender_category)
    {
        $mapping = [
            'Monsieur'     => 1,
            'Mr'           => 1,
            'M.'           => 1,
            'M'            => 1,
            'Madame'       => 2,
            'Mme'          => 2,
            'Mademoiselle' => 3,
            'Mlle'         => 3,
            'Mlle.'        => 3,
        ];

        return isset($mapping[$gender_category]) ? $mapping[$gender_category] : 1;
    }
    /**
     * Mappe la situation familiale
     * @param string $status
     * @return string
     */
    private static function mapSituationFamiliale($status)
    {
        $mapping = [
            'celibataire'            => 'C',
            'Concubin(e)'            => 'K',
            'divorce(e)'             => 'D',
            'marie(e)'               => 'M',
            'pacse(e)'               => 'P',
            'separe(e)'              => 'S',
            'union_libre'            => 'U',
            'marie_contrat_mariage'  => 'A',
            'marie_separation_biens' => 'B',
            'veuf(ve)'               => 'V',
        ];

        // Recherche case-insensitive
        $statusLower = strtolower($status);
        foreach ($mapping as $key => $value) {
            if (stripos($statusLower, $key) !== false) {
                return $value;
            }
        }

        // Si déjà au bon format
        if (in_array(strtoupper($status), ['C', 'K', 'D', 'M', 'P', 'S', 'U', 'A', 'B', 'V'])) {
            return strtoupper($status);
        }

        return ''; // Inconnu
    }

    /**
     * Mappe la catégorie socio-professionnelle
     * @param string $csp
     * @return string
     */
    private static function mapCSP($csp)
    {
        $mapping = [
            'exploitant agricole'       => '1',
            'agriculteur'               => '1',
            'chef entreprise'           => '2',
            'commerçant'                => '2',
            'artisan'                   => '2',
            'cadre'                     => '3',
            'profession intellectuelle' => '3',
            'profession intermediaire'  => '4',
            'technicien'                => '4',
            'employe'                   => '5',
            'ouvrier'                   => '6',
            'retraite'                  => '7',
            'Retraite'                  => '7',
            'sans activite'             => '8',
            'inactif'                   => '8',
        ];

        // Recherche case-insensitive
        $cspLower = strtolower($csp);
        foreach ($mapping as $key => $value) {
            if (stripos($cspLower, $key) !== false) {
                return $value;
            }
        }

        // Si déjà au format numérique
        if (is_numeric($csp) && $csp >= 1 && $csp <= 8) {
            return (string) $csp;
        }

        return ''; // Inconnu
    }

    /**
     * Mappe le régime social selon la documentation
     * @param string $regime
     * @return string
     */
    private static function mapRegimeSocial($regime)
    {
        $mapping = [
            'Régime Général'                 => 'RG',
            'CPAM'                           => 'RG',
            'Régime Local'                   => 'RL',
            'CPAM Alsace-Moselle'            => 'RL',
            'Régime Social des Indépendants' => 'RSI',
            'RSI'                            => 'RSI',
            'Mutualité Sociale Agricole'     => 'MSA',
            'MSA'                            => 'MSA',
            'AMEXA'                          => 'MSA',
            'Autres régimes spéciaux'        => 'RSP',
            'Fonctionnaires'                 => 'RSP',
            'Cheminots'                      => 'RSP',
        ];

        // Recherche case-insensitive
        foreach ($mapping as $key => $value) {
            if (stripos($regime, $key) !== false) {
                return $value;
            }
        }

        // Si déjà au bon format
        if (in_array(strtoupper($regime), ['RG', 'RL', 'RSI', 'MSA', 'RSP'])) {
            return strtoupper($regime);
        }

        return 'RG'; // Valeur par défaut
    }

    /**
     * Extrait le numéro de département depuis le code postal
     * @param string $zipcode
     * @return string
     */
    private static function extractDepartment($zipcode)
    {
        // Récupère les 2 premiers chiffres
        $dept = substr($zipcode, 0, 2);

        // Validation : doit être entre 01 et 95 (hors 20)
        $deptNum = intval($dept);
        if ($deptNum >= 1 && $deptNum <= 95 && $dept !== '20') {
            return $dept;
        }

        // Gestion de la Corse (2A et 2B)
        if (strlen($zipcode) >= 3 && substr($zipcode, 0, 2) === '20') {
            $thirdChar = substr($zipcode, 2, 1);
            if ($thirdChar === '0' || $thirdChar === '1') {
                return '2A'; // Corse-du-Sud
            } else {
                return '2B'; // Haute-Corse
            }
        }

        return $dept;
    }

    /**
     * Ajoute les données de l'assuré secondaire
     * @param array $specifics
     * @return array
     */
    private static function addSpouseData($specifics)
    {
        $spouseData = [];

        if (! empty($specifics['spouse_gender'])) {
            $spouseData['spouseGender'] = self::mapCiviliteToInt($specifics['spouse_gender']);
        }

        if (! empty($specifics['spouse_lastname'])) {
            $spouseData['spouseLastname'] = substr($specifics['spouse_lastname'], 0, 250);
        }

        if (! empty($specifics['spouse_firstname'])) {
            $spouseData['spouseFirstname'] = substr($specifics['spouse_firstname'], 0, 250);
        }

        if (! empty($specifics['spouse_birthdate'])) {
            try {
                $spouseBirthdate       = new DateTime($specifics['spouse_birthdate']);
                $spouseData['custom2'] = $spouseBirthdate->format('Y-m-d');
            } catch (Exception $e) {
                // Si la date est invalide, on ne l'ajoute pas
            }
        }

        if (! empty($specifics['spouse_regime_social'])) {
            $spouseData['custom3'] = self::mapRegimeSocial($specifics['spouse_regime_social']);
        }

        return $spouseData;
    }

    private static function addContractDates($specifics)
    {
        $contractDates = [];
        $today         = new DateTime();

        // Helper pour convertir les dates en format valide
        $convertDate = function ($dateStr) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }
            return $dateStr;
        };

        // ---- Date d’effet ----
        if (! empty($specifics['contract_start_date'])) {
            try {
                $formatted                = $convertDate($specifics['contract_start_date']);
                $startDate                = new DateTime($formatted);
                $contractDates['custom4'] = $startDate->format('Y-m-d'); // <- on prend la date envoyée
            } catch (Exception $e) {
                $contractDates['custom4'] = $today->format('Y-m-d'); // fallback
            }
        } else {
            $contractDates['custom4'] = $today->format('Y-m-d');
        }

        // ---- Date d’échéance ----
        if (! empty($specifics['contract_end_date'])) {
            try {
                $formatted                = $convertDate($specifics['contract_end_date']);
                $endDate                  = new DateTime($formatted);
                $contractDates['custom5'] = $endDate->format('Y-m-d');
            } catch (Exception $e) {
                $contractDates['custom5'] = (clone $today)->modify('+1 year')->format('Y-m-d');
            }
        } else {
            $contractDates['custom5'] = (clone $today)->modify('+1 year')->format('Y-m-d');
        }

        return $contractDates;
    }

    public static function make_montescalier_responses($json_response, $output)
    {
        // Code existant pour Euro CRM...
        if (empty($json_response)) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API vide ou invalide",
            ];
        }

        $response_data = null;

        if (is_array($json_response) && isset($json_response[0])) {
            $response_data = $json_response[0];
        } elseif (is_object($json_response)) {
            $response_data = $json_response;
        }

        if ($response_data === null) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Format de réponse API non reconnu",
            ];
        }

        if (isset($response_data->NO_DOSSIER) && ! empty($response_data->NO_DOSSIER)) {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $response_data->NO_DOSSIER,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to EURO CRM",
            ];
        }

        $error_message = "Erreur inconnue";
        if (isset($response_data->ERREUR)) {
            $error_message = $response_data->ERREUR;
        } elseif (isset($response_data->DOUBLON)) {
            $error_message = "Doublon détecté: " . $response_data->DOUBLON;
        } elseif (isset($response_data->ERROR)) {
            $error_message = $response_data->ERROR;
        } elseif (isset($response_data->message)) {
            $error_message = $response_data->message;
        }

        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => $error_message,
        ];
    }

}
