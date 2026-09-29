<?php

namespace App\Controllers;


class LoginController
{
    /**
     * Method to makeLoginData
     * @param mixed $input_data
     * @return array
     */
    public static function makeLoginData($input_data)
    {
        /** 
        * create login DATA payload
        */
        $login_data = array(
            "partname" => $input_data['partner'] ?? null,
            "token"    => $input_data['token'] ?? null
        );
        return $login_data;
    }

    public static function login($lead, $conn)
    {
        try {
            $query = 'select * from fournisseurs where login=\'' . $lead['partname'] . '\' and mdp=\'' . $lead['token'] . '\'';
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            return ($row != null ? TRUE : FALSE);
        } catch (\Throwable $th) {
            return FALSE;
        }
    }
    

}