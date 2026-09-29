<?php

namespace App\Services\Formations;

use App\Providers\CurlProvider;
use App\Services\SubServices\CpfSubServices;
use Exception;


class CpfServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead CPF bureautique info to a googlesheets according to the client.
     * @param mixed $formationModel
     * @param mixed $send_url
     * @param mixed $logfile
     * @param mixed $client_name
     * @return array
     */
    public static function send_bureautique_sheets($formationModel, $send_url, $logfile, $client_name)
    {
        $classics = $formationModel->getClassics();
        // $specifics        = $formationModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 0);

        try {

            $headers = array(
                "Content-Type: application/json"
            );

            $data = CpfSubServices::make_formations_bureautique_datas($classics, $gender_category);
            parent::logger('../logs/formation/' . $logfile, $data);

            CurlProvider::post_requests($send_url, $headers, json_encode($data));

            return parent::common_spreadsheets_responses("$client_name CPF", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name CPF", false);
        }
    }

    /**
     * Method to send lead CPF langue info to a googlesheets according to the client.
     * @param mixed $formationModel
     * @param mixed $send_url
     * @param mixed $logfile
     * @param mixed $client_name
     * @return array
     */
    public static function send_langue_sheets($formationModel, $send_url, $logfile, $client_name)
    {
        $classics  = $formationModel->getClassics();
        $specifics = $formationModel->getSpecifics();

        try {

            $headers = array(
                "Content-Type: application/json"
            );

            $data = CpfSubServices::make_formations_langue_datas($classics, $specifics);
            parent::logger('../logs/formation/' . $logfile, $data);

            CurlProvider::post_requests($send_url, $headers, json_encode($data));

            return parent::common_spreadsheets_responses("$client_name CPF", true);
        } catch (Exception $e) {

            return parent::common_spreadsheets_responses("$client_name CPF", false);
        }
    }
}