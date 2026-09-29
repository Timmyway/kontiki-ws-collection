<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class BatiwebSubServices
{

    public static function make_data($classics, $specifics, $campagne) 
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        $isOwner = [
            'Proprietaire' => 1,
            'Locataire'    => 2
        ];
        $housingType = [
            'Maison' => 1,
            'Appartement' => 2
        ];
        $gender = [
            'mr' => 1,
            'mme' => 2
        ];
        $security = [
            "login" => "kontiki",
            "password" => "_XU3oqyr,8eb",
            "environment" => "PROD"
        ];
        $data = array(
            'firstname' => $classics['firstname'],
            'lastname' => $classics['lastname'],
            'civility' => $gender[$classics['civility']],
            'address'  => $classics['address'],
            'zipcode' =>  $classics['zipcode'],
            'city' =>  $classics['city'],
            'email' => $classics['email'],
            'cellphone' => $phone,
            'landline' => $phone,
            'isowner' => $isOwner[$classics['situation']],
            'housingtype' => $housingType[$specifics['type_logement']],
        );
        switch ($campagne) {
            case 'PV':
                $data += [
                    'workdescription' => "Panneaux photovoltaïques",
                    'worktype' => 114,
                ];
                break;
            case 'PAC':
                $data += [
                    'workdescription' => "Pompe à chaleur air-eau",
                    'worktype' => 111,
                ];
                break;
        }

        return [
            'security' => $security,
            'request' => $data
        ];
    }

    public static function response($response)
    {
        $output = json_decode($response, true);
        if ($output['code'] === 0) {
            return array(
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to Batiweb",
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

        $url = "https://webservices.e-travaux.com/json/lead/import";
        $data = self::make_data($classics, $specifics, $campagne);

        switch ($campagne) {
            case 'PV':
                $logfile = "travaux/panneau_";
                break;
            case 'PAC':
                $logfile = "travaux/pac_";
                break;
        }

        try {
            ApiService::logger('../logs/' . $logfile . 'batiweb_before.json', $data);
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    "Accept: application/json",
                    "Content-type: application/json"
                ]
            ));
            $output = curl_exec($curl);
            
            $logdata = json_decode($output, true) === null ?  $output : json_decode($output, true);
            ApiService::logger('../logs/' . $logfile . 'batiweb_after.json', $logdata);
            
            curl_close($curl);
            
            return self::response($output);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}