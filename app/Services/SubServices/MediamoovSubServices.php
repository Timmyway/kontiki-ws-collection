<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class MediamoovSubServices
{

    public static function make_audition_data($classics, $campagne) 
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        $data = [
            'token_api' => '5619d7ef81f75abb86a3c529013f59931420085b',
            'firstname' => $classics['firstname'],
            'lastname' => $classics['lastname'],
            'address'  => $classics['address'],
            'zipcode' =>  $classics['zipcode'],
            'city' =>  $classics['city'],
            'email' => $classics['email'],
            'tel' => $phone,
            'optin' => 1
        ];

        switch ($campagne) {
            case "217":
                $date = new \Datetime($classics['birthdate']);
                $civ = [
                    "mr" => 1,
                    "mme" => 2
                ];
               
                $data += [
                    "gender" => $civ[$classics['civility']],
                    "dateOfBirthday" => $date->format('Y-m-d')
                ];
                break;
        }

        return $data;
    }

    public static function response_by_audition($output)
    {
        $data = json_decode($output, true) === null ?  $output : json_decode($output, true);

        if (isset($data[0][0]['contact_exist']) && $data[0][0]['contact_exist'] !== "") {
            return array(
                "status"       => "error",
                "api_response" => $data,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => $data[0][0]['contact_exist']
            );
            
        } else if(isset($data['status']) && $data['status'] === "error"){
            return array(
                "status"       => "error",
                "api_response" => $data,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => $data['message']
            );
        } else {
            return array(
                "status"       => "success",
                "api_response" => $data,
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to Mediamoov",
            );
        }
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

        $url = "https://www.media-optin.com/api/campaigns/$campagne/contacts";
        $data = self::make_audition_data($classics, $campagne);
        $logfile = "assurance/audition_$campagne";

        try {
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'mediamoov_before.json', $data);
            
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $data,
            ));
            $output = curl_exec($curl);

            var_dump($output);

            $logdata = [
                "email" => $classics['email'],
                "api_response" => json_decode($output, true) === null ?  $output : json_decode($output, true),
                "date" => date('Y-m-d H:i:s')
            ];

            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'mediamoov_after.json', $logdata);
            
            curl_close($curl);
            
            return self::response_by_audition($output);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    } 
}