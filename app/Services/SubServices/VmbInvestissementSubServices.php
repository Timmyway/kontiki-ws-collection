<?php

namespace App\Services\SubServices;

class VmbInvestissementSubServices
{
    /**
     * Method to provide VMB INVESTISSEMENTS impot format
     * @param mixed $impot
     * @return string
     */
    public static function vmb_impot_transform($impot)
    {
        if ($impot === 'entre-2500-et-5000-euros') {
            return 'De 2500 à 5000 euro';
        } elseif ($impot === 'entre-5000-et-10000-euros') {
            return 'De 5000 à 7500 euro';
        } else {
            return 'Plus de 10000 euro';
        }
    }

    public static function make_pinel_datas($classics, $specifics, $gender_category, $matrimoniale, $situation, $yearofbirth)
    {
        $data = array(
            "records" => [
                array(
                    "fields" => array(
                        "date-de-collecte-import"                  => date('d-m-Y'),
                        "id-fiche"                                 => "245472",
                        "statut"                                   => "Nouveau",
                        "campagne"                                 => "Défiscalisation",
                        "fournisseur"                              => "Kontiki",
                        "type-lead"                                => "CPL",
                        "civ-import"                               => $gender_category,
                        "nom-import"                               => $classics['lastname'],
                        "prenom-import"                            => $classics['firstname'],
                        "tel-import"                               => $classics['phone'],
                        "email-import"                             => $classics['email'],
                        "adresse-import"                           => $classics['address'],
                        "cp-import"                                => $classics['zipcode'],
                        "ville-import"                             => $classics['city'],
                        "situation-familiale"                      => $matrimoniale,
                        "nombre d'enfants à charge"                => $specifics['children'],
                        "votre Imposition annuelle sur le revenu"  => VmbInvestissementSubServices::vmb_impot_transform($specifics['impot']),
                        "revenu mensuel du foyer"                  => $specifics['revenuMensuel'],
                        "apport personnel"                         => $specifics['apportPerso'],
                        "capacite d'epargne mensuelle"             => $specifics['epargneMensuel'],
                        "vous êtes"                                => $situation,
                        "votre situation professionnelle actuelle" => $classics['situationPro'],
                        "annee de naissance"                       => $yearofbirth,
                    )
                )
            ]
        );

        return $data;
    }

    public static function make_pinel_responses($curl_response, $http_code, $json_response)
    {

        if (in_array($http_code, [200, 201, 202])) {
            return array(
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->records[0]->id,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to VMB INVESTISSEMENT",
            );
        }

        return array(
            "status"       => "error",
            "api_response" => $curl_response,
            "id_part"      => "0",
            "ws_statut"    => "error",
            "description"  => "error sending leads, " . $json_response->error->message
        );
    }
}