<?php

namespace App\Models;


class PingModel
{

    # ping information
    private $ping_data;
    private $client_id;


    /**
     * Defisc Api Class Constructor
     * @param mixed $ping_data
     * @param mixed $client_id
     */
    public function __construct(array $ping_data, string $client_id)
    {
        $this->ping_data = $ping_data ?? [];
        $this->client_id = $client_id ?? null;
    }


    // getters
    /**
     * getter of ping data data
     * @return array|string
     */
    public function getPingData(){
        return $this->ping_data;
    } 

    /**
     * getter of the ClientID
     * @return string
     */
    public function getClientID(){
        return $this->client_id;
    }


    // setters 

    /**
     * Setter of ping data data
     * @param mixed $ping_data
     * @return void
     */
    public function setPingData($ping_data)
    {
        $this->ping_data = $ping_data;
    }

    /**
     * Setter of client ID
     * @param mixed $clientID
     * @return void
     */
    public function setClientID($clientID)
    {
        $this->client_id = $clientID;
    }
}