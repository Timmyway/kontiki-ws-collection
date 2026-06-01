<?php
namespace App\Services;

use App\Models\ApiModel as AssuranceModel;
use App\Services\Assurances\PretServices;
use App\Services\Assurances\SanteServices;
use App\Services\Assurances\VieServices;
use App\Services\SubServices\CardataSubServices;
use App\Services\SubServices\LeadsFRSubServices;
use App\Services\SubServices\MediamoovSubServices;
use App\Services\SubServices\OceadsSubServices;
use App\Services\SubServices\TestClientSubServices;
use App\Services\Travaux\IsolationServices;

class SendAssuranceService
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
    // public function __construct(
    //     array $lead = null,
    //     array $assurance_data = null,
    //     string $client_id = null,
    //     string $date_of_insert = null
    // )
    // public function __construct(mixed $lead = null, mixed $assurance_data = null, mixed $client_id = null, mixed $date_of_insert = null)
    public function __construct($lead = null, $assurance_data = null, $client_id = null, $date_of_insert = null)
    {
        $this->assuranceModel = new AssuranceModel($lead, $assurance_data, $client_id, $date_of_insert);
    }

    /**
     * Principale send method dispatcher.
     * @return array
     */
    public function send()
    {
        $id = $this->assuranceModel->getClientID();

        switch ($id) {
            case 'assurance#1':
                return PretServices::send_lead_creative($this->assuranceModel);
            case 'assurance#2':
                $url_send    = 'https://script.google.com/macros/s/AKfycbwPGTMCoh8L09PHumpl1jK8XVVI8HlBljKYBtxM-s_Jh5boHdnS45rGkXvisQLUVOudqw/exec';
                $client_name = 'SOFANMEDIA';
                $logfile     = 'assurance_vie_sofanmedia.json';
                return VieServices::send_assurance_sheets($this->assuranceModel, $url_send, $logfile, $client_name);
            case 'assurance#4':
                return PretServices::send_filiassur($this->assuranceModel, 'assurance#4');
            case 'assurance#5':
                return PretServices::send_filiassur($this->assuranceModel, 'assurance#5');
            case 'assurance#6':
                return PretServices::send_persee_media($this->assuranceModel);
            case 'assurance#7':
                return OceadsSubServices::send_oceads($this->assuranceModel, "SANTE");
            case 'assurance#8':
                return MediamoovSubServices::send($this->assuranceModel, "216");
            case 'assurance#9':
                return CardataSubServices::send($this->assuranceModel, 'jpTX');
            case 'assurance#10':
                return MediamoovSubServices::send($this->assuranceModel, "217");
            case 'assurance#11':
                return VieServices::send_assurance_obseque_mutac_sheets($this->assuranceModel);
            case 'assurance#12':
                return SanteServices::LMP($this->assuranceModel);
            case 'assurance#13':
                return LeadsFRSubServices::send($this->assuranceModel, "MUTU");
            case 'assurance#14':
                return IsolationServices::AstonGroup($this->assuranceModel, "MUTUELLE SENIOR");
            case 'assurance#16':
                return TestClientSubServices::send($this->assuranceModel, "assurance_animaux");
            case 'assurance#17':
                return PretServices::send_euro_crm($this->assuranceModel, "assurance emprunteur");
            case 'assurance#18':
                return SanteServices::send_euro_crm($this->assuranceModel, "MUTUELLE SENIOR");
            case 'assurance#19':
                return PretServices::send_meedia_moov($this->assuranceModel, "MUTUELLE SENIOR");
            case 'assurance#20':
                return SanteServices::DeltaCRM($this->assuranceModel, "auxillaires_veto");
            case 'assurance#21':
                return SanteServices::send_WebRivage($this->assuranceModel, "MUTUELLE SENIOR");
            case 'assurance#23':
                return PretServices::send_meedia_moov_emprunteur($this->assuranceModel, "assurance_emprunteur_mediamov");
            case 'assurance#24':
                return SanteServices::FlexyLead($this->assuranceModel, "MUTUELLE SENIOR");
            case 'assurance#25':
                return ApiService::common_send_leadvalue_mutuelle($this->assuranceModel, "9", "assurance/leadvalue", "MUTUELLE SENIOR");
            case 'assurance#27':
                return PretServices::confluentDigital_mutuel_senior($this->assuranceModel, "MUTUELLE SENIOR");

            case 'assurance#28':
                return ApiService::common_send_dataopp_assurance(
                    $this->assuranceModel,
                    "assurance/dataopp_emprunteur",
                    "71",
                    "info-emprunteur",
                    "assurance_emprunteur",
                    "assurance emprunteur"
                );
              
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
