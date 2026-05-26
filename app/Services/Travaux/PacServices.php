<?php
namespace App\Services\Travaux;

use App\Providers\CurlProvider;
use DateTime;
use Exception;
use SoapClient;

class PacServices extends \App\Services\ApiService
{

    // methods

    /**
     * Method to check which PAC type to send.
     * @param string $plain_type
     * @param int $category
     * @return string
     */
    public static function check_type_pac($plain_type, $category)
    {
        switch ($category) {
            case 1:
                $air_eau       = "pompe-a-chaleur-air-eau-rdv-gratuit";
                $air_air       = "climatisation-rdv-gratuit";
                $chaudiere_gaz = "chaudiere-gaz-rdv-devis-gratuit";
                break;

            default:
                $air_eau       = "pompe-a-chaleur-air-eau";
                $air_air       = "pompe-a-chaleur-air-air";
                $chaudiere_gaz = "pompe-a-chaleur-chaudiere-gaz";
                break;
        }

        switch ($plain_type) {
            case 'pac-air-eau':
                return $air_eau;
            case 'pac-air-air':
                return $air_air;
            case 'pac-chaudiere-gaz':
                return $chaudiere_gaz;

            default:
                return $air_eau;
        }
    }

    /**
     * Method to generate random string of length = 20.
     * @return string
     */
    public static function generate_random_pass()
    {
        $characters   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = substr(str_shuffle($characters), 0, 20);

        return $randomString;
    }

    /**
     * Method for sending leads "Pome a chaleur" information to PROXISERVE.
     * @param mixed $travauxModel
     * @return array
     */
    public static function send_proxiserve($travauxModel)
    {
        $login          = "kontiki";
        $password       = "gjRqg3hdp#Ns7xnb";
        $storeId        = 1;
        $countryId      = 1;
        $shippingMethod = "freeshipping_freeshipping";
        $paymentMethod  = "free";
        $rule           = "kontiki";
        $source         = "";
        $api_url        = 'https://www.proxiserve.fr/monchauffagisteprive/api/v2_soap/?wsdl&time=' . time();

        $classics        = $travauxModel->getClassics();
        $specifics       = $travauxModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);
        $SKU             = PacServices::check_type_pac($specifics['description'], 1);

        // Connexion à l'API.
        try {

            // Create the SOAP client
            $proxy     = new SoapClient($api_url);
            $sessionId = $proxy->login($login, $password);
        } catch (\Throwable $th) {
            throw $th;
        }

        try {

            // log data before send.
            $entry_data = [
                "classics"  => $classics,
                "specifics" => $specifics,
            ];
            parent::logger("../logs/travaux/pac_proxiserve_before.json", $entry_data);

            // Création du panier.
            $cartId = $proxy->shoppingCartCreate($sessionId, $storeId);

            // Gestion du client avec couverture des deux cas. ( client exist ou pas)
            $filterCustomer = [
                'complex_filter' => [
                    [
                        'key'   => 'email',
                        'value' => ['key' => '=', 'value' => $classics['email']],
                    ],
                ],
            ];
            $customerList = $proxy->customerCustomerList($sessionId, $filterCustomer);
            if (count($customerList) > 0) {
                $customer = (array) $customerList[0];
            } else {
                $result = $proxy->customerCustomerCreate($sessionId, [
                    'email'      => $classics['email'],
                    'firstname'  => $classics['firstname'],
                    'lastname'   => $classics['lastname'],
                    'password'   => PacServices::generate_random_pass(),
                    'website_id' => 1,
                    'store_id'   => $storeId,
                    'group_id'   => 1,
                    'prefix'     => $gender_category,
                ]);
                if ($result) {
                    $customer = (array) $proxy->customerCustomerInfo($sessionId, $result); // Version V1.3
                }
            }
            $customer['mode'] = 'customer';
            $responseCustomer = $proxy->shoppingCartCustomerSet($sessionId, $cartId, $customer);

            // Gestion du produit.
            $responseProduct = $proxy->shoppingCartProductAdd($sessionId, $cartId, [['sku' => $SKU, 'qty' => 1]]);

            // Gestion des adresses.
            $address = [
                [
                    'mode'                => 'shipping',
                    'firstname'           => $classics['firstname'],
                    'lastname'            => $classics['lastname'],
                    'street'              => $classics['address'],
                    'city'                => $classics['city'],
                    'region'              => '',
                    'telephone'           => $classics['phone'],
                    'postcode'            => $classics['zipcode'],
                    'country_id'          => $countryId,
                    'is_default_shipping' => 0,
                    'is_default_billing'  => 0,
                ],
                [
                    'mode'                => 'billing',
                    'firstname'           => $classics['firstname'],
                    'lastname'            => $classics['lastname'],
                    'street'              => $classics['address'],
                    'city'                => $classics['city'],
                    'region'              => '',
                    'telephone'           => $classics['phone'],
                    'postcode'            => $classics['zipcode'],
                    'country_id'          => $countryId,
                    'is_default_shipping' => 0,
                    'is_default_billing'  => 0,
                ],
            ];
            $responseAddress = $proxy->shoppingCartCustomerAddresses($sessionId, $cartId, $address);

            // Gestion de la méthode de livraison.
            $responseShippingMethod = $proxy->shoppingCartShippingMethod($sessionId, $cartId, $shippingMethod);

            // Gestion de la méthode de paiement.
            $paymentMethodArray = [
                'po_number'    => null,
                'method'       => $paymentMethod,
                'cc_cid'       => null,
                'cc_owner'     => null,
                'cc_number'    => null,
                'cc_type'      => null,
                'cc_exp_year'  => null,
                'cc_exp_month' => null,
            ];
            $responsePayment = $proxy->shoppingCartPaymentMethod($sessionId, $cartId, $paymentMethodArray);

            // Ajout du rendez-vous.
            $assignIntervention = $proxy->shoppingCartInterventionAssign($sessionId, $cartId, $classics['zipcode']);

            // Création et finalisation de la commande.
            $orderId = $proxy->shoppingCartOrder($sessionId, $cartId, $storeId, null);
            $result  = $proxy->orderLeadFinalize($sessionId, $orderId, $rule, $source, $classics['lead_id']);

            $response_data = [
                "responseCustomer"       => $responseCustomer,
                "responseProduct"        => $responseProduct,
                "responseAddress"        => $responseAddress,
                "responseShippingMethod" => $responseShippingMethod,
                "responsePayment"        => $responsePayment,
                "assignIntervention"     => $assignIntervention,
            ];
            parent::logger("../logs/travaux/pac_proxiserve_after.json", $response_data);

            if ($assignIntervention != true) {
                return [
                    "status"       => "error",
                    "api_response" => json_encode($response_data),
                    "id_part"      => "",
                    "ws_statut"    => "not eligible",
                    "description"  => "lead has been send PROXISERVE but the ZIPCODE is not eligible",
                ];
            }
            return [
                "status"       => "success",
                "api_response" => json_encode($response_data),
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to PROXISERVE",
            ];
        } catch (\Throwable $th) {
            //throw $th;
            return parent::common_internal_server_error();
        }
    }

    public static function send_lovvis_ads_proxiserve($travauxModel, $client)
    {
        $login          = "lovvis-ads";
        $password       = "Toto\$GFK5j7Bd9&J";
        $storeId        = 1;
        $countryId      = 1;
        $shippingMethod = "freeshipping_freeshipping";
        $paymentMethod  = "free";
        $rule           = "lovvis-ads";
        $source         = 1130;
        $api_url        = 'https://www.proxiserve.fr/monchauffagisteprive/api/v2_soap/?wsdl&time=' . time();

        $classics        = $travauxModel->getClassics();
        $specifics       = $travauxModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);

        switch ($client) {
            case 'teletech':
                $SKU = "climatisation-rdv-gratuit";
                break;
            case 'tersea':
                $SKU = "climatisation-boost-rdv-gratuit-tersea";
                break;
            default:
                $SKU = "climatisation-rdv-gratuit";
                break;
        }

        // Connexion à l'API.
        try {

            // Create the SOAP client
            $proxy     = new SoapClient($api_url);
            $sessionId = $proxy->login($login, $password);
        } catch (\Throwable $th) {
            throw $th;
        }

        try {

            // log data before send.
            $entry_data = [
                "classics"  => $classics,
                "specifics" => $specifics,
            ];
            parent::logger("../logs/travaux/pac_lovvis_ads_proxiserve_before.json", $entry_data);

            // Création du panier.
            $cartId = $proxy->shoppingCartCreate($sessionId, $storeId);

            // Gestion du client avec couverture des deux cas. ( client exist ou pas)
            $filterCustomer = [
                'complex_filter' => [
                    [
                        'key'   => 'email',
                        'value' => ['key' => '=', 'value' => $classics['email']],
                    ],
                ],
            ];
            $customerList = $proxy->customerCustomerList($sessionId, $filterCustomer);
            if (count($customerList) > 0) {
                $customer = (array) $customerList[0];
            } else {
                $result = $proxy->customerCustomerCreate($sessionId, [
                    'email'      => $classics['email'],
                    'firstname'  => $classics['firstname'],
                    'lastname'   => $classics['lastname'],
                    'password'   => PacServices::generate_random_pass(),
                    'website_id' => 1,
                    'store_id'   => $storeId,
                    'group_id'   => 1,
                    'prefix'     => $gender_category,
                ]);
                if ($result) {
                    $customer = (array) $proxy->customerCustomerInfo($sessionId, $result); // Version V1.3
                }
            }
            $customer['mode'] = 'customer';
            $responseCustomer = $proxy->shoppingCartCustomerSet($sessionId, $cartId, $customer);

            // Gestion du produit.
            $responseProduct = $proxy->shoppingCartProductAdd($sessionId, $cartId, [['sku' => $SKU, 'qty' => 1]]);

            // Gestion des adresses.
            $address = [
                [
                    'mode'                => 'shipping',
                    'firstname'           => $classics['firstname'],
                    'lastname'            => $classics['lastname'],
                    'street'              => $classics['address'],
                    'city'                => $classics['city'],
                    'region'              => '',
                    'telephone'           => $classics['phone'],
                    'postcode'            => $classics['zipcode'],
                    'country_id'          => $countryId,
                    'is_default_shipping' => 0,
                    'is_default_billing'  => 0,
                ],
                [
                    'mode'                => 'billing',
                    'firstname'           => $classics['firstname'],
                    'lastname'            => $classics['lastname'],
                    'street'              => $classics['address'],
                    'city'                => $classics['city'],
                    'region'              => '',
                    'telephone'           => $classics['phone'],
                    'postcode'            => $classics['zipcode'],
                    'country_id'          => $countryId,
                    'is_default_shipping' => 0,
                    'is_default_billing'  => 0,
                ],
            ];
            $responseAddress = $proxy->shoppingCartCustomerAddresses($sessionId, $cartId, $address);

            // Gestion de la méthode de livraison.
            $responseShippingMethod = $proxy->shoppingCartShippingMethod($sessionId, $cartId, $shippingMethod);

            // Gestion de la méthode de paiement.
            $paymentMethodArray = [
                'po_number'    => null,
                'method'       => $paymentMethod,
                'cc_cid'       => null,
                'cc_owner'     => null,
                'cc_number'    => null,
                'cc_type'      => null,
                'cc_exp_year'  => null,
                'cc_exp_month' => null,
            ];
            $responsePayment = $proxy->shoppingCartPaymentMethod($sessionId, $cartId, $paymentMethodArray);

            // Ajout du rendez-vous.
            $assignIntervention = $proxy->shoppingCartInterventionAssign($sessionId, $cartId, $classics['zipcode']);

            // Création et finalisation de la commande.
            $orderId = $proxy->shoppingCartOrder($sessionId, $cartId, $storeId, null);
            $result  = $proxy->orderLeadFinalize($sessionId, $orderId, $rule, $source, $classics['lead_id']);

            $response_data = [
                "responseCustomer"       => $responseCustomer,
                "responseProduct"        => $responseProduct,
                "responseAddress"        => $responseAddress,
                "responseShippingMethod" => $responseShippingMethod,
                "responsePayment"        => $responsePayment,
                "assignIntervention"     => $assignIntervention,
            ];
            parent::logger("../logs/travaux/pac_lovvis_ads_proxiserve_after.json", $response_data);

            if ($assignIntervention != true) {
                return [
                    "status"       => "error",
                    "api_response" => json_encode($response_data),
                    "id_part"      => "",
                    "ws_statut"    => "not eligible",
                    "description"  => "lead has been send PROXISERVE but the ZIPCODE is not eligible",
                ];
            }
            return [
                "status"       => "success",
                "api_response" => json_encode($response_data),
                "id_part"      => "",
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to PROXISERVE",
            ];
        } catch (\Throwable $th) {
            //throw $th;
            return parent::common_internal_server_error();
        }
    }

    public static function send_unitead_sheets($travauxModel, $url, $logfile, $typeTravaux)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        $headers   = [
            'Content-Type: application/x-www-form-urlencoded',
            'API-Key: $2y$10$vejPLjDoliDQznFaNXrO9O.NxWaLtaskmJzpJAFRVkfCpDMJ1.6iO',
        ];
        $urlWS = "https://api.ulead.app/v1.2/lead";

        if ($logfile === 'panneau_unitead') {
            $siteId   = '8';
            $campaign = "Unitead PV";
        } else if ($logfile === 'pac_unitead') {
            $siteId   = '9';
            $campaign = "Unitead PAC";
        }

        $typeHeating = [
            "bois"        => "Bois",
            "electricite" => "Electricité",
            "fuel"        => "Fioul",
            "gaz"         => "Gaz",
            "autre"       => "Autre type de chauffage",
        ];
        $typeResidence = [
            "Locataire"    => "Locataire",
            "Proprietaire" => "Propriétaire",
        ];

        try {
            $datasheet = [
                "site_id"             => $siteId,
                "origin_code"         => 'ktk2024',
                "customer_ip"         => $classics['ip'],
                "customer_user_agent" => $classics['userAgent'],
                "lead_date"           => date('d-m-Y H:i:s'),
                "email"               => $classics['email'],
                "telephone"           => $classics['phone'],
                "prenom_nom"          => $classics['lastname'] . ' ' . $classics['firstname'],
                "code_postal"         => $classics['zipcode'],
                "type_travaux"        => $typeTravaux,
                "type_chauffage"      => $specifics['type_chauffage'],
                "type_residence"      => $classics['situation'],
                "type_habitation"     => $specifics['type_logement'],
            ];

            $data = [
                "site_id"             => $siteId,
                "origin_code"         => 'ktk2024',
                "customer_ip"         => $classics['ip'],
                "customer_user_agent" => $classics['userAgent'],
                //"lead_date"           => date('d/m/Y'),
                "lead_date"           => time(),
                "email"               => $classics['email'],
                "telephone"           => $classics['phone'],
                "prenom_nom"          => $classics['lastname'] . ' ' . $classics['firstname'],
                "code_postal"         => $classics['zipcode'],
                "type_travaux"        => $typeTravaux,
                "type_chauffage"      => $typeHeating[$specifics['type_chauffage']],
                "type_residence"      => $specifics['type_logement'] . ' - ' . $typeResidence[$classics['situation']],
                //"type_habitation"     => $specifics['type_logement']
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);

            if ($data['type_residence'] === "Maison - Propriétaire") {
                CurlProvider::post_requests($url, [], $datasheet);

                $curl_response = CurlProvider::post_requests($urlWS, $headers, http_build_query($data));
                $responses     = json_decode($curl_response[0]);
                parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

                if ($responses->status) {
                    return [
                        "status"       => "success",
                        "api_response" => $curl_response,
                        "id_part"      => $responses->uniqid,
                        "ws_statut"    => "ok",
                        "description"  => "lead has been send successfully to $campaign",
                    ];
                } else {
                    return [
                        "status"       => "error",
                        "api_response" => $curl_response,
                        "id_part"      => "",
                        "ws_statut"    => $responses->status_detail,
                        "description"  => "error sending leads to $campaign",
                    ];
                }
            } else {
                return [
                    "status"       => "error",
                    "api_response" => "",
                    "id_part"      => "",
                    "ws_statut"    => "Invalid Data",
                    "description"  => "error sending leads to $campaign",
                ];
            }

            //return parent::common_spreadsheets_responses($campaign, true);
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function send_mailomedia_sheets($travauxModel, $url)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        $phone     = preg_replace('/^(?:\+?33|0)/', '0', $classics['phone']);
        $civ       = [
            "mr"  => "M.",
            "mme" => "Mme.",
        ];
        try {
            $data = [
                "Civilité"         => $civ[$classics['civility']],
                "Prénom"           => $classics['firstname'],
                "Nom"              => $classics['lastname'],
                "Email"            => $classics['email'],
                "Téléphone"        => $phone,
                "Code postal"      => $classics['zipcode'],
                "Ville"            => $classics['city'],
                "Adresse"          => $classics['address'],
                "Type de logement" => $specifics['type_logement'], //Maison ou Appartement
            ];

            parent::logger('../logs/travaux/pac_mailomedia_before.json', $data);
            $response = CurlProvider::post_requests($url, ['Content-Type: application/json'], json_encode($data));
            $logs     = [
                "email"    => $classics['email'],
                "response" => json_decode($response[0], true) !== null ? json_decode($response[0], true) : $response[0],
            ];
            parent::logger('../logs/travaux/pac_mailomedia_after.json', json_encode($logs));

            if ($response[0] === "Accepted") {
                return [
                    "status"       => "success",
                    "api_response" => $response[0],
                    "id_part"      => "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Mailomedia",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => json_decode($response[0], true) !== null ? json_decode($response[0], true) : $response[0],
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "error sending leads to Mailomedia",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }

    public static function TedJordanSrl($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        /* https://docs.google.com/spreadsheets/d/1ws0iHd2A5UwEc8UASYJMWMN22PSid-UTc5eRYsPx5XU/edit?hl=fr&gid=0#gid=0 */

        $url = "https://script.google.com/macros/s/AKfycbzYR4O5tCdFKhcRXzEaIIS0JyHzxV0qGz-eHFxSEWwWpRdelBcHxTyv9hjY12bLFf8Y/exec";
        try {
            $data = [
                'Date'                 => date('Y-m-d'),
                'Lead Type'            => 'PAC',
                'Is_Owner'             => $classics['situation'],
                'Type_logement'        => $specifics['type_logement'],
                'Postal_code'          => $classics['zipcode'],
                'Electricity_bill'     => '',
                'Full_name'            => $classics['firstname'] . ' ' . $classics['lastname'],
                'Email'                => $classics['email'],
                'Tel'                  => $classics['phone'],
                'Energie de chauffage' => $specifics['type_chauffage'],
            ];

            parent::logger('../logs/travaux/panneau_TedJordanSrl_before.json', $data);
            CurlProvider::post_requests($url, [], $data);

            return parent::common_spreadsheets_responses("Ted Jordan Srl", true);
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("Ted Jordan Srl", false);
        }
    }

    public static function FlexyLead($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url = "https://flexlead.leadbyte.co.uk/restapi/v1.3/leads";

        $gender = [
            "mr"  => "Homme",
            "mme" => "Femme",
        ];
        $typeHeating = [
            "bois"        => "Bois",
            "electricite" => "Electricité",
            "fuel"        => "Fioul",
            "gaz"         => "Gaz",
            "autre"       => "Autre",
        ];

        try {
            $data = [
                'campid'                      => "FR--PAC",
                'sid'                         => "79",
                // 'testmode' => "yes",
                'email'                       => $classics['email'],
                'firstname'                   => $classics['firstname'],
                'lastname'                    => $classics['lastname'],
                'dob'                         => " 01/01/1987",
                'street1'                     => $classics['address'],
                'towncity'                    => $classics['city'],
                'postcode'                    => $classics['zipcode'],
                'phone1'                      => $classics['phone'],
                'Vous_êtes_?'                 => $classics['situation'],
                'vous_habitez_dans_un(e)*?'   => $specifics['type_logement'],
                'type_de_chauffage_?'         => $typeHeating[$specifics['type_chauffage']],
                'situation_professionnelle_?' => "En activite",
                'sexe_?'                      => $gender[$classics['civility']],
            ];

            parent::logger('../logs/travaux/pac_FlexyLead_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, ["X_KEY: 02ea2da5f2daa4d9c464dd5a7450abd2"], json_encode($data));

            $responses = json_decode($curl_response[0], true);
            parent::logger('../logs/travaux/pac_FlexyLead_after.json', $responses);

            if ($responses['status'] === "Success") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['results'][0]['queueId'] ?? 0,
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to Flexylead",
                ];
            } else if ($responses['status'] === "Error") {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => $responses['errors'][0] ?? $responses['message'],
                    "description"  => "error sending leads to Flexylead: " . $responses['errors'][0],
                ];
            }
        } catch (\Exception $e) {
            return parent::common_spreadsheets_responses("FlexyLead", false);
        }
    }

    private static function formatBirthdate($birthdate)
    {
        if (empty($birthdate)) {
            return null;
        }

        try {
            $date = new \DateTime($birthdate);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function confluentDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url = "https://service.comparer-changer.com/__ws/send_lead.php";
        // $url = "https://service.comparer-changer.com/__ws/send_lead_test.php";
        $logfile    = "pac_confluent_digital";
        $civModelId = [
            'mr'  => "homme",
            'mrs' => "homme",
            'm'   => "homme",
            'mme' => "femme",
            'F'   => "femme",
        ];

        $ownerTypeModelId = [
            'Proprietaire'  => "owner",
            'Proprietaires' => "owner",
            'Locataire'     => "tenant",
            'Locataires'    => "tenant",
        ];

        $assetTypeModelId = [
            'Appartement' => "apartment",
            'appartement' => "apartment",
            'Maison'      => "house",
            'maison'      => "house",
        ];

        $heaterType = [
            "gaz"         => "Gaz",
            "fioul"       => "Fioul",
            "fuel"        => "Fioul",
            "electricite" => "Electrique",
            "bois"        => "Bois",
            "autre"       => "Autre",
        ];
        // $phoneFormat = [
        //     "/^06(\d{8})$/" => "+336$1",
        // ];

        $birthdate = $classics['birthdate'] ?? null;
        // $birthdateFormatted = self::formatBirthdate($birthdate);
        if (empty($birthdate) || ! self::formatBirthdate($birthdate)) {
            $birthdateFormatted = "1951-12-12";
        } else {
            $birthdateFormatted = self::formatBirthdate($birthdate);
        }
        $campaign = "CONFLUENT DIGITAL PAC";

        try {
            $data = [
                'url_source'           => 'kontiki',
                'interest_area_id'     => 9,
                'token'                => '92112cd7c474af2797b92435e6b9847735e2daaa',
                'ip'                   => $classics['ip'],
                'gender'               => $civModelId[$classics['civility']],
                'firstname'            => $classics['firstname'],
                'name'                 => $classics['lastname'],
                'address'              => $classics['address'],
                'zipcode'              => $classics['zipcode'],
                'city'                 => $classics['city'],
                'email'                => $classics['email'],
                'birthday'             => $birthdateFormatted,
                // 'phone' => $classics['phone'],
                'phone'                => preg_replace("/^06(\d{8})$/", "+336$1", preg_replace("/[^0-9]/", "", $classics['phone'])),
                'home_situation'       => $ownerTypeModelId[$classics['situation']],
                'home_information'     => $assetTypeModelId[$specifics['type_logement']],
                "optin_cgu"            => 1,
                "optin_partners"       => 1,
                'heater_type'          => $heaterType[$specifics['type_chauffage']],
                'central_heating_type' => 508,
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, [], $data);

            $responses = json_decode($curl_response[0], true);

            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if ($responses['status'] === "recorded" || $responses['status'] === "Lead correct") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['infos'],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['infos'] ?? "error sending leads to $campaign: ",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function cpryDigital($travauxModel, string $type)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();

        $url     = "https://leadstudio.leadbyte.co.uk/restapi/v1.3/leads";
        $logfile = strtolower($type) . "_cpry_digital";

        $ownerTypeModelId = [
            'Proprietaire'  => "oui",
            'Proprietaires' => "oui",
            'Locataire'     => "non",
            'Locataires'    => "non",
        ];

        $assetTypeModelId = [
            'Appartement' => "appartement",
            'appartement' => "appartement",
            'Maison'      => "maison",
            'maison'      => "maison",
        ];

        $heaterType = [
            "gaz"         => "Gaz",
            "fioul"       => "Fioul",
            "fuel"        => "Fioul",
            "electricite" => "Electricite",
            "bois"        => "Bois",
            "autre"       => "Autre",
        ];

        $headers = [
            "X_KEY: 874a0724fea1b5ff30cf0a8fe161bde4",
            "content-type: application/json",
        ];

        try {

            if ($type === "PV") {
                $campaign = "CPRY DIGITAL PV";
                $data     = [
                    "campid"       => "SOLAIRE-AFFI",
                    "sid"          => "19",
                    "email"        => $classics['email'],
                    "firstname"    => $classics['firstname'],
                    "lastname"     => $classics['lastname'],
                    "postcode"     => $classics['zipcode'],
                    "phone1"       => $classics['phone'],
                    "source"       => $classics['referer'],
                    "url"          => $classics['referer'],
                    "c1"           => $classics['referer'],
                    "chauffage"    => $heaterType[$specifics['type_chauffage']],
                    "propriétaire" => $ownerTypeModelId[$classics['situation']],
                    "habitation"   => $assetTypeModelId[$specifics['type_logement']],
                    "testmode"     => "yes",
                ];

            } elseif ($type === "PAC") {
                $campaign = "CPRY DIGITAL PAC";
                $data     = [
                    "campid"       => "BU-KA-AFFI-PAC",
                    "sid"          => "19",
                    "email"        => $classics['email'],
                    "firstname"    => $classics['firstname'],
                    "lastname"     => $classics['lastname'],
                    "postcode"     => $classics['zipcode'],
                    "phone1"       => $classics['phone'],
                    "c2"           => $classics['referer'],
                    "chauffage"    => $heaterType[$specifics['type_chauffage']],
                    "proprietaire" => $ownerTypeModelId[$classics['situation']],
                    "habitation"   => $assetTypeModelId[$specifics['type_logement']],
                    "testmode"     => "yes",
                ];

            } elseif ($type === "ITE") {
                $campaign = "CPRY DIGITAL ITE";
                $data     = [
                    "campid"       => "AFFI-ITE-BACKUP",
                    "sid"          => "19",
                    "email"        => $classics['email'],
                    "firstname"    => $classics['firstname'],
                    "lastname"     => $classics['lastname'],
                    "postcode"     => $classics['zipcode'],
                    // "phone1"       => $classics['phone'],
                    "c2"           => $classics['referer'],
                    "chauffage"    => $heaterType[$specifics['type_chauffage']],
                    "proprietaire" => $ownerTypeModelId[$classics['situation']],
                    "habitation"   => $assetTypeModelId[$specifics['type_logement']],
                    "testmode"     => "yes",
                ];
            } else {
                return parent::common_internal_server_error();
            }

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);

            $curl_response = CurlProvider::post_requests($url, $headers, json_encode($data));
            $responses     = json_decode($curl_response[0], true);

            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if (isset($responses['status']) && $responses['status'] === "Success") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['results'][0]['queueId'] ?? "",
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => implode(", ", $responses['errors'] ?? ["error sending leads to $campaign"]),
                ];
            }

        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    private static $campaignCategories = [
        'pannsol#20' => 37,  // Panneaux photovoltaïques
        'iso#8'      => 12,  // Isolation
        'pac#20'     => 36,  // Pompe à chaleur
        'douche#3'   => 160, // Douche sénior
        'fenetre#3'  => 72,  // Fenêtre
    ];
    public static function PerfusionDigital($travauxModel)
    {
        $classics  = $travauxModel->getClassics();
        $specifics = $travauxModel->getSpecifics();
        $headers   = [
            'Content-Type: application / json',
            'X-API-Key: dd6c2930229472157203ddd814d1ef73b95b38fb887f66eb08feadca0024e3a4',
            'X-API-Environment: TEST',
        ];

        $url        = "https://leads.perfusiondigital.com/api/leads/submit";
        $logfile    = "pac_Perfusion_Digital";
        $civModelId = [
            'mr'  => "1",
            'mrs' => "1",
            'm'   => "1",
            'mme' => "2",
            'F'   => "2",
        ];

        $ownerTypeModelId = [
            'Proprietaire'  => "1",
            'Proprietaires' => "1",
            'Locataire'     => "2",
            'Locataires'    => "2",
        ];

        $assetTypeModelId = [
            'Appartement' => "2",
            'appartement' => "2",
            'Maison'      => "1",
            'maison'      => "1",
        ];
        $delay = [
            'urgent'      => "1",
            '1 an'        => "5",
            '6 mois'      => "4",
            'dans 6 mois' => "4",
        ];

        $campaign = "Perfusion_ Digital_PAC";

        try {
            $data = [
                'category_id' => 20,
                'tracker1'    => $campaign,
                'lead_data'   => [
                    'email'           => $classics['email'],
                    'firstname'       => $classics['firstname'],
                    'lastname'        => $classics['lastname'],
                    'civility'        => $civModelId[$classics['civility']] ?? null,

                    // Converti ton format automatique en format brut (à vérifier selon API)
                    'cellphone'       => preg_replace(
                        "/^06(\d{8})$/",
                        "+336$1",
                        preg_replace("/[^0-9]/", "", $classics['phone'])
                    ),

                    'city'            => $classics['city'],
                    'zipcode'         => $classics['zipcode'],
                    'isowner'         => $ownerTypeModelId[$classics['situation']] ?? null,
                    'workdescription' => null,
                    'housingtype'     => $assetTypeModelId[$specifics['type_logement']] ?? null,
                    'delay'           => $delay[$specifics['custom_field_3']] ?? null,
                ],
            ];

            parent::logger('../logs/travaux/' . $logfile . '_before.json', $data);
            $curl_response = CurlProvider::post_requests($url, $headers, $data);

            $responses = json_decode($curl_response[0], true);

            parent::logger('../logs/travaux/' . $logfile . '_after.json', $responses);

            if ($responses['status'] === "recorded" || $responses['status'] === "Lead correct") {
                return [
                    "status"       => "success",
                    "api_response" => $curl_response,
                    "id_part"      => $responses['infos'],
                    "ws_statut"    => "ok",
                    "description"  => "lead has been send successfully to $campaign",
                ];
            } else {
                return [
                    "status"       => "error",
                    "api_response" => $curl_response,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => $responses['infos'] ?? "error sending leads to $campaign: ",
                ];
            }
        } catch (\Exception $e) {
            return parent::common_internal_server_error();
        }
    }
    public static function send_meedia_moov($assuranceModel, $type = null)
    {
        $url_send = 'https://www.media-optin.com/api/campaigns/203/contacts';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cache-Control: no-cache',
        ];

        $classics        = $assuranceModel->getClassics();
        $specifics       = $assuranceModel->getSpecifics();
        $gender_category = parent::category_gender_transform($classics['civility'], 3);

        try {
            $birthdate = new DateTime($classics['birthdate']);
            $data      = MontEscalierServices::make_mediamoov_pac_datas(
                $classics,
                $specifics,
                $gender_category,
                $birthdate
            );

            // 🔹 LOG AVANT ENVOI
            parent::logger('../logs/travaux/mediamoov_pac_before.json', $data);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url_send,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_VERBOSE        => false,
            ]);

            $output     = curl_exec($curl);
            $curl_error = curl_error($curl);
            $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // 🔹 LOG DU CODE HTTP + SORTIE
            parent::logger('../logs/travaux/mediamoov_pac_http_code.json', [
                'http_code' => $http_code,
                'output'    => $output,
                'data_sent' => $data,
            ]);

            // 🔹 Gestion des erreurs cURL
            if ($curl_error) {
                parent::logger('../logs/travaux/mediamoov_pac_curl_error.json', [
                    'error'     => $curl_error,
                    'http_code' => $http_code,
                    'output'    => $output,
                ]);
                return [
                    "status"       => "error",
                    "api_response" => "cURL Error: " . $curl_error,
                    "id_part"      => "",
                    "ws_statut"    => "error",
                    "description"  => "Erreur de connexion: " . $curl_error,
                ];
            }

            // 🔹 LOG DE LA RÉPONSE BRUTE
            parent::logger('../logs/travaux/mediamoov_pac_original.json', $output);

            // 🔹 Traitement via la fonction de réponse
            $final_response = MontEscalierServices::make_mediamoov_pac_responses($output, $http_code);

            // 🔹 LOG FINAL
            parent::logger('../logs/travaux/mediamoov_pac_after.json', $final_response);

            return $final_response;

        } catch (Exception $e) {
            // 🔹 LOG D'EXCEPTION
            parent::logger('../logs/travaux/mediamoov_pac_exception.json', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return parent::common_internal_server_error();
        }
    }
}
