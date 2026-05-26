<?php
namespace App\Services\SubServices;

class DataOppSubServices
{

    // methods

    /**
     * Method to provide DATA OPP phone wanted format
     * @param mixed $phone
     * @return mixed
     */
    public static function dataopp_phone_format($phone)
    {
        if (substr($phone, 0, 3) === "+33") {
            $phone = 0 . substr($phone, 3);
        } elseif (substr($phone, 0, 2) === "33") {
            $phone = 0 . substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Method to provide DATA OPP day/month/Year of birth right format
     * @param mixed $birthdate
     * @return array<string>|bool
     */
    public static function dataopp_dob_format($birthdate)
    {
        if ($birthdate) {
            if (strpos($birthdate, '-') !== false) {
                return explode('-', $birthdate);
            } else if (strpos($birthdate, '/') !== false) {
                return explode('/', $birthdate);
            } else if (strpos($birthdate, ':') !== false) {
                return explode(':', $birthdate);
            } else {
                return $birthdate;
            }
        } else {
            return $birthdate;
        }
    }

    /**
     * Detect if the lead is a 'houseowner' for Dataopp.
     * @param mixed $situation
     * @return string
     */
    public static function dataopp_houseowner_format($situation)
    {
        if ($situation == 'Proprietaire') {
            return 'Oui';
        } else {
            return 'Non';
        }

    }

    /**
     * Method to make_dataopp_datas
     * @param mixed $cid
     * @param mixed $subdomain
     * @param mixed $campaign
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $situation_familiale
     * @param mixed $heating_type
     * @param mixed $energetic_cost
     * @return array
     */
    public static function make_dataopp_datas($cid, $subdomain, $campaign, $classics, $specifics, $situation_familiale, $heating_type)
    {

        $splited_birthdate = DataOppSubServices::dataopp_dob_format($classics['birthdate']);
        $data              = [
            "pubid"     => "56",
            "cid"       => $cid,
            "subdomain" => $subdomain,
            "scheme"    => "https",
            "terms"     => "1",
            "bases"     => "1",
            "apidl"     => "1",
            "apilf"     => "1",
            "subid"     => "CC-" . $classics['affiliateID'],
            "firstname" => $classics['firstname'],
            "lastname"  => $classics['lastname'],
            "email"     => $classics['email'],
            "phone"     => DataOppSubServices::dataopp_phone_format($classics['phone']),
            "zipcode"   => $classics['zipcode'],
            "ip"        => $classics['ip'],
        ];
        // if ($campaign !== 'iso') {
        if (! in_array($campaign, ['iso', 'assurance_emprunteur'])) {

            $data['EXTUSER[statut-immobilier]'] = $situation_familiale;
            $data['EXTUSER[logement]']          = $specifics['type_logement'];
            $data['address']                    = $classics['address'];
            $data['city']                       = $classics['city'];
        }
        if (in_array($campaign, ['pannsol', 'pac'])) {
            $data['EXTUSER[situation]'] = $classics['situationPro'];
            $data['dobDay']             = $splited_birthdate[0];
            $data['dobMonth']           = $splited_birthdate[1];
            $data['dobYear']            = $splited_birthdate[2];
        }
        if ($campaign === 'iso') {
            $data['EXTUSER[houseowner]'] = DataOppSubServices::dataopp_houseowner_format($classics['situation']);
        }
        if ($campaign === 'pac') {
            $data['EXTUSER[chauffage]']             = $heating_type;
            $data['EXTUSER[depenses-energetiques]'] = $specifics['energetic_cost'];
        }

        if ($campaign === 'assurance_emprunteur') {
            $data['EXTUSER[loan-amount]']   = intval($specifics['amount']);
            $data['EXTUSER[loan-rate]']     = $specifics['custom_field_6'];
            $data['EXTUSER[loan-nb-years]'] = $specifics['custom_field_1'];
            
        }

        if ($campaign === 'assurance_emprunteur') {
            $data['dobDay']   = $splited_birthdate[0];
            $data['dobMonth'] = $splited_birthdate[1];
            $data['dobYear']  = $splited_birthdate[2];
        }

        if ($campaign === 'assurance_emprunteur') {
            $data['dobDay']   = $splited_birthdate[2];
            $data['dobMonth'] = $splited_birthdate[1];
            $data['dobYear']  = $splited_birthdate[0];
        }
        return $data;
    }

    /**
     * Method to make_dataopp_responses
     * @param mixed $json_response
     * @param mixed $curl_response
     * @return array
     */
    public static function make_dataopp_responses($json_response, $curl_response, $campaign)
    {
        // return response
        if ($json_response->result === true) {
            return [
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->leadid,
                "ws_statut"    => "ok",
                "description"  => "lead \"{$campaign}\" has been send successfully to DataOpp",
            ];
        }
        return [
            "status"       => "error",
            "api_response" => $curl_response,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => "error sending leads, " . json_encode($json_response->error),
        ];
    }

}
