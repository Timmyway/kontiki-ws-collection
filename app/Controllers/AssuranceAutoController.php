<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class AssuranceAutoController
{
    /**
     * Method that provide assurance data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeData($input_data) 
    {
        /**
        * create lead assurance auto DATA payload
        */
        $data = array(
            "registration" => $input_data['registration'] ?? '',
            "brand" => $input_data['brand'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "date_insured" => $input_data['date_insured'] ?? '',

        );

        return $data;
    }

    /**
     * Method for saving assurance data inner assurance table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return void
     */
    public static function save($conn, $login_data, $lead, $data)
    {
        try {
            $query = SavesRequests::save_assurance_auto_query($data, $lead, $login_data);
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
    public static function update($conn, $lead, $data)
    {
        $encoded_registration = base64_encode($data['registration']);
        $encoded_brand = base64_encode($data['brand']);
        $encoded_date_insured = base64_encode($data['date_insured']);
        $encoded_custom_field_1 = base64_encode($data['custom_field_1']);
        $encoded_custom_field_2 = base64_encode($data['custom_field_2']);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurance_auto SET registration=?, brand=?, custom_field_1=?, custom_field_2=?, date_insured=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssi', $encoded_registration, $encoded_brand, $encoded_custom_field_1, $encoded_custom_field_2, $encoded_date_insured, $lead['lead_id']);

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
            file_put_contents('update_assurance_auto_error.txt', $th);
            return false;
        }
    }

}