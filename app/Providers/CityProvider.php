<?php

namespace App\Providers;

class CityProvider
{
    /**
     * Summary of check_city
     * @param mixed $zipcode
     * @return mixed
     */
    public static function check_city($zipcode){

        $data = array(
            "zipcode" => $zipcode
        );
        $endpoint = "https://budgetdevis.com/support/api/cityzipcode";
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            // send data
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $output = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($output);
            if($response){
                return $response[0]->city;
            }else{
                return null;
            }
        } catch (\Throwable $th) {
            file_put_contents('check_city_error.txt', $th);
            return null;
        }
    }
}