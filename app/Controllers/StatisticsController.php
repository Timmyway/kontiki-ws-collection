<?php

namespace App\Controllers;

class StatisticsController
{
    public static function setStat($mysqli, $date)
    {
        $request = "SELECT 
                    DATE_FORMAT(lhc.validation_date, '%Y-%m') AS month,
                    f.login AS supplier,
                    c.name AS client,
                    COUNT(lhc.validation_date) AS sent,
                    SUM(
                        CASE 
                            WHEN lhc.leads_status IN ('Submitted', 'Sent', 'OK', 'ok', 'Ok', 'success', 'Accepted') 
                            THEN 1 ELSE 0 
                        END
                        ) AS valid
                    FROM leads_has_clients lhc
                    LEFT JOIN fournisseurs f ON lhc.leads_fournisseurs_id = f.id
                    LEFT JOIN clients c ON lhc.clients_id = c.id
                    WHERE f.login NOT LIKE 'coregistration-%'
                    AND c.name IS NOT NULL
                    AND DATE_FORMAT(lhc.validation_date, '%Y-%m') = '$date'
                    GROUP BY month, supplier, client
                    HAVING sent
                    ORDER BY month, supplier, client";
        
        $result = $mysqli->query($request);
        $items = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($items as $item) {
            $month = $item['month'];
            $supplier = $item['supplier'];
            $client = $item['client'];

            $select_stmt = $mysqli->prepare("SELECT id FROM statistics WHERE month = ? AND supplier = ? AND client = ?");
            $select_stmt->bind_param("sss", $month, $supplier, $client);
            $select_stmt->execute();
            $stat_result = $select_stmt->get_result();
            $stat = $stat_result->fetch_assoc();

            if ($stat) {
                $stmt = $mysqli->prepare("UPDATE statistics SET payout = ?, sent = ?, valid = ? WHERE id = ?");
                $stmt->bind_param("diis", $item['payout'], $item['sent'], $item['valid'], $stat['id']);
            } else {
                $stmt = $mysqli->prepare("INSERT INTO statistics (month, supplier, client, payout, sent, valid) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssidd", $item['month'], $item['supplier'], $item['client'], $item['payout'], $item['sent'], $item['valid']);
            }
            $stmt->execute();
        }
    }

    public static function getStat($mysqli, $get = [])
    {
        $request = "SELECT id, month, supplier, client, payout, sent, valid FROM statistics WHERE id > 0";

        if (isset($get['date']) && !empty($get['date'])) {
            $date = $get['date'];
            $request .= " AND month = '" . $mysqli->real_escape_string($date) . "'";
        }
        if (isset($get['q']) && !empty($get['q'])) {
            $q = $get['q'];
            $request .= " AND (client LIKE '%" . $mysqli->real_escape_string($q) . "%' OR supplier LIKE '%" . $mysqli->real_escape_string($q) . "%')";
        }
        $request .= " ORDER BY month DESC";

        if (isset($get) && !empty($get)) {
            foreach ($get as $id => $values) {
                $payout = $values['payout'] ?? null;
                $updated = "UPDATE statistics SET payout = COALESCE(?, payout) WHERE id = ?";
                $stmt = $mysqli->prepare($updated);
                $stmt->bind_param("di", $payout, $id);
                $stmt->execute();
            }
        }

        $result = $mysqli->query($request);
        $items = $result->fetch_all(MYSQLI_ASSOC);

        return $items;
    }
}