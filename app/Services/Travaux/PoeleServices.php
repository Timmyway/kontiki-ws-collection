<?php
namespace App\Services\Travaux;
use App\Providers\CurlProvider;
class PoeleServices extends \App\Services\ApiService
{
    public static function azur($defiscModel, string $type)
    {
        $classics  = $defiscModel->getClassics();
        $specifics = $defiscModel->getSpecifics();
        $url     = "https://adomos.leadbyte.com/restapi/v1.3/leads";
        $logfile = strtolower($type) . "_azur";
        $headers = [
            "X-KEY: 59bbd1ac6c851f01f1525b48c9359560",  // tiret, pas underscore
            "content-type: application/json",            // JSON si tu envoies du JSON
        ];
        try {
            if ($type === "defisc") {
                $campaign = "AZUR DEFISC";
                $data     = [
                    "campid"       => "DEFISC",
                    "sid"          => "18",
                    "Email"        => $classics['email'],
                    "First_Name"   => $classics['firstname'],
                    "Last_Name"    => $classics['lastname'],
                    "Postcode"     => $classics['zipcode'],
                    "Phone_1"      => $classics['phone'],
                    "impots_paye"  => $specifics['impot'],
                    "optin"        => "1",                // champ obligatoire manquant
                    "Opt_in_Date"  => date("d/m/Y H:i:s"), // date du consentement
                    "testmode"     => "yes",
                ];
            } else {
                return parent::common_internal_server_error();
            }
            parent::logger('../logs/defisc/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, $headers, json_encode($data));
            $responses     = json_decode($curl_response[0], true);
            parent::logger('../logs/defisc/' . $logfile . '_after.json', $responses);
            if (isset($responses['status']) && $responses['status'] === "Success") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['results'][0]['queueId'] ?? "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => implode(", ", $responses['errors'] ?? ["error sending leads to $campaign"]),
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
}