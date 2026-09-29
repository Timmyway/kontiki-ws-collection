<?php

namespace App\Services\SubServices;

class AlarmSubServices
{

    // methods

    public static function make_alarm_datas($classics, $specifics, $birthdate_object, $lead_source_id, $lead_source)
    {
        $data = array(
            "Country"                => 7,
            "CustomerName"           => $classics['firstname'] . ' ' . $classics['lastname'],
            "FirstName"              => $classics['firstname'],
            "LastName"               => $classics['lastname'],
            "DateOfBirth"            => is_bool($birthdate_object) ? null : $birthdate_object->format('Y-m-d'),
            "SSN"                    => $specifics['ssn'] ?? null,
            "InvoiceAddress"         => array(
                "Street1" => $classics['address'],
                "Street2" => null,
                "Street3" => null,
                "Street4" => null,
                "ZipCode" => $classics['zipcode'],
                "City"    => $classics['city']
            ),
            "InstallationAddress"    => array(
                "Street1" => $classics['address'],
                "Street2" => null,
                "Street3" => null,
                "ZipCode" => $classics['zipcode'],
                "City"    => $classics['city']
            ),
            "Email"                  => $classics['email'],
            "Mobilephone"            => $classics['phone'],
            "Homephone"              => null,
            "LeadSourceId"           => $lead_source_id,
            "LeadChannelLogicalName" => $lead_source,
            "SalesTypeLogicalName"   => "xxx-sa_genericreason-xxx-sal-p-SalesType HQ"
        );

        return $data;
    }

    public static function make_alarm_responses($output, $http_response_code)
    {
        if ($http_response_code === 201) {
            return array(
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to SECTOR ALARM",
            );
        }
        return array(
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => "error sending leads. BAD REQUEST"
        );
    }
}