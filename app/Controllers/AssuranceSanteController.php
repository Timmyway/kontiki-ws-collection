<?php

namespace App\Controllers;
use App\Requests\SavesRequests;

class AssuranceSanteController
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
            "besoin" => $input_data['besoin'] ?? null,
            "regime_social" => $input_data['regime_social'] ?? null,
            "profession" => $input_data['profession'] ?? null,
            "profession_compl" => $input_data['profession_compl'] ?? null,
            "situation_famille" => $input_data['situation_famille'] ?? null,
            "nombre_enfant" => $input_data['nombre_enfant'] ?? 0,
            "assurer_conjoint" => $input_data['assurer_conjoint'] ?? null,
            "cid" => $input_data['cid'] ?? null,
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "custom_field_3" => $input_data['custom_field_3'] ?? '',
            "custom_field_4" => $input_data['custom_field_4'] ?? '',
            "custom_field_5" => $input_data['custom_field_5'] ?? '',
            "custom_field_6" => $input_data['custom_field_6'] ?? '',
            
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
            $query = SavesRequests::save_assurance_sante_query($data, $lead, $login_data);
            // file_put_contents('save_assurance_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_assurance_sante_error.txt', $th);
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
        // $encoded_besoin = base64_encode($data['besoin']);
        // $encoded_regime_social = base64_encode($data['regime_social']);
        // $encoded_profession = base64_encode($data['profession']);
        // $encoded_profession_compl = base64_encode($data['profession_compl']);
        // $encoded_situation_famille = base64_encode($data['situation_famille']);
        // $encoded_assurer_conjoint = base64_encode($data['assurer_conjoint']);
        // $encoded_cid = base64_encode($data['cid']);
        $encoded_besoin = base64_encode($data['besoin'] ?? '');
        $encoded_regime_social = base64_encode($data['regime_social'] ?? '');
        $encoded_profession = base64_encode($data['profession'] ?? '');
        $encoded_profession_compl = base64_encode($data['profession_compl'] ?? '');
        $encoded_situation_famille = base64_encode($data['situation_famille'] ?? '');
        $encoded_assurer_conjoint = base64_encode($data['assurer_conjoint'] ?? '');
        $encoded_cid = base64_encode($data['cid'] ?? '');
        $encode_custom_field_1 = base64_encode($data['custom_field_1'] ?? '');
        $encode_custom_field_2 = base64_encode($data['custom_field_2'] ?? '');
        $encode_custom_field_3 = base64_encode($data['custom_field_3'] ?? '');
        $encode_custom_field_4 = base64_encode($data['custom_field_4'] ?? '');
        $encode_custom_field_5 = base64_encode($data['custom_field_5'] ?? '');
        $encode_custom_field_6 = base64_encode($data['custom_field_6'] ?? '');
        
       
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurance SET besoin=?, regime_social=?, profession=?, profession_compl=?, situation_famille=?, nombre_enfant=?, assurer_conjoint=?, custom_field_1=?, custom_field_2=?, cid=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'ssssssssssssssi', $encoded_besoin, $encoded_regime_social, $encoded_profession, $encoded_profession_compl, $encoded_situation_famille, $data['nombre_enfant'], $encoded_assurer_conjoint, $encoded_cid, $encode_custom_field_1, $encode_custom_field_2, $encode_custom_field_3, $encode_custom_field_4, $encode_custom_field_5, $encode_custom_field_6, $lead['lead_id']);

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
            file_put_contents('update_assurance_sante_error.txt', $th);
            return false;
        }
    }

}