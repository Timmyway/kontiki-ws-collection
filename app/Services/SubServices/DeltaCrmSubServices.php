<?php

namespace App\Services\SubServices;

class DeltaCrmSubServices{
    
    /**
     * Method to provide lead creative "assurance auto" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
    public static function make_data($classics){        
        
        $training = 'Sante animale Vie Pro';
        $couponDate = date('Y-m-d H:i:s');
        $campaign_code = '9184';
        $id = -1;
        $couponnumber = 0;
        $couponSupplier = 'ZZ';
        $data = array(
            'id'              => $id,
            'couponSupplier'  => $couponSupplier,
            'training'        => $training,
            'couponDate'      => $couponDate,
            'firstName'       => $classics['firstname'],
            'lastName'        => $classics['lastname'],
            'email'           => $classics['email'],
            'mobile'          => $classics['phone'],
            'couponNumber'    => $couponnumber,
            'campaign'        => $campaign_code,
            
        );
        return $data;
    }
    public static function response ($json_response, $output){
        if (empty($json_response)){
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse Api vide ou invalide",
            );
        }

        $response_data = null;
        if (is_array($json_response) && isset($json_response[0])) {
            $response_data = $json_response[0];
        } elseif (is_object($json_response)) {
            $response_data = $json_response;
        }
        if ($response_data === null) {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Format de réponse inattendu",
            );
        }

        if (isset($response_data->Status) && $response_data->Status === "Ok") {
            return array(
                "status"       => "success",
                "api_response" => $response_data->Description === '-1 Duplicate' 
                    ? 'Lead created (Duplicate)' 
                    : 'Lead created successfully',
                "id_part"      => (string)$response_data->ID,
                "ws_statut"    => "ok",
                "description"  => $response_data->Description ?? 'No description.',
            );
            
        } else {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => $response_data->Description ?? 'No description.',
            );
            
        }
    }
}
?>