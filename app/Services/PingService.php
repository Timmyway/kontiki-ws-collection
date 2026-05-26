<?php

namespace App\Services;


use Exception;
use App\Models\PingModel;
use App\Providers\CurlProvider;

class PingService extends ApiService
{
    # Lead Value private Data
    private $url_ping_lead_value = 'https://api.leadvalue.fr/ping?token=&api_key='; # for PROD

    # ping information
    private $pingModel;


    /**
     * Defisc Api Class Constructor
     * @param mixed $ping_data
     * @param mixed $client_id
     */
    public function __construct(array $ping_data, string $client_id)
    {
        $this->pingModel = new PingModel($ping_data, $client_id);
    }

    /**
     * Commonly Method that make ping for LEAD VALUE.
     * @param mixed $form_id
     * @param mixed $api_key
     * @return bool
     */
    public function common_ping_leadvalue($form_id, $api_key)
    {
        $classics = $this->pingModel->getPingData();

        try {
            $data = array(
                "form_id" => $form_id,
                "zip"     => $classics['zipcode'],
                "town"    => $classics['city']
            );
            parent::logger('../logs/ping/ping_leadvalue_before.json', $data);

            $headers = array(
                "Content-Type: application/json",
            );

            $curl_response = CurlProvider::post_requests($this->url_ping_lead_value . $api_key, $headers, json_encode($data));

            parent::logger('../logs/ping/ping_leadvalue_after.json', json_decode($curl_response[0]));

            // return response
            if ($curl_response[1] === 200) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Method that make ping for ENI.
     * @return bool
     */
    public function ping_eni()
    {
        $classics = $this->pingModel->getPingData();

        $cp_eni  = file_get_contents("../storages/app/cp_list/cp_eni.json");
        $cp_data = json_decode($cp_eni, true);

        foreach ($cp_data as $cp_val) {
            if ($cp_val["cp"] == $classics["zipcode"]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Method that make ping for Lead creative campaign "douche senior".
     * @return bool
     */
    public function ping_lead_creative_douche()
    {
        $classics = $this->pingModel->getPingData();

        $cp_data = [13, 83, 06, 04, 84, 30, 34, 11, 66, 12, 64, 65, 40, 33, 47, 24, 32, 31, 81, 82, 16, 17, 50, 29, 56, 22, 35, 44];
        $ciblage = intval(substr($classics["zipcode"], 0, 2));

        foreach ($cp_data as $cp_val) {
            if ($cp_val == $ciblage) {
                return true;
            }
        }
        return false;
    }

    /**
     * Principlae sned method dispatcher.
     * @return bool
     */
    public function ping()
    {
        $id = $this->pingModel->getClientID();

        switch ($id) {
            case 'pannsol#1':
                return $this->common_ping_leadvalue("135", "8770d71a1e36b27316d58091797a66bc");
            case 'pac#1':
                return $this->common_ping_leadvalue("127", "a1f40edf9106fa22a2ebb5207aa1f93d");
            case 'energy#1':
                return $this->ping_eni();
            case 'douche#1':
                return $this->ping_lead_creative_douche();

            default:
                return false;
        }
    }

}