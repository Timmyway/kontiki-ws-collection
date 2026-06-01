<?php

namespace App\Services\SubServices;

use DateTime;

class FiliassurSubServices
{
    /**
     * Method to provide Filiassur "assurance emprunteur" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
  
    /**
     * Map situation familiale texte vers entier selon API Euro CRM
     * @param string|null $situation
     * @return int|null
     */
    private static function mapSituationFamiliale($situation)
    {
        if (! $situation) {
            return null;
        }

        $mapping = [
            'Célibataire' => 1,
            'Celibataire' => 1,
            'Marié(e)'    => 2,
            'Marie(e)'    => 2,
            'Marié'       => 2,
            'Marie'       => 2,
            'Pacsé(e)'    => 3,
            'Pacse(e)'    => 3,
            'Pacsé'       => 3,
            'Pacse'       => 3,
            'Concubinage' => 4,
            'Veuf(ve)'    => 5,
            'Veuf'        => 5,
            'Veuve'       => 5,
            'Divorcé(e)'  => 6,
            'Divorce(e)'  => 6,
            'Divorcé'     => 6,
            'Divorce'     => 6,
        ];

        return $mapping[$situation] ?? 1;
    }

    private static function formatDateNaissance($date)
    {
        if (empty($date)) {
            return null;
        }

        $formats = [
            'Ymd',
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            $dateObject = \DateTime::createFromFormat($format, $date);

            if ($dateObject && $dateObject->format($format) === $date) {
                return $dateObject->format('d/m/Y');
            }
        }

        return null;
    }
    /**
     * Method to provide Filiassur "assurance emprunteur" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
    
    public static function make_assurance_pret_datas($classics, $specifics)
    {
        $phone    = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $isMobile = preg_match('/^0[6-7]\d{8}$/', $phone);
        $isFix    = preg_match('/^(01|02|03|04|05)[0-9]{8}$/', $phone);

        $gender = [
            'mr'  => 1,
            'mme' => 2,
        ];

        // custom_field_4 = "seul" ou "couple" → Quotite
        $quotite = ($specifics['custom_field_4'] ?? 'seul') === 'couple' ? 50 : 100;

        // Capital restant dû
        $capitalRestantDu = isset($classics['montant_pret'])
            ? (int) $classics['montant_pret']
            : null;

        // Taux assurance actuel (custom_field_6)
        $tauxAssurance = isset($specifics['custom_field_6'])
            ? (float) $specifics['custom_field_6']
            : null;

        // Durée restante en mois (custom_field_1)
        $dureeRestante = isset($specifics['custom_field_1'])
            ? (int) $specifics['custom_field_1']
            : null;

        $data = [[
            "personne"     => [
                "type"                     => 1,
                "civilite"                 => $gender[$classics['civility']],
                "nom"                      => $classics['lastname'],
                "prenom"                   => $classics['firstname'],
                "dateNaissance"            => self::formatDateNaissance($classics['birthdate']),
                "regime"                   => $specifics['custom_field_2'] ?? null,
                "situationProfessionnelle" => $specifics['profession'] ?? null,
                "situationFamiliale"       => self::mapSituationFamiliale($specifics['custom_field_5'] ?? null),
                "coordonnees"              => [
                    "telMobile"  => $isMobile ? $phone : null,
                    "telFixe"    => $isFix    ? $phone : null,
                    "mail"       => $classics['email'],
                    "voie"       => $classics['address'],
                    "codePostal" => $classics['zipcode'],
                    "ville"      => $classics['city'],
                    "pays"       => "France",
                ],
                "Pret"                     => [
                    "CapitalRestantDu" => $capitalRestantDu,
                    "Taux"             => $tauxAssurance,
                    "Duree"            => $dureeRestante,
                    "Emprunteurs"      => [
                        [
                            "Civilite"        => $gender[$classics['civility']],
                            "Nom"             => $classics['lastname'],
                            "Prenom"          => $classics['firstname'],
                            "DateNaissance"   => self::formatDateNaissance($classics['birthdate']),
                            "Quotite"         => $quotite,
                            "Encours"         => $capitalRestantDu,
                        ],
                    ],
                ],
            ],
            "codeExterne"  => strval($classics['lead_id']),
            "dateCollecte" => date('Y-m-d\TH:i:s\Z'),
            "urlCollecte"  => $classics['referer'],
            "conformite"   => 1,
        ]];

        return $data;
    }

    public static function make_mutuelle_sante_datas($classics, $specifics)
    {
        $phone    = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $isMobile = preg_match('/^0[6-7]\d{8}$/', $phone);
        $isFix    = preg_match('/^(01|02|03|04|05)[0-9]{8}$/', $phone);

        $gender = ['mr' => 1, 'mme' => 2];

        $regime = [
            'general' => '1',
            'agricole' => '2',
            'social' => '3',
            'alsace_moselle' => '4',
            'ALSACE_MOSELLE'   => '4',
            'REGIME_AGRICOLE'  => '3',
            'SECURITE_SOCIALE' => '1',
            'TNS'              => '2',
        ];

        // Bloc famille (conjoint / enfants) — à adapter selon tes custom_fields
        $famille = [];
        if (!empty($specifics['conjoint_datenaissance'])) {
            $famille[] = [
                "type"          => 2, // Conjoint
                "civilite"      => $gender[$specifics['conjoint_civilite'] ?? 'mme'] ?? 2,
                "nom"           => $specifics['conjoint_nom'] ?? '',
                "prenom"        => $specifics['conjoint_prenom'] ?? '',
                "dateNaissance" => $specifics['conjoint_datenaissance'],
                "regime"        => $specifics['conjoint_regime'] ?? null,
            ];
        }
        // Ajouter enfants si besoin de la même façon (type => 3)

        $data = [[
            "personne"          => [
                "type"                     => 1,
                "civilite"                 => $gender[$classics['civility']],
                "nom"                      => $classics['lastname'],
                "prenom"                   => $classics['firstname'],
                "dateNaissance"            => self::formatDateNaissance($classics['birthdate']),
                "regime"                   => $regime[$specifics['custom_field_2'] ?? null],
                "situationProfessionnelle" => $specifics['profession'] ?? null,
                "situationFamiliale"       => self::mapSituationFamiliale($specifics['custom_field_5'] ?? null),
                "coordonnees"              => [
                    "telMobile"  => $isMobile ? $phone : null,
                    "telFixe"    => $isFix ? $phone : null,
                    "mail"       => $classics['email'],
                    "voie"       => $classics['address'],
                    "codePostal" => $classics['zipcode'],
                    "ville"      => $classics['city'],
                    "pays"       => "France",
                ],
            ],
            "famille"           => $famille,
            "codeExterne"       => strval($classics['lead_id']),
            "dateCollecte"      => date('Y-m-d\TH:i:s\Z'),
            "urlCollecte"       => $classics['referer'],
            "conformite"        => 1,
            "dateHeureCallback" => $specifics['custom_field_4'] ?? null,
        ]];

        return $data;
    }

    /**
     * Method to provide Filiassur "assurance emprunteur" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_assurance_pret_responses($json_response, $output)
    {

        if ($json_response["succeeded"]) {
            if ($json_response["integratedProspect"] === 1) {
                return array(
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Filiassur",
                );
            } else {
                return array(
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads"
                );
            }
        } else {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "error sending leads - " . $json_response["message"]
            );
        }
    }
}
