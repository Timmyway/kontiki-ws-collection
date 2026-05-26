<?php

namespace App\Services;


use App\Models\ApiModel as AssuranceAutoModel;
use App\Services\Assurances\AssuranceAutoServices;
use App\Services\Travaux\IsolationServices;

class SendAssuranceAutoService
{

    # leads specifics information
    private $assuranceModel;


    /**
     * Assurance Api Class Constructor
     * @param mixed $lead
     * @param mixed $assurance_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null,
        array $assurance_data = null,
        string $client_id = null,
        string $date_of_insert = null
    ) {
        $this->assuranceModel = new AssuranceAutoModel($lead, $assurance_data, $client_id, $date_of_insert);
    }


    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->assuranceModel->getClientID();

        switch ($id) {
            case 'assurance#3':
                return AssuranceAutoServices::send_euro_crm($this->assuranceModel);
            case 'assurance#15':
                return IsolationServices::AstonGroup($this->assuranceModel, "ASSURANCE AUTO");
            
            default:
                return array(
                    "status"       => "error",
                    "api_response" => "null",
                    "id_part"      => "",
                    "ws_statut"    => "",
                    "description"  => "error sending leads, UNKNOWN CLIENTS"
                );
        }
    }

}