<?php

namespace App\Models;


class LoginModel
{
    # login information
    private $partname;
    private $token;

    /**
     * Summary of __construct
     * @param mixed $partner
     * @param mixed $token
     */
    public function __construct(
        string $partner, 
        string $token,
    ){
        $this->partname = $partner;
        $this->token    = $token;
    }


    // getters

    /**
     * Summary of getPartname
     * @return string
     */
    public function getPartname(){
        return $this->partname;
    } 

    /**
     * Summary of getToken
     * @return string
     */
    public function getToken(){
        return $this->token;
    }


    // setters 

    /**
     * Summary of setPartname
     * @param mixed $partner
     * @return void
     */
    public function setPartname($partner)
    {
        $this->partname = $partner;
    }

    /**
     * Summary of setToken
     * @param mixed $token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}