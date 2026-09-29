<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class YacuzaSubServices
{

    public static function make_data($classics, $specifics, $tag) 
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        $phone = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        $date = new \DateTime($classics['birthdate']);

        $heatingType = [
            "gaz" => "gas",
            "fioul" => "oil",
            "fuel" => "oil",
            "electricite" => "electricity",
            "bois" => "wood",
            "autre" => "other"
        ];
        $homeType = [
            "Maison" => "house",
            "Appartement" => "apartment"
        ];
        $relationType = [
            "Proprietaire" => "owner",
            "Locataire" => "tenant"
        ];
         
        $data = array(
            'firstName' => $classics['firstname'],
            'lastName' => $classics['lastname'],
            'emailAddress' => $classics['email'],
            'phoneNumber' => $phone,
            'zipCode' => $classics['zipcode'],
            'homeType' => $homeType[$specifics['type_logement']],
            'relationType' => $relationType[$classics["situation"]],
            'ipAddress' =>  $classics["ip"],
            'userAgent' =>  $classics["userAgent"],
            "jobType" => "working",
        );

        switch ($tag) {
            case 'PV':
                $data += [
                    'verticalName' => "FR-solar-panel",
                ];
                break;
            case 'PAC':
                $data += [
                    'verticalName' => "FR-heat-pump",
                    'heatingType' => $heatingType[$specifics["type_chauffage"]],
                ];
                break;
            case 'ITE':
                $data += [
                    'verticalName' => "FR-external-insulation",
                    'birthYear' => $date->format('Y'),
                    'streetAddress' => $classics['address'],
                    'cityName' => $classics['city'],
                    'heatingType' => $heatingType[$specifics["type_chauffage"]],
                ];
                break;
        }

        return $data;
    }

    public static function response($output)
    {
        $decoded= json_decode($output, true) !== null ? json_decode($output, true) : $output;
        if ($decoded['success'] === true) {
            return array(
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to Yacuza",
            );
        } else {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "error sending leads"
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

        $url = "https://app.yac.la/api/lead";
        $data = self::make_data($classics, $specifics, $campagne);
        $token = base64_encode('kontiki:eMAjAgC3yG');

        switch($campagne) {
            case 'PV':
                $logfile = "travaux/panneau_";
                break;
            case 'PAC':
                $logfile = "travaux/pac_";
                break;
            case 'ITE':
                $logfile = "travaux/isolation_";
                break;
        }
        

        try {
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'yacuza_before.json', $data);
            
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Basic ' . $token,
                    'Content-Type: application/json',
                ]
            ));
            $output = curl_exec($curl);
            
            $logdata = json_decode($output, true) === null ?  $output : json_decode($output, true);
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'yacuza_after.json', $logdata);
            curl_close($curl);
            
            return self::response($output);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}