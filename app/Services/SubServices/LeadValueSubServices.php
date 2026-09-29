<?php
namespace App\Services\SubServices;

class LeadValueSubServices
{

    // methods

    /**
     * Method to provide LEAD VALUE phone wanted format
     * @param mixed $phone
     * @return string
     */
    public static function lead_value_phone_format($phone)
    {
        $str_array = str_split($phone, 2);
        $result    = implode(" ", $str_array);
        return $result;
    }

    /**
     * Method to make_leadvalue_datas
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @return array
     */
    public static function make_leadvalue_datas($classics, $specifics, $gender_category, $form_id)
    {
        $situation = "Propriétaire"; // Par défaut: Propriétaire
        if (isset($specifics['situation_immo'])) {
            $mapping = [
                'proprietaire'       => "Propriétaire",
                'futur propriétaire' => "Futur propriétaire",
                'locataire'          => "Locataire",
                'futur locataire'    => "Autre",
                'administrateur'     => "Autre",
                'autre'              => "Autre",
            ];
            $situation = $mapping[strtolower($specifics['situation_immo'])] ?? 1;
        }
        $civility               = self::formatCivility($classics['civility']);

        // accept only propriétaire.
        $data = [
            "lead_type"    => "handy_services",
            "housing_type" => $specifics['type_logement'],
            "situation"    => $situation,
            "lead_form_id" => $form_id,
            "ref_ext"      => $classics['lead_id'],
            "civility"     => $civility,
            "lastname"     => $classics['lastname'],
            "firstname"    => $classics['firstname'],
            "phone1"       => LeadValueSubServices::lead_value_phone_format($classics['phone']),
            "optin"        => "1",
            "email"        => $classics['email'],
            "address"      => $classics['address'],
            "zip"          => $classics['zipcode'],
            "town"         => $classics['city'],
            "energy"       => $specifics['type_chauffage'],
            "ip"           => $classics["ip"],
            "no_email"     => "0",
        ];

        return $data;
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
    /**
     * Convertit la valeur interne "who_is_to_be_assured" vers la valeur attendue par le client.
     */
    private static function formatWhoIsToBeAssured($value)
    {
        $mapping = [
            "seul"               => "Vous seulement",
            "seul_avec_enfants"  => "Vous et vos enfants",
            "couple"             => "Vous et votre conjoint",
            "couple_avec_enfant" => "Toute la famille",
        ];

        return $mapping[strtolower(trim($value))] ?? null;
    }
    /**
     * Convertit la valeur interne "social_regime" vers la valeur attendue par le client.
     */
    private static function formatSocialRegime($value)
    {
        $mapping = [
            "general"        => "Général",
            "tns"            => "Régime TNS",
            "alsace_moselle" => "Alsace-moselle",
            // Si jamais tu ajoutes plus tard "agricole", il te suffit de rajouter :
            // "agricole" => "Agricole",
        ];

        return $mapping[strtolower(trim($value))] ?? null;
    }
    /**
     * Convertit la profession interne vers la valeur attendue par le client.
     */
    private static function formatProfession($value)
    {
        if (! $value) {
            return null;
        }

        $value = strtolower(trim($value));

        $mapping = [

            // Salarié(e)
            "salarie"             => "Salarié(e) non-cadre",
            "employé"             => "Salarié(e) non-cadre",
            "employe"             => "Salarié(e) non-cadre",
            "ouvrier"             => "Salarié(e) non-cadre",

            // Cadre
            "cadre"               => "Salarié(e) cadre",

            // Fonctionnaires
            "fonctionnaire"       => "Fonctionnaire d'état",
            "agent-assurance"     => "Fonctionnaire d'état", // à ajuster si besoin

            // Retraité
            "retraité"            => "Retraité(e)",
            "retraite"            => "Retraité(e)",

            // Demandeur d’emploi
            "demandeur emploi"    => "Recherche d'emploi",
            "demandeur-emploi"    => "Recherche d'emploi",

            // Sans profession
            "sans profession"     => "Sans profession",

            // Étudiant
            "etudiant"            => "Etudiant(e)",
            "étudiant"            => "Etudiant(e)",

            // Indépendants
            "independant"         => "Profession libérale",
            "profession-liberale" => "Profession libérale",

            // Chef d’entreprise
            "chef entreprise"     => "Chef(fe) d'entreprise",
            "chef-d-entreprise"   => "Chef(fe) d'entreprise",

            // Commerçant
            "commerçant"          => "Commerçant(e)",
            "commercant"          => "Commerçant(e)",

            // Artisan
            "artisan"             => "Artisan",

            // Enseignant
            "enseignant"          => "Enseignant(e)",

            // Agriculteur
            "agriculteur"         => "Agriculteur(trice)",
            "agriculteur(trice)"  => "Agriculteur(trice)",
        ];

        // Renvoie la valeur mappée ou "Sans profession" par défaut
        return $mapping[$value] ?? "Sans profession";
    }
    /**
     * Convertit l'état marital du formulaire vers la valeur attendue par le client.
     */
    private static function formatMaritalStatus($value)
    {
        if (! $value) {
            return "Union libre"; // fallback logique
        }

        $value = strtolower(trim($value));

        $mapping = [
            // Marié
            "marie"        => "Marié(e)",
            "marie(e)"     => "Marié(e)",

            // Pacsé
            "pacsé"        => "Pacsé(e)",
            "pacse"        => "Pacsé(e)",
            "pacsé(e)"     => "Pacsé(e)",

            // Union libre
            "celibataire"  => "Union libre",
            "concubin"     => "Union libre",
            "concubin(e)"  => "Union libre",
            "reconversion" => "Union libre",
            "inconnu"      => "Union libre",

            // Séparé
            "separe"       => "Séparé(e)",
            "separe(e)"    => "Séparé(e)",
            "divorce"      => "Séparé(e)",

            // veuf = plus proche : séparé
            "veuf"         => "Séparé(e)",
            "veuf(ve)"     => "Séparé(e)",
        ];

        return $mapping[$value] ?? "Union libre";
    }
/**
 * Convertit la civilité du formulaire vers la valeur attendue par le client.
 */
    private static function formatCivility($value)
    {
        if (! $value) {
            return "M."; // fallback logique
        }

        $value = strtolower(trim($value));

        $mapping = [
            // Homme
            "mr"       => "M.",
            "m"        => "M.",
            "m."       => "M.",
            "monsieur" => "M.",
            "1"        => "M.",

            // Femme
            "mrs"      => "Mme",
            "mme"      => "Mme",
            "mme."     => "Mme",
            "madame"   => "Mme",
            "mm"       => "Mme",
            "f"        => "Mme",
            "2"        => "Mme",
        ];

        return $mapping[$value] ?? "M.";
    }

    /**
     * Method to make_leadvalue_datas
     * @param mixed $classics
     * @param mixed $specifics
     * @param mixed $gender_category
     * @return array
     */
    public static function make_leadvalue_datas_mutuelle($classics, $specifics, $gender_category, $form_id)
    {
        $birthdate = $classics['birthdate'] ?? null;

        // Appel correct de la méthode interne
        $birthdateFormatted     = self::formatBirthdate($birthdate);
        $whoIsAssuredFormatted  = self::formatWhoIsToBeAssured($specifics["custom_field_1"]);
        $socialRegimeFormatted  = self::formatSocialRegime($specifics["custom_field_2"]);
        $professionFormatted    = self::formatProfession($specifics["professionnal_situation"]);
        $maritalStatusFormatted = self::formatMaritalStatus($specifics["custom_field_5"]);
        $civility               = self::formatCivility($classics['civility']);
        // accept only propriétaire.
        $data = [
            "lead_type"                             => "finances",
            "lead_form_id"                          => $form_id,
            "who_is_to_be_assured"                  => $whoIsAssuredFormatted,
            "number_of_children"                    => "0",
            'birthdate'                             => $birthdateFormatted,
            "social_regime"                         => $socialRegimeFormatted,
            "profession"                            => $professionFormatted,
            "do_you_currently_benefit_from_a_mutua" => "Oui",
            "marital_status"                        => $maritalStatusFormatted,
            "civility"                              => $civility,
            "lastname"                              => $classics['lastname'],
            "firstname"                             => $classics['firstname'],
            "email"                                 => $classics['email'],
            "address"                               => $classics['address'],
            "zip"                                   => $classics['zipcode'],
            "town"                                  => $classics['city'],
            "phone1"                                => LeadValueSubServices::lead_value_phone_format($classics['phone']),
            "ip"                                    => $classics["ip"],
            "ref_ext"                               => $classics['lead_id'],
            "optin"                                 => "1",
            "no_email"                              => "0",
        ];
        return $data;
    }

    public static function make_leadvalue_responses($json_response, $curl_response, $campaign)
    {
        if ($json_response->data[0]->lead_status == "1") {
            return [
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->data[0]->id,
                "ws_statut"    => "ok",
                "description"  => "lead \"{$campaign}\" has been send successfully to LEAD VALUE",
            ];
        } elseif ($json_response->data[0]->lead_status == "0") {
            return [
                "status"       => "success",
                "api_response" => $curl_response,
                "id_part"      => $json_response->data[0]->id,
                "ws_statut"    => "ok",
                "description"  => "sent with status : " . $json_response->data[0]->status,
            ];
        }
        return [
            "status"       => "error",
            "api_response" => $curl_response,
            "id_part"      => $json_response->data[0]->id,
            "ws_statut"    => "error",
            "description"  => "error sending leads, " . $json_response->data[0]->status,
        ];
    }
}