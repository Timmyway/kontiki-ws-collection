<?php
namespace App\Services\Travaux;

use App\Providers\ExcelCreatorProvider;
use App\Providers\ExcelWriterProvider;
use App\Providers\PhoneNumberProvider;
use App\Services\SubServices\EuroCrmServices;
use DateTime;
use Exception;

class EniServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to check the eni blaclist phone number.
     * @param mixed $phone
     * @return bool
     */
    public static function eni_check_phone_number($phone)
    {
        $a = 0;
        while ($a <= 10) {
            $filepath      = "../storages/app/base_repoussoir/eni/eni_base_repoussoir_$a.json";
            $eni_blacklist = file_get_contents($filepath);
            $phone_data    = json_decode($eni_blacklist, true);

            foreach ($phone_data as $phone_value) {
                if ($phone_value === $phone) {
                    return true;
                }
            }

            $a++;
        }
        return false;
    }

    /**
     * Method to send leads ENI info to LEAD CREATIVE.
     * @param mixed $travauxModel
     * @return array
     */
    public static function send_eni($travauxModel)
    {
        $classics = $travauxModel->getClassics();
        // $specifics = $travauxModel->getSpecifics();

        try {
            $optin_date                 = date('d/m/Y H:i:s');
            $phone_number_no_whitespace = intval(PhoneNumberProvider::remove_whitespace($classics['phone']));
            $phone_number               = intval(PhoneNumberProvider::remove_plus_33($phone_number_no_whitespace));

            // check if the phone number is not blacklisted in the "base repoussoir" list.
            $is_phone_blacklisted = EniServices::eni_check_phone_number($phone_number);
            file_put_contents('../logs/travaux/eni_writeFile_error.txt', $is_phone_blacklisted);
            if ($is_phone_blacklisted) {
                return [
                    "status"       => "error",
                    "api_response" => "the phone number is blacklisted",
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads, the phone number is blacklisted",
                ];
            } else {
                $filepath = "../storages/app/energy/eni/eni_leads_" . date('d-m-Y') . ".xlsx";
                $headers  = ["REFERENCE", "CIVILITE", "NOM", "PRENOM", "NUMERO TELEPHONE", "EMAIL", "VILLE", "ZIPCODE", "DATE DE COLLECTE", "IP"];
                $data     = [
                    $classics['lead_id'],
                    $classics['civility'],
                    $classics['lastname'],
                    $classics['firstname'],
                    $phone_number,
                    $classics['email'],
                    $classics['city'],
                    intval($classics['zipcode']),
                    $optin_date,
                    $classics['ip'],
                ];
                parent::logger('../logs/travaux/eni_data.json', $data);

                if (! file_exists($filepath)) {
                    try {
                        $ExcelCreatorProvider = new ExcelCreatorProvider($filepath, $headers);
                        $ExcelCreatorProvider->createFile();
                    } catch (\Throwable $th) {
                        // throw $th;
                        file_put_contents('../logs/travaux/eni_createFile_error.txt', $th);
                    }

                }

                try {
                    $ExcelWriterProvider = new ExcelWriterProvider($filepath);
                    $ExcelWriterProvider->appendRow($data);
                    $ExcelWriterProvider->save();

                    $write_status = true;
                } catch (\Throwable $th) {
                    //throw $th;
                    file_put_contents('../logs/travaux/eni_writeFile_error.txt', $th);
                    $write_status = false;
                }

                // return response
                if ($write_status === true) {
                    return [
                        "status"       => "success",
                        "api_response" => "saved",
                        "id_part"      => "0",
                        "ws_statut"    => "ok",
                        "description"  => "lead for ENI saved successfully",
                    ];
                } else {
                    return [
                        "status"       => "error",
                        "api_response" => "error appending data to the excelFile",
                        "id_part"      => "",
                        "ws_statut"    => "error",
                        "description"  => "error sending leads, error appending data to the excelFile",
                    ];
                }
            }
        } catch (Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    /**
     * Method to send lead ASSURANCE PRET info to LEAD CREATIVE
     * @return array
     */
    public static function send_euro_crm($travauxModel)
    {
        // URL de recette pour les tests
        $url_send = 'https://ws-plateformeleads.1sa.io/recette/vendeurlead';

        // URL de production (à activer après validation des tests)
        // $url_send = 'https://ws-plateformeleads.1sa.io/production/vendeurlead';

        $headers = [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Accept: application/json',
            'accountId: API_KONTIKI_MEDIA',             // Remplacer par votre vrai accountId
            // 'apiKey: a80eb7f24fe9ec285b3860448baabef6', // Remplacer par votre vraie apiKey
            'apiKey: WmKF9gFMyG9Fvw9H##!'
        ];

        $classics        = $travauxModel->getClassics();
        $specifics       = $travauxModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);
        $birthdate       = new DateTime($classics['birthdate']);

        try {
            $data = EuroCrmServices::make_energy_datas($classics, $specifics, $gender_category, $birthdate);

            // Log avant envoi
            parent::logger('../logs/travaux/euro_crm_energy_before.json', $data);

            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30, // Timeout de 30 secondes
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
            ]);

            $output    = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Log après envoi
            parent::logger('../logs/travaux/euro_crm_energy_after.json', [
                'http_code' => $http_code,
                'response'  => json_decode($output),
            ]);

            curl_close($curl);

            // Vérifier le code HTTP
            if ($http_code !== 200) {
                return [
                    "status"       => "error",
                    "api_response" => $output,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur HTTP {$http_code}",
                ];
            }

            $json_response = json_decode($output);
            return EuroCrmServices::make_energy_responses($json_response, $output);

        } catch (Exception $e) {
            parent::logger('../logs/travaux/euro_crm_energy_error.json', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return parent::common_internal_server_error();
        }
    }
}
