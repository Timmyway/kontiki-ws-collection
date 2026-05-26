<?php

namespace App\Requests;

class ValidatedRequests
{
    /**
     * Provide the begin parts of validated query
     * @return string
     */
    public static function validated_begin_query_parts() : string
    {
        return 'select leads.id, leads_has_clients.validation_date, leads.receive_date, leads.civility, leads.firstname, leads.lastname, leads.email, 
                leads.phone, leads.birthdate, leads.zipcode, leads.city, leads.address as address, leads.ip, leads.userAgent, leads.referer, leads.affiliateID, leads.user_sender, 
                leads_has_clients.api_response, leads_has_clients.description, leads_has_clients.leads_status, clients.name as client, leads.description_call as comments
                from leads_has_clients';
    }

    /**
     * Provide additional parts of validated query
     * @param mixed $partenaire
     * @return string
     */
    public static function validated_query_parts($partenaire) : string
    {
        return 'INNER JOIN leads ON leads.id = leads_has_clients.leads_id
                INNER JOIN clients ON clients.id = leads_has_clients.clients_id
                where leads_fournisseurs_id = (select id from fournisseurs where login=\'' . $partenaire . '\')
                AND leads_fournisseurs_tags_id = (select tags_id from fournisseurs where login=\'' . $partenaire . '\' )';
    }

    /**
     * Method to provide the correct query for validated lead to exports.
     * @param mixed $partenaire
     * @param mixed $offset
     * @return string
     */
    public static function validated_downloadable_query($partenaire, $export_criteria) : string
    {
        $begin_parts = ValidatedRequests::validated_begin_query_parts();
        $query_parts = ValidatedRequests::validated_query_parts($partenaire);
        if ($export_criteria["monthly"] === true) {
            return $begin_parts . ' ' . $query_parts . ' AND MONTH(leads_has_clients.validation_date) = ' . $export_criteria["month_rank"]
                . ' AND YEAR(leads_has_clients.validation_date) =  ' . $export_criteria["year"] . ' order by leads_has_clients.validation_date ASC';
        }
        return $begin_parts . ' ' . $query_parts . ' AND leads_has_clients.validation_date BETWEEN \'' . $export_criteria["start_date"]
            . '\' AND \'' . $export_criteria["end_date"] . '\' order by leads_has_clients.validation_date ASC';
    }

    /**
     * Method to provide the correct query for validated requests.
     * @param mixed $partenaire
     * @param mixed $offset
     * @param mixed $limit
     * @return string
     */
    public static function validated_query($partenaire, $offset, $limit) : string
    {
        $begin_parts = ValidatedRequests::validated_begin_query_parts();
        $query_parts = ValidatedRequests::validated_query_parts($partenaire);
        
        return $begin_parts . ' ' . $query_parts . ' order by leads_has_clients.validation_date DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
    }

    /**
     * Method to provide the correct query for validated requests leads counts.
     * @param mixed $partenaire
     * @return string
     */
    public static function validated_count_query($partenaire) : string
    {
        $query_parts = ValidatedRequests::validated_query_parts($partenaire);
        return 'SELECT COUNT(*) AS totals FROM leads_has_clients ' . $query_parts;
    }

    /**
     * Method to provide the correct query for validated requests leads daily counts.
     * @param mixed $partenaire
     * @return string
     */
    public static function todays_validated_count_query($partenaire) : string
    {
        $query_parts = ValidatedRequests::validated_query_parts($partenaire);
        return 'SELECT COUNT(*) AS todays_count FROM leads_has_clients  ' . $query_parts . ' AND DATE(leads_has_clients.validation_date) = DATE(NOW()) ';
    }
}