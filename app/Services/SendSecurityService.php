<?php
namespace App\Services;

use App\Models\ApiModel as SecurityModel;
use App\Services\Securites\AlarmServices;
use App\Services\Travaux\IsolationServices;

class SendSecurityService
{
    # leads specifics information
    private $securityModel;

    /**
     * Security Api Class Constructor
     * @param mixed $lead
     * @param mixed $security_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null,
        array $security_data = null,
        string $client_id = null,
        string $date_of_insert = null
    ) {
        $this->securityModel = new SecurityModel($lead, $security_data, $client_id, $date_of_insert);
    }

    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->securityModel->getClientID();

        switch ($id) {
            case 'security#1':
                return AlarmServices::send_sector_alarm($this->securityModel);
            case 'security#2':
                return IsolationServices::AstonGroup($this->securityModel, "ALARME IDF");
            case 'security#3':
                return IsolationServices::AstonGroup($this->securityModel, "ALARME NATIO");
            case 'security#4':
                return AlarmServices::send_compleo_alarm($this->securityModel);
            default:
                return [
                    "status"       => "error",
                    "api_response" => "null",
                    "id_part"      => "",
                    "ws_statut"    => "",
                    "description"  => "error sending leads, UNKNOWN CLIENTS",
                ];
        }
    }

}
