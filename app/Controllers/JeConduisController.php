<?php

namespace App\Controllers;

class JeConduisController
{
    /**
     * Method that provide jeconduis data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeJeConduisData($input_data) 
    {
        
        /**
        * create lead jeconduis DATA payload
        */
        $je_conduis_data = array(
            "country" => $input_data['country'] ?? '',
            "origine" => $input_data['origine'] ?? '',
            "datecollecte" => $input_data['datecollecte'] ?? NULL,
            "urlcollecte" => $input_data['urlcollecte'] ?? '',
            "delai" => $input_data['delai'] ?? '',
            "type_achat" => $input_data['type_achat'] ?? '',
            "financement" => $input_data['financement'] ?? '',
            "budget" => $input_data['budget'] ?? '',
            "nb_personnes" => $input_data['nb_personnes'] ?? '',
            "usage" => $input_data['usage'] ?? '',
            "kilometrage" => $input_data['kilometrage'] ?? '',
            "motorisation" => $input_data['motorisation'] ?? '',
            "borne_recharge" => $input_data['borne_recharge'] ?? '',
            "boite" => $input_data['boite'] ?? '',
            "priorite" => $input_data['priorite'] ?? '',
            "carrosserie" => $input_data['carrosserie'] ?? '',
            "marques_modeles" => $input_data['marques_modeles'] ?? NULL,
            "duree_conservation" => $input_data['duree_conservation'] ?? '',
            "note_libre" => $input_data['note_libre'] ?? '',
            "recommendation_source" => $input_data['recommendation_source'] ?? '',
        );

        return $je_conduis_data;
    }

    /**
     * Method for saving jeconduis data inner jeconduis table on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $jeconduis_data
     * @return void
     */
    public static function save($conn, $lead, $je_conduis_data)
    {
        try {
            $sql = 'INSERT INTO jeconduis (country, origine, datecollecte, urlcollecte, delai, type_achat, financement, budget, nb_personnes, `usage`, kilometrage, motorisation, borne_recharge, boite, priorite, carrosserie, marques_modeles, duree_conservation, note_libre, recommendation_source, leads_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = mysqli_prepare($conn, $sql);

            // Colonnes texte encodées en Base64
            $champsTexte = [
                'country', 'origine', 'urlcollecte', 'delai', 'type_achat',
                'financement', 'budget', 'nb_personnes', 'usage', 'kilometrage',
                'motorisation', 'borne_recharge', 'boite', 'priorite', 'carrosserie',
                'duree_conservation', 'note_libre', 'recommendation_source',
            ];
            $enc = [];
            foreach ($champsTexte as $champ) {
                $enc[$champ] = base64_encode($je_conduis_data[$champ] ?? '');
            }

            // DATETIME et JSON : valeur brute, NULL si vide
            $datecollecte    = ($je_conduis_data['datecollecte'] ?? '') !== '' ? $je_conduis_data['datecollecte'] : NULL;
            $marques_modeles = ($je_conduis_data['marques_modeles'] ?? '') !== '' ? json_encode($je_conduis_data['marques_modeles']) : NULL;
            $lead_id         = (int) $lead['lead_id'];

            mysqli_stmt_bind_param($stmt, 'ssssssssssssssssssssi',
                $enc['country'],
                $enc['origine'],
                $datecollecte,
                $enc['urlcollecte'],
                $enc['delai'],
                $enc['type_achat'],
                $enc['financement'],
                $enc['budget'],
                $enc['nb_personnes'],
                $enc['usage'],
                $enc['kilometrage'],
                $enc['motorisation'],
                $enc['borne_recharge'],
                $enc['boite'],
                $enc['priorite'],
                $enc['carrosserie'],
                $marques_modeles,
                $enc['duree_conservation'],
                $enc['note_libre'],
                $enc['recommendation_source'],
                $lead_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (\Throwable $th) {
            var_dump($th);
            file_put_contents('save_jeconduis_error.txt', $th);
            return false;
        }
    }

    /**
     * Method to update the jeconduis data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $jeconduis_data
     * @return bool
     */
    public static function update($conn, $lead, $jeconduis_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE jeconduis SET country=?, origine=?, datecollecte=?, urlcollecte=?, delai=?, type_achat=?, financement=?, budget=?, nb_personnes=?, `usage`=?, kilometrage=?, motorisation=?, borne_recharge=?, boite=?, priorite=?, carrosserie=?, marques_modeles=?, duree_conservation=?, note_libre=?, recommendation_source=? WHERE leads_id=?");

            // Colonnes texte à encoder en Base64
            $champsTexte = [
                'country', 'origine', 'urlcollecte', 'delai', 'type_achat',
                'financement', 'budget', 'nb_personnes', 'usage', 'kilometrage',
                'motorisation', 'borne_recharge', 'boite', 'priorite', 'carrosserie',
                'duree_conservation', 'note_libre', 'recommendation_source',
            ];

            $enc = [];
            foreach ($champsTexte as $champ) {
                $enc[$champ] = base64_encode($jeconduis_data[$champ] ?? '');
            }

            // Colonnes DATETIME et JSON : pas de Base64, NULL si vide
            $datecollecte    = ($jeconduis_data['datecollecte'] ?? '') !== '' ? $jeconduis_data['datecollecte'] : null;
            $marques_modeles = ($jeconduis_data['marques_modeles'] ?? '') !== '' ? json_encode($jeconduis_data['marques_modeles']) : null;
            $lead_id         = $lead['lead_id'];

            mysqli_stmt_bind_param($stmt, 'ssssssssssssssssssssi',
                $enc['country'],
                $enc['origine'],
                $datecollecte,
                $enc['urlcollecte'],
                $enc['delai'],
                $enc['type_achat'],
                $enc['financement'],
                $enc['budget'],
                $enc['nb_personnes'],
                $enc['usage'],
                $enc['kilometrage'],
                $enc['motorisation'],
                $enc['borne_recharge'],
                $enc['boite'],
                $enc['priorite'],
                $enc['carrosserie'],
                $marques_modeles,
                $enc['duree_conservation'],
                $enc['note_libre'],
                $enc['recommendation_source'],
                $lead_id
            );

            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        } catch (\Throwable $th) {
            file_put_contents('update_jeconduis_error.txt', $th);
            return false;
        }
    }

}