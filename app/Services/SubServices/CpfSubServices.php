<?php

namespace App\Services\SubServices;

class CpfSubServices
{

    // methods

    /**
     * method to create payload for formations bureautique.
     * @param mixed $classics
     * @param mixed $gender_transform
     * @return array
     */
    public static function make_formations_bureautique_datas($classics, $gender_transform)
    {
        $data = array(
            "Référence"           => $classics['lead_id'],
            "Date de validation"  => date('d-m-Y H:i:s'),
            "Civilité"            => $gender_transform,
            "Nom"                 => $classics['lastname'],
            "Prénom"              => $classics['firstname'],
            "Email"               => $classics['email'],
            "Numéro de téléphone" => $classics['phone'],
            "Code postal"         => $classics['zipcode'],
            "Ville"               => $classics['city']
        );

        return $data;
    }

    /**
     * method to create payload for formations langue.
     * @param mixed $classics
     * @param mixed $specifics
     * @return array
     */
    public static function make_formations_langue_datas($classics, $specifics)
    {
        $data = array(
            "Référence"           => $classics['lead_id'],
            "Date de validation"  => date('d-m-Y H:i:s'),
            "Nom"                 => $classics['lastname'],
            "Prénom"              => $classics['firstname'],
            "Email"               => $classics['email'],
            "Numéro de téléphone" => $classics['phone'],
            "Code postal"         => $classics['zipcode'],
            "Ville"               => $classics['city'],
            "Langue choisi"       => $specifics['langue']
        );

        return $data;
    }
}