<?php

namespace App\Providers;

use DateTime;

class TimeProvider
{
    /**
     * Summary of getTime
     * @return string
     */
    public static function getTime()
    {
        $date    = new DateTime();
        $newDate = $date->format('Y-m-d H:i:s');
        return $newDate;
    }

    /**
     * Method to format date into french format.
     * @param mixed $datetime
     * @return mixed
     */
    public static function frenchFormat($datetime)
    {
        $french_date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if (is_bool($french_date)) {
            return $datetime;
        } else {
            return $french_date->format("d/m/Y H:i:s");
        }

    }
}