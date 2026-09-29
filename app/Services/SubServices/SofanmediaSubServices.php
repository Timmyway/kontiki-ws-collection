<?php

namespace App\Services\SubServices;

class SofanmediaSubServices
{
    /**
     * Sofanmedia method to create pinel lead into sheets.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $dob
     * @param mixed $matrimoniale
     * @param mixed $situation
     * @return array
     */
    public static function make_pinel_datas($classics, $specifics, $gender_category, $dob, $matrimoniale, $situation)
    {
        $data = array(
            "Référence"           => $classics['lead_id'],
            "Date de validation"  => date('d-m-Y H:i:s'),
            "Civilité"            => $gender_category,
            "Nom"                 => $classics['lastname'],
            "Prénom"              => $classics['firstname'],
            "Date de naissance"   => $dob,
            "Email"               => $classics['email'],
            "Numéro de téléphone" => $classics['phone'],
            "Code postal"         => $classics['zipcode'],
            "Ville"               => $classics['city'],
            "Situation"           => $situation,
            "Situation Familiale" => $matrimoniale,
            "Impot"               => $specifics['impot']
        );

        return $data;
    }

    /**
     * Sofanmedia method to create pac lead into sheets.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_transform
     * @return array
     */
    public static function make_common_travaux_sheets_datas($classics, $specifics, $gender_transform)
    {
        $data = array(
            "REFERENCE"          => $classics['lead_id'],
            "DATE_DE_VALIDATION" => date('d-m-Y H:i:s'),
            "CIVILITE"           => $gender_transform,
            "NOM"                => $classics['lastname'],
            "PRENOM"             => $classics['firstname'],
            "EMAIL"              => $classics['email'],
            "TELEPHONE"          => $classics['phone'],
            "CODE_POSTALE"       => $classics['zipcode'],
            "VILLE"              => $classics['city'],
            "STATUS_IMMOBILIER"  => $classics['situation'],
            "TYPE_CHAUFFAGE"     => $specifics['type_chauffage'],
            "TYPE_LOGEMENT"      => $specifics['type_logement'],
            "ID_BASE"            => $classics['affiliateID']
        );

        return $data;
    }

    /**
     * Sofanmedia method to create rac lead.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $status_logement
     * @param mixed $birthdate_object
     * @param mixed $gender_transform
     * @return array
     */
    public static function make_rachat_datas($classics, $specifics, $status_logement, $birthdate_object, $gender_transform)
    {

        switch ($specifics['contract']) {
            case 'CDI':
                $contract = 1;
                break;
            case 'CDD':
                $contract = 2;
                break;

            default:
                $contract = 3;
                break;
        }

        $data = array(
            "from_lead_id"              => $classics['lead_id'],
            "form_id"                   => "288",
            "pret_type_id"              => 8,
            "lib_nb_credit_immo_id"     => $specifics['nb_credit_immo'] == 0 ? 9 : $specifics['nb_credit_immo'],
            "montant_mensualites_immo"  => array_sum(array_map('intval', explode(',', json_decode($specifics['mensualites_immo'])))),
            "montant_restant_du_immo"   => array_sum(array_map('intval', explode(',', json_decode($specifics['restant_du_immo'])))),
            "lib_nb_credit_conso_id"    => $specifics['nb_credit_conso'],
            "montant_mensualites_conso" => array_sum(array_map('intval', explode(',', json_decode($specifics['mensualites_conso'])))),
            "montant_restant_du_conso"  => array_sum(array_map('intval', explode(',', json_decode($specifics['restant_du_conso'])))),
            "lib_propriete_id"          => $status_logement,
            "revenu_mensuel"            => $specifics['revenu_mensuel'],
            "lib_fichage_id"            => $specifics['fichage'] == "oui" ? 2 : 1,
            "lib_contrat_travail_id"    => $contract,
            "jour_date_naissance"       => is_bool($birthdate_object) ? '' : $birthdate_object->format('d'),
            "mois_date_naissance"       => is_bool($birthdate_object) ? '' : $birthdate_object->format('m'),
            "annee_date_naissance"      => is_bool($birthdate_object) ? '' : $birthdate_object->format('Y'),
            "pays_naissance_id"         => 1,
            "nationalite_id"            => 1,
            "lib_co_emprunteur_id"      => 1,
            "lib_civilite_id"           => $gender_transform,
            "nom"                       => $classics['lastname'],
            "prenom"                    => $classics['firstname'],
            "telephone_fixe"            => $classics['phone'],
            "email"                     => $classics['email'],
            "adresse"                   => $classics['address'],
            "code_postal"               => $classics['zipcode'],
            "ville"                     => $classics['city']
        );

        return $data;
    }

    /**
     * Sofanmedia method to create rac delivery response.
     * @param mixed $json_response
     * @param mixed $curl_response
     * @return array
     */
    public static function make_rachat_responses($json_response, $curl_response)
    {
        if ($json_response->success == false) {
            return array(
                "status"       => "error",
                "api_response" => $curl_response,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Error sending leads, " . $json_response->data->message
            );
        }
        return array(
            "status"       => "success",
            "api_response" => $curl_response,
            "id_part"      => $json_response->data->id ?? "",
            "ws_statut"    => "ok",
            "description"  => "lead has been send successfully to SOFANMEDIA via NeoManager"
        );
    }

    /**
     * Sofanmedia method to create assurance lead into sheets.
     * @param mixed $classics
     * @param mixed $specifics
     * @return array
     */
    public static function make_assurance_vie_sheets_datas($classics, $specifics) 
    {
        $data = array(
            "Référence"           => $classics['lead_id'],
            "Date de validation"  => date('d-m-Y H:i:s'),
            "lastname"                 => $classics['lastname'],
            "firstname"              => $classics['firstname'],
            "email"               => $classics['email'],
            "phone" => $classics['phone'],
            "zipcode"         => $classics['zipcode'],
            "amount"       => $specifics['amount']
        );

        return $data;
    }
}