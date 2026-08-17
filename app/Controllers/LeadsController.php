<?php

namespace App\Controllers;

use App\Providers\ConnexionProvider;
use App\Providers\PhoneNumberProvider;
use App\Providers\TimeProvider;
use App\Requests\CurrentRequests;
use App\Requests\DiscardedRequests;
use App\Requests\SavesRequests;
use App\Requests\ValidatedRequests;


class LeadsController
{
    /**
     * Method to Decode response to send to the clients.
     * @param mixed $array
     * @return array
     */
    public static function decodeResponse($array) : array
    {
        foreach ($array as $key => $value) {
            if($key === 'id'){
                continue;
            }
            if (is_array($value)) {
                // If the value is an array, call this function recursively.
                $array[$key] = LeadsController::decodeResponse($value);
            } else {
                // If the value is not an array, try to decode it.
                // $isCorrectBase64 = LeadsController::isBase64Encoded($value);
                if (!empty($value) && is_string($value)) {
                    $decoded = addslashes(base64_decode($value));
                } else {
                    $decoded = '';
                }
                // $decoded = addslashes(base64_decode($value));
                if (mb_check_encoding($decoded, 'UTF-8') && $decoded !== "") {
                    // If the value was successfully decoded, replace it with the decoded version.

                    // check if $value is a phone number
                    if ($key === 'phone') {
                        $array[$key] = PhoneNumberProvider::remove_plus_33(PhoneNumberProvider::remove_whitespace($decoded));
                    } else if (in_array($key, ['firstname', 'lastname', 'city'])) {
                        $array[$key] = ucfirst(strtolower($decoded));
                    } else {
                        $array[$key] = $decoded;
                    }
                }
                // If the value was not successfully decoded, leave it as it is.

                // check if $value is a date and transform it into a good date format.
                if (in_array($key, ['receive_date', 'validation_date', 'discard_date'])) {
                    $array[$key] = TimeProvider::frenchFormat($value);
                }
            }
        }
        return $array;
    }

    /**
     * Method to provid classics data right format.
     * @param mixed $input_data
     * @param mixed $ip
     * @param mixed $city
     * @return array
     */
    public static function makeClassicsData($input_data, $ip, $city, $userAgent, $referer)
    {
        if (isset($input_data['id_base'])) {
            $base_id = $input_data['id_base'];
        } else {
            $base_id = $input_data['affiliateID'];
        }

        /**
         * create lead classics DATA payload
         */
        $lead = array(
            "lead_id"          => $input_data['id'] ?? null,
            "civility"         => $input_data['civility'] ?? null,
            "birthdate"        => $input_data['birthdate'] ?? null,
            "firstname"        => $input_data['firstname'] ?? null,
            "lastname"         => $input_data['lastname'] ?? null,
            "email"            => $input_data['email'] ?? null,
            "phone"            => $input_data['phone'] ?? null,
            "zipcode"          => $input_data['zipcode'] ?? null,
            "city"             => $city ?? null,
            "address"          => $input_data['address'] ?? null,
            "geo"              => $input_data['geo'] ?? 'FR',
            "situation"        => $input_data['situation'] ?? null, // (Proprietaire | Locataire)
            "matrimoniale"     => $input_data['matrimoniale'] ?? null, // (Célibataire | marié etc...)
            "situationPro"     => $input_data['situationPro'] ?? null, // (situation professionnel ex: Salarié | en activité | retraité)
            "affiliateID"      => $base_id ?? 0,
            "ip"               => $ip,
            "userAgent"        => $userAgent,
            "referer"          => $referer,
            "description_call" => $input_data['description_call'] ?? null, // for comment if needed
            "call_up_moment"   => $input_data['call_up_moment'] ?? null, // (*mandatory for re-call module)
            "user_sender"      => $input_data['user_sender'] ?? null, // the user that treated the lead
            "user_action"      => $input_data['user_action'] ?? "commenting", // (depends on the user api call action : discarding | commenting | sending)
            "receive_date"     => $input_data['receive_date'] ?? null,
            "accept_cgu"         => !empty($input_data['acceptCGU']) ? 1 : 0, // consentement contact tel/RGPD
            "accept_cgu_partner" => !empty($input_data['acceptCGUPartner']) ? 1 : 0,
        );

        return $lead;
    }

    /**
     * Method to get lists of "not" validated leads.
     * @param mixed $partenaire
     * @param mixed $offset
     * @param mixed $limit
     * @param mixed $partners
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_leads($partenaire, $offset, $limit, $partners, $conn)
    {
        try {
            /** 
             * first get list of leads
             */
            $list_query = CurrentRequests::which_query($partenaire, $offset, $limit, $partners);
            $rows       = ConnexionProvider::fetch_all($conn, $list_query);


            /** 
             * next get leads count.
             */
            $count_query = CurrentRequests::which_count_query($partenaire, $partners);
            $leads_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays leads count.
             */
            $today_count_query = CurrentRequests::which_todays_count_query($partenaire, $partners);
            $today_leads_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($rows),
                "count"        => $leads_count['totals'],
                "todays_count" => $today_leads_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to get lists of validated leads.
     * @param mixed $partenaire
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_leads_has_clients($partenaire, $offset, $limit, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            $validated_query = ValidatedRequests::validated_query($partenaire, $offset, $limit);
            $validated_rows  = ConnexionProvider::fetch_all($conn, $validated_query);


            /** 
             * next get validated leads count.
             */
            $count_query     = ValidatedRequests::validated_count_query($partenaire);
            $validated_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays validated leads count.
             */
            $today_count_query     = ValidatedRequests::todays_validated_count_query($partenaire);
            $today_validated_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($validated_rows),
                "count"        => $validated_count['totals'],
                "todays_count" => $today_validated_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to download leads validated or discarded.
     * @param string $partenaire
     * @param string $export_reason
     * @param mixed $export_criteria
     * @param mixed $conn
     * @return array|null
     */
    public static function downloadable_leads($partenaire, $export_reason, $export_criteria, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            if ($export_reason === 'validated') {
                $downloadable_query = ValidatedRequests::validated_downloadable_query($partenaire, $export_criteria);
            } else {
                $downloadable_query = DiscardedRequests::discarded_downloadable_query($partenaire, $export_criteria);
            }
            $downloadable_rows = ConnexionProvider::fetch_all($conn, $downloadable_query);

            // close the connexion
            mysqli_close($conn);

            if (count($downloadable_rows) > 0) {
                // assemble the final results
                $final_result = array(
                    "headers" => array_keys($downloadable_rows[0]),
                    "rows"    => LeadsController::decodeResponse($downloadable_rows)
                );


                return $final_result;
            }
            return null;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to get lists of discarded leads.
     * @param mixed $partenaire
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_discarded_leads($partenaire, $offset, $limit, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            $discarded_query = DiscardedRequests::discarded_query($partenaire, $offset, $limit);
            $discarded_rows  = ConnexionProvider::fetch_all($conn, $discarded_query);


            /** 
             * next get validated leads count.
             */
            $count_query     = DiscardedRequests::discarded_count_query($partenaire);
            $discarded_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays validated leads count.
             */
            $today_count_query     = DiscardedRequests::todays_discarded_count_query($partenaire);
            $today_discarded_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($discarded_rows),
                "count"        => $discarded_count['totals'],
                "todays_count" => $today_discarded_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method for saving leads from LP to the DB.
     * @param mixed $lead
     * @param mixed $login_data
     * @param mixed $conn
     * @param mixed $date
     * @return mixed
     */
    public static function save_leads($lead, $login_data, $conn, $date)
    {
        try {
            $query = SavesRequests::save_leads_query($date, $lead, $login_data);
            // file_put_contents('save_leads_query.txt', $query);
            $conn->query($query);
            $last_id = $conn->insert_id;
            $conn->commit();
            // $conn->close();
            return $last_id;
        } catch (\Throwable $th) {
            file_put_contents('save_leads_error.txt', $th);
            //throw $th;
        }
    }

    /**
     * Method for saving clients response to the DB
     * after sending leads to it.
     * @param mixed $conn
     * @param mixed $date
     * @param mixed $partenaire
     * @param mixed $id_du_lead
     * @param mixed $send_result
     * @param mixed $client
     * @return void
     */
    public static function save_leads_has_clients($conn, $date, $partenaire, $id_du_lead, $send_result, $client)
    {
        try {
            $query = SavesRequests::save_leads_has_client_query($date, $send_result, $id_du_lead, $partenaire, $client);
            // file_put_contents('save_leads_has_clients_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('save_leads_has_clients_error.txt', $th);
        }
    }

    /**
     * Method for updating leads data from LEADIT.
     * @param mixed $conn
     * @param mixed $lead
     * @return bool
     */
    public static function update_lead($conn, $lead)
    {
        try {
            switch ($lead['user_action']) {
                case 'discarding':
                    $call_status = "CALLED";
                    $discard_date = TimeProvider::getTime();
                    break;
                case 'sending':
                    $call_status = "CALLED";
                    $discard_date = null;
                    break;

                default:
                    $call_status = "NOT CALLED";
                    $discard_date = null;
                    break;
            }

            // define the column names and values you want to update
            $column_names  = array(
                "discard_date",
                "civility",
                "firstname",
                "lastname",
                "email",
                "phone",
                "zipcode",
                "city",
                "address",
                "birthdate",
                "user_sender",
                "description_call",
                "call_status"
            );
            $column_values = array(
                $discard_date,
                $lead["civility"],
                base64_encode($lead["firstname"]),
                base64_encode($lead["lastname"]),
                base64_encode($lead["email"]),
                base64_encode($lead["phone"]),
                base64_encode($lead["zipcode"]),
                base64_encode($lead["city"]),
                base64_encode($lead["address"]),
                $lead["birthdate"],
                $lead["user_sender"],
                base64_encode($lead['description_call'] ?? ''),
                $call_status
            );

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE leads SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, array($lead["lead_id"]));
            $bind_params_ref = array();
            foreach ($bind_params as $key => $value) {
                $bind_params_ref[$key] = &$bind_params[$key];
            }
            array_unshift($bind_params_ref, $param_types);
            array_unshift($bind_params_ref, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $bind_params_ref);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_leads_error.txt', $th);
            return false;
        }
    }

    /**
     * Method for updating leads status 
     * when discarding or commenting from LEADIT.
     * @param mixed $posted_data
     * @param mixed $conn
     * @return bool
     */
    public static function comment_or_discard_lead($posted_data, $conn)
    {
        try {
            if ($posted_data['is_action_discard']) {
                $call_status = "CALLED";
            } else {
                $call_status = "NOT CALLED";
            }
            $desc_call = base64_encode($posted_data['description_call']);
            // $query = "update leads set call_status = 'not callable' where id = $id";
            $stmt = mysqli_prepare($conn, "UPDATE leads SET discard_date=?, call_status=?, description_call=?, user_sender=? WHERE id=?");

            mysqli_stmt_bind_param($stmt, 'ssssi', $posted_data['discard_date'], $call_status, $desc_call, $posted_data['user_sender'], $posted_data['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('save_leads_has_clients_error.txt', $th);
            return false;
        }
    }
}