<?php

namespace App\Services\Assurances;

use App\Services\SubServices\EuroCrmServices;
use DateTime;
use Exception;


class AssuranceAutoServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE
     * @return array
     */
    public static function send_euro_crm($assuranceModel)
    {
        $url_send = 'https://ws-conciergerie.eurocrm.com/recette/lead/';
        $headers = [
            'Content-Type: application/json',
            'accountId: API_KONTIKI_MEDIA',
            'apiKey: a80eb7f24fe9ec285b3860448baabef6'
        ];
        
        $classics         = $assuranceModel->getClassics();
        $specifics        = $assuranceModel->getSpecifics();
        $gender_category  = parent::category_gender_transform($classics['civility'], 3);
        $birthdate = new DateTime($classics['birthdate']);

        try {

            $data = EuroCrmServices::make_assurance_auto_datas($classics, $specifics, $gender_category, $birthdate);
            parent::logger('../logs/assurance/euro_crm_auto_before.json', $data);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data)
            ));

            $output = curl_exec($curl);
            parent::logger('../logs/assurance/euro_crm_auto_after.json', json_decode($output));
            curl_close($curl);
            $json_response = json_decode($output);

            return EuroCrmServices::make_assurance_auto_responses($json_response, $output);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }
}