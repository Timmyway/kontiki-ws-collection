<?php
namespace App\Services;

use App\Providers\CurlProvider;
use App\Services\SubServices\DataOppSubServices;
use App\Services\SubServices\EdileadSubServices;
use App\Services\SubServices\GoracashSubServices;
use App\Services\SubServices\LeadValueSubServices;
use App\Services\SubServices\SofanmediaSubServices;
use DateTime;
use Exception;

class ApiService
{

    /**
     * method to format gender/civility by category.
     * @param mixed $civility
     * @param int $category
     * @return string
     */
    public static function category_gender_transform($civility, $category)
    {
        switch ($category) {
            case 1:
                $male   = 'M';
                $female = 'F';
                break;
            case 2:
                $male   = 'mr';
                $female = 'mrs';
                break;
            case 3:
                $male   = 'Monsieur';
                $female = 'Madame';
                break;
            case 4:
                $male   = '50';
                $female = '51';
                break;
            case 5:
                $male   = 'MONSIEUR';
                $female = 'MADAME';
                break;
            case 6:
                $male   = 1;
                $female = 2;
                break;

            default:
                $male   = 'monsieur';
                $female = 'madame';
                break;
        }
        switch ($civility) {
            case "mme":
                return $female;
            case "mr":
                return $male;
            default:
                return $male;
        }
    }

    /**
     * method to format situation by category.
     * @param mixed $situation
     * @param int $category
     * @return string
     */
    public static function category_situation_transform($situation, $category)
    {
        switch ($category) {
            case 1:
                $prop  = 'P';
                $loc   = 'L';
                $other = 'O';
                break;
            case 2:
                $prop  = 'Propriétaire';
                $loc   = 'Locataire';
                $other = 'Autre';
                break;
            case 3:
                $prop  = 1;
                $loc   = 2;
                $other = 4;
                break;

            default:
                $prop  = 'Proprietaire';
                $loc   = 'Locataire';
                $other = 'Autre';
                break;
        }
        switch ($situation) {
            case 'Proprietaire':
                return $prop;
            case 'Locataire':
                return $loc;

            default:
                return $other;
        }
    }

    /**
     * method to format matrimoniale situation by category.
     * @param mixed $matrimoniale
     * @param mixed $category
     * @return string
     */
    public static function category_matrimoniale_transform($matrimoniale, $category)
    {
        switch ($category) {
            case 1:
                $celibat = 'Célibataire';
                $marie   = 'Marié';
                $divorce = 'Divorcé';
                $veuf    = 'Veuf';
                $autre   = 'En union libre';
                break;

            default:
                $celibat = 'celibataire';
                $marie   = 'marie';
                $divorce = 'divorce';
                $veuf    = 'veuf(e)';
                $autre   = 'concubinage';
                break;
        }
        switch ($matrimoniale) {
            case 'je-suis-celibataire':
                return $celibat;
            case 'je-suis-marie-pacse':
                return $marie;
            case 'je-suis-divorce':
                return $divorce;
            case 'je-suis-veuf-veuve':
                return $veuf;

            default:
                return $autre;
        }
    }

    /**
     * method to format project type according to the client.
     * @param mixed $projectType
     * @return int
     */
    public static function defisc_projectType_transform($projectType)
    {
        switch ($projectType) {
            case 'Immobilier':
                return 750010000;
            case 'Placement':
                return 750010001;

            default:
                return 750010000;
        }
    }

    /**
     * Method to create and write in a log file
     * @param mixed $file
     * @param mixed $new_data
     * @return void
     */
    public static function logger($file, $new_data)
    {
        // Read the existing JSON data from the file
        $json_data = file_get_contents($file);
        $data      = json_decode($json_data, true);

        // Append the new data to the array
        $data[] = $new_data;

        // Write the updated data back to the file
        $json_data = json_encode($data);
        file_put_contents($file, $json_data);
    }

    /**
     * Method to calculate age according to the birthdate
     * @param mixed $birthdate
     * @return int
     */
    public static function calculate_age($birthdate)
    {
        $dob  = date('Y-m-d', strtotime($birthdate));
        $now  = new DateTime();
        $diff = $now->diff(new DateTime($dob));

        $age = $diff->y; // Get the years component of the difference

        return $age;
    }

    /**
     * Method to capitalize the first character of a string.
     * @param mixed $the_string
     * @return string
     */
    public static function first_character_capitalizer($the_string): string
    {
        return ucfirst(strtolower($the_string));
    }

    /**
     * Commonly used method to return internal server error.
     * @return array<string>
     */
    public static function common_internal_server_error()
    {
        return [
            "status"       => "error",
            "api_response" => "null",
            "id_part"      => "",
            "ws_statut"    => "",
            "description"  => "error sending leads, SOMETHING WENT WRONG",
        ];
    }

    /**
     * Commonly method used for creating pinel leads data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $utm
     * @return array
     */
    public static function common_pinel_datas($classics, $specifics, $utm)
    {
        try {
            $dob = DateTime::createFromFormat('d/m/Y', $classics['birthdate'])->format('Y-m-d');
        } catch (\Throwable $th) {
            $dob = $classics['birthdate'];
        }
        $data = [
            "Fields" => [
                [
                    "Value" => $utm,
                    "Key"   => "if_utm_activity",
                ],
                [
                    "Value" => $classics['email'],
                    "Key"   => "if_ctc1_email1",
                ],
                [
                    "Value" => $classics['lastname'],
                    "Key"   => "if_ctc1_lastname",
                ],
                [
                    "Value" => $classics['firstname'],
                    "Key"   => "if_ctc1_firstname",
                ],
                [
                    "Value" => $classics['zipcode'],
                    "Key"   => "if_ctc1_postalcode",
                ],
                [
                    "Value" => $classics['city'],
                    "Key"   => "if_ctc1_city",
                ],
                [
                    "Value" => $classics['phone'],
                    "Key"   => "if_ctc1_mobilephone",
                ],
                [
                    "Value" => ApiService::defisc_projectType_transform($specifics['project_type']),
                    "Key"   => "if_project_type",
                ],
                [
                    "Value" => $dob,
                    "Key"   => "if_ctc1_birthday",
                ],
                [
                    "Value" => ApiService::category_gender_transform($classics['civility'], 0),
                    "Key"   => "if_ctc1_salutation",
                ],
                [
                    "Value" => ApiService::category_matrimoniale_transform($classics['matrimoniale'], 0),
                    "Key"   => "if_ctc1_familysituationid",
                ],
                [
                    "Value" => ApiService::category_situation_transform($classics['situation'], 1),
                    "Key"   => "if_family_customerproperties",
                ],
            ],
        ];

        return $data;
    }

    /**
     * Commonly method used for creating pinel response
     * @param mixed $curl_response
     * @param mixed $http_code
     * @param mixed $json_response
     * @param mixed $client
     * @return array
     */
    public static function common_pinel_responses($curl_response, $http_code, $json_response, $client)
    {
        if ($http_code === 200) {
            return [
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->datas,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to $client",
            ];
        } elseif ($http_code === 201) {
            return [
                "status"       => "error",
                "api_response" => $curl_response,
                "id_part"      => $json_response->datas,
                "ws_statut"    => "doublon",
                "description"  => "the lead already exist in the client side of $client",
            ];
        } elseif ($http_code === 202) {
            return [
                "status"       => "error",
                "api_response" => $curl_response,
                "id_part"      => $json_response->datas,
                "ws_statut"    => "contact déjà envoyé",
                "description"  => "the lead has already been sent once to $client",
            ];
        }
        return [
            "status"       => "error",
            "api_response" => $curl_response,
            "id_part"      => $json_response->datas ?? "",
            "ws_statut"    => "error",
            "description"  => "error sending leads, SOMETHING WENT WRONG..",
        ];
    }

    /**
     * Commonly method used for spreadsheets writting responses.
     * @param mixed $campaign
     * @return array
     */
    public static function common_spreadsheets_responses($campaign, $success)
    {
        if ($success) {
            return [
                "status"       => "success",
                "api_response" => "writting to the spreedsheets OK",
                "id_part"      => "0",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to $campaign googlesheets",
            ];
        }
        return [
            "status"       => "error",
            "api_response" => "writting to the spreedsheets ERROR",
            "id_part"      => "0",
            "ws_statut"    => "error",
            "description"  => "error sending leads, Something went wrong when writting to the drive",
        ];
    }

    /**
     * Commonly method used for sending any kind of lead information to LEAD VALUE.
     * @param mixed $model
     * @param string $form_id
     * @param string $logfile
     * @param string $campaign
     * @return array
     */
    public static function common_send_leadvalue($model, $form_id, $logfile, $campaign)
    {
        $url_send_pannsol_leadvalue = 'https://api.leadvalue.fr/view/leads?token=&api_key=a1f40edf9106fa22a2ebb5207aa1f93d';

        $classics        = $model->getClassics();
        $specifics       = $model->getSpecifics();
        $gender_category = ApiService::category_gender_transform($classics['civility'], 1);

        try {
            $headers = [
                "Content-Type: application/json",
            ];

            $data = LeadValueSubServices::make_leadvalue_datas($classics, $specifics, $gender_category, $form_id);
            ApiService::logger('../logs/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send_pannsol_leadvalue, $headers, json_encode($data));
            ApiService::logger('../logs/' . $logfile . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            // return response
            return LeadValueSubServices::make_leadvalue_responses($json_response, $curl_response[0], $campaign);
        } catch (Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Commonly method used for sending any kind of lead information to LEAD VALUE.
     * @param mixed $model
     * @param string $form_id
     * @param string $logfile
     * @param string $campaign
     * @return array
     */
    public static function common_send_leadvalue_mutuelle($model, $form_id, $logfile, $campaign)
    {
        $url_send_pannsol_leadvalue = 'https://api.leadvalue.fr/view/leads?token=&api_key=a1f40edf9106fa22a2ebb5207aa1f93d';

        $classics        = $model->getClassics();
        $specifics       = $model->getSpecifics();
        $gender_category = ApiService::category_gender_transform($classics['civility'], 1);

        try {
            $headers = [
                "Content-Type: application/json",
            ];

            $data = LeadValueSubServices::make_leadvalue_datas_mutuelle($classics, $specifics, $gender_category, $form_id);
            ApiService::logger('../logs/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send_pannsol_leadvalue, $headers, json_encode($data));
            ApiService::logger('../logs/' . $logfile . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            // return response
            return LeadValueSubServices::make_leadvalue_responses($json_response, $curl_response[0], $campaign);
        } catch (Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Commonly method used for sending any kind of lead information to DATA OPP.
     * @param mixed $model
     * @param string $logfile
     * @param string $cid
     * @param string $subdomain
     * @param string $campaign_short
     * @param string $campaign
     * @return array
     */
    public static function common_send_dataopp($model, $logfile, $cid, $subdomain, $campaign_short, $campaign)
    {
        $url_send_pannsol_dataopp = 'https://api.ma-transition-energetique.com/';

        $classics            = $model->getClassics();
        $specifics           = $model->getSpecifics();
        $situation_familiale = ApiService::category_situation_transform($classics['situation'], 0);
        $heating_type        = ApiService::first_character_capitalizer($specifics['type_chauffage']);

        try {

            $headers = [
                "Content-Type: application/x-www-form-urlencoded",
            ];

            $data = DataOppSubServices::make_dataopp_datas($cid, $subdomain, $campaign_short, $classics, $specifics, $situation_familiale, $heating_type);
            ApiService::logger('../logs/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send_pannsol_dataopp, $headers, http_build_query($data));

            ApiService::logger('../logs/' . $logfile . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return DataOppSubServices::make_dataopp_responses($json_response, $curl_response[0], $campaign);
        } catch (Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Commonly method used for sending any kind of lead information to Edilead.
     * @param mixed $model
     * @param mixed $logfile
     * @param mixed $ndflow_id
     * @param mixed $utm
     * @param mixed $campaign
     * @return array
     */
    public static function common_send_edilead($model, $logfile, $ndflow_id, $utm, $campaign)
    {
        $url_send = 'https://api2.edilead.com/wsleads/dashboard/model/ndflowlist/new/flowlead/?provider_id=151';

        $classics        = $model->getClassics();
        $specifics       = $model->getSpecifics();
        $gender_category = ApiService::category_gender_transform($classics['civility'], 3);
        try {
            $yearofbirth = DateTime::createFromFormat('d/m/Y', $classics['birthdate'])->format('Y');
        } catch (\Throwable $th) {
            $yearofbirth = $classics['birthdate'];
        }
        $matrimoniale = ApiService::category_matrimoniale_transform($classics['matrimoniale'], 1);
        $situation    = ApiService::category_situation_transform($classics['situation'], 2);

        try {
            $headers = [
                "Content-Type: application/json",
            ];

            $data = EdileadSubServices::make_datas($classics, $specifics, $utm, $ndflow_id, $gender_category, $matrimoniale, $situation, $yearofbirth, $campaign);
            ApiService::logger('../logs/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send, $headers, json_encode($data));

            ApiService::logger('../logs/' . $logfile . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return EdileadSubServices::make_responses($json_response, $curl_response[0]);
        } catch (Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }

    /**
     * Commonly method used for sending any kind of lead travaux "energy" information to spreadsheets.
     * @param mixed $travauxModel
     * @param string $send_url
     * @param string $logfile
     * @param string $client_name
     * @return array
     */
    public static function common_send_travaux_energy_sheets($travauxModel, $send_url, $logfile, $client_name)
    {

        $classics        = $travauxModel->getClassics();
        $specifics       = $travauxModel->getSpecifics();
        $gender_category = ApiService::category_gender_transform($classics['civility'], 0);

        try {

            $headers = [
                "Content-Type: application/json",
            ];

            $data = SofanmediaSubServices::make_common_travaux_sheets_datas($classics, $specifics, $gender_category);
            ApiService::logger("../logs/travaux/$logfile", $data);

            CurlProvider::post_requests($send_url, $headers, json_encode($data));

            return ApiService::common_spreadsheets_responses($client_name, true);
        } catch (Exception $e) {

            return ApiService::common_spreadsheets_responses($client_name, false);
        }
    }

    /**
     * Commonly method used for sending any kind of lead information to goracash.
     * @param mixed $travauxModel
     * @param string $type_travaux
     * @param string $logfile
     * @return array
     */
    public static function common_send_goracash($travauxModel, $type_travaux, $logfile)
    {
        $auth_data = [
            "client_id"     => "134bc427491bf6674def570f50401e593e1c0588.apps.goracash.prod",
            "client_secret" => "a5b9d00afad874994dd5ed5ab919afafeefcb7e8",
        ];
        $auth_url = "https://ws.goracash.com/v1/auth/getAccessToken?" . http_build_query($auth_data);

        $send_url = "https://ws.goracash.com/v1/lead/estimation/create";

        $classics        = $travauxModel->getClassics();
        $specifics       = $travauxModel->getSpecifics();
        $gender_category = ApiService::category_gender_transform($classics['civility'], 5);
        $situation       = ApiService::category_situation_transform($classics['situation'], 0);
        switch ($type_travaux) {
            case '120174619':
                $desc = "Type de logement :" . $specifics['type_logement'] . ", Status: $situation, Type de chauffage: " . $specifics['type_chauffage'];
                break;
            case '120176786':
                $desc = "Type de logement :" . $specifics['type_logement'] . ", Status: $situation, Debut du travaux éstimé: " . $specifics['date_start'];
                break;
            case '174511091':
                $desc = "Type de logement :" . $specifics['type_logement'] . ", Status: $situation, Projet: " . $specifics['custom_field_1'] . ", Type de materiaux: " . $specifics['custom_field_2'];
                break;
            default:
                $desc = "Type de logement :" . $specifics['type_logement'] . ", Status: $situation, Type de chauffage: " . $specifics['type_chauffage'];
                break;
        }

        try {
            // try authentication
            $auth_response = CurlProvider::get_requests($auth_url);
            $access_token  = json_decode($auth_response[0])->access_token;

            if ($access_token !== "") {

                $headers = [];

                $data = GoracashSubServices::make_datas($classics, $auth_data["client_id"], $access_token, $gender_category, $desc, $type_travaux);
                ApiService::logger("../logs/" . $logfile . "_before.json", $data);

                $curl_response = CurlProvider::post_requests($send_url, $headers, $data);

                $json_response = json_decode($curl_response[0]);
                ApiService::logger("../logs/" . $logfile . "_after.json", $json_response);

                return GoracashSubServices::make_responses($json_response, $curl_response);
            }
            return [
                "status"       => "error",
                "api_response" => "",
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Goracash web service authentification error",
            ];

        } catch (\Throwable $th) {
            //throw $th;
            return ApiService::common_internal_server_error();
        }

    }

    public static function common_send_dataopp_assurance($model, $logfile, $cid, $subdomain, $campaign_short, $campaign)
    {
        $url_send_dataopp_assurance = 'https://api.mameilleure-assurance.com/';

        $classics  = $model->getClassics();
        $specifics = $model->getSpecifics();

        try {
            $headers = [
                "Content-Type: application/x-www-form-urlencoded",
            ];

            $data = DataOppSubServices::make_dataopp_datas($cid, $subdomain, $campaign_short, $classics, $specifics, null, null);
            ApiService::logger('../logs/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url_send_dataopp_assurance, $headers, http_build_query($data));

            ApiService::logger('../logs/' . $logfile . '_after.json', json_decode($curl_response[0]));
            $json_response = json_decode($curl_response[0]);

            return DataOppSubServices::make_dataopp_responses($json_response, $curl_response[0], $campaign);
        } catch (Exception $e) {
            return ApiService::common_internal_server_error();
        }
    }
}
