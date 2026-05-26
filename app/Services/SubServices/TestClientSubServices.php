<?php

namespace App\Services\SubServices;

use App\Providers\CurlProvider;
use App\Services\ApiService;

class TestClientSubServices
{

    public static function make_data($classics, $specifics, $tag) 
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        $phone = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        $date = new \DateTime($classics['birthdate']);
                 
        $data = array(
            'TestfirstName' => $classics['firstname'],   // <-- corrigé
            'TestlastName' => $classics['lastname'],
            'TestemailAddress' => $classics['email'],       // <-- corrigé
            'TestphoneNumber' => $phone,                   // <-- corrigé
            'TestzipCode' => $classics['zipcode'],
            'ipAddress' =>  $classics["address"],
            'Type animal' => $specifics['custom_field_1'] ?? '',
            'Age Animal' => $specifics['custom_field_2'] ?? '',
            'Vaccin Animal' => $specifics['custom_field_3'] ?? '',
            'Marque Animal' => $specifics['custom_field_4'] ?? '',
            'Race Animal' => $specifics['custom_field_5'] ?? '',
            'Assurance Animal' => $specifics['custom_field_6'] ?? '',
            
        );

        switch ($tag) {
            case 'assurance_animaux':
                $data += [
                    'sheet' => 'assurance_animaux',
                    'TestfirstName' => $classics['name'],
                    'TestlastName' => $classics['lastname'],
                    'TestemailAddress' => $classics['email'],
                    "TestphoneNumber" => $classics['phone'],
                    "TestzipCode"      => $classics['zipcode'],
                    "ipAddress"         => $classics['address'],
                    'Type animal' => $specifics['custom_field_1'],
                    'Age Animal' => $specifics['custom_field_2'],
                    'Vaccin Animal' => $specifics['custom_field_3'],
                    'Marque Animal' => $specifics['custom_field_4'],
                    'Race Animal' => $specifics['custom_field_5'],
                    'Assurance Animal' => $specifics['custom_field_6'],
                ];
                break;
        }

        return $data;
    }

    public static function response($output)
    {
        if ($output) {
                return array(
                    "status"       => "success",
                    "api_response" => "writting to the spreedsheets OK",
                    "id_part"      => "0",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to TestClient googlesheets",
                );
            }
            return array(
                "status"       => "error",
                "api_response" => "writting to the spreedsheets ERROR",
                "id_part"      => "0",
                "ws_statut"    => "error",
                "description"  => "error sending leads, Something went wrong when writting to the drive"
            );
    }

    public static function logger($file, $new_data)
    {
        $json_data = file_get_contents($file);
        $data      = json_decode($json_data, true);

        $data[] = $new_data;

        $json_data = json_encode($data);
        file_put_contents($file, $json_data);
    }
public static function send($model, $campagne)

    {

        $classics  = $model->getClassics();

        $specifics = $model->getSpecifics();

       

        //https://docs.google.com/spreadsheets/d/1PJdtuGBGM4GqQP-WRjg_r7XD5NoYRHMjsfMf7KCfqkc/edit?hl=fr&gid=0#gid=0


        $url = "https://script.google.com/macros/s/AKfycby2lmSnWwRKDnbV-IppimZeIoAGGKdYKvbxMRail-3-xI-9Zv6lP_fI3BV3M1VLJhSI/exec";

        $data = self::make_data($classics, $specifics, $campagne);



        switch($campagne) {

            case 'assurance_animaux':

                $logfile = "assurance/auto_";

                break;

        }



        try {

             CurlProvider::post_requests($url, [], $data);
            return self::response(true);

        } catch (\Exception $e) {

            return ApiService::common_internal_server_error();

        }

    }
    
}
