<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class SecurityController
{
    /**
     * Method that provide security data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeSecurityData($input_data)
    {
        /**
         * create lead security DATA payload
         */
        $security_data = array(
            "canal" => $input_data['canal'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
        );

        return $security_data;
    }

    /**
     * Method for saving security data inner security table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $security_data
     * @return void
     */
    public static function save_security($conn, $login_data, $lead, $security_data)
    {
        try {
            $query = SavesRequests::save_security_query($security_data, $lead, $login_data);
            // file_put_contents('save_security_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_security_error.txt', $th);
        }
    }

    /**
     * Method to update the security data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $security_data
     * @return bool
     */
    public static function update_security($conn, $lead, $security_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE securities SET canal=? , custom_field_1=?, custom_field_2=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssi', $security_data['canal'], $security_data['custom_field_1'], $security_data['custom_field_2'], $lead['lead_id']);

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
            file_put_contents('update_security_error.txt', $th);
            return false;
        }
    }
}
