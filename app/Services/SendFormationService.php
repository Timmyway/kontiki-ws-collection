<?php

namespace App\Services;


use App\Models\ApiModel as FormationModel;
use App\Services\Formations\CpfServices;

class SendFormationService
{
    # leads specifics information
    private $formationModel;


    /**
     * Formations Api Class Constructor
     * @param mixed $lead
     * @param mixed $lead_id
     * @param mixed $formation_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null,
        array $formation_data = null,
        string $client_id = null,
        string $date_of_insert = null
    ) {
        $this->formationModel = new FormationModel($lead, $formation_data, $client_id, $date_of_insert);
    }



    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->formationModel->getClientID();

        switch ($id) {
            case 'formation#1':
                $url_send = 'https://script.google.com/macros/s/AKfycbwYFH3CmX2yKkgvQxErYfvQODXf6LIsYSW7WkqmTdXOLO6JndHxZEY--oxguzVjAddpOg/exec?gid=0';
                $client_name = 'AM BUSNESS';
                $logfile = 'cpf_am_busness.json';
                return CpfServices::send_bureautique_sheets($this->formationModel, $url_send, $logfile, $client_name);
            case 'formation#2':
                $url_send = 'https://script.google.com/macros/s/AKfycbyxG7Zx6lKVCyzlNIzBI6v5dRORXPw8W9ZNx-_zQ_dz5bNcmEDgLiZUI2XsWCbH4SMN/exec?gid=0';
                $client_name = 'SOFANMEDIA';
                $logfile = 'cpf_langue_sofanmedia.json';
                return CpfServices::send_langue_sheets($this->formationModel, $url_send, $logfile, $client_name);

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