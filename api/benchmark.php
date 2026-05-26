<?php

require_once('../bootstrap/app.php');

$expected_key = defined('BENCHMARK_API_KEY') ? BENCHMARK_API_KEY : ($benchmark_key ?? null);

if (empty($expected_key)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["success" => false, "message" => "Server misconfiguration: BENCHMARK_API_KEY not defined"]);
    exit;
}

$provided_key = $_SERVER['HTTP_X_BENCHMARK_KEY'] ?? '';

if (empty($provided_key) || $provided_key !== $expected_key) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// ── Seulement GET ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 Ok");
    echo json_encode("ok");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// ── Paramètres ────────────────────────────────────────────
$since  = isset($_GET['since']) && !empty($_GET['since']) ? $_GET['since'] : null;
$limit  = isset($_GET['limit'])  ? min((int)$_GET['limit'], 1000)  : 500;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// ── Mapping civility → gender ──────────────────────────────
// ws-collection stocke "mr" / "mme", Go veut "M" / "F"
function mapGender($civility): string {
    $map = ['mr' => 'M', 'mme' => 'F', 'm' => 'M', 'f' => 'F'];
    return $map[strtolower(trim($civility ?? ''))] ?? '';
}

function mapThematic($tags_id, $partner_login): string {
    $travaux_logins = [
        'kontiki-20', 'kontiki-18', 'kontiki-42', 'kontiki-43',
        'kontiki-44', 'kontiki-50', 'kontiki-54', 'kontiki-75',
        'kontiki-19', 'kontiki-82', 'kontiki-83',
    ];
    $assurance_logins = [
        'kontiki-70', 'kontiki-34', 'kontiki-47', 'kontiki-52',
        'kontiki-65', 'kontiki-66', 'kontiki-73', 'kontiki-76',
        'kontiki-77', 'kontiki-78', 'kontiki-79',
    ];
    $assurance_auto_logins = ['kontiki-57'];
    $defisc_logins         = ['kontiki-14', 'kontiki-16'];
    $formation_logins      = ['kontiki-49', 'kontiki-51', 'kontiki-80', 'kontiki-81'];
    $security_logins       = ['kontiki-48'];
    $finance_logins        = ['kontiki-10'];

    if (in_array($partner_login, $travaux_logins))       return 'travaux';
    if (in_array($partner_login, $assurance_logins))     return 'assurance';
    if (in_array($partner_login, $assurance_auto_logins)) return 'assurance_auto';
    if (in_array($partner_login, $defisc_logins))        return 'defisc';
    if (in_array($partner_login, $formation_logins))     return 'formation';
    if (in_array($partner_login, $security_logins))      return 'security';
    if (in_array($partner_login, $finance_logins))       return 'finance';

    return 'autre';
}

function safeDecode($value): string {
    if (empty($value)) return '';
    $decoded = base64_decode($value, true);
    // Si le décodage échoue ou retourne des caractères non-UTF8 → retourner brut
    if ($decoded === false) return $value;
    if (!mb_check_encoding($decoded, 'UTF-8')) return $value;
    return $decoded;
}

function extractDepartment($zipcode): string {
    $zip = trim($zipcode);
    if (strlen($zip) >= 2) {
        // DOM-TOM : 97X, 98X
        if (substr($zip, 0, 2) === '97' || substr($zip, 0, 2) === '98') {
            return substr($zip, 0, 3);
        }
        return substr($zip, 0, 2);
    }
    return '';
}

$where_since = '';
$params      = [];
$types       = '';

if ($since !== null) {
    $where_since = 'AND leads.receive_date > ?';
    $params[]    = $since;
    $types      .= 's';
}

$query_count = '
    SELECT COUNT(*) AS total
    FROM leads
    INNER JOIN fournisseurs ON fournisseurs.id = leads.fournisseurs_id
    WHERE 1=1 ' . $where_since;

$query_leads = '
    SELECT
        leads.id,
        leads.receive_date,
        leads.civility,
        leads.email,
        leads.phone,
        leads.zipcode,
        leads.city,
        leads.birthdate,
        leads.ip,
        leads.affiliateID,
        fournisseurs.login     AS partner_login,
        fournisseurs.name      AS partner_name,
        fournisseurs.tags_id   AS tags_id
    FROM leads
    INNER JOIN fournisseurs ON fournisseurs.id = leads.fournisseurs_id
    WHERE 1=1 ' . $where_since . '
    ORDER BY leads.receive_date ASC
    LIMIT ? OFFSET ?';

try {
    $stmt_count = $conn->prepare($query_count);
    if ($params) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total        = (int)$result_count->fetch_assoc()['total'];
    $stmt_count->close();
} catch (\Throwable $th) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["success" => false, "message" => "Count error: " . $th->getMessage()]);
    exit;
}

try {
    $stmt = $conn->prepare($query_leads);

    $params_leads  = array_merge($params, [$limit, $offset]);
    $types_leads   = $types . 'ii';
    $stmt->bind_param($types_leads, ...$params_leads);

    $stmt->execute();
    $result = $stmt->get_result();

    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $email     = safeDecode($row['email']);
        $phone     = safeDecode($row['phone']);
        $zipcode   = safeDecode($row['zipcode']);
        $city      = safeDecode($row['city']);
        $birthdate = $row['birthdate'] ?? null;

        $age = null;
        if (!empty($birthdate) && $birthdate !== '0000-00-00') {
            try {
                $birth = new DateTime($birthdate);
                $now   = new DateTime();
                $age   = (int)$now->diff($birth)->y;
                if ($age < 0 || $age > 120) $age = null;
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        $partner_login = $row['partner_login'] ?? '';
        $thematic      = mapThematic($row['tags_id'], $partner_login);
        $department    = extractDepartment($zipcode);
        $gender        = mapGender($row['civility']);

        $leads[] = [
            'id'             => (int)$row['id'],
            'receive_date'   => $row['receive_date'],
            'email'          => $email,
            'phone'          => $phone,
            'zipcode'        => $zipcode,
            'city'           => $city,
            'department'     => $department,
            'age'            => $age,
            'gender'         => $gender,
            'partner_login'  => $partner_login,
            'partner_name'   => $row['partner_name'] ?? '',
            'thematic'       => $thematic,
            'source'         => 'ws-collection', // Identifiant fixe de cette source
        ];
    }

    $stmt->close();
    $conn->close();

} catch (\Throwable $th) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["success" => false, "message" => "Query error: " . $th->getMessage()]);
    exit;
}

// ── Réponse ───────────────────────────────────────────────
header("HTTP/1.1 200 OK");
header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "total"   => $total,
    "count"   => count($leads),
    "leads"   => $leads,
]);