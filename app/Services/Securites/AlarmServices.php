<?php

namespace App\Services\Securites;

use App\Providers\CurlProvider;
use App\Services\SubServices\AlarmSubServices;
use App\Services\SubServices\CompleoSubServices;
use DateTime;
use Exception;


class AlarmServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead ALARM info to Sector Alarm.
     * @return array
     */
    public static function send_sector_alarm($securityModel)
    {
        $url_send_pannsol_dataopp = 'https://api.cloud.sectoralarm.net/leads/v2/lead';

        $classics         = $securityModel->getClassics();
        $specifics        = $securityModel->getSpecifics();
        $birthdate_object = DateTime::createFromFormat('d/m/Y', $classics["birthdate"]);
        $lead_source_id   = "37";
        $lead_source      = "Kontiki Emailing";

        if ($specifics['canal'] == 'sms') {
            $lead_source_id = "36";
            $lead_source    = "Kontiki SMS";
        } else if ($specifics['canal'] == 'emailing-callcenter') {
            $lead_source_id = "33";
            $lead_source    = "Kontiki Emailing Call center";
        }

        try {

            $headers = array(
                "Content-Type: application/json",
                "Ocp-Apim-Subscription-Key: 6d32591c78b44e0b8b15e5811031c531"
            );

            $data = AlarmSubServices::make_alarm_datas($classics, $specifics, $birthdate_object, $lead_source_id, $lead_source);
            parent::logger('../logs/security/sector_alarm_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send_pannsol_dataopp, $headers, json_encode($data));

            parent::logger('../logs/security/sector_alarm_after.json', json_decode($curl_response[0]));

            return AlarmSubServices::make_alarm_responses($curl_response[0], $curl_response[1]);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function send_compleo_alarm($securityModel)
    {
        $url_send = 'https://compleocrm.com/api/affiliate/V1/create/';
        $headers = [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Accept: application/json',
            'key : bq1eb176qer8b16qer81b6qer8b1r6',
            'utm_source: ktk'

        ];

        $classics         = $securityModel->getClassics();
        $specifics        = $securityModel->getSpecifics();


        try {

            $data = CompleoSubServices::make_alarm_datas($classics, $specifics);
            parent::logger('../logs/security/compleo_mutuel_senior_before.json', $data);

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
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // LOG IMPORTANT : Vérifier le code HTTP
            parent::logger('../logs/security/compleo_http_code.json', [
                'http_code' => $http_code,
                'output' => $output
            ]);

            $json_response = json_decode($output);

            parent::logger('../logs/security/compleo_original.json', $json_response);


            $final_response = CompleoSubServices::make_alarm_responses($json_response, $http_code);


            parent::logger('../logs/security/compleo_senior_after.json', $final_response);

            return $final_response;
        } catch (Exception $e) {
            // LOG ERREUR
            parent::logger('../logs/security/compleo_exception.json', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return parent::common_internal_server_error();
        }
    }
}