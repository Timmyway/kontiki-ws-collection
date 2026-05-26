<?php

namespace App\Models;


class ApiModel
{
    # leads information
    private $classics_data;
    private $client_id;
    private $date_of_insert;

    # leads specifics information
    private $specifics_data;


    /**
     * Api Class Constructor
     * @param mixed $lead
     * @param mixed $tags_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null, 
        array $tags_data = null,
        string $client_id = null, 
        string $date_of_insert = null
    ){
        $this->classics_data = $lead ?? [];
        $this->specifics_data = $tags_data ?? [];
        $this->client_id = $client_id ?? null;
        $this->date_of_insert = $date_of_insert ?? null;
    }


    // getters
    /**
     * getter of lead classics data
     * @return array|string
     */
    public function getClassics(){
        return $this->classics_data;
    } 

    /**
     * getter of lead Specifics data
     * @return array|string
     */
    public function getSpecifics(){
        return $this->specifics_data;
    } 

    /**
     * getter of the ClientID
     * @return string
     */
    public function getClientID(){
        return $this->client_id;
    }

    /**
     * getter of the Insert date of the lead
     * @return string
     */
    public function getDoi(){
        return $this->date_of_insert;
    }


    // setters 

    /**
     * Setter of lead Classics data
     * @param mixed $lead
     * @return void
     */
    public function setClassics($lead)
    {
        $this->classics_data = $lead;
    }

    /**
     * Setter of lead  data
     * @param mixed $tags_data
     * @return void
     */
    public function setSpecifics($tags_data)
    {
        $this->specifics_data = $tags_data;
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

    /**
     * Setter of the lead insert date.
     * @param mixed $date
     * @return void
     */
    public function setDoi($date)
    {
        $this->date_of_insert = $date;
    }
}