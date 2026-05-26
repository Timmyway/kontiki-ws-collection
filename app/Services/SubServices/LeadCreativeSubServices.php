<?php

namespace App\Services\SubServices;

class LeadCreativeSubServices
{

    // methods

    /**
     * Method to provide lead creative professionnal situation to id.
     * @param mixed $profession
     * @return int|string
     */
    public static function pret_lead_creative_profession_transform($profession)
    {
        switch ($profession) {
            case 'Salarie':
                return 19;
            case 'Fonctionnaire':
                return 21;
            case 'Independant':
                return 22;
            case 'Artisan-Commerciale':
                return 23;
            case 'Agriculteur':
                return 24;
            case 'Retraite-Autre':
                return 25;

            default:
                return 25;
        }
    }

    /**
     * Method to provide lead creative bank value to id.
     * @param mixed $bank
     * @return int|string
     */
    public static function pret_lead_creative_bank_transform($bank)
    {
        switch ($bank) {
            case 'Axa':
                return 35;
            case 'BNP':
                return 36;
            case 'Caisse-d-epargne':
                return 37;
            case 'Credit-mutuel':
                return 38;
            case 'Credit-agricole':
                return 39;
            case 'CIC':
                return 40;
            case 'Banque-populaire':
                return 41;
            case 'Banque-postale':
                return 42;
            case 'Credit-du-nord':
                return 43;
            case 'HSBC':
                return 44;
            case 'Societe-generale':
                return 45;
            case 'LCL':
                return 46;

            default:
                return 47;
        }
    }

    /**
     * Method to provide lead crative "objet du bien" value to id.
     * @param mixed $propriete
     * @return int|string
     */
    public static function pret_lead_creative_bien_transfirom($propriete)
    {
        switch ($propriete) {
            case 'Residence-principale':
                return 349;
            case 'Residence-secondaire':
                return 350;
            case 'Investissement-locatif':
                return 351;
            case 'Credit-cosommation':
                return 352;

            default:
                return 349;
        }
    }

    /**
     * Method to provide lead creative "objectif pret" valu to id.
     * @param mixed $objectif
     * @return int|string
     */
    public static function pret_lead_creative_objectif_transform($objectif)
    {
        switch ($objectif) {
            case 'nouveau-pret':
                return 346;
            case 'renegociation-pret':
                return 347;
            case 'investissement-pret':
                return 348;

            default:
                return 346;
        }
    }

    /**
     * Method to provide lead creative "assurance pret" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
    public static function make_assurance_pret_datas($classics, $specifics, $gender_category, $birthdate_object)
    {
        $data = array(
            'pret_type_id'                     => '11',
            'login'                            => 'Leadcreative',
            'password'                         => '9yFfc2HCxdY249',
            'lib_co_emprunteur_id'             => 55,
            'lib_profession_id'                => LeadCreativeSubServices::pret_lead_creative_profession_transform($classics['situationPro']),
            'lib_banque_id'                    => LeadCreativeSubServices::pret_lead_creative_bank_transform($specifics['bank']),
            'lib_civilite_id'                  => intval($gender_category),
            'nom'                              => $classics['lastname'],
            'prenom'                           => $classics['firstname'],
            'adresse'                          => $classics['address'],
            'code_postal'                      => $classics['zipcode'],
            'ville'                            => $classics['city'],
            'telephone_fixe'                   => $classics['phone'],
            'email'                            => $classics['email'],
            'jour_date_naissance'              => strval(intval($birthdate_object->format('d'))),
            'mois_date_naissance'              => strval(intval($birthdate_object->format('m'))),
            'annee_date_naissance'             => $birthdate_object->format('Y'),
            'provenance'                       => 'LCADE',
            'lib_type_propriete'               => LeadCreativeSubServices::pret_lead_creative_bien_transfirom($specifics['bien']),
            'lib_assurance_objectif'           => LeadCreativeSubServices::pret_lead_creative_objectif_transform($specifics['objectif']),
            'emprunteur_fumeur'                => $specifics['fumeur'] === 'Oui' ? 143 : 144,
            'emprunteur_sport_a_risque'        => 144,
            'profession_a_risque'              => 144,
            'travail_manutention'              => 144,
            'travail_hauteur'                  => 144,
            'taux_couverture'                  => 100,
            'lib_deplacement_pro'              => 144,
            'prets_assurance[1][montant_pret]' => intval($specifics['amount']),
            'prets_assurance[1][taux_pret]'    => $specifics['rate'],
            'prets_assurance[1][duree_pret]'   => intval($specifics['duration']),
            'prets_assurance[1][type_pret]'    => 353
        );

        return $data;
    }

    /**
     * Method to provide lead creative "assurance pret" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_assurance_pret_responses($json_response, $output)
    {
        if ($json_response->success === "true") {
            return array(
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $json_response->ref_devissima,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to LEAD CREATIVE",
            );
        }
        return array(
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => $json_response->ref_devissima ?? "",
            "ws_statut"    => "error",
            "description"  => "error sending leads" . $json_response->error_msg
        );
    }

    /**
     * Method to provide lead creative "douche senior" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $situation
     * @param mixed $correct_phone
     * @return array
     */
    public static function make_douche_senior_datas($classics, $specifics, $situation, $correct_phone)
    {
        $data = array(
            "firstname"                      => $classics["firstname"],
            "lastname"                       => $classics["lastname"],
            "emailaddress1"                  => $classics["email"],
            "address1_postalcode"            => $classics["zipcode"],
            "address1_telephone1"            => $correct_phone,
            "sfa_prm_message_complementaire" => "situation immobilier: $situation, type de logement: " . $specifics["type_logement"],
            "sfa_lead_origin"                => 229660008,
            "sfa_lead_origin_details"        => 229660077,
            "sfa_produit"                    => 229660000,
            "campaignid@odata.bind"          => "/campaigns(6234618f-8852-ec11-8c62-6045bd8f58fb)"
        );

        return $data;
    }

    /**
     * Method to provide lead creative "douche" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_douche_senior_responses($json_response, $output)
    {
        if ($json_response) {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => 0,
                "ws_statut"    => "error",
                "description"  => "Error on sending the lead. " . $json_response->error->message ?? "",
            );
        }
        return array(
            "status"       => "success",
            "api_response" => $output,
            "id_part"      => null,
            "ws_statut"    => "ok",
            "description"  => "lead DOUCHE SENIOR has been send successfully to LEAD CREATIVE",
        );
    }
}