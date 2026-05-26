<?php

namespace App\Services\Defiscalisations;

use App\Providers\CurlProvider;
use App\Services\SubServices\MyOptinSubServices;
use App\Services\SubServices\SofanmediaSubServices;
use App\Services\SubServices\VmbInvestissementSubServices;
use DateTime;
use Exception;


class PinelServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead PINEL info to LEAD-CREATIVE.
     * @param mixed $defiscModel
     * @return array
     */
    public static function common_send_method($defiscModel, $url_send, $utm, $api_key, $filename, $client_name)
    {
        $classics  = $defiscModel->getClassics();
        $specifics = $defiscModel->getSpecifics();

        try {
            $headers = array(
                "Content-Type: application/json",
                "Authorization: $api_key"
            );

            $data = parent::common_pinel_datas($classics, $specifics, $utm);
            parent::logger('../logs/defisc/' . $filename . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send, $headers, json_encode($data));

            parent::logger('../logs/defisc/' . $filename . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return parent::common_pinel_responses($curl_response[0], $curl_response[1], $json_response, $client_name);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    /**
     * Method to send lead PINEL info to MY-OPTIN.
     * @param mixed $defiscModel
     * @return array
     */
    public static function send_my_optin($defiscModel)
    {
        $url_send = 'https://property.my-opt-in.com/platform/api?';

        $classics        = $defiscModel->getClassics();
        $specifics       = $defiscModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 1);
        try {
            $dob = DateTime::createFromFormat('d/m/Y', $classics['birthdate'])->format('m/d/Y');
        } catch (\Throwable $th) {
            $dob = $classics['birthdate'];
        }
        $doi             = $defiscModel->getDoi();
        $situation       = parent::category_situation_transform($classics['situation'], 0);

        try {

            $data = MyOptinSubServices::make_myoptin_pinel_datas($classics, $specifics, $gender_category, $situation, $dob, $doi);
            parent::logger('../logs/defisc/pinel_myoptin_before.json', $data);

            $url           = $url_send . http_build_query($data);
            $curl_response = CurlProvider::get_requests($url);

            parent::logger('../logs/defisc/pinel_myoptin_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return MyOptinSubServices::make_myoptin_pinel_responses($json_response, $curl_response);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    /**
     * Method to send lead PINEL info to VMB-INVESTISSEMENTS-SAS.
     * @param mixed $defiscModel
     * @return array
     */
    public static function send_vmb($defiscModel)
    {
        $url_send = 'https://api.airtable.com/v0/appmL9VZCPgqsEgBo/kontiki-import-defisc?maxRecords=&view=vue%20globale';

        $api_key         = "patPxhiEHTSBCShBx.6565188b34f3cea34b237c92eccdf3c4e711815ac384a96326d7d3a75759703f";
        $classics        = $defiscModel->getClassics();
        $specifics       = $defiscModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 2);
        try {
            $yearofbirth = DateTime::createFromFormat('d/m/Y', $classics['birthdate'])->format('Y');
        } catch (\Throwable $th) {
            $yearofbirth = $classics['birthdate'];
        }
        $matrimoniale    = parent::category_matrimoniale_transform($classics['matrimoniale'], 1);
        $situation       = parent::category_situation_transform($classics['situation'], 2);

        try {
            $headers = array(
                "Content-Type: application/json",
                "Authorization: Bearer $api_key"
            );

            $data = VmbInvestissementSubServices::make_pinel_datas($classics, $specifics, $gender_category, $matrimoniale, $situation, $yearofbirth);
            parent::logger('../logs/defisc/pinel_vmb_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send, $headers, json_encode($data));

            parent::logger('../logs/defisc/pinel_vmb_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return VmbInvestissementSubServices::make_pinel_responses($curl_response[0], $curl_response[1], $json_response);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_sofanmedia_client_two($defiscModel)
    {
        $url_send = 'https://script.google.com/macros/s/AKfycbxL8qz5-VUDTZIZjvUV6jM1C-N-Kv8Wc1udrKBGgR-4QLSybNZp-84_0IY-1Ra-Q5Zt/exec?gid=0';

        $classics        = $defiscModel->getClassics();
        $specifics       = $defiscModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 0);
        try {
            $dob = DateTime::createFromFormat('d/m/Y', $classics['birthdate'])->format('Y-m-d');
        } catch (\Throwable $th) {
            $dob = $classics['birthdate'];
        }
        $matrimoniale    = parent::category_matrimoniale_transform($classics['matrimoniale'], 0);
        $situation       = parent::category_situation_transform($classics['situation'], 2);

        try {
            $headers = array(
                "Content-Type: application/json"
            );

            $data = SofanmediaSubServices::make_pinel_datas($classics, $specifics, $gender_category, $dob, $matrimoniale, $situation);
            parent::logger('../logs/defisc/pinel_sofanmedia_client2.json', $data);

            CurlProvider::post_requests($url_send, $headers, json_encode($data));

            return parent::common_spreadsheets_responses("SOFANMEDIA PINEL 2", true);
        } catch (\Throwable $th) {
            //throw $th;
            return parent::common_spreadsheets_responses("SOFANMEDIA PINEL 2", false);
        }
    }
}