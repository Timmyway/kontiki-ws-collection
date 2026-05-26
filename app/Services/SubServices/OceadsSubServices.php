<?php
namespace App\Services\SubServices;

use App\Services\ApiService;
use DateTime;

class OceadsSubServices
{
    /**
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
    public static function make_oceads_datas($classics, $specifics, $campagne)
    {

        $gender = [
            "mr"       => "3638",
            "monsieur" => "3638",
            "Monsieur" => "3638",
            "mme"      => "3639",
            "madame"   => "3639",
            "Madame"   => "3639",
        ];

        $phone     = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $birthDate = new DateTime($classics['birthdate']);

        $HeatingType = [
            "electricite" => "3650",
            "Electricite" => "3650",
            "fioul"       => "3651",
            "Fioul"       => "3651",
            "fuel"        => "3651",
            "Fuel"        => "3651",
            "gaz"         => "3652",
            "Gaz"         => "3652",
            "bois"        => "3653",
            "Bois"        => "3653",
            "autre"       => "3654",
            "Autre"       => "3654",
        ];

        $AssetType = [
            "Maison"      => "3655",
            "maison"      => "3655",
            "Appartement" => "3656",
            "appartement" => "3656",
        ];

        $OwnerType = [
            "Proprietaire" => "3657",
            "proprietaire" => "3657",
            "Locataire"    => "3658",
            "locataire"    => "3658",
        ];

        $data = [
            "authKey"         => "67db34b6f44aee5d24cc239b7742538a",
            "civilityModelId" => $gender[$classics['civility']],
            "lastName"        => $classics['lastname'],
            "firstName"       => $classics['firstname'],
            "email"           => $classics['email'],
            "phoneNumber"     => $phone,
            "postalCode"      => $classics['zipcode'],
            "city"            => $classics['city'],
            "address"         => $classics['address'],
        ];

        switch ($campagne) {
            case "PAC":
                $data += [
                    "deliveryId"         => 968,
                    "heatingTypeModelId" => $HeatingType[$specifics["type_chauffage"]],
                    "assetTypeModelId"   => $AssetType[$specifics['type_logement']],
                    "ownerTypeModelId"   => $OwnerType[$classics["situation"]],
                ];
                break;

            case "PANSOL":
                $data += [
                    "deliveryId"         => 969,
                    "heatingTypeModelId" => $HeatingType[$specifics["type_chauffage"]],
                    "assetTypeModelId"   => $AssetType[$specifics['type_logement']],
                    "ownerTypeModelId"   => $OwnerType[$classics["situation"]],
                ];
                break;

            case "SANTE":
                $data += [
                    "deliveryId" => 970,
                    "birthDate"  => $birthDate->format('Y-m-d') ?? null,
                ];
                break;
            case "ITE":
                $data += [
                    'deliveryId'       => 1765,
                    'ownerTypeModelId' => $OwnerType[$classics['situation']],
                    'assetTypeModelId' => $AssetType[$specifics['type_logement']],
                    'heatingTypeModelId' => $HeatingType[$specifics['type_chauffage']],
                ];
        }

        return $data;
    }

    public static function logger($file, $new_data)
    {
        $json_data = file_get_contents($file);
        $data      = json_decode($json_data, true);

        $data[] = $new_data;

        $json_data = json_encode($data);
        file_put_contents($file, $json_data);
    }

    public static function send_oceads($travauxModel, $campagne)
    {
        $url       = "https://leads.oceads.com/import";
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $data = self::make_oceads_datas($classics, $specifics, $campagne);

        switch ($campagne) {
            case "PAC":
                $logfile = "travaux/pac_";
                break;

            case "PANSOL":
                $logfile = "travaux/panneau_";
                break;

            case "SANTE":
                $logfile = "assurance/sante_";
                break;
            case "ITE":
                $logfile = "travaux/ite_";
                break;
        }

        try {

            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'oceads_before.json', $data);

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ],
            ]);
            $output = curl_exec($curl);
            var_dump($output);
            self::logger(dirname(__DIR__, 3) . '/' . 'logs/' . $logfile . 'oceads_after.json', json_decode($output, true));

            curl_close($curl);
            $json_response = json_decode($output, true);

            if ($json_response["statusText"] === "ok") {
                return [
                    "status"       => "success",
                    "api_response" => $output,
                    "id_part"      => $json_response["leadId"],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Oceads",
                ];
            } else if ($json_response["statusText"] === "rejected") {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads - " . $json_response["reason"],
                ];
            }
        } catch (\Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}
