<?php

namespace App\Services;


use App\Models\ApiModel as DefiscModel;
use App\Services\Defiscalisations\PinelServices;
use App\Services\Travaux\PoeleServices;

class SendDefiscService
{

    # common send url
    private $common_send_url = 'https://prod-quintesens-fournisseur-api.azurewebsites.net/api/Projects/Import'; # for PROD

    # Lead Creative private Data
    private $api_key_lead_creative = 'gbkibfldcijk1jm34e5kea31';
    private $utm_activity_lead_creative = 'LEADCREATIVE';

    # SOFANMEDIA client 1 private Data
    private $api_key_sofanmedia = 'c1jf37gj86cd4ljnchd87l27'; # for PROD
    private $utm_activity_sofanmedia = 'DBC';

    # leads specifics information
    private $defiscModel;


    /**
     * Defisc Api Class Constructor
     * @param mixed $lead
     * @param mixed $defisc_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null,
        array $defisc_data = null,
        string $client_id = null,
        string $date_of_insert = null
    ) {
        $this->defiscModel = new DefiscModel($lead, $defisc_data, $client_id, $date_of_insert);
    }

    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->defiscModel->getClientID();

        switch ($id) {
            case 'defisc#1':
                return PinelServices::send_my_optin($this->defiscModel);
            case 'defisc#2':
                return PinelServices::common_send_method(
                    $this->defiscModel,
                    $this->common_send_url,
                    $this->utm_activity_lead_creative,
                    $this->api_key_lead_creative,
                    "pinel_leadcreative",
                    "LEAD CREATIVE"
                );
            case 'defisc#3':
                return PinelServices::send_vmb($this->defiscModel);
            case 'defisc#4':
                $ndflow_id = "0f32d178a8ca4b09a14d9951fa2c0c87b99cb79498ca732f109bba29a322748c";
                $utm = "Kontiki_media_achat_defisc";
                return ApiService::common_send_edilead($this->defiscModel, "defisc/pinel_edilead", $ndflow_id, $utm, "defisc");
            case 'defisc#5':
                return PinelServices::common_send_method(
                    $this->defiscModel,
                    $this->common_send_url,
                    $this->utm_activity_sofanmedia,
                    $this->api_key_sofanmedia,
                    "pinel_sofanmedia_client_one",
                    "SOFANMEDIA client 1"
                );
            case 'defisc#6':
                return PinelServices::send_sofanmedia_client_two($this->defiscModel);
            case 'defisc#7':
                return PoeleServices::azur($this->defiscModel, 'defisc');
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