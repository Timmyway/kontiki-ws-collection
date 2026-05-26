<?php

namespace App\Services\Finances;

use App\Providers\CurlProvider;
use App\Services\SubServices\SofanmediaSubServices;
use DateTime;
use Exception;


class RacServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to send lead "rachat de credits" info to Sofanmedia.
     * @return array
     */
    public static function send_sofanmedia($fianceModel)
    {
        $auth_data = array(
            "client_id"     => "AjqxuLcaxAOwv8h",
            "client_secret" => "uyUS976UrApaW8OlduOnN9ArwjY58L5q",
            "grant_type"    => "client_credentials"
        );

        $auth_url = "https://manager.leads-shop.fr/api/v1/oauth/token";

        $send_url = "https://manager.leads-shop.fr/api/v1/lead?access_token=";

        $classics         = $fianceModel->getClassics();
        $specifics        = $fianceModel->getSpecifics();
        $status_logement  = parent::category_situation_transform($classics['situation'], 3);
        $birthdate_object = DateTime::createFromFormat('d/m/Y', $classics["birthdate"]);
        $gender_category  = parent::category_gender_transform($classics['civility'], 6);

        try {
            $headers = array(
                "Content-Type: application/json"
            );
            // try authentication
            $auth_response = CurlProvider::post_requests($auth_url, $headers, json_encode($auth_data));
            $access_token  = json_decode($auth_response[0])->access_token;

            if ($access_token !== "") {

                $data = SofanmediaSubServices::make_rachat_datas($classics, $specifics, $status_logement, $birthdate_object, $gender_category);
                // $file = fopen("../logs/finances/test.json", "w");
                // fclose($file);
                // file_put_contents("../logs/finances/test.json", json_encode([]));
                // parent::logger("../logs/finances/test.json", $data);

                parent::logger("../logs/finances/rac_sofanmedia_before.json", $data);

                $curl_response = CurlProvider::post_requests($send_url . $access_token, array(), $data);

                $json_response = json_decode($curl_response[0]);
                parent::logger("../logs/finances/rac_sofanmedia_after.json", $json_response);

                return SofanmediaSubServices::make_rachat_responses($json_response, $curl_response);
            }
            return array(
                "status"       => "error",
                "api_response" => $auth_response[0],
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Sofanmedia web service authentification error"
            );
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }
}