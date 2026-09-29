<?php

namespace App\Services\SubServices;

class MyOptinSubServices
{

    /**
     * Method to make_myoptin_pinel_datas
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $situation
     * @param mixed $dob
     * @param mixed $doi
     * @return array
     */
    public static function make_myoptin_pinel_datas($classics, $specifics, $gender_category, $situation, $dob, $doi)
    {
        $data = array(
            "firstname"    => $classics['firstname'],
            "lastname"     => $classics['lastname'],
            "phone"        => $classics['phone'],
            "mail"         => $classics['email'],
            "gender"       => $gender_category,
            "ip"           => $classics['ip'],
            "ref"          => "cid-1060",
            "geo"          => $classics['geo'],
            "dob"          => $dob,
            "doi"          => $doi,
            "database"     => "57",
            "city"         => $classics['city'],
            "zipcode"      => $classics['zipcode'],
            "custom_field" => '{"Impot":"' . $specifics['impot'] . '"}',
            "custom_data"  => '{"Situation":"' . $situation . '"}'
        );

        return $data;
    }

    /**
     * Method to make_myoptin_pinel_responses
     * @param mixed $json_response
     * @param mixed $curl_response
     * @return array
     */
    public static function make_myoptin_pinel_responses($json_response, $curl_response)
    {
        if ($json_response) {
            if ($json_response->success === 'ok') {
                return array(
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $json_response->customer_id,
                    "ws_statut"    => $json_response->success,
                    "description"  => $json_response->message
                );
            } else {
                return array(
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $json_response->success,
                    "description"  => $json_response->message
                );
            }
        }
        return array(
            "status"       => "error",
            "api_response" => "null",
            "id_part"      => "",
            "ws_statut"    => "",
            "description"  => "error sending leads, SOMETHING WENT WRONG"
        );
    }

}