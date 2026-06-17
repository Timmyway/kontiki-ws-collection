<?php
namespace App\Services;

use App\Models\ApiModel as TravauxModel;
use App\Services\SubServices\BatiwebSubServices;
use App\Services\SubServices\LeadsFRSubServices;
use App\Services\SubServices\OceadsSubServices;
use App\Services\SubServices\PerfusionDigitalSubServices;
use App\Services\SubServices\ProsperaleadsSubServices;
use App\Services\SubServices\YacuzaSubServices;
use App\Services\Travaux\ClimatisationServices;
use App\Services\Travaux\DoucheServices;
use App\Services\Travaux\EniServices;
use App\Services\Travaux\IsolationServices;
use App\Services\Travaux\PacServices;
use App\Services\Travaux\PanneauServices;
use App\Services\Travaux\ViteundevisServices;

class SendTravauxService
{

    # leads specifics information
    private $travauxModel;

    /**
     * Travaux Api Class Constructor
     * @param mixed $lead
     * @param mixed $travaux_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null,
        array $travaux_data = null,
        string $client_id = null,
        string $date_of_insert = null
    ) {
        $this->travauxModel = new TravauxModel($lead, $travaux_data, $client_id, $date_of_insert);
    }

    // methods

    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->travauxModel->getClientID();

        switch ($id) {
            case 'pannsol#1':
                return ApiService::common_send_leadvalue($this->travauxModel, "135", "travaux/leadvalue_panneau", "panneau solaire");
            case 'pannsol#2':
                return ApiService::common_send_dataopp($this->travauxModel, "travaux/dataopp_panneau", "7", "solaire", "pannsol", "panneau solaire");
            case 'pannsol#3':
                $url_send    = 'https://script.google.com/macros/s/AKfycbyhWF1rTDErXGiFHWVBp1h5C31OMu7XXDVryFxDUAupHcamzGidD3e5_HHM7X_EMlPt/exec?gid=1457367489';
                $logifile    = 'panneau_sofanmedia.json';
                $client_name = 'SOFANMEDIA PANNEAU SOLAIRE';
                return ApiService::common_send_travaux_energy_sheets($this->travauxModel, $url_send, $logifile, $client_name);
            case 'pannsol#4':
                $url_send    = 'https://script.google.com/macros/s/AKfycbwYFH3CmX2yKkgvQxErYfvQODXf6LIsYSW7WkqmTdXOLO6JndHxZEY--oxguzVjAddpOg/exec?gid=539667105';
                $logifile    = 'panneau_am_busness.json';
                $client_name = 'AM BUSNESS';
                return ApiService::common_send_travaux_energy_sheets($this->travauxModel, $url_send, $logifile, $client_name);
            case 'pannsol#5':
                return ApiService::common_send_goracash($this->travauxModel, "120174633", "travaux/panneau_goracash");
            case 'pannsol#6':
                $url_send = "https://script.google.com/macros/s/AKfycbxJuXnOco_XKm9u8AV54_VtenUB6VZxQJHvKEWb58ZHdzy2AGElrIf-nv4YzF7mZYE/exec";
                return PacServices::send_unitead_sheets($this->travauxModel, $url_send, 'panneau_unitead', "BAR-TH-143");
            case 'pannsol#7':
                return PanneauServices::send_adkomo($this->travauxModel);
            case 'pannsol#8':
                return OceadsSubServices::send_oceads($this->travauxModel, "PANSOL");
            case 'pannsol#9':
                return PanneauServices::send_mediamoov($this->travauxModel);
            case 'pannsol#10':
                return YacuzaSubServices::send($this->travauxModel, "PV");
            case 'pannsol#11':
                return PanneauServices::send_persee_media($this->travauxModel);
            case 'pannsol#12':
                return BatiwebSubServices::send($this->travauxModel, "PV");
            case 'pannsol#13':
                return PanneauServices::send_persee_media_ws($this->travauxModel, "PV");
            case 'pannsol#14':
                return PanneauServices::send_lead_creative($this->travauxModel);
            case 'pannsol#15':
                return PanneauServices::confluentDigital($this->travauxModel);
            case 'pannsol#16':
                return PanneauServices::TedJordanSrl($this->travauxModel);
            case 'pannsol#17':
                return PanneauServices::FlexyLead($this->travauxModel);
            case 'pannsol#18':
                return IsolationServices::AstonGroup($this->travauxModel, "PV");
            case 'pannsol#19':
                return LeadsFRSubServices::send($this->travauxModel, "PV");
            case 'pannsol#20':
                return ViteundevisServices::send($this->travauxModel, 'pannsol#20');
            case 'pannsol#22':
                return PerfusionDigitalSubServices::send($this->travauxModel, "PV");
            case 'pannsol#23':
                return PacServices::cpryDigital($this->travauxModel, "PV");
            case 'pannsol#24':
                return ProsperaleadsSubServices::send_prosperaleads($this->travauxModel, "PV");

            case 'pag#1':
                return ApiService::common_send_dataopp($this->travauxModel, "travaux/poele_dataopp", "41", "poele-granules", "pag", "poele a granules");
            case 'iso#1':
                return ApiService::common_send_dataopp($this->travauxModel, "travaux/isolation_dataopp", "62", "ite", "iso", "isolation");
            case 'iso#2':
                $url_send    = 'https://script.google.com/macros/s/AKfycbzVuF_5LNMARCQKErw172IlM_RoKkCZWFqH1VzDGIw_G43llsqdOZ_7Fl5owq4uLCPhPA/exec';
                $client_name = 'MOKHTAR';
                $logfile     = 'isolation_exterieure_mokhtar.json';
                return IsolationServices::send_isolation_mokhtar_sheets($this->travauxModel, $logfile, $url_send, $client_name);
            case 'iso#3':
                return YacuzaSubServices::send($this->travauxModel, "ITE");
            case 'iso#4':
                return IsolationServices::send_isolation_societeMooner_sheets($this->travauxModel);
            case 'iso#5':
                return IsolationServices::confluentDigital($this->travauxModel);
            case 'iso#6':
                return IsolationServices::AstonGroup($this->travauxModel, "ITE");
            case 'iso#7':
                return LeadsFRSubServices::send($this->travauxModel, "ITE");
            case 'iso#8':
                return ViteundevisServices::send($this->travauxModel, 'iso#8');
            case 'iso#9':
                return PerfusionDigitalSubServices::send($this->travauxModel, "ITE");
            case 'iso#10':
                // return IsolationServices::oceads_isolation($this->travauxModel, "ITE");
                return OceadsSubServices::send_oceads($this->travauxModel, "ITE");
            case 'iso#11':
                return PacServices::cpryDigital($this->travauxModel, "ITE");
            case 'iso#12':
                return IsolationServices::FlexyLead($this->travauxModel, "ITE");
            case 'iso#13':
                return ProsperaleadsSubServices::send_prosperaleads($this->travauxModel, "ITE");

            case 'energy#1':
                return EniServices::send_eni($this->travauxModel);
            case 'energy#2':
                return EniServices::send_euro_crm($this->travauxModel);
            case 'pac#1':
                return ApiService::common_send_leadvalue($this->travauxModel, "127", "travaux/pac_leadvalue", "pompe a chaleur");
            case 'pac#3':
                return ApiService::common_send_goracash($this->travauxModel, "174511092", "travaux/pac_goracash");
            case 'pac#4':
                return ApiService::common_send_dataopp($this->travauxModel, "travaux/pac_dataopp", "8", "renov", "pac", "pompe a chaleur");
            case 'pac#5':
                $ndflow_id = "c5d302b1f47603ca9e718ac6365397285f4fb620160cac4bd79fecb9e26670ee";
                $utm       = "kontiki_média_achat_pac";
                return ApiService::common_send_edilead($this->travauxModel, "travaux/pac_edilead", $ndflow_id, $utm, "pac");
            case 'pac#7':
                $url_send    = 'https://script.google.com/macros/s/AKfycbyhWF1rTDErXGiFHWVBp1h5C31OMu7XXDVryFxDUAupHcamzGidD3e5_HHM7X_EMlPt/exec?gid=0';
                $logifile    = 'pac_sofanmedia.json';
                $client_name = 'SOFANMEDIA PAC';
                return ApiService::common_send_travaux_energy_sheets($this->travauxModel, $url_send, $logifile, $client_name);
            case 'pac#8':
                return PacServices::send_proxiserve($this->travauxModel);
            case 'pac#9':
                $url_send = "https://script.google.com/macros/s/AKfycbwA6jGc2F7wBBt9zVsPGo1e70I3ZhM6G7-YeQqpuujZraUwH6Ms1w5OrgDhqlhoEoZE/exec";
                return PacServices::send_unitead_sheets($this->travauxModel, $url_send, 'pac_unitead', "BAR-TH-104");
            case 'pac#10':
                return OceadsSubServices::send_oceads($this->travauxModel, "PAC");
            case 'pac#11':
                $url_send = "https://hook.eu1.make.com/4w72tohfe91a4vux8hwu0wtfj1j3c4c3";
                return PacServices::send_mailomedia_sheets($this->travauxModel, $url_send);
            case 'pac#12':
                return YacuzaSubServices::send($this->travauxModel, "PAC");
            case 'pac#13':
                return BatiwebSubServices::send($this->travauxModel, "PAC");
            case 'pac#14':
                return PacServices::TedJordanSrl($this->travauxModel);
            case 'pac#15':
                return PacServices::FlexyLead($this->travauxModel);
            case 'pac#16':
                return LeadsFRSubServices::send($this->travauxModel, "PAC");
            case 'pac#17':
                return PacServices::send_lovvis_ads_proxiserve($this->travauxModel, 'teletech');
            case 'pac#18':
                return PacServices::send_lovvis_ads_proxiserve($this->travauxModel, 'tersea');
            case 'pac#19':
                return IsolationServices::AstonGroup($this->travauxModel, "PAC");
            case 'pac#20':
                return ViteundevisServices::send($this->travauxModel, 'pac#20');
            case 'pac#22':
                return PacServices::confluentDigital($this->travauxModel, "PAC");
            case 'pac#23':
                return PerfusionDigitalSubServices::send($this->travauxModel, "PAC");
            case 'pac#24':
                return PacServices::cpryDigital($this->travauxModel, "PAC");
            case 'pac#25':
                return PacServices::send_meedia_moov($this->travauxModel, "PAC");   
            case 'pac#26':
                return ProsperaleadsSubServices::send_prosperaleads($this->travauxModel, "PAC"); 
            case 'douche#1':
                return DoucheServices::send_lead_creative($this->travauxModel);
            case 'douche#2':
                return ApiService::common_send_goracash($this->travauxModel, "120176786", "travaux/douche_goracash");
            case 'douche#3':
                return ViteundevisServices::send($this->travauxModel, 'douche#3');
            case 'douche#4':
                return DoucheServices::confluentDigital($this->travauxModel, "douche");
            case 'fenetre#1':
                return IsolationServices::AstonGroup($this->travauxModel, "FENETRE DE TOIT ET VERRIERE");
            case 'fenetre#2':
                return ApiService::common_send_goracash($this->travauxModel, "174511091", "travaux/fenetre_reparation_goracash");
            case 'fenetre#3':
                return ViteundevisServices::send($this->travauxModel, 'fenetre#3');
            case 'clim#1':
                return ClimatisationServices::confluentDigital($this->travauxModel, "clim#1");
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
