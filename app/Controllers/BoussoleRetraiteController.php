<?php

namespace App\Controllers;

class BoussoleRetraiteController
{
    /**
     * Method that provide jeconduis data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeBoussoleRetraiteData($input_data) 
    {
        
        /**
        * create lead boussole retraite DATA payload
        */
        $boussole_retraite_data = array(
            "tranche_age"       => $input_data['tranche_age'] ?? '',
            "preoccupation"     => $input_data['preoccupation'] ?? '',
            "preparation"       =>  $input_data['preparation'] ?? '',
            "connaissances"     => $input_data['connaissances'] ?? '',
            "risque"            => $input_data['risque'] ?? '',
            "statut_logement"   => $input_data['statut_logement'] ?? '',
            "revenu"            => $input_data['revenu'] ?? '',
            "capacite_epargne"  => $input_data['capacite_epargne'] ?? '',
            "profile_id"        => $input_data['profile_id'] ?? '',
            "profile_name"      => $input_data['profile_name'] ?? ' ',
            "lead_temperature"  => $input_data['lead_temperature'] ?? '',
            "tags"              => $input_data['tags'] ?? '',
        );

        return $boussole_retraite_data;
    }

    /**
     * Method for saving boussole retraite data inner boussole retraite table on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $boussole_retraite_data
     * @return void
     */
    public static function save($conn, $lead, $boussole_retraite_data)
    {
        try {
            $sql = 'INSERT INTO boussole_retraite (tranche_age, preoccupation, preparation, connaissances, risque, statut_logement, revenu, capacite_epargne, profile_id, profile_name, lead_temperature, tags, leads_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $sql);

            self::bind($stmt, 'ssssssssssssi', $boussole_retraite_data, $lead);

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (\Throwable $th) {
            var_dump($th);
            file_put_contents('save_boussole_retraite_error.txt', $th);
            return false;
        }
    }

    /**
     * Method to update the boussole retraite data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $boussole_retraite_data
     * @return bool
     */
    public static function update($conn, $lead, $boussole_retraite_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE boussole_retraite SET tranche_age=?, preoccupation=?, preparation=?, connaissances=?, risque=?, statut_logement=?, revenu=?, capacite_epargne=?, profile_id=?, profile_name=?, lead_temperature=?, tags=? WHERE leads_id=?");

            self::bind($stmt, 'ssssssssssssi', $boussole_retraite_data, $lead);

            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        } catch (\Throwable $th) {
            var_dump($th);
            file_put_contents('update_boussole_retraite_error.txt', $th);
            return false;
        }
    }

    /**
     * Method to update the boussole retraite data on the DB.
     * @param mixed $stmt
     * @param mixed $field_count
     * @param mixed $boussole_retraite_data
     * @param mixed $lead
     * @return void
     */
    private static function bind($stmt, $field_count, $boussole_retraite_data, $lead) {
        $tranche_age = base64_encode($boussole_retraite_data['tranche_age'] ?? '');
        $preoccupation = base64_encode($boussole_retraite_data['preoccupation'] ?? '');
        $preparation = base64_encode($boussole_retraite_data['preparation'] ?? '');
        $connaissances = base64_encode($boussole_retraite_data['connaissances'] ?? '');
        $risque = base64_encode($boussole_retraite_data['risque'] ?? '');
        $statut_logement = base64_encode($boussole_retraite_data['statut_logement'] ?? '');
        $revenu = base64_encode($boussole_retraite_data['revenu'] ?? '');
        $capacite_epargne = base64_encode($boussole_retraite_data['capacite_epargne'] ?? '');
        $profile_id = base64_encode($boussole_retraite_data['profile_id'] ?? '');
        $profile_name = base64_encode($boussole_retraite_data['profile_name'] ?? '');
        $lead_temperature = base64_encode($boussole_retraite_data['lead_temperature'] ?? '');
        $tags = base64_encode($boussole_retraite_data['tags'] ?? '');
        $leads_id = (int) $lead['lead_id'];

        mysqli_stmt_bind_param($stmt, $field_count,
            $tranche_age,
            $preoccupation,
            $preparation,
            $connaissances,
            $risque,
            $statut_logement,
            $revenu,
            $capacite_epargne,
            $profile_id,
            $profile_name,
            $lead_temperature,
            $tags,
            $leads_id
        );
    }
}