<?php
namespace App\Controllers;

use App\Requests\SavesRequests;

class TravauxController
{
    /**
     * Method that provide travaux data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeTravauxData($input_data)
    {
        /**
         * create lead travaux DATA payload
         */
        if (isset($input_data['difficulty'])) {
            $travaux_desc = $input_data['difficulty'];
        } else if ((isset($input_data['description']))) {
            $travaux_desc = $input_data['description'];
        } else {
            $travaux_desc = null;
        }

        $travaux_data = [
            "type_logement"          => $input_data['type_logement'] ?? null,
            "type_chauffage"         => $input_data['type_chauffage'] ?? null,
            "description"            => $travaux_desc ?? null,                 // for some project details
            "date_start"             => $input_data['date_start'] ?? null,     // for project starting date.
            "energetic_cost"         => $input_data['energetic_cost'] ?? null, // not save in the DB, just used for PAC data.
            "partToInsulate"         => $input_data['partToInsulate'] ?? null, // for isolation
            "type_emetteur"          => $input_data['type_emetteur'] ?? null,
            "type_client"            => $input_data['type_client'] ?? null,
            "surface_logement"       => $input_data['surface_logement'] ?? null,
            "preferred_contact_time" => $input_data['preferred_contact_time'] ?? null,
            "custom_field_1"         => $input_data['custom_field_1'] ?? null,
            "custom_field_2"         => $input_data['custom_field_2'] ?? null,
            "custom_field_3"         => $input_data['custom_field_3'] ?? null,
            "custom_field_4"         => $input_data['custom_field_4'] ?? null,
            "custom_field_5"         => $input_data['custom_field_5'] ?? null,
        ];

        return $travaux_data;
    }

    /**
     * Method for saving travaux data inner travaux table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $travaux_data
     * @param mixed $id_travaux_kontiki
     * @return void
     */
    public static function save_travaux($conn, $login_data, $lead, $travaux_data, $id_travaux_kontiki)
    {
        try {
            $query = SavesRequests::save_travaux_query($login_data, $lead, $travaux_data, $id_travaux_kontiki);
            // file_put_contents('save_travaux_query.txt', $query);
            $conn->query($query);
            $conn->commit();

            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_travaux_error.txt', $th);
        }
    }

    /**
     * Method to update the travaux data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $travaux_data
     * @return bool
     */
    public static function update_travaux($conn, $lead, $travaux_data)
    {
        try {

            // define the column names and values you want to update
            $column_names = [
                "type_chauffage",
                "type_logement",
                "partToInsulate",
                "situation_immo",
                "date_start",
                "description",
                "type_emetteur", //nouveau colonne
                "type_client",
                "surface_logement",
                "preferred_contact_time",
                "custom_field_1",
                "custom_field_2",
                "custom_field_3",
                "custom_field_4",
                "custom_field_5",
            ];
            $column_values = [
                $travaux_data['type_chauffage'],
                $travaux_data['type_logement'],
                $travaux_data['partToInsulate'],
                $lead['situation'],
                $travaux_data['date_start'],
                $travaux_data['description'],
                $travaux_data['type_emetteur'],
                $travaux_data['type_client'],
                $travaux_data['surface_logement'],
                $travaux_data['preferred_contact_time'],
                $travaux_data['custom_field_1'],
                $travaux_data['custom_field_2'],
                $travaux_data['custom_field_3'],
                $travaux_data['custom_field_4'],
                $travaux_data['custom_field_5'],
            ];

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE travaux SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE leads_id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, [$lead["lead_id"]]);
            $bind_params_ref = [];
            foreach ($bind_params as $key => $value) {
                $bind_params_ref[$key] = &$bind_params[$key];
            }
            array_unshift($bind_params_ref, $param_types);
            array_unshift($bind_params_ref, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $bind_params_ref);

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
            file_put_contents('update_travaux_error.txt', $th);
            return false;
        }
    }
}
