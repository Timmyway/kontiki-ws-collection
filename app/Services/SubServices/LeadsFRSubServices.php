<?php

namespace App\Services\SubServices;

use App\Services\ApiService;

class LeadsFRSubServices
{

    public static function make_data($classics, $specifics, $tag) 
    {
        $date = new \DateTime($classics['birthdate']);

        $civilite = [
            "mr" => "M",
            "mme" => "F"
        ];

        $heatingType = [
            "gaz" => "gaz",
            "fioul" => "fioul",
            "fuel" => "fioul",
            "electricite" => "electricite",
            "bois" => "charbon",
            "autre" => "nc"
        ];
        $homeType = [
            "Maison" => "maison",
            "Appartement" => "appartement"
        ];
        $relationType = [
            "Proprietaire" => "proprietaire",
            "Locataire" => "locataire"
        ];
         
        $data = array(
            'apikey' => 'JBVFYGBXHR7YM26RO7L4',
            'civilite' => $civilite[$classics['civility']],
            'prenom' => $classics['firstname'],
            'nom' => $classics['lastname'],
            'email' => $classics['email'],
            'telmob' => $classics['phone'],
            'cp' => $classics['zipcode'],
            'ville' => $classics['city'],
            'adresse' => $classics['address'],
            'ddn' => !empty($classics['birthdate']) ? date('Y-m-d', strtotime($classics['birthdate'])) : '1952-01-01',
            "pays" => "FR",
            "rgpdtel" => 1,
            "rgpdmail" => 1,
            "rgpdsms" => 1
        );

        switch ($tag) {
            case 'PV':
                $data += [
                    'sitekey' => "kontiki_media_ltd_pv",
                    'area' => 'RENO',
                    'besoin_photo' => 1,
                    'logement' => $homeType[$specifics['type_logement']], //
                    'statut' => $relationType[$classics["situation"]], //
                    'residence' => "principale", //
                    "profession" => "nc",
                ];
                break;
            case 'PAC':
                $data += [
                    'sitekey' => "kontiki_media_ltd_pac",
                    'area' => 'RENO',
                    'energy' => $heatingType[$specifics['type_chauffage']],
                    'besoin_pac' => 1,
                    'logement' => $homeType[$specifics['type_logement']], //
                    'statut' => $relationType[$classics["situation"]], //
                    'residence' => "principale", //
                    "profession" => "nc",
                ];
                break;
            case 'ITE':
                $data += [
                    'sitekey' => "kontiki_media_ltd_ite",
                    'area' => 'RENO',
                    'besoin_iso_murext' => 1,
                    'logement' => $homeType[$specifics['type_logement']], //
                    'statut' => $relationType[$classics["situation"]], //
                    'residence' => "principale", //
                    "profession" => "nc",
                ];
                break;
            case 'MUTU':
                $data += [
                    'sitekey' => "kontiki_media_ltd_mutuelle-senior",
                    'area' => 'MUTU',
                    "profession" => "nc",
                    "assured" => $specifics["custom_field_1"],
                    "social_regime" => $specifics["custom_field_2"],
                    "expat" => "0"
                ];
        }

        return $data;
    }

    public static function response($output)
    {
        $decoded = json_decode($output, true) !== null ? json_decode($output, true) : $output;
        if (isset($decoded['success']) && $decoded['success'] === 1) {
            return array(
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $decoded["lead_id"],
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to Leads.fr",
            );
        } else {
            return array(
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "error sending leads : " . $decoded['message']
            );
        }
    }

    public static function logger($file, $new_data)
    {
        // Créer le répertoire s'il n'existe pas
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Lire les données existantes si le fichier existe, sinon initialiser un tableau vide
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

        // Ajouter les nouvelles données
        $data[] = $new_data;

        // Sauvegarder
        file_put_contents($file, json_encode($data));
    }

    public static function send($model, $campagne)
    {
        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        $url = "https://marketplace.leads.fr/api.php";
        $data = self::make_data($classics, $specifics, $campagne);

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
            case 'MUTU':
                $logfile = "assurance/mutuelle_";
                break;
        }
        

        try {
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'leads_fr_before.json', $data);
            
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $data
            ));
            $output = curl_exec($curl);
            
            
            $logdata = json_decode($output, true) === null ?  $output : json_decode($output, true);
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'leads_fr_after.json', $logdata);
            curl_close($curl);
            
            return self::response($output);
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}