<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class DefiscController
{
    /**
     * Method that provide defiscalisation data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeDefiscData($input_data) {
        /**
        * create lead defiscalisation DATA payload
        */
        $defisc_data = array(
            "impot" => $input_data['impot'] ?? null, //prélèvement impot
            "impotAnnuel" => $input_data['impotAnnuel'] ?? null,
            "project_type" => $input_data['project_type'] ?? null, // (immobilier || placement)
            "children" => $input_data['children'] ?? null, // (number of children in charge)
            "revenuMensuel" => $input_data['revenuMensuel'] ?? null,
            "apportPerso" => $input_data['apportPerso'] ?? null, // (apport personnel)
            "epargneMensuel" => $input_data['epargneMensuel'] ?? null // (capacité d'épargne mensuel)
        );

        return $defisc_data;
    }

    /**
     * Method for saving defisc data inner defiscalisation table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $defisc_data
     * @return void
     */
    public static function save_defisc($conn, $login_data, $lead, $defisc_data)
    {
        try {
            $query = SavesRequests::save_defisc_query($defisc_data, $lead, $login_data);
            // file_put_contents('save_mutuelle_query.txt', $query);
            $conn->query($query);
            $conn->commit();
        } catch (\Throwable $th) {
            file_put_contents('save_defiscalisation_error.txt', $th);
        }
    }

    /**
     * Method to update the defiscalisation data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $defisc_data
     * @return bool
     */
    public static function update_defisc($conn, $lead, $defisc_data){
        try {
            $impotAnnuel = base64_encode($defisc_data['impot']);
            // $query = "update leads set call_status = 'not callable' where id = $id";
            $stmt = mysqli_prepare($conn, "UPDATE defiscalisation SET status_leads=?, matrimonialSituation=?, imposition=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssi', $lead['situation'], $lead['matrimoniale'], $impotAnnuel, $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_defisc_error.txt', $th);
            return false;
        }
    }

}