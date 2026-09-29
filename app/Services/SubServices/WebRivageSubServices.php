<?php

namespace App\Services\SubServices;

class WebRivageSubServices
{
    public static function make_data($classics, $specifics, $birthdate)
    {
        $civility = self::transformCivility($classics['civility'] ?? '');
        $data = array(
            "data_email"         => $classics['email'],
            "data_phone"         => $classics['phone'],
            "data_civility"      => $civility,
            "data_firstname"     => $classics['firstname'],
            "data_lastname"      => $classics['lastname'],
            "data_birthday"      => $birthdate->format('Y-m-d'),
            "data_zipcode"       => $classics['zipcode'],
            "data_city"          => $classics['city'],
            "data_address"       => $classics['address'],
            "complex_regime"     => !empty($specifics['custom_field_7']) ?
                self::mapRegimeSocial($specifics['custom_field_7']) : 1,
            "complex_profession" => !empty($specifics['profession']) ?
                self::mapProfession($specifics['profession']) : '',
            "ipFormInput"        => $classics['ip'] ?? '',
            "referer"          => $classics['referer'] ?? '',
        );

        // Validation de l'âge (60+ uniquement)
        $age = $birthdate->diff(new \DateTime())->y;
        if ($age < 60) {
            throw new \Exception("Age minimum requis: 60 ans (âge actuel: {$age} ans)");
        }

        // Validation profession Etudiant non acceptée
        if (!empty($specifics['profession'])) {
            $professionValue = self::mapProfession($specifics['profession']);
            if ($professionValue === 17) {
                throw new \Exception("Profession 'Etudiant' non acceptée");
            }
        }



        return $data;
    }

    private static function transformCivility($civility)
    {
        $civility = strtolower(trim($civility));

        // Mapping des différentes variantes
        $mrVariants = ['monsieur', 'mr', 'm', 'm.', 'homme', 'male', 'h'];
        $mrsVariants = ['madame', 'mme', 'mademoiselle', 'mlle', 'mrs', 'ms', 'femme', 'female', 'f'];

        if (in_array($civility, $mrVariants)) {
            return 'mr';
        } elseif (in_array($civility, $mrsVariants)) {
            return 'mrs';
        }

        // Par défaut
        return 'mr';
    }

    private static function mapProfession($profession)
    {
        // Normaliser l'entrée
        $normalized = trim(ucwords(strtolower($profession)));
        $mapping = [
            'Agent d\'assurance'                 => 21,
            'Agent de maitrise'                  => 12,
            'Agriculteur'                        => 8,
            'Artisan'                            => 2,
            'Cadre'                              => 10,
            'Cadre supérieur'                    => 11,
            'Chef d\'entreprise'                 => 9,
            'Commerçant'                         => 3,
            'Conjoint collaborateur'             => 24,
            'Demandeur d\'emploi'                => 25,
            'Employé'                            => 15,
            'Etudiant'                           => 17,
            'Fonctionnaire et assimilé'          => 5,
            'Gérant société'                     => 26,
            'Homme/Femme au foyer'               => 19,
            'Mandataire social'                  => 30,
            'Militaire'                          => 18,
            'Ouvrier'                            => 16,
            'Intermédiaires'                     => 13,
            'Profession libérale'                => 4,
            'Profession Médicale Salarié'        => 22,
            'Profession Médicale Non Salarié'    => 23,
            'Retraité'                           => 6,
            'Salarié'                            => 27,
            'Sans profession'                    => 7,
            'Visiteur médical'                   => 28,
            'VRP'                                => 29,
            'Autre'                              => 20,
        ];
        return isset($mapping[$normalized]) ? $mapping[$normalized] : 7;
    }
    private static function mapRegimeSocial($regime_social)
    {
        // Mapping selon les valeurs LOV de la documentation
        $mapping = [
            'Regime Generale'         => 1,
            'Regime General'          => 1,
            'TNS'                     => 2,
            'Travailleur Non Salarie' => 2,
            'Alsace Moselle'          => 3,
            'Alsace-Moselle'          => 3,
            'Agricole TNS'            => 4,
            'Regime Agricole'         => 4,
            'Etudiant'                => 5,
            'Frontalier Suisse'       => 6,
            'Salarie Agricole'        => 10,
        ];
        return isset($mapping[$regime_social]) ? $mapping[$regime_social] : 'Securite Sociale';
    }

    public static function make_response($json_response, $output, $http_code)
    {
        // LOG pour debug
        error_log("make_response called with http_code: " . $http_code);

        // Mapping des codes HTTP (source de vérité unique)
        $codeMap = [
            200 => [
                'status'      => 'success',
                'ws_statut'   => 'success',
                'description' => 'Collecte créée avec succès'
            ],
            400 => [
                'status'      => 'error',
                'ws_statut'   => 'error',
                'description' => 'Erreur dans la requête'
            ],
            404 => [
                'status'      => 'error',
                'ws_statut'   => 'error',
                'description' => 'Ressource non trouvée'
            ],
            409 => [
                'status'      => 'error',
                'ws_statut'   => 'error',
                'description' => 'Collecte dupliquée'
            ],
        ];

        // Tentative d'extraction du message depuis la réponse (si disponible)
        $customMessage = null;

        if (!empty($json_response)) {
            // Cas 1: String pure (ex: "Duplicated Collect")
            if (is_string($json_response)) {
                $customMessage = $json_response;
            }
            // Cas 2: Objet/Array avec message
            elseif (is_object($json_response) || is_array($json_response)) {
                $data = is_array($json_response) ? (object)$json_response : $json_response;
                if (isset($data->message) && !empty($data->message)) {
                    $customMessage = $data->message;
                }
            }
        }

        // Tentative d'extraction de l'ID (peu importe le format)
        $id = "";
        if (is_object($json_response) || is_array($json_response)) {
            $data = is_array($json_response) ? (object)$json_response : $json_response;
            $possibleIdKeys = ['id', 'collectId', 'collectionId', 'id_part', 'partId'];
            foreach ($possibleIdKeys as $k) {
                if (isset($data->{$k}) && $data->{$k} !== "") {
                    $id = (string)$data->{$k};
                    break;
                }
            }
        }

        // DÉCISION BASÉE UNIQUEMENT SUR LE CODE HTTP
        if (isset($codeMap[$http_code])) {
            $mapped = $codeMap[$http_code];

            // Utiliser le message custom si disponible, sinon le message par défaut
            $finalDescription = $customMessage ?? $mapped['description'];

            // Pour les succès (200), formater une réponse JSON propre
            if ($http_code === 200) {
                // CORRECTION ICI : Créer un output structuré au lieu de garder {}
                $structuredOutput = json_encode([
                    "success" => true,
                    "message" => $finalDescription,
                    "http_code" => 200
                ]);

                return [
                    "status"       => "success",
                    "api_response" => $structuredOutput,  // ← OUTPUT STRUCTURÉ
                    "id_part"      => $id,
                    "ws_statut"    => "success",
                    "description"  => $finalDescription,
                ];
            }

            // Pour les erreurs (400, 404, 409, etc.)
            return [
                "status"       => "error",
                "api_response" => $output, // Garder la réponse brute pour debug
                "id_part"      => $id,
                "ws_statut"    => "error",
                "description"  => $finalDescription,
            ];
        }

        // Fallback pour les codes HTTP inconnus (500, 502, etc.)
        error_log("Unknown HTTP code: " . $http_code);
        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => $id,
            "ws_statut"    => "error",
            "description"  => "Erreur inconnue (HTTP " . $http_code . ")",
        ];
    }
}
