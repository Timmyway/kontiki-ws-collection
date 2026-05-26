<?php

namespace App\Services\SubServices;

class GoracashSubServices
{

    /**
     * Method to make a data payload ofr goracash.
     * @param mixed $classics
     * @param mixed $client_id
     * @param mixed $token
     * @param mixed $gender_category
     * @param mixed $description
     * @param mixed $type_travaux
     * @return array
     */
    public static function make_datas($classics, $client_id, $token, $gender_category, $description, $type_travaux)
    {
        $data = array(
            'client_id'    => $client_id,
            'access_token' => $token,
            'gender'       => $gender_category,
            'firstname'    => $classics['firstname'],
            'lastname'     => $classics['lastname'],
            'email'        => $classics['email'],
            'phone'        => $classics['phone'],
            'zipcode'      => $classics['zipcode'],
            'city'         => $classics['city'],
            'type'         => $type_travaux,
            'description'  => $description,
            'tracker'      => strval($classics['lead_id'])
        );

        return $data;
    }

    /**
     * method to make a response payload for goracash.
     * @param mixed $json_response
     * @param mixed $curl_response
     * @return array
     */
    public static function make_responses($json_response, $curl_response)
    {
        if ($json_response->status == "error") {
            return array(
                "status"       => "error",
                "api_response" => $curl_response,
                "id_part"      => $json_response->id ?? "",
                "ws_statut"    => "error",
                "description"  => "Error sending leads, $json_response->message"
            );
        }
        return array(
            "status"       => "success",
            "api_response" => $curl_response,
            "id_part"      => $json_response->id,
            "ws_statut"    => "ok",
            "description"  => "lead has been send successfully to GORACASH"
        );
    }
}