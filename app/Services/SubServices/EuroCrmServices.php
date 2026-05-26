<?php
namespace App\Services\SubServices;

class EuroCrmServices
{

    /**
     * Method to provide lead creative "assurance auto" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */
    public static function make_assurance_auto_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $data = [
            "CIVILITE"                 => $gender_category,
            "PRENOM"                   => $classics['firstname'],
            "NOM"                      => $classics['lastname'],
            "CODE_POSTAL"              => $classics['zipcode'],
            "VILLE"                    => $classics['city'],
            "EMAIL_1"                  => $classics['email'],
            "ADRESSE"                  => $classics['address'],
            "TELEPHONE_MOBILE_1"       => $classics['phone'],
            "DATE_DE_NAISSANCE"        => $birthdate->format('Y-m-d'),
            "MARQUE_DE_VOITURE"        => $specifics['brand'],
            "IMMATRICULATION"          => $specifics['registration'],
            "BESOIN_ASSURANCE_VOITURE" => $specifics['date_insured'],
        ];

        return $data;
    }

    /**
     * Method to provide lead creative "assurance auto" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_assurance_auto_responses($json_response, $output)
    {
        if (isset($json_response[0]->ACCEPTED) && ! empty($json_response[0]->ACCEPTED)) {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $json_response[0]->ACCEPTED,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to EURO CRM",
            ];
        }
        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => $json_response[0]->REJECTED,
        ];
    }

    /**
     * Method to provide lead creative "assurance emprunteur" lead data.
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @param mixed $birthdate_object
     * @return array
     */

    public static function make_assurance_emprunte_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $data = [
            "civilite"           => self::mapCiviliteToInt($gender_category),
            "prenom"             => $classics['firstname'],
            "nom"                => $classics['lastname'],
            "dateNaissance"      => $birthdate->format('Y-m-d'),
            "telephonePortable"  => $classics['phone'],
            "mail"               => $classics['email'],
            "profession"         => $classics['situationPro'] ?? '',
            "situationFamiliale" => $specifics['situation_famille'] ?? '',
            "codePostal"         => $classics['zipcode'],
            "ville"              => $classics['city'],
            "pays"               => "FRANCE",
            "capitalRestantDu"   => $specifics['montant_pret'] ?? '',
            "codeExterne"        => strval($classics['lead_id']),

        ];

        return $data;
    }

    /**
     * Method to provide lead creative "assurance emprunteur" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_assurance_emprunte_responses($json_response, $output)
    {
        // Gérer le cas où $json_response est null ou vide
        if (empty($json_response)) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API vide ou invalide",
            ];
        }

        // Normaliser la réponse : convertir en tableau si c'est un objet
        $response_data = null;

        if (is_array($json_response) && isset($json_response[0])) {
            // Cas tableau avec premier élément
            $response_data = $json_response[0];
        } elseif (is_object($json_response)) {
            // Cas objet direct
            $response_data = $json_response;
        }

        // Vérifier si on a des données à traiter
        if ($response_data === null) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Format de réponse API non reconnu",
            ];
        }

        // Vérifier le succès (NO_DOSSIER) selon la documentation Euro CRM
        if (isset($response_data->NO_DOSSIER) && ! empty($response_data->NO_DOSSIER)) {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $response_data->NO_DOSSIER,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to EURO CRM",
            ];
        }

        // Cas d'erreur selon la documentation (ERREUR, DOUBLON)
        $error_message = "Erreur inconnue";
        if (isset($response_data->ERREUR)) {
            $error_message = $response_data->ERREUR;
        } elseif (isset($response_data->DOUBLON)) {
            $error_message = "Doublon détecté: " . $response_data->DOUBLON;
        } elseif (isset($response_data->ERROR)) {
            $error_message = $response_data->ERROR;
        } elseif (isset($response_data->message)) {
            $error_message = $response_data->message;
        }

        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => $error_message,
        ];
    }

    public static function make_assurance_mutuel_senior_datas($classics, $specifics, $gender_category, $birthdate)
    {
        $data = [
            // "civilite"                       => $gender_category,
            "civilite"                    => self::mapCiviliteToInt($gender_category),
            "prenom"                      => $classics['firstname'],
            "nom"                         => $classics['lastname'],
            "codePostal"                  => $classics['zipcode'],
            "ville"                       => $classics['city'],
            "mail"                        => $classics['email'],
            "telephonePortable"           => $classics['phone'],
            "dateNaissance"               => $birthdate->format('Y-m-d'),
            "profession"                  => ! empty($specifics['profession']) ?
            self::mapProfession($specifics['profession']) : '',

            "situationFamiliale"          => ! empty($specifics['custom_field_5']) ?
            self::mapSituationFamiliale($specifics['custom_field_5']) : '',

            "ASSURE"                      => $specifics['custom_field_1'] ?? '',
            "regime"                      => ! empty($specifics['custom_field_7']) ?
            self::mapRegimeSocial($specifics['custom_field_7']) : '',
            "BESOIN SANTE COMPLEMENTAIRE" => $specifics['custom_field_3'] ?? '',
            "DATE_CONTRAT_SOUHAITE"       => $specifics['custom_field_4'] ?? '',
            "OPTIN"                       => $specifics['custom_field_6'] ?? '',
        ];

        // Validation de l'âge (55+ uniquement)
        $age = $birthdate->diff(new \DateTime())->y;
        if ($age < 55) {
            throw new \Exception("Age minimum requis: 55 ans (âge actuel: {$age} ans)");
        }

        // Validation code postal France métropolitaine
        if (! self::isMetropolitanFrance($classics['zipcode'])) {
            throw new \Exception("Seule la France métropolitaine est acceptée");
        }

        // Adresse complète si disponible
        if (! empty($classics['address'])) {
            // Essayer de parser l'adresse (numéro + rue)
            $addressParts = self::parseAddress($classics['address']);
            if ($addressParts['numero']) {
                $data["numeroVoie"] = $addressParts['numero'];
            }
            if ($addressParts['rue']) {
                $data["rue"] = $addressParts['rue'];
            }
        }

        $data["pays"] = "FRANCE";

        return $data;

    }

    /**
     * Method to provide lead creative "assurance auto" send response data.
     * @param mixed $json_response
     * @param mixed $output
     * @return array
     */
    public static function make_assurance_mutuel_senior_responses($json_response, $output)
    {

        // Gérer le cas où $json_response est null ou vide
        if (empty($json_response)) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API vide ou invalide",
            ];
        }

        // Normaliser la réponse : convertir en tableau si c'est un objet
        $response_data = null;

        if (is_array($json_response) && isset($json_response[0])) {
            // Cas tableau avec premier élément
            $response_data = $json_response[0];
        } elseif (is_object($json_response)) {
            // Cas objet direct
            $response_data = $json_response;
        }

        // Vérifier si on a des données à traiter
        if ($response_data === null) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Format de réponse API non reconnu",
            ];
        }

        // Vérifier le succès (NO_DOSSIER) selon la documentation Euro CRM
        if (isset($response_data->NO_DOSSIER) && ! empty($response_data->NO_DOSSIER)) {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $response_data->NO_DOSSIER,
                "ws_statut"    => "ok",
                "description"  => "lead has been send successfully to EURO CRM",
            ];
        }

        // Cas d'erreur selon la documentation (ERREUR, DOUBLON)
        $error_message = "Erreur inconnue";
        if (isset($response_data->ERREUR)) {
            $error_message = $response_data->ERREUR;
        } elseif (isset($response_data->DOUBLON)) {
            $error_message = "Doublon détecté: " . $response_data->DOUBLON;
        } elseif (isset($response_data->ERROR)) {
            $error_message = $response_data->ERROR;
        } elseif (isset($response_data->message)) {
            $error_message = $response_data->message;
        }

        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => $error_message,
        ];
    }
    // /**
    //  * Method to provide lead creative "assurance auto" send response data.
    //  * @param mixed $json_response
    //  * @param mixed $output
    //  * @return array
    //  */
    //  public static function make_energy_datas($classics, $specifics, $gender_category, $birthdate)
    // {

    //     $data = array(
    //         // "civilite"                       => $gender_category,
    //         "civilite"                       => self::mapCiviliteToInt($gender_category),
    //         "prenom"                         => $classics['firstname'],
    //         "nom"                            => $classics['lastname'],
    //         "codePostal"                     => $classics['zipcode'],
    //         "ville"                          => $classics['city'],
    //         "mail"                           => $classics['email'],
    //         "telephonePortable"              => $classics['phone'],
    //         "dateNaissance"                   => $birthdate->format('Y-m-d'),
    //         "profession"                    => !empty($specifics['profession']) ?
    //                            self::mapProfession($specifics['profession']) : '',

    //         "situationFamiliale"             => !empty($specifics['custom_field_5']) ?
    //                            self::mapSituationFamiliale($specifics['custom_field_5']) : '',

    //         "ASSURE"                         => $specifics['custom_field_1'] ?? '',
    //          "regime"                        => !empty($specifics['custom_field_7']) ?
    //                           self::mapRegimeSocial($specifics['custom_field_7']) : '',
    //         "BESOIN SANTE COMPLEMENTAIRE"    => $specifics['custom_field_3'] ?? '',
    //         "DATE_CONTRAT_SOUHAITE"          => $specifics['custom_field_4'] ?? '',
    //         "OPTIN"                          => $specifics['custom_field_6'] ?? '',
    //     );

    //     // Validation de l'âge (55+ uniquement)
    //     $age = $birthdate->diff(new \DateTime())->y;
    //     if ($age < 55) {
    //         throw new \Exception("Age minimum requis: 55 ans (âge actuel: {$age} ans)");
    //     }

    //     // Validation code postal France métropolitaine
    //     if (!self::isMetropolitanFrance($classics['zipcode'])) {
    //         throw new \Exception("Seule la France métropolitaine est acceptée");
    //     }

    //     // Adresse complète si disponible
    //     if (!empty($classics['address'])) {
    //         // Essayer de parser l'adresse (numéro + rue)
    //         $addressParts = self::parseAddress($classics['address']);
    //         if ($addressParts['numero']) {
    //             $data["numeroVoie"] = $addressParts['numero'];
    //         }
    //         if ($addressParts['rue']) {
    //             $data["rue"] = $addressParts['rue'];
    //         }
    //     }

    //     $data["pays"] = "FRANCE";

    //     return $data;

    // }

    // /**
    //  * Method to provide lead creative "assurance auto" send response data.
    //  * @param mixed $json_response
    //  * @param mixed $output
    //  * @return array
    //  */
    // public static function make_energy_responses($json_response, $output)
    // {

    //     // Gérer le cas où $json_response est null ou vide
    //     if (empty($json_response)) {
    //         return array(
    //             "status"       => "error",
    //             "api_response" => $output,
    //             "id_part"      => "",
    //             "ws_statut"    => "error",
    //             "description"  => "Réponse API vide ou invalide"
    //         );
    //     }

    //     // Normaliser la réponse : convertir en tableau si c'est un objet
    //     $response_data = null;

    //     if (is_array($json_response) && isset($json_response[0])) {
    //         // Cas tableau avec premier élément
    //         $response_data = $json_response[0];
    //     } elseif (is_object($json_response)) {
    //         // Cas objet direct
    //         $response_data = $json_response;
    //     }

    //     // Vérifier si on a des données à traiter
    //     if ($response_data === null) {
    //         return array(
    //             "status"       => "error",
    //             "api_response" => $output,
    //             "id_part"      => "",
    //             "ws_statut"    => "error",
    //             "description"  => "Format de réponse API non reconnu"
    //         );
    //     }

    //     // Vérifier le succès (NO_DOSSIER) selon la documentation Euro CRM
    //     if (isset($response_data->NO_DOSSIER) && !empty($response_data->NO_DOSSIER)) {
    //         return array(
    //             "status"       => "success",
    //             "api_response" => $output,
    //             "id_part"      => $response_data->NO_DOSSIER,
    //             "ws_statut"    => "ok",
    //             "description"  => "lead has been send successfully to EURO CRM",
    //         );
    //     }

    //     // Cas d'erreur selon la documentation (ERREUR, DOUBLON)
    //     $error_message = "Erreur inconnue";
    //     if (isset($response_data->ERREUR)) {
    //         $error_message = $response_data->ERREUR;
    //     } elseif (isset($response_data->DOUBLON)) {
    //         $error_message = "Doublon détecté: " . $response_data->DOUBLON;
    //     } elseif (isset($response_data->ERROR)) {
    //         $error_message = $response_data->ERROR;
    //     } elseif (isset($response_data->message)) {
    //         $error_message = $response_data->message;
    //     }

    //     return array(
    //         "status"       => "error",
    //         "api_response" => $output,
    //         "id_part"      => "",
    //         "ws_statut"    => "error",
    //         "description"  => $error_message
    //     );
    // }
    /**
     * Prépare les données pour l'API Euro CRM Energy (1SA)
     * @param array $classics
     * @param array $specifics
     * @param string $gender_category
     * @param DateTime $birthdate
     * @return array
     */
    public static function make_energy_datas($classics, $specifics, $gender_category, $birthdate)
    {
        // ✅ VALIDATION DE L'ÂGE (18 minimum selon la doc)
        $age = $birthdate->diff(new \DateTime())->y;
        if ($age < 18 || $age > 99) {
            throw new \Exception("Age doit être entre 18 et 99 ans (âge actuel: {$age} ans)");
        }

        // ✅ NETTOYER LE TÉLÉPHONE
        $phone = preg_replace('/[^0-9]/', '', $classics['phone']);
        $phone = preg_replace('/^(?:33|0033)/', '0', $phone);

        // Vérifier si mobile ou fixe
        $isMobile = preg_match('/^0[6-7]\d{8}$/', $phone);
        $isFix    = preg_match('/^0[1-5]\d{8}$/', $phone);

        if (! $isMobile && ! $isFix) {
            throw new \Exception("Numéro de téléphone invalide: {$phone}");
        }

        // ✅ CONSTRUCTION DES DONNÉES SELON LA DOCUMENTATION 1SA
        $data = [
                                                         // === CHAMPS OBLIGATOIRES ===
            "MARQUE_COLLECTE"          => "Budgetdevis", // À adapter selon votre marque
            "MARQUE_DESTINATAIRE"      => "PLUSIEURS_MARQUES_DESTINATAIRES",
            "AFFICHAGE_MARQUE_CLIENTE" => "MARQUE_CLIENTE_AFFICHEE",
            "NIVEAU_QUALITE_LEAD"      => "MEDIUM",   // HIGH, MEDIUM ou LOW
            "CATEGORIE_LEAD"           => "emaling",  // B2 = Formulaire court
            "CANAL_COLLECTE_1"         => "emailing", // À adapter selon votre source
            "LEVIER_COLLECTE_1"        => "emailing",
            "URL_COLLECTE"             => $classics['referer'],

                                                                        // === OPT-INS (OBLIGATOIRE pour OPT_IN_CDF_TEL) ===
            "OPT_IN_CDF_TEL"           => $specifics["custom_field_3"], // Horodatage de l'opt-in
                                                                        // === DONNÉES PROSPECT (OBLIGATOIRES) ===
            "CIVILITE"                 => $gender_category,
            "NOM"                      => $classics['lastname'],
            "PRENOM"                   => $classics['firstname'],
            "EMAIL_1"                  => $classics['email'],
            "CODE_POSTAL"              => $classics['zipcode'],
            "VILLE"                    => $classics['city'],

            // === BESOIN ÉNERGIE (OBLIGATOIRE) ===
            "MES_OU_CDF"               => self::mapMesOuCdf($specifics),
            "BESOINS"                  => self::mapBesoins($specifics),
            "ID_SOUS_CAMPAGNE"         => $classics["affiliateID"],
        ];

        // ✅ TÉLÉPHONE MOBILE OU FIXE (au moins un obligatoire)
        if ($isMobile) {
            $data["TELEPHONE_MOBILE_1"] = $phone;
        } else {
            $data["TELEPHONE_FIXE"] = $phone;
        }

        // ✅ CHAMPS OPTIONNELS
        if (! empty($gender_category)) {
            $data["CIVILITE"] = self::mapCivilite($gender_category);
        }

        if (! empty($birthdate)) {
            $data["DATE_DE_NAISSANCE"] = $birthdate->format('Y-m-d');
        }

        // Adresse complète
        if (! empty($classics['address'])) {
            $addressParts = self::parseAddress($classics['address']);
            if (! empty($addressParts['numero'])) {
                $data["NUMERO_VOIE"] = $addressParts['numero'];
            }
            if (! empty($addressParts['rue'])) {
                $data["LIBELLE_VOIE"] = $addressParts['rue'];
            }
        }

        // Fournisseur actuel (si disponible)
        if (! empty($specifics['custom_field_4'])) {
            $data["FOURNISSEUR_ACTUEL_ELEC"] = self::mapFournisseur($specifics['custom_field_4']);
        }

        // Produits travaux souhaités
        if (! empty($specifics['produits_travaux_1'])) {
            $data["PRODUITS_TRAVAUX_SOUHAITES_1"] = self::mapProduitsTravaux($specifics['produits_travaux_1']);
        }

        // Type de logement
        if (! empty($specifics['type_logement'])) {
            $data["TYPE_LOGEMENT"] = self::mapTypeLogement($specifics['type_logement']);
        }

        // Statut habitation
        if (! empty($specifics['situation'])) {
            $data["STATUT_HABITATION"] = self::mapStatutHabitation($specifics['situation']);
        }

        // Surface logement
        if (! empty($specifics['surface_logement'])) {
            $data["SURFACE_LOGEMENT"] = $specifics['surface_logement'];
        }

        // Compteur Linky
        if (isset($specifics['linky'])) {
            $data["LINKY"] = $specifics['linky'] ? "LINKY" : "PAS_DE_LINKY";
        }

        // ID de traçabilité (recommandé)
        if (! empty($classics['lead_id'])) {
            $data["ID_LEAD"] = strval($classics['lead_id']);
        }

        // CPL (si applicable)
        if (! empty($specifics['cpl'])) {
            $data["CPL_CONTRACTUEL"] = number_format((float) $specifics['cpl'], 2, '.', '');
        }

        return $data;
    }

    /**
     * Traite la réponse de l'API Euro CRM Energy (1SA)
     * @param mixed $json_response
     * @param string $output
     * @return array
     */
    public static function make_energy_responses($json_response, $output)
    {
        // Gérer réponse vide
        if (empty($json_response)) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Réponse API vide ou invalide",
            ];
        }

        // Normaliser la réponse
        $response_data = null;

        if (is_array($json_response) && isset($json_response[0])) {
            $response_data = $json_response[0];
        } elseif (is_object($json_response)) {
            $response_data = $json_response;
        }

        if ($response_data === null) {
            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "error",
                "description"  => "Format de réponse API non reconnu",
            ];
        }

        // ✅ CAS DE SUCCÈS - Selon la documentation 1SA
        if (isset($response_data->ACCEPTED) && ! empty($response_data->ACCEPTED)) {
            return [
                "status"       => "success",
                "api_response" => $output,
                "id_part"      => $response_data->ACCEPTED, // NUMERO_DOSSIER
                "ws_statut"    => "ok",
                "description"  => "Lead energy envoyé avec succès à Euro CRM (1SA)",
            ];
        }

        // ✅ CAS DE REJET - Selon la documentation 1SA (page 13)
        if (isset($response_data->REJECTED) && ! empty($response_data->REJECTED)) {
            $error_message = self::interpretRejectionCode($response_data->REJECTED);

            return [
                "status"       => "error",
                "api_response" => $output,
                "id_part"      => "",
                "ws_statut"    => "rejected",
                "description"  => "Lead rejeté: {$error_message}",
            ];
        }

        // Cas d'erreur générique
        return [
            "status"       => "error",
            "api_response" => $output,
            "id_part"      => "",
            "ws_statut"    => "error",
            "description"  => "Erreur inconnue lors de l'envoi du lead",
        ];
    }

    /**
     * Map civilité vers format 1SA (LOV CIVILITE)
     */
    private static function mapCivilite($gender_category)
    {
        $mapping = [
            'Monsieur'     => 'M',
            'Mr'           => 'M',
            'M.'           => 'M',
            'M'            => 'M',
            'Madame'       => 'MME',
            'Mme'          => 'MME',
            'Mademoiselle' => 'MME',
            'Mlle'         => 'MME',
        ];

        return $mapping[$gender_category] ?? 'M';
    }

    /**
     * Map MES ou CDF (Mise en Service ou Changement de Fournisseur)
     */
    private static function mapMesOuCdf($specifics)
    {
        // 'demenager' → MES (Mise en Service)
        // 'changer'   → CDF (Changement de Fournisseur)
        if (! empty($specifics['custom_field_2']) && $specifics['custom_field_2'] === 'demenager') {
            return 'MES';
        }
        return 'CDF';
    }

    private static function mapBesoins($specifics)
    {
        if (! empty($specifics['custom_field_1'])) {
            $mapping = [
                'electricite' => 'ELEC',
                'gaz'         => 'GAZ',
                'elec_gaz'    => 'DUALE',
            ];
            return $mapping[$specifics['custom_field_1']] ?? 'ELEC';
        }
        return 'ELEC';
    }

    /**
     * Map fournisseur énergie selon LOV 1SA
     */
    private static function mapFournisseur($fournisseur)
    {
        $normalized = strtoupper(self::removeAccents(trim($fournisseur)));

        $mapping = [
            'EDF'           => 'EDF',
            'ENGIE'         => 'ENGIE',
            'TOTALENERGIES' => 'TOTALENERGIES',
            'TOTAL'         => 'TOTALENERGIES',
            'ENI'           => 'PLENITUDE/ENI',
            'PLENITUDE'     => 'PLENITUDE/ENI',
            'OHM'           => 'OHM_ENERGIE',
            'OHM ENERGIE'   => 'OHM_ENERGIE',
            'ILEK'          => 'ILEK',
            'WEKIWI'        => 'WEKIWI',

            'engie'         => 'ENGIE',
            'edf'           => 'EDF',
            'totalenergie'  => 'TOTALENERGIES',
            'mint_energie'  => 'AUTRE', // MINT ENERGIE absent de la LOV 1SA
            'ohm_energie'   => 'OHM_ENERGIE',
            'autre'         => 'AUTRE',
        ];

        return $mapping[$normalized] ?? 'AUTRE';
    }

    /**
     * Map produits travaux selon LOV 1SA
     */
    private static function mapProduitsTravaux($produit)
    {
        $normalized = strtoupper(self::removeAccents(trim($produit)));

        $validValues = [
            'ISOLATION', 'ISOLATION_COMBLES_TOIT', 'ISOLATION_EXTERIEURE',
            'POMPE_A_CHALEUR', 'PAC_AIR_AIR', 'PAC_AIR_EAU',
            'PANNEAUX_SOLAIRES', 'CHAUFFE-EAU',
            'REMPLACEMENT_CHAUDIERE', 'REMPLACEMENT_CHAUDIERE_FIOUL',
            'PORTES_ET_FENETRES', 'VENTILATION_VMC', 'VMC_DOUBLE_FLUX', 'VMC_SIMPLE_FLUX',
            'THERMOSTAT_CONNECTE', 'DOMOTIQUE', 'ECLAIRAGE', 'CANALISATION', 'AUTRE',
        ];

        return in_array($normalized, $validValues) ? $normalized : 'AUTRE';
    }

    /**
     * Map type de logement selon LOV 1SA
     */
    private static function mapTypeLogement($type)
    {
        $normalized = strtoupper(self::removeAccents(trim($type)));

        $mapping = [
            'MAISON'              => 'MAISON',
            'MAISON_INDIVIDUELLE' => 'MAISON_INDIVIDUELLE_ANCIEN',
            'APPARTEMENT'         => 'APPARTEMENT',
            'APPARTEMENT_ANCIEN'  => 'APPARTEMENT_ANCIEN',
            'APPARTEMENT_NEUF'    => 'APPARTEMENT_NEUF',
            'MAISON_NEUF'         => 'MAISON_INDIVIDUELLE_NEUF',
        ];

        return $mapping[$normalized] ?? 'MAISON';
    }

    /**
     * Map statut habitation selon LOV 1SA
     */
    private static function mapStatutHabitation($statut)
    {
        $normalized = strtoupper(self::removeAccents(trim($statut)));

        $mapping = [
            'PROPRIETAIRE'          => 'PROPRIETAIRE',
            'LOCATAIRE'             => 'LOCATAIRE',
            'HEBERGE'               => 'HEBERGE_A_TITRE_GRATUIT',
            'PROPRIETAIRE_BAILLEUR' => 'PROPRIETAIRE_BAILLEUR',
        ];

        return $mapping[$normalized] ?? 'PROPRIETAIRE';
    }

    /**
     * Interprète les codes de rejet de l'API 1SA
     */
    private static function interpretRejectionCode($code)
    {
        $codes = [
            'REJET_DEDUPLICATION_CLIENT'   => 'Lead déjà existant dans la base de la marque',
            'REJET_DEDUPLICATION_PROSPECT' => 'Lead déjà envoyé dans les 3 derniers mois',
            'REJET_FAUX_NUMÉRO'            => 'Vérification HLR négative (numéro invalide)',
            'REJET_FAUX_EMAIL'             => 'Vérification d\'email échouée',
            'REJET_DONNEES_INCORRECTES'    => 'Format de données non conforme',
            'REJET_DONNEES_OBLIGATOIRES'   => 'Champ obligatoire manquant',
            'REJET_CRITERES_D_ELIGIBILITÉ' => 'Lead en dehors des critères de la marque',
            'REJET_HEURE_DE_COLLECTE'      => 'Lead hors plage horaire autorisée',
            'REJET_TIMESTAMP'              => 'Date de collecte trop ancienne (>30s)',
            'REJET_DEPASSEMENT_DE_VOLUME'  => 'Quota de leads atteint',
        ];

        return $codes[$code] ?? $code;
    }

    /**
     * Parse une adresse pour extraire numéro et rue
     */
    private static function parseAddress($address)
    {
        $result = ['numero' => '', 'rue' => ''];

        if (preg_match('/^(\d+[a-zA-Z]?)\s+(.*)$/', trim($address), $matches)) {
            $result['numero'] = $matches[1];
            $result['rue']    = trim($matches[2]);
        } else {
            $result['rue'] = $address;
        }

        return $result;
    }

    /**
     * Map civilité texte vers entier selon API Euro CRM
     * @param string $gender_category
     * @return int
     */
    private static function mapCiviliteToInt($gender_category)
    {
        $mapping = [
            'Monsieur'     => 1,
            'Mr'           => 1,
            'M.'           => 1,
            'M'            => 1,
            'Madame'       => 2,
            'Mme'          => 2,
            'Mademoiselle' => 3,
            'Mlle'         => 3,
            'Mlle.'        => 3,
        ];

        return isset($mapping[$gender_category]) ? $mapping[$gender_category] : 1;
    }
    /**
     * Map profession vers les valeurs acceptées par l'API Euro CRM
     * @param string $profession
     * @return string
     */
    private static function mapProfession($profession)
    {
        // Normaliser l'entrée
        $normalized = strtoupper(self::removeAccents(trim($profession)));

        // Mapping selon les valeurs LOV de la documentation (page 16-18)
        $mapping = [
            'AGENT_ASSURANCE'        => 'Agent assurance',
            'AGENT ASSURANCE'        => 'Agent assurance',
            'ARTISAN'                => 'Artisan',
            'CADRE'                  => 'Cadre',
            'CHEF_ENTREPRISE'        => 'Chef entreprise',
            'CHEF ENTREPRISE'        => 'Chef entreprise',
            'COMMERCANT'             => 'Commerçant',
            'CONJOINT_COLLABORATEUR' => 'Conjoint collaborateur',
            'CONJOINT COLLABORATEUR' => 'Conjoint collaborateur',
            'DEMANDEUR_EMPLOI'       => 'Demandeur emploi',
            'DEMANDEUR EMPLOI'       => 'Demandeur emploi',
            'CHOMEUR'                => 'Demandeur emploi',
            'EMPLOYE'                => 'Employé',
            'ETUDIANT'               => 'Etudiant',
            'EXPLOITANT_AGRICOLE'    => 'Exploitant agricole',
            'EXPLOITANT AGRICOLE'    => 'Exploitant agricole',
            'AGRICULTEUR'            => 'Exploitant agricole',
            'FONCTIONNAIRE'          => 'Fonctionnaire',
            'GERANT_SOCIETE'         => 'Gerant de societe',
            'GERANT DE SOCIETE'      => 'Gerant de societe',
            'GERANT'                 => 'Gerant de societe',
            'OUVRIER'                => 'Ouvrier',
            'PROFESSION_LIBERALE'    => 'Profession libérale',
            'PROFESSION LIBERALE'    => 'Profession libérale',
            'LIBERAL'                => 'Profession libérale',
            'MEDECIN_NON_SALARIE'    => 'Profession médicales et paramedicales NON SALARIE',
            'MEDECIN NON SALARIE'    => 'Profession médicales et paramedicales NON SALARIE',
            'MEDECIN_SALARIE'        => 'Professions medicales et para medicales salariés',
            'MEDECIN SALARIE'        => 'Professions medicales et para medicales salariés',
            'MEDECIN'                => 'Professions medicales et para medicales salariés',
            'RETRAITE'               => 'Retraité',
            'RETIRE'                 => 'Retraité',
            'SANS_PROFESSION'        => 'Sans profession',
            'SANS PROFESSION'        => 'Sans profession',
            'INACTIVE'               => 'Sans profession',
            'INACTIF'                => 'Sans profession',
        ];

        return isset($mapping[$normalized]) ? $mapping[$normalized] : 'Sans profession';
    }

    /**
     * Map situation_famille vers les valeurs acceptées par l'API Euro CRM
     * @param string $situation_famille
     * @return string
     */
    private static function mapSituationFamiliale($situation_famille)
    {
        // Mapping selon les valeurs LOV de la documentation (page 17-18)
        $mapping = [
            'CELIBATAIRE' => 'Célibataire',
            'CONCUBIN'    => 'Concubin(e)',
            'DIVORCE'     => 'Divorcé(e)',
            'MARIE'       => 'Marié(e)',
            'PACSE'       => 'Pacsé(e)',
            'SEPARE'      => 'Séparé(e)',
            'VEUF'        => 'Veuf(ve)',
        ];

        return isset($mapping[$situation_famille]) ? $mapping[$situation_famille] : 'Célibataire';
    }
    /**
     * Vérifie si le code postal correspond à la France métropolitaine
     * @param string $zipcode
     * @return bool
     */
    private static function isMetropolitanFrance($zipcode)
    {
        // France métropolitaine: codes postaux de 01000 à 95999
        // Exclut les DOM-TOM (97xxx, 98xxx)
        $zip = intval($zipcode);
        return $zip >= 1000 && $zip <= 95999;
    }

    /**
     * Map regime_social vers les valeurs acceptées par l'API Euro CRM
     * @param string $regime_social
     * @return string
     */
    private static function mapRegimeSocial($regime_social)
    {
        // Mapping selon les valeurs LOV de la documentation
        $mapping = [
            'ALSACE_MOSELLE'   => 'Alsace Moselle',
            'REGIME_AGRICOLE'  => 'Regime Agricole',
            'SECURITE_SOCIALE' => 'Securite Sociale',
            'TNS'              => 'TNS',
        ];

        return isset($mapping[$regime_social]) ? $mapping[$regime_social] : 'Securite Sociale';
    }

    /**
     * Remove accents from string for normalization
     * @param string $str
     * @return string
     */
    private static function removeAccents($str)
    {
        $accents = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n',

        ];

        return strtr($str, $accents);
    }

}
