<?php

namespace App\Controllers;

class EnergiesController
{
    /**
     * Method that provide energies data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function MakeEnergiesData($input_data)
    {
        
        /**
        * create lead energy DATA payload
        */
        $energies_data = array(
            "type_energie"                      => $input_data['type_energie'] ?? '',
            "montant_approximatif_facture"      => $input_data['montant_approximatif_facture'] ?? '',
            "type_fournisseur_actuel"           => $input_data['type_fournisseur_actuel'] ?? '',
            "statut_logement"                   => $input_data['statut_logement'] ?? '',
            "type_logement"                     => $input_data['type_logement'] ?? '',
            "surface_approximative_logement"    => $input_data['surface_approximative_logement'] ?? '',
            "nombre_personne_foyer"             => $input_data['nombre_personne_foyer'] ?? '',
            "list_projet"                       => json_encode($input_data['list_projet']) ?? '',
        );

        return $energies_data;
    }

    /**
     * Method for saving energy data inner energies table on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $energy_data
     * @return void
     */
    public static function save($conn, $lead, $energy_data)
    {
        try {
            $sql = 'INSERT INTO energies (type_energie, montant_approximatif_facture, type_fournisseur_actuel, statut_logement, type_logement, surface_approximative_logement, nombre_personne_foyer, list_projet, leads_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $sql);

            self::bind($stmt, 'ssssssssi', $energy_data, $lead);

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (\Throwable $th) {
            var_dump($th);
            file_put_contents('save_energy_error.txt', $th);
            return false;
        }
    }

    /**
     * Method to update the energy data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $energy_data
     * @return bool
     */
    public static function update($conn, $lead, $energy_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "energies SET type_energie=?, montant_approximatif_facture=?, type_fournisseur_actuel=?, statut_logement=?, type_logement=?, surface_approximative_logement=?, nombre_personne_foyer=?, list_projet=? WHERE leads_id=?");

            self::bind($stmt, 'ssssssssi', $energy_data, $lead);

            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        } catch (\Throwable $th) {
            var_dump($th);
            file_put_contents('update_energy_error.txt', $th);
            return false;
        }
    }

    /**
     * Method to update the energy data on the DB.
     * @param mixed $stmt
     * @param mixed $field_count
     * @param mixed $energy_data
     * @param mixed $lead
     * @return void
     */
    private static function bind($stmt, $field_count, $energy_data, $lead) {
        $tranche_age = $energy_data['type_energie'] ?? '';
        $montant_approximatif_facture = $energy_data['montant_approximatif_facture'] ?? '';
        $type_fournisseur_actuel = $energy_data['type_fournisseur_actuel'] ?? '';
        $statut_logement = $energy_data['statut_logement'] ?? '';
        $type_logement = $energy_data['type_logement'] ?? '';
        $surface_approximative_logement = $energy_data['surface_approximative_logement'] ?? '';
        $nombre_personne_foyer = $energy_data['nombre_personne_foyer'] ?? '';
        $list_projet = json_encode($energy_data['list_projet'] ?? '');
        $leads_id = (int) $lead['lead_id'];

        mysqli_stmt_bind_param($stmt, $field_count,
            $tranche_age,
            $montant_approximatif_facture,
            $type_fournisseur_actuel,
            $statut_logement,
            $type_logement,
            $surface_approximative_logement,
            $nombre_personne_foyer,
            $list_projet,
            $leads_id
        );
    }
}