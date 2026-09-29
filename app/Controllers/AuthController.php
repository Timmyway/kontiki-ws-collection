<?php

namespace App\Controllers;


class AuthController
{
    /**
     * LeadIT authentication controller.
     * @param mixed $email
     * @param mixed $mdp
     * @param mixed $conn
     * @return array|bool|null
     */
    public static function auth($email, $mdp, $conn)
    {
        try {
            $query = "select * from users_validation where email = ? and mdp = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $query);

            // Bind parameters to statement
            mysqli_stmt_bind_param($stmt, "ss", $email, $mdp);

            // Execute statement
            mysqli_stmt_execute($stmt);

            // Get result set
            $result = mysqli_stmt_get_result($stmt);

            // Fetch all rows as associative arrays
            $row = mysqli_fetch_assoc($result);
            
            // Close statement and connection
            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            return $row;
        } catch (\Throwable $th) {
            return null;
        }
    }
    

}