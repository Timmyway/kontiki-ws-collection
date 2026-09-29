<?php

namespace App\Providers;

class CurlProvider
{
    /**
     * Make a curl post_requests
     * @param mixed $url
     * @param array $headers
     * @param mixed $data
     * @return array
     */
    public static function post_requests($url, $headers, $data, $certificat = null)
    {
        try {
            $ch = curl_init();
            if ($certificat !== null) {
                curl_setopt($ch, CURLOPT_CAINFO, $certificat);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST           => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => $data
            ));
            // send data
            $output             = curl_exec($ch);
            if ($output === false) {
                echo 'Erreur cURL : ' . curl_error($ch);
            }
            $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [$output, $http_response_code];
        } catch (\Throwable $th) {
            //throw $th;
            return ['', $th];
        }
    }

    /**
     * Make a curl get_requests
     * @param mixed $url
     * @return array
     */
    public static function get_requests($url)
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_MAXREDIRS      => 1,
                CURLOPT_FOLLOWLOCATION => true
            ));
            // send data
            $output             = curl_exec($ch);
            $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [$output, $http_response_code];
        } catch (\Throwable $th) {
            //throw $th;
            return ['', $th];
        }
    }
}