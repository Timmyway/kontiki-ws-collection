<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class CardataSubServices
{

    public static function make_data($classics, $specifics, $tag) 
    {
        $phone = str_replace(' ', '', $classics['phone']);
        $phone = preg_replace('/^(?:\+?33|0)/', '0', $phone);
        
        $date = new \DateTime($classics['birthdate']);
        $gender = ["mr" => "1", "mme" => "2"];
        $data = array(
            'source' => 101,
        );

        switch ($tag) {
            case 'jpTX':
                $data += [
                    'campagne' => $tag,
                    'civilite' => $gender[$classics['civility']],
                    'prenom' => $classics['firstname'],
                    'nom' => $classics['lastname'],
                    'date_naissance' => $date->format(('Y-m-d')),
                    'email' => $classics['email'],
                    'telephone' => $phone,
                    'adresse' => $classics['address'],
                    'code_postal' => $classics['zipcode'],
                    'ville' => $classics['city'],
                ];
                break;
        }

        return $data;
    }

    public static function response($output)
    {
        $decoded= json_decode($output, true) !== null ? json_decode($output, true) : $output;
        if ($decoded['statut'] === "OK") {
            return array(
                "status"       => "success",
                "api_response" => json_encode($decoded),
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to Cardata",
            );
        } else {
            return array(
                "status"       => "error",
                "api_response" => json_encode($decoded),
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

        $url = "https://leadflow.cardata.fr/api";
        $data = self::make_data($classics, $specifics, $campagne);

        switch($campagne) {
            case 'jpTX':
                $logfile = "assurance/audition_";
                break;
        }
        

        try {
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'cardata_before.json', $data);
            
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ]
            ));
            $output = curl_exec($curl);
            
            $logdata = json_decode($output, true) === null ?  $output : json_decode($output, true);
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'cardata_after.json', $logdata);
            curl_close($curl);
            
            return self::response($output);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}