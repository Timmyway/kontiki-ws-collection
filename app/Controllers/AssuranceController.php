<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class AssuranceController
{
    /**
     * Method that provide assurance data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeAssuranceData($input_data) 
    {
        
        /**
        * create lead assurance DATA payload
        */
        $assurance_data = array(
            "bank" => $input_data['bank_assurance'] ?? '',
            "bien" => $input_data['property_assurance'] ?? '',
            "objectif" => $input_data['objectif_assurance'] ?? '',
            "fumeur" => $input_data['fumeur'] ?? '',
            "profession" => $input_data['profession'] ?? $input_data['situationPro'] ?? '',
            "amount" => $input_data['montant_pret'] ?? '',
            "rate" => $input_data['taux_pret'] ?? '',
            "duration" => $input_data['duree_pret'] ?? '',
            "quote_type" => $input_data['quote_type'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "custom_field_3" => $input_data['custom_field_3'] ?? '',
            "custom_field_4" => $input_data['custom_field_4'] ?? '',
            "custom_field_5" => $input_data['custom_field_5'] ?? '',
            "custom_field_6" => $input_data['custom_field_6'] ?? '',
            "custom_field_7" => $input_data['custom_field_7'] ?? '',
            "custom_field_8" => $input_data['custom_field_8'] ?? '',
        );

        return $assurance_data;
    }

    /**
     * Method for saving assurance data inner assurance table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return void
     */
    public static function save_assurance($conn, $login_data, $lead, $assurance_data)
    {
        try {
            $query = SavesRequests::save_assurance_query($assurance_data, $lead, $login_data);
            // file_put_contents('save_assurance_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_assurance_error.txt', $th);
        }
    }

    /**
     * Method to update the assurance data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return bool
     */
    public static function update_assurance($conn, $lead, $assurance_data)
    {
        $encoded_amount = isset($assurance_data['amount']) ? base64_encode($assurance_data['amount']) : '';
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurances SET montant_pret=?, taux_pret=?, duree_pret=?, quote_type=?, professionnal_situation=?, custom_field_1=?, custom_field_2=?, custom_field_3=?, custom_field_4=?, custom_field_5=?, custom_field_6=? , custom_field_7=?, custom_field_8=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssssssssssi', $encoded_amount, $assurance_data['rate'], $assurance_data['duration'], $assurance_data['quote_type'], $assurance_data["profession"], $assurance_data['custom_field_1'], $assurance_data['custom_field_2'], $assurance_data['custom_field_3'], $assurance_data['custom_field_4'], $assurance_data['custom_field_5'], $assurance_data['custom_field_6'], $assurance_data['custom_field_7'], $assurance_data['custom_field_8'], $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_assurance_error.txt', $th);
            return false;
        }
    }

}