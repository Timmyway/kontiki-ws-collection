<?php
 
namespace App\Services\Assurances;
 
use App\Services\SubServices\SofanmediaSubServices;
use App\Providers\CurlProvider;
use App\Services\SubServices\EuroCrmServices;
use DateTime;
use Exception;
 
 
class SanteServices extends \App\Services\ApiService
{
 
    // methods
 
    /**
     * Method to send lead Sante LMP.
     * @return array
     */
    public static function LMP($assuranceModel)
    {
        $classics  = $assuranceModel->getClassics();
        $specifics = $assuranceModel->getSpecifics();
        $birthdate = DateTime::createFromFormat('Y-m-d', $classics["birthdate"]);
 
        try {
                       
            $data = array(
                "NOM"                 => $classics['lastname'],
                "PRENOM"              => $classics['firstname'],
                "EMAIL"               => $classics['email'],
                "TEL"                 => $classics['phone'],
                "DNA"                 => (int)$birthdate->format('Y'),
                "DNM"                 => (int)$birthdate->format('m'),
                "DNJ"                 => (int)$birthdate->format('d'),
                "POIDS"               => (float)$specifics['custom_field_1'],
                "POIDSAPERDRE"        => (float)$specifics['custom_field_2'],
                "COMMENT"             => "Poire",
                "DIV1"                => $classics['affiliateID'],
                "DIV2"                => "Emailing"
            );
 
           
 
            $encodedData = array_map(function($value) {
                return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            }, $data);
            $queryString = http_build_query($encodedData);
           
            parent::logger('../logs/assurance/sante_lmp_before.json', $data);
            $curl_response = CurlProvider::get_requests("https://formulaire.lm-nutri.fr/28ykon99/?$queryString");
           
           
            parent::logger('../logs/assurance/sante_lmp_after.json', ["email" => $classics['email'], "response" => $curl_response[0]]);
 
           
            if($curl_response[0] == "CODERR=0") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to LMP Santé"
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads to LMP Santé"
                ];
            }
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }
 
    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE
     * @return array
     */
    public static function send_euro_crm($assuranceModel)
    {
        // $url_send = 'https://ws-mutuelle-sante-senior.eurocrm.com/production/lead';
        $url_send = 'https://ws-mutuelle-sante-senior.eurocrm.com/recette/lead';
        $headers = [
            'Content-Type: application/json',
            'accountId: API_KONTIKI_MEDIA',
            'apiKey: a80eb7f24fe9ec285b3860448baabef6',
            'Cache-Control : no-cache'
        ];
       
        $classics         = $assuranceModel->getClassics();
        $specifics        = $assuranceModel->getSpecifics();
        $gender_category  = parent::category_gender_transform($classics['civility'], 3);
        $birthdate = new DateTime($classics['birthdate']);
 
        try {
 
            $data = EuroCrmServices::make_assurance_mutuel_senior_datas($classics, $specifics, $gender_category, $birthdate);
            parent::logger('../logs/assurance/euro_crm_mutuel_senior_before.json', $data);
 
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data)
            ));
 
            $output = curl_exec($curl);
            parent::logger('../logs/assurance/euro_crm_mutuel_senior_after.json', json_decode($output));
            curl_close($curl);
            $json_response = json_decode($output);
 
            return EuroCrmServices::make_assurance_mutuel_senior_responses($json_response, $output);
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }
}
 