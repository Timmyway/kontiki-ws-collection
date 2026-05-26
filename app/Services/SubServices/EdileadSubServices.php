<?php

namespace App\Services\SubServices;

class EdileadSubServices
{

    /**
     * Method to provide EDILEAD impot format
     * @param mixed $impot
     * @return string
     */
    public static function edilead_impot_transform($impot)
    {
        if ($impot === 'entre-2500-et-5000-euros') {
            return '>2500';
        } elseif ($impot === 'entre-5000-et-10000-euros') {
            return '>5000';
        } else {
            return '>10000';
        }
    }

    public static function make_datas($classics, $specifics, $utm, $ndflow_id, $gender_category, $matrimoniale, $situation, $yearofbirth, $campaign)
    {
        $data = array(
            "civility"            => $gender_category,
            "lastname"            => $classics['lastname'],
            "firstname"           => $classics['firstname'],
            "zipcode"             => $classics['zipcode'],
            "email"               => $classics['email'],
            "phone_number_mobile" => $classics['phone'],
            "housing_status"      => $situation,
            "utm_source"          => $utm,
            "ndflow_id"           => $ndflow_id,
            "form_url_referer"    => $classics['ip']
        );

        if ($campaign == "defisc") {
            $data["year_of_birth"]    = $yearofbirth;
            $data["income_tax"]       = EdileadSubServices::edilead_impot_transform($specifics['impot']);
            $data["family_situation"] = $matrimoniale;
        } elseif ($campaign == "pac") {
            $data["kind_of_good"] = $specifics["type_logement"];
            $data["heater_type"]  = $specifics["type_chauffage"];
        }

        return $data;
    }

    /**
     * Summary of make_responses
     * @param object $json_response
     * @param string $curl_response
     * @return array
     */
    public static function make_responses($json_response, $curl_response)
    {
        if ($json_response->status == 1) {
            return array(
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->ndlead_id_created ?? "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to EDILEAD",
            );
        }
        return array(
            "status"       => "error",
            "api_response" => $curl_response,
            "id_part"      => $json_response->ndlead_id_created ?? "",
            "ws_statut"    => "error",
            "description"  => "error sending leads, " . $json_response->error_detail ?? ''
        );
    }
}