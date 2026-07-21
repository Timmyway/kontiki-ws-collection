<?php

namespace App\Requests;

class CurrentRequests
{
    /**
     * Provide the first part of a select query
     * @return string
     */
    public static function begin_query(): string
    {
        return 'select leads.id, leads.receive_date, leads.call_up_moment, leads.civility, leads.firstname, leads.lastname, leads.phone, leads.email, leads.birthdate,
            leads.ip, leads.userAgent, leads.referer, leads.zipcode, leads.city, leads.address, leads.affiliateID, leads.description_call, leads.user_sender,';
    }

    /**
     * Provide a part of an end query of a select lead
     * @param mixed $partenaire
     * @return string
     */
    public static function end_query_parts($partenaire): string
    {
        return 'LEFT OUTER JOIN leads_has_clients ON leads_has_clients.leads_id = leads.id
            WHERE fournisseurs_id = (select id from fournisseurs where login=\'' . $partenaire . '\') 
            AND fournisseurs_tags_id = (select tags_id from fournisseurs where login=\'' . $partenaire . '\' ) 
            AND leads_has_clients.leads_id IS NULL 
            AND leads.call_status = \'NOT CALLED\' ';
    }

    /**
     * Provide the final part of a select query
     * @param mixed $partenaire
     * @param mixed $limit
     * @param mixed $offset
     * @return string
     */
    public static function end_query($partenaire, $limit, $offset): string
    {
        $parts = CurrentRequests::end_query_parts($partenaire);
        return $parts . ' order by leads.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
    }

    /**
     * Provide the final part of a select query
     * @param mixed $partenaire
     * @param mixed $date_filter
     * @return string
     */
    public static function counts_end_query($partenaire, $date_filter = ''): string
    {
        $parts = CurrentRequests::end_query_parts($partenaire);
        return $parts . ' ' . $date_filter . ' order by leads.id DESC';
    }

    /**
     * Provide the part of a select counts lead query
     * @param mixed $partenaire
     * @param mixed $partners
     * @param mixed $begin_query
     * @param mixed $end_query
     * @return string
     */
    public static function counts_specifics_query($partenaire, $partners, $begin_query, $end_query): string
    {

        if (in_array($partenaire, $partners["travaux"])) {
            /* --- for tag = TRAVAUX --- */
            return $begin_query . ' INNER JOIN travaux ON travaux.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["defisc"])) {
            /* --- for tag = DEFISCALISATION --- */
            return $begin_query . ' INNER JOIN defiscalisation ON defiscalisation.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["insurances"])) {
            /* --- for tag = INSURANCES --- */
            return $begin_query . ' INNER JOIN assurances ON assurances.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["formations"])) {
            /* --- for tag = FORMATIONS --- */
            return $begin_query . ' INNER JOIN formations ON formations.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["securities"])) {
            /* --- for tag = SECURITIES --- */
            return $begin_query . ' INNER JOIN securities ON securities.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["finances"])) {
            /* --- for tag = RACHAT_DE_CREDITS --- */
            return $begin_query . ' INNER JOIN rachat_de_credits ON rachat_de_credits.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["assurance_auto"])) {
            /* --- for tag = ASSURANCE_AUTO --- */
            return $begin_query . ' INNER JOIN assurance_auto ON assurance_auto.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["assurance"])) {
            /* --- for tag = ASSURANCE(Mutuelle senior) --- */
            return $begin_query . ' INNER JOIN assurance ON assurance.leads_id = leads.id ' . $end_query;
        } else {
            return '';
        }
    }

    /**
     * Method to provide the correct query for each current requests.
     * @param mixed $partenaire
     * @param mixed $offset
     * @param mixed $limit
     * @param mixed $partners
     * @return string
     */
    public static function which_query($partenaire, $offset, $limit, $partners): string
    {
        // the begin of the query
        $begin_query = CurrentRequests::begin_query();
        // the end of the query
        $end_query = CurrentRequests::end_query($partenaire, $limit, $offset);
        // SQL QUERY for specifics tags
        if (in_array($partenaire, $partners["travaux"])) {
            /* --- for tag = TRAVAUX --- */
            return $begin_query . ' travaux.date_start, travaux.description, travaux.situation_immo as situation, 
            travaux.type_chauffage, travaux.type_logement, travaux.partToInsulate, travaux.type_emetteur, travaux.type_client, travaux.surface_logement, travaux.preferred_contact_time,
            travaux.custom_field_1, travaux.custom_field_2, travaux.custom_field_3, travaux.custom_field_4, travaux.custom_field_5
            FROM leads INNER JOIN travaux ON travaux.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["defisc"])) {
            /* --- for tag = DEFISCALISATION --- */
            return $begin_query . ' defiscalisation.matrimonialSituation as matrimoniale, defiscalisation.status_leads as situation, defiscalisation.imposition as impot
            FROM leads INNER JOIN defiscalisation ON defiscalisation.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["securities"])) {
            /* --- for tag = SECURITIES --- */
            return $begin_query . ' securities.canal, securities.custom_field_1, securities.custom_field_2 FROM leads INNER JOIN securities ON securities.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["insurances"])) {
            /* --- for tag = INSURANCES --- */
            return $begin_query . ' assurances.bank, assurances.property_assurance, assurances.objectif_assurance, 
            assurances.montant_pret, assurances.quote_type, assurances.taux_pret, assurances.duree_pret, assurances.professionnal_situation as situationPro, assurances.custom_field_1, assurances.custom_field_2, assurances.custom_field_3, assurances.custom_field_4, assurances.custom_field_5, assurances.custom_field_6 , assurances.custom_field_7, assurances.custom_field_8
            FROM leads INNER JOIN assurances ON assurances.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["formations"])) {
            /* --- for tag = FORMATIONS --- */
            return $begin_query . ' formations.situationPro, formations.langue, formations.custom_field_1, formations.custom_field_2, formations.custom_field_3, formations.custom_field_4, formations.custom_field_5 FROM leads INNER JOIN formations ON formations.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["finances"])) {
            /* --- for tag = RACHAT_DE_CREDITS --- */
            return $begin_query . ' rachat_de_credits.status_logement as situation, rachat_de_credits.fichage, rachat_de_credits.contract, 
            rachat_de_credits.nb_credit_conso, rachat_de_credits.mensualites_conso, rachat_de_credits.restant_du_conso, rachat_de_credits.type_credit_conso, 
            rachat_de_credits.nb_credit_immo, rachat_de_credits.mensualites_immo, rachat_de_credits.restant_du_immo, rachat_de_credits.revenu_mensuel 
            FROM leads INNER JOIN rachat_de_credits ON rachat_de_credits.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["assurance_auto"])) {
            /* --- for tag = ASSURANCE_AUTO --- */
            return $begin_query . ' assurance_auto.registration, assurance_auto.brand, assurance_auto.custom_field_1, assurance_auto.custom_field_2, assurance_auto.date_insured  
            FROM leads INNER JOIN assurance_auto ON assurance_auto.leads_id = leads.id ' . $end_query;
        } elseif (in_array($partenaire, $partners["assurance"])) {
            /* --- for tag = ASSURANCE(mutuelle Senior) --- */
            return $begin_query . ' assurance.besoin, assurance.regime_social, assurance.profession, assurance.profession_compl, assurance.situation_famille, assurance.nombre_enfant, assurance.assurer_conjoint, assurance.cid, assurance.custom_field_1, assurance.custom_field_2, assurance.custom_field_3, assurance.custom_field_4, assurance.custom_field_5, assurance.custom_field_6  
            FROM leads INNER JOIN assurance ON assurance.leads_id = leads.id ' . $end_query;
        } else {
            return '';
        }
    }

    /**
     * Method to provide the correct query for current requests leads counts.
     * @param mixed $partenaire
     * @param mixed $partners
     * @return string
     */
    public static function which_count_query($partenaire, $partners): string
    {
        // the begin of the query
        $begin_query = 'SELECT COUNT(*) AS totals FROM leads';
        // the end of the query
        $end_query = CurrentRequests::counts_end_query($partenaire);
        // SQL QUERY for specifics tags
        return CurrentRequests::counts_specifics_query($partenaire, $partners, $begin_query, $end_query);
    }


    /**
     * Method to provide the correct query for current requests leads daily counts.
     * @param mixed $partenaire
     * @param mixed $partners
     * @return string
     */
    public static function which_todays_count_query($partenaire, $partners): string
    {
        // the begin of the query
        $begin_query = 'SELECT COUNT(*) AS todays_count FROM leads';
        // the end of the query
        $end_query = CurrentRequests::counts_end_query($partenaire, 'AND DATE(receive_date) = DATE(NOW())');
        // SQL QUERY for specifics tags
        return CurrentRequests::counts_specifics_query($partenaire, $partners, $begin_query, $end_query);
    }
}
