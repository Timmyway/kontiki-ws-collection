<?php

namespace App\Requests;


class DiscardedRequests
{
    /**
     * Method that provid the begin part of the discarded query.
     * @return string
     */
    public static function begin_query_parts() : string
    {
        return 'select leads.id, leads.receive_date, leads.discard_date, leads.civility, leads.firstname, leads.lastname, leads.phone, leads.email, leads.birthdate,
            leads.ip, leads.userAgent, leads.referer, leads.zipcode, leads.city, leads.address, leads.affiliateID, leads.description_call, leads.user_sender
            FROM leads LEFT OUTER JOIN leads_has_clients ON leads_has_clients.leads_id = leads.id';
    }

    /**
     * Provide additional parts of discarded query
     * @param mixed $partenaire
     * @return string
     */
    public static function discarded_query_parts($partenaire) : string
    {

        return 'WHERE fournisseurs_id = (select id from fournisseurs where login=\'' . $partenaire . '\') 
            AND fournisseurs_tags_id = (select tags_id from fournisseurs where login=\'' . $partenaire . '\' ) 
            AND leads_has_clients.leads_id IS NULL 
            AND leads.call_status = \'CALLED\' ';
    }


    /**
     * Method to provide the correct query for discarded requests.
     * @param mixed $partenaire
     * @param mixed $offset
     * @return string
     */
    public static function discarded_query($partenaire, $offset, $limit) : string
    {
        $begin_parts = DiscardedRequests::begin_query_parts();
        $query_parts = DiscardedRequests::discarded_query_parts($partenaire);
        return $begin_parts . ' ' . $query_parts . ' order by leads.discard_date DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
    }


    /**
     * Method to provide the correct query for discarded lead to exports.
     * @param mixed $partenaire
     * @param mixed $offset
     * @return string
     */
    public static function discarded_downloadable_query($partenaire, $export_criteria) : string
    {
        $begin_parts = DiscardedRequests::begin_query_parts();
        $query_parts = DiscardedRequests::discarded_query_parts($partenaire);
        if ($export_criteria["monthly"] === true) {
            return $begin_parts . ' ' . $query_parts . ' AND MONTH(leads.receive_date) = ' . $export_criteria["month_rank"]
                . ' AND YEAR(leads.receive_date) =  ' . $export_criteria["year"] . ' order by leads.receive_date ASC';
        }
        return $begin_parts . ' ' . $query_parts . ' AND leads.receive_date BETWEEN \'' . $export_criteria["start_date"]
            . '\' AND \'' . $export_criteria["end_date"] . '\' order by leads.receive_date ASC';
    }

    /**
     * Method to provide the correct query for discarded requests leads counts.
     * @param mixed $partenaire
     * @return string
     */
    public static function discarded_count_query($partenaire) : string
    {
        $query_parts = DiscardedRequests::discarded_query_parts($partenaire);
        return 'SELECT COUNT(*) AS totals FROM leads 
        LEFT OUTER JOIN leads_has_clients ON leads_has_clients.leads_id = leads.id ' . $query_parts;
    }

    /**
     * Method to provide the correct query for discarded requests leads daily counts.
     * @param mixed $partenaire
     * @return string
     */
    public static function todays_discarded_count_query($partenaire) : string
    {
        $query_parts = DiscardedRequests::discarded_query_parts($partenaire);
        return 'SELECT COUNT(*) AS todays_count FROM leads 
        LEFT OUTER JOIN leads_has_clients ON leads_has_clients.leads_id = leads.id 
        ' . $query_parts . ' AND DATE(leads.discard_date) = DATE(NOW()) ';
    }
}