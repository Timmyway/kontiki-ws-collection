<?php

namespace App\Providers;

class ConnexionProvider
{
    /**
     * Provider method to fetch multiple rows
     * @param mixed $conn
     * @param mixed $query
     * @return array
     */
    public static function fetch_all($conn, $query)
    {
        //prepare statement
        $stmt = mysqli_prepare($conn, $query);
        // Execute statement
        mysqli_stmt_execute($stmt);
        // Get result set
        $result = mysqli_stmt_get_result($stmt);
        // Fetch all rows as associative arrays
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Close statement
        mysqli_stmt_close($stmt);

        // return results
        return $rows;
    }
    
    public static function fetch_count($conn, $query)
    {
        // prepare the statement
        $stmt = mysqli_prepare($conn, $query);
        // Execute statement
        mysqli_stmt_execute($stmt);
        // Get result set
        $result = mysqli_stmt_get_result($stmt);
        // Fetch the row as associative arrays
        $count = mysqli_fetch_assoc($result);
        // Close statement
        mysqli_stmt_close($stmt);

        // return counts results
        return $count;
    }
}