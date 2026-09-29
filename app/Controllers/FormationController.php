<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class FormationController
{
    /**
     * Method that provide formations data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeFormationData($input_data)
    {
        /**
         * create lead formations DATA payload
         */
        if (
            (isset($input_data['salarieDuPriveOuTravailleurIndependant']) && ! empty($input_data['salarieDuPriveOuTravailleurIndependant']))
            && (isset($input_data['travailleDepuisCesDernieresAnnees']) && ! empty($input_data['travailleDepuisCesDernieresAnnees']))
            && (isset($input_data['retraite']) && ! empty($input_data['retraite']))) {
            $situationPro = $input_data['salarieDuPriveOuTravailleurIndependant']
                . "\n" . $input_data['travailleDepuisCesDernieresAnnees']
                . "\n" . $input_data['retraite'];
        } elseif (isset($input_data['situationPro']) && ! empty($input_data['situationPro'])) {
            $situationPro = $input_data['situationPro'];
        } else {
            $situationPro = "";
        }

        // payload for formation data.
        $formation_data = array(
            "situationPro" => $situationPro,
            "langue"       => $input_data['langue'] ?? null,
        );

        return $formation_data;
    }

    /**
     * Method for saving formations data inner formations table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $formation_data
     * @return void
     */
    public static function save_formation($conn, $login_data, $lead, $formation_data)
    {
        try {
            $query = SavesRequests::save_formation_query($formation_data, $lead, $login_data);
            // file_put_contents('save_formation_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_formation_error.txt', $th);
        }
    }

    /**
     * Method to update the formations data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $formation_data
     * @return bool
     */
    public static function update_formation($conn, $lead, $formation_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE formations SET situationPro=?, langue=?, custom_field_1=?, custom_field_2=?, custom_field_3=?, custom_field_4=?, custom_field_5=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssssi', $formation_data['situationPro'], $formation_data['langue'], $formation_data['custom_field_1'], $formation_data['custom_field_2'], $formation_data['custom_field_3'], $formation_data['custom_field_4'], $formation_data['custom_field_5'], $lead['lead_id']);

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
            file_put_contents('update_formation_error.txt', $th);
            return false;
        }
    }

}