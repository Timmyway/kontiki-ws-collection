<?php

namespace App\Services\SubServices;

use App\Providers\CurlProvider;
use App\Services\ApiService;

class AuxillairesVetoSubServices
{
    
    public static function make_data($classics, $specifics, $tag)
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        $training = 'Sante animale Vie Pro';
        $campaign_code = '9183';
        $couponDate = date('Y-m-d H:i:s');

        $data = array(
            'id'              => -1,
            'couponSupplier'  => 'ZZ',
            'training'        => $training,
            'couponDate'      => $couponDate,
            'firstName'       => $classics['firstname'],
            'lastName'        => $classics['lastname'],
            'email'           => $classics['email'],
            'mobile'          => $phone,
            'couponNumber'    => 0,
            'campaign'        => $campaign_code,
            
        );
        return $data;
    }

    public static function response($json_response, $http_code)
    {
        // Si la réponse n'est pas un array, c'est une erreur (string brute)
        if (!is_array($json_response)) {
            return [
                "status"       => "error",
                "api_response" => "DeltaCRM Error",
                "id_part"      => "0",
                "ws_statut"    => "error",
                "description"  => is_string($json_response) ? $json_response : "Invalid response format",
            ];
        }
        
        $status = $json_response['Status'] ?? 'Unknown';
        $id = $json_response['ID'] ?? 0;
        $description = $json_response['Description'] ?? 'No description.';

        if ($http_code === 200 && $status === 'Ok') {
            // La requête a réussi, y compris en cas de doublon
            $ws_statut = 'ok';
            $api_response = $description === '-1 Duplicate' 
                ? 'Lead created (Duplicate)' 
                : 'Lead created successfully';
            $response_status = 'success';
            $id_part = (string)$id;
        } else {
            // Cas d'erreur (Status: "Error" ou HTTP Code différent de 200)
            $ws_statut = 'error';
            $api_response = 'DeltaCRM Error';
            $response_status = 'error';
            $id_part = '0';
        }

        return [
            "status"       => $response_status,
            "api_response" => $api_response,
            "id_part"      => $id_part,
            "ws_statut"    => $ws_statut,
            "description"  => $description,
        ];
    }

    public static function logger($file, $new_data)
    {
        $json_data = file_get_contents($file);
        $data      = json_decode($json_data, true);

        $data[] = $new_data;

        $json_data = json_encode($data);
        file_put_contents($file, $json_data);
    }

}

