<?php

namespace App\Services;

use App\Models\ApiModel as financesModel;
use App\Services\Finances\RacServices;

class SendFinancesService
{
    # leads specifics information
    private $financeModel;


    /**
     * Finacnes Api Class Constructor
     * @param mixed $lead
     * @param mixed $finances_data
     * @param mixed $client_id
     * @param mixed $validation_date
     */
    public function __construct(
        array $lead = null,
        array $finances_data = null,
        string $client_id = null,
        string $validation_date = null
    ) {
        $this->financeModel = new financesModel($lead, $finances_data, $client_id, $validation_date);
    }

    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->financeModel->getClientID();

        switch ($id) {
            case 'rac#1':
                return RacServices::send_sofanmedia($this->financeModel);

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