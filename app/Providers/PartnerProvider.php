<?php
namespace App\Providers;

class PartnerProvider
{
    public static function getName($conn, $partname)
    {
        $stmt = $conn->prepare("SELECT name FROM fournisseurs WHERE login = ?");
        $stmt->bind_param("s", $partname);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['name'] ?? null;
    }
}