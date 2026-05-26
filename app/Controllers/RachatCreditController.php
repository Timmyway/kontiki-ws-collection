<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class RachatCreditController
{
    /**
     * Method that provide rac data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeRacData($input_data)
    {
        /**
         * create lead rac DATA payload
         */
        $rac_data = array(
            "fichage"           => $input_data['fichage'] ?? null,
            "contract"          => $input_data['contract'] ?? null,
            "nb_credit_conso"   => intval($input_data['nb_credit_conso']) ?? 0,
            "mensualites_conso" => json_encode($input_data['mensualites_conso']) ?? null,
            "restant_du_conso"  => json_encode($input_data['restant_du_conso']) ?? null,
            "type_credit_conso" => json_encode($input_data['type_credit_conso']) ?? null,
            "nb_credit_immo"    => intval($input_data['nb_credit_immo']) ?? 0,
            "mensualites_immo"  => json_encode($input_data['mensualites_immo']) ?? null,
            "restant_du_immo"   => json_encode($input_data['restant_du_immo']) ?? null,
            "revenu_mensuel"    => strval($input_data['revenu_mensuel']) ?? "0",
        );

        return $rac_data;
    }

    /**
     * Method for saving rac data inner rachat_de_credits table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $rac_data
     * @param mixed $lead
     * @return void
     */
    public static function save_rac($conn, $login_data, $rac_data, $lead)
    {
        try {
            $query = SavesRequests::save_rac_query($rac_data, $lead, $login_data);
            // file_put_contents('save_rac_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_rac_error.txt', $th);
        }
    }

    /**
     * Method to update the rac data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $rac_data
     * @return bool
     */
    public static function update_rac($conn, $lead, $rac_data)
    {
        try {

            // define the column names and values you want to update
            $column_names  = array(
                "status_logement",
                "fichage",
                "contract",
                "nb_credit_conso",
                "mensualites_conso",
                "restant_du_conso",
                "type_credit_conso",
                "nb_credit_immo",
                "mensualites_immo",
                "restant_du_immo",
                "revenu_mensuel"
            );
            $column_values = array(
                $lead['situation'],
                $rac_data['fichage'],
                $rac_data['contract'],
                $rac_data['nb_credit_conso'],
                base64_encode($rac_data['mensualites_conso']),
                base64_encode($rac_data['restant_du_conso']),
                base64_encode($rac_data['type_credit_conso']),
                $rac_data['nb_credit_immo'],
                base64_encode($rac_data['mensualites_immo']),
                base64_encode($rac_data['restant_du_immo']),
                base64_encode($rac_data['revenu_mensuel'])
            );

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE rachat_de_credits SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE leads_id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, array($lead["lead_id"]));
            $bind_params_ref = array();
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
            file_put_contents('update_rac_error.txt', $th);
            return false;
        }
    }

}