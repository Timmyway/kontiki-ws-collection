<?php
namespace App\Services\SubServices;

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
    /*  public static function make_assurance_pret_datas($classics, $specifics)
    {
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $isMobile = preg_match('/^0[6-7]\d{8}$/', $phone);
        $isFix = preg_match('/^(01|02|03|04|05)[0-9]{8}$/', $phone);
        
        $gender = [
            'mr'   => 1,
            'mme'   => 2,
        ];
        
        $data = array([
            "personne"          => [
                "type"                     => 1,
                "civilite"                 => $gender[$classics['civility']],
                "nom"                      => $classics['lastname'],
                "prenom"                   => $classics['firstname'],
                "dateNaissance"            => $classics['birthdate'],
                "profession"               => $classics['situationPro'] ?? null,
                "regime"                   => $specifics['regime_social'] ?? null,
                "situationProfessionnelle" => $specifics['profession'] ?? null,
                "situationFamiliale"       => $specifics['situation_famille'] ?? null,
                "coordonnees"              => [
                    "telMobile"  => $isMobile ? $phone : null,
                    "telFixe"    => $isFix ? $phone : null,
                    "mail"       => $classics['email'],
                    "voie"       => $classics['address'],
                    "codePostal" => $classics['zipcode'],
                    "ville"      => $classics['city'],
                    "pays"       => "France"
                ]
            ],
            "codeExterne"       => strval($classics['lead_id']),
            "dateCollecte"      => date('Y-m-d\TH:i:s\Z'),
            "urlCollecte"       => $classics['referer'],
            "conformite"        => 1,
        ]);
        return $data;
    }
*/
    /**
     * Method to provide Filiassur "assurance emprunteur" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    /*public static function make_assurance_pret_responses($json_response, $output)
    {
        
        if($json_response["succeeded"]) {
            if($json_response["integratedProspect"] === 1) {
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
        $phone                 = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $isMobile              = preg_match('/^0[6-7]\d{8}$/', $phone);
        $isFix                 = preg_match('/^(01|02|03|04|05)[0-9]{8}$/', $phone);
        $mapsituationFamiliale = ($specifics['custom_field_5']);

        $gender = [
            'mr'  => 1,
            'mme' => 2,
        ];

        $data = [[
            "personne"          => [
                "type"                     => 1,
                "civilite"                 => $gender[$classics['civility']],
                "nom"                      => $classics['lastname'],
                "prenom"                   => $classics['firstname'],
                "dateNaissance"            => $classics['birthdate'],
                "profession"               => $classics['situationPro'] ?? null,
                "regime"                   => $specifics['custom_field_2'] ?? null,
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
            "codeExterne"       => strval($classics['lead_id']),
            "dateCollecte"      => date('Y-m-d\TH:i:s\Z'),
            "urlCollecte"       => $classics['referer'],
            "conformite"        => 1,
            "dateHeureCallback" => $specifics['custom_field_4'],
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
                return [
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Filiassur",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads",
                ];
            }
        } else {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "error sending leads - " . $json_response["message"],
            ];
        }
    }
}
