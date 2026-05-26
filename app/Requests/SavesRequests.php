<?php

namespace App\Requests;

class SavesRequests
{
    /**
     * Method that provide query for save leads
     * @param mixed $date
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_leads_query($date, $lead, $login_data): string
    {
        return 'insert into leads
            (receive_date,call_up_moment,email,firstname,lastname,civility,birthdate,zipcode,address,city,phone,affiliateID,ip,userAgent,referer,call_status,fournisseurs_id, fournisseurs_tags_id)
            VALUES (\'' . $date . '\',
                \'' . $lead['call_up_moment'] . '\',
                \'' . base64_encode($lead['email']) . '\',
                \'' . base64_encode($lead['firstname']) . '\',
                \'' . base64_encode($lead['lastname']) . '\',
                \'' . $lead['civility'] . '\',
                \'' . $lead['birthdate'] . '\',
                \'' . base64_encode($lead['zipcode']) . '\',
                \'' . base64_encode($lead['address']) . '\',
                \'' . base64_encode($lead['city']) . '\',
                \'' . base64_encode($lead['phone']) . '\',
                \'' . $lead['affiliateID'] . '\',
                \'' . $lead['ip'] . '\',
                \'' . $lead['userAgent'] . '\',
                \'' . $lead['referer'] . '\',
                \'NOT CALLED\',
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\') ,
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }

    /**
     * Method that provide query for save leads_has_client
     * @param mixed $date
     * @param mixed $send_result
     * @param mixed $id_du_lead
     * @param mixed $partenaire
     * @param mixed $client
     * @return string
     */
    public static function save_leads_has_client_query($date, $send_result, $id_du_lead, $partenaire, $client): string
    {
        return 'insert into leads_has_clients (validation_date,api_response,id_partenaire,leads_status,description,leads_id,leads_fournisseurs_id,leads_fournisseurs_tags_id,clients_id)
            VALUES (\'' . $date . '\',
            \'' . base64_encode(json_encode($send_result['api_response'])) . '\',
            \'' . $send_result['id_part'] . '\' ,
            \'' . $send_result['ws_statut'] . '\' ,
            \'' . base64_encode($send_result['description']) . '\' ,
            (select id from leads where id=' . $id_du_lead . '),
                (select id from fournisseurs where login=\'' . $partenaire . '\' ),
                (select tags_id from fournisseurs where login=\'' . $partenaire . '\' ),
                (select id from clients where name=\'' . $client . '\')
            )';
    }

    /**
     * Method  that provide query for save defiscalisation data.
     * @param mixed $defisc_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_defisc_query($defisc_data, $lead, $login_data): string
    {
        return 'insert into defiscalisation (imposition, matrimonialSituation, status_leads, affiliateid, ip, impotsAnnuels, economie_a_placer, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
                VALUES (
                    \'' . base64_encode($defisc_data['impot']) . '\',
                    \'' . $lead['matrimoniale'] . '\',
                    \'' . $lead['situation'] . '\',
                    \'' . $lead['affiliateID'] . '\',
                    \'' . $lead['ip'] . '\',
                    \'' . base64_encode($defisc_data['impotAnnuel']) . '\',
                    \'' . $defisc_data['apportPerso'] . '\',
                    \'' . $lead["lead_id"] . '\' ,
                    (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                    (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
                )';
    }

    /**
     * Method  that provide query for save travaux data.
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $travaux_data
     * @param mixed $id_travaux_kontiki
     * @return string
     */
    public static function save_travaux_query($login_data, $lead, $travaux_data, $id_travaux_kontiki): string
    {
        return 'insert into travaux (situation_immo, date_start, description, type_logement, type_chauffage, partToInsulate, type_emetteur, type_client, surface_logement, preferred_contact_time, custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id, travaux_kontiki_id)
                VALUES (
                    \'' . $lead['situation'] . '\',
                    \'' . $travaux_data['date_start'] . '\',
                    \'' . $travaux_data['description'] . '\',
                    \'' . $travaux_data['type_logement'] . '\',
                    \'' . $travaux_data['type_chauffage'] . '\',
                    \'' . $travaux_data['partToInsulate'] . '\',
                    \'' . $travaux_data['type_emetteur'] . '\',
                    \'' . $travaux_data['type_client'] . '\',
                    \'' . $travaux_data['surface_logement'] . '\',
                    \'' . $travaux_data['preferred_contact_time'] . '\',
                    \'' . $travaux_data['custom_field_1'] . '\',
                    \'' . $travaux_data['custom_field_2'] . '\',
                    \'' . $travaux_data['custom_field_3'] . '\',
                    \'' . $travaux_data['custom_field_4'] . '\',
                    \'' . $travaux_data['custom_field_5'] . '\',
                    \'' . $lead["lead_id"] . '\' ,
                    (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                    (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                    ' . $id_travaux_kontiki . '
                )';
    }

    /**
     * Method that provide query for save assurance data.     
     * @param mixed $assurance_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_assurance_query($assurance_data, $lead, $login_data): string
    {
        error_log("Version corrigée utilisée");
        return 'insert into assurances (bank, property_assurance, objectif_assurance, montant_pret, quote_type, taux_pret, duree_pret, professionnal_situation, custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5, custom_field_6, custom_field_7,
         leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
            VALUES (
                \'' . base64_encode($assurance_data['bank'] ?? '') . '\',
                \'' . base64_encode($assurance_data['bien'] ?? '') . '\',
                \'' . ($assurance_data['objectif'] ?? '') . '\',
                \'' . base64_encode($assurance_data['amount'] ?? '') . '\',
                \'' . ($assurance_data['quote_type'] ?? '') . '\',
                \'' . ($assurance_data['rate'] ?? '') . '\',
                \'' . ($assurance_data['duration'] ?? '') . '\',
                \'' . ($assurance_data['profession'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_1'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_2'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_3'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_4'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_5'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_6'] ?? '') . '\',
                \'' . ($assurance_data['custom_field_7'] ?? '') . '\',
                \'' . $lead["lead_id"] . '\' ,
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }

    /**
     * Method  that provide query for save security data.
     * @param mixed $security_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_security_query($security_data, $lead, $login_data): string
    {
        return 'insert into securities (canal, custom_field_1, custom_field_2, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
        VALUES (
            \'' . ($security_data['canal'] ?? '') . '\',
            \'' . ($security_data['custom_field_1'] ?? '') . '\',
            \'' . ($security_data['custom_field_2'] ?? '') . '\',
            \'' . $lead["lead_id"] . '\',
            (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
            (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
        )';
    }

    /**
     * Method  that provide query for save formation data.
     * @param mixed $formation_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_formation_query($formation_data, $lead, $login_data): string
    {
        return 'insert into formations (situationPro, langue, leads_id, leads_fournisseurs_id, custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5, leads_fournisseurs_tags_id)
            VALUES (
                \'' . $formation_data['situationPro'] . '\',
                \'' . $formation_data['langue'] . '\',
                \'' . ($formation_data['custom_field_1'] ?? '') . '\',
                \'' . ($formation_data['custom_field_2'] ?? '') . '\',
                \'' . ($formation_data['custom_field_3'] ?? '') . '\',
                \'' . ($formation_data['custom_field_4'] ?? '') . '\',
                \'' . ($formation_data['custom_field_5'] ?? '') . '\',
                \'' . $lead["lead_id"] . '\' ,
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }

    /**
     * Method  that provide query to save rac data.
     * @param mixed $rac_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_rac_query($rac_data, $lead, $login_data): string
    {
        return 'insert into rachat_de_credits (status_logement, fichage, contract, nb_credit_conso, mensualites_conso, restant_du_conso, type_credit_conso, nb_credit_immo, mensualites_immo, restant_du_immo, revenu_mensuel, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
            VALUES (
                \'' . $lead['situation'] . '\',
                \'' . $rac_data['fichage'] . '\',
                \'' . $rac_data['contract'] . '\',
                \'' . $rac_data['nb_credit_conso'] . '\',
                \'' . base64_encode($rac_data['mensualites_conso']) . '\',
                \'' . base64_encode($rac_data['restant_du_conso']) . '\',
                \'' . base64_encode($rac_data['type_credit_conso']) . '\',
                \'' . $rac_data['nb_credit_immo'] . '\',
                \'' . base64_encode($rac_data['mensualites_immo']) . '\',
                \'' . base64_encode($rac_data['restant_du_immo']) . '\',
                \'' . base64_encode($rac_data['revenu_mensuel']) . '\',
                \'' . $lead["lead_id"] . '\' ,
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }

    /**
     * Method that provide query for save assurance auto data.     
     * @param mixed $assurance_auto_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_assurance_auto_query($data, $lead, $login_data): string
    {
        return 'insert into assurance_auto (registration, brand, custom_field_1, custom_field_2, date_insured, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
            VALUES (
                \'' . base64_encode($data['registration']) . '\',
                \'' . base64_encode($data['brand']) . '\',
                \'' . base64_encode($data['custom_field_1']) . '\',
                \'' . base64_encode($data['custom_field_2']) . '\',
                \'' . base64_encode($data['date_insured']) . '\',
                \'' . $lead["lead_id"] . '\' ,
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }

    /**
     * Method that provide query for save assurance data.     
     * @param mixed $assurance_data
     * @param mixed $lead
     * @param mixed $login_data
     * @return string
     */
    public static function save_assurance_sante_query($data, $lead, $login_data): string
    {
        return 'insert into assurance (besoin, regime_social, profession, profession_compl, situation_famille, nombre_enfant, assurer_conjoint, cid, custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5, custom_field_6, leads_id, leads_fournisseurs_id, leads_fournisseurs_tags_id)
            VALUES (
                \'' . base64_encode($data['besoin'] ?? '') . '\',
                \'' . base64_encode($data['regime_social'] ?? '') . '\',
                \'' . base64_encode($data['profession'] ?? '') . '\',
                \'' . base64_encode($data['profession_compl'] ?? '') . '\',
                \'' . base64_encode($data['situation_famille'] ?? '') . '\',
                \'' . $data['nombre_enfant'] . '\',
                \'' . base64_encode($data['assurer_conjoint'] ?? '') . '\',
                \'' . base64_encode($data['cid'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_1'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_2'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_3'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_4'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_5'] ?? '') . '\',
                \'' . base64_encode($assurance_data['custom_field_6'] ?? '') . '\',
                
                \'' . $lead["lead_id"] . '\' ,
                (select id from fournisseurs where login=\'' . $login_data['partname'] . '\'),
                (select tags_id from fournisseurs where login=\'' . $login_data['partname'] . '\')
            )';
    }
}
