<?php

namespace App\Services\SubServices;

class CompleoSubServices
{

    // methods

    public static function make_alarm_datas($classics, $specifics)
    {
        $key = "bq1eb176qer8b16qer81b6qer8b1r6";
        $campaignid = "41";
        $utm_source = "ktk";
        $timestamp_lead = isset($classics['receive_date']) ? strtotime($classics['receive_date']) : time();
        $data = array(

            "key"                     => $key,
            "campaignid"              => $campaignid,
            "timestamp_lead"          => $timestamp_lead,
            "utm_source"              => $utm_source,
            "first_name"              => $classics['firstname'],
            "last_name"               => $classics['lastname'],
            "civ"                     => !empty($classics['civility']) ?
                self::mapCivility($classics['civility']) : '',
            "adresse"                 => $classics['address'],
            "cp"                      => $classics['zipcode'],
            "city"                    => $classics['city'],
            "email"                   => $classics['email'],
            "phone"                   => $classics['phone'],
            "url"                     => $classics['referer'],
            "IP"                      => $classics['ip'],
            "type_bien"               => !empty($specifics['custom_field_1']) ?
                self::mapTypeLogement($specifics['custom_field_1']) : '',
            "situation"               => !empty($specifics['custom_field_2']) ?
                self::mapSituation($specifics['custom_field_2']) : ''
        );

        return $data;
    }


    public static function make_alarm_responses($output, $http_response_code)
{
    // Si la réponse est une chaîne JSON, on la décode
    if (is_string($output)) {
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $output = $decoded;
        }
    }

    if (
        $http_response_code === 200 ||
        (is_array($output) && isset($output['result']) && strtolower($output['result']) === 'success')
    ) {
        return [
            "status"       => "success",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "ok",
            "description"  => "lead has been send successfully to COMPLEO CAMPAIGN ALARM",
        ];
    }

    // Sinon c’est une erreur
    return [
        "status"       => "error",
        "api_response" => $output,
        "id_part"      => "",
        "ws_statut"    => "error",
        "description"  => "error sending leads. BAD REQUEST",
    ];
}



    /**
     * Map situation_famille vers les valeurs acceptées par l'API Euro CRM  
     * @param string $situation_famille
     * @return string
     */
    private static function mapTypeLogement($type_bien)
    {
        // Mapping selon les valeurs LOV de la documentation (page 17-18)
        $mapping = [
            'Maison' => "1",
            'Appartement' => "2",
            'Bureau' => "3",
            'Autre' => "4",
        ];

        return isset($mapping[$type_bien]) ? $mapping[$type_bien] : '1';
    }

    /**
     * Map situation_famille vers les valeurs acceptées par l'API Euro CRM  
     * @param string $situation_famille
     * @return string
     */
    private static function mapSituation($situation)
    {
        $mapping = [
            'Proprietaire' => "1",
            'Locataire' => "2",
            'Locatiare' => "2",
            'Autre' => "3"
        ];

        return isset($mapping[$situation]) ? $mapping[$situation] : '1';
    }
    /**
     * Map situation_famille vers les valeurs acceptées par l'API Euro CRM  
     * @param string $situation_famille
     * @return string
     */
    private static function mapCivility($situation)
    {
        $mapping = [
            'mr' => "m",
            'mme' => "mme",
            'mlle' => "mlle",
            'm' => "m"
        ];

        return isset($mapping[$situation]) ? $mapping[$situation] : '1';
    }
}


// {
//     "result": "success",
//     "error": null,
//     "id": "3756379",
//     "affiliate campaign": "1063",
//     "purchase id": "10506215"
// }