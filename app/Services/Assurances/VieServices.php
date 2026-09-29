<?php

namespace App\Services\Assurances;

use App\Services\SubServices\SofanmediaSubServices;
use App\Providers\CurlProvider;
use DateTime;
use Exception;


class VieServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE.
     * @return array
     */
    public static function send_assurance_sheets($assuranceModel, $url, $logfile, $client_name)
    {
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();

        try {
                        
            $data = array(
                "Référence"           => $classics['lead_id'],
                "Date de validation"  => date('d-m-Y H:i:s'),
                "Nom"                 => $classics['lastname'],
                "Prénom"              => $classics['firstname'],
                "Email"               => $classics['email'],
                "Numéro de téléphone" => $classics['phone'],
                "Code postal"         => $classics['zipcode'],
                "Montant à placer"       => $specifics['amount']
            );

            parent::logger('../logs/assurance/' . $logfile, $data);

            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("$client_name Assurance Vie", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name Assurance Vie", false);
        }
    }
    public static function send_assurance_obseque_mutac_sheets($assuranceModel)
    {
        $url = "https://script.google.com/macros/s/AKfycbzuI0941wQk5C2UnYdiYZyVnaNmbCKm-fDIEdQHcJfxS7w7ItH4OI1n3ZDR9ixf6Gm0DQ/exec";
        $logfile = "assurance_obseque_mutac.json";
        $client_name = "Mutac";
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();

        try {
                        
            $data = array(
                "DATE"                => date('d-m-Y H:i:s'),
                "NOM"                 => $classics['lastname'],
                "PRÉNOM"              => $classics['firstname'],
                "EMAIL"               => $classics['email'],
                "NUMÉRO DE TÉLÉPHONE" => $classics['phone'],
                "DATE DE NAISSANCE" => $classics['birthdate'],
                "CAPITAL OBSÈQUES"    => $specifics['amount'],
                "TYPE DE DEVIS"       => $specifics['quote_type'],
            );

            parent::logger('../logs/assurance/' . $logfile, $data);

            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("$client_name Assurance Obsèques", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name Assurance Obsèques", false);
        }
    }
}