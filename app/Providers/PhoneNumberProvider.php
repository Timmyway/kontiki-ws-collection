<?php

namespace App\Providers;

class PhoneNumberProvider
{
    /**
     * Method to remove whitespace on phonenumber.
     * @param mixed $number
     * @return string
     */
    public static function remove_whitespace($number) : string
    {
        return str_replace(' ', '', $number);
    }

    /**
     * METHOD TO REMOVE +33 OR 33 on phonenumber.
     * @param mixed $number
     * @return string
     */
    public static function remove_plus_33($number) : string
    {
        return preg_replace('/^(?:\+?33|0)/', '0', $number);
    }
}