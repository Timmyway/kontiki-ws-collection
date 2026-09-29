<?php

namespace App\Providers;

use App\Controllers\LeadsController;
use App\Services\SendAssuranceService;
use App\Services\SendFormationService;
use App\Services\SendSecurityService;
use App\Services\SendTravauxService;

class DeliveryDestinationProvider
{
    public static function dispacth($deliverable, $classicsData, $specificsData, $insert_date, $partname, $clients, $conn)
    {
        //explode deliverable to get tag on [0] and clients list on [1]
        $delivery = explode('/', $deliverable);

        //explode clients list to array and do all operation foreach client.
        $delivery_clients = explode('-', $delivery[1]);
        $delivery_results = [];

        foreach ($delivery_clients as $client) {
            /**.
             * detect destination Api service class.
             */
            $clientID = $delivery[0] . '#' . $client;
            switch ($delivery[0]) {
                case 'assurance':
                    $lead_properties = new SendAssuranceService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'security':
                    $lead_properties = new SendSecurityService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'formation':
                    $lead_properties = new SendFormationService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'pannsol':
                    $lead_properties = new SendTravauxService($classicsData, $specificsData, $clientID, $insert_date);
                    break;

                default:
                    $lead_properties = new SendAssuranceService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
            }
            $status = $lead_properties->send();
            //append the result to the results arrays
            $delivery_results[] = $status;
        }

        // write each response to the database after sending the data.
        foreach ($delivery_results as $send_response) {

            // SAVE CLIENTS responses into the DB after sending the lead.
            /*
             */
            LeadsController::save_leads_has_clients($conn, TimeProvider::getTime(), $partname, $classicsData["lead_id"], $send_response, $clients[$clientID]);
        }
    }
}