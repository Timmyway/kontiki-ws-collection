<?php
require_once(__DIR__.'/../bootstrap/app.php');

use App\Controllers\AuthController;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    // login params
    $login = $input_data['email'] ?? null;
    $password = $input_data['password'] ?? null;

    //main programs
    if (empty($login)) {
        $response = array(
            "user" => NULL,
            "status" => "error",
            "message" => "Authentication error, missing login",
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } 
    elseif (empty($password)) {
        $response = array(
            "user" => NULL,
            "status" => "error",
            "message" => "Authentication error, missing passowrd",
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        $encrypt_mdp = base64_encode($password);
        //launch mode
        $loggedIn = AuthController::auth($login, $encrypt_mdp, $conn);
        if (empty($loggedIn)) {
            $response = array(
                "user" => NULL,
                "status" => "error",
                "message" => "Authentification failed, no matching credentials"
            );
            
            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            $response = array(
                "user" => $loggedIn,
                "status" => "success",
                "message" => "Authentification success"
            );
            
            header("HTTP/1.1 202 Accepted");
            echo json_encode($response);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";
            
    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "user" => NULL,
        "status" => "error",
        "message" => "Not authorized"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
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
<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Controllers\LeadsController;


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($_GET);
    /**
     * get page limit
     */
    $page_limit = isset($_GET['limit']) && $_GET['limit'] !== 'undefined' ? $_GET['limit'] : 50;
    /**
     * get page offset
     */
    $page_offset = isset($_GET['offset']) && $_GET['offset'] !== 'undefined' ? $_GET['offset'] : 0;

    if (empty($login_data['partname']) or empty($login_data['token'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            
            $res = LeadsController::get_leads($login_data['partname'], $page_offset, $page_limit, $partners, $conn);

            if (! $res) {
                header("HTTP/1.1 200 OK");
                echo 'Erreur';
                // echo "Get Nothing, maybe there is an errors.. try again";
            } else {                
                header("HTTP/1.1 200 OK");
                //testDebug($res);
                echo $res;                
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}

function testDebug($resp) {
	// Convert the array to a JSON string
	$jsonData = json_encode($resp, JSON_PRETTY_PRINT);

	// Specify the file path where you want to save the JSON data
	$filePath = 'output.json';

	// Write the JSON data to the file
	file_put_contents($filePath, $jsonData);

	// Check if the file write was successful
	if (file_exists($filePath)) {
		echo 'Data has been successfully written to the file.';
	} else {
		echo 'Error writing data to the file.';
	}
}
<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Controllers\LeadsController;



if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($_GET);
    /**
     * get page limit
     */
    $page_limit = isset($_GET['limit']) && $_GET['limit'] !== 'undefined' ? $_GET['limit'] : 50;
    /**
     * get page offset
     */
    $page_offset = isset($_GET['offset']) && $_GET['offset'] !== 'undefined' ? $_GET['offset'] : 0;

    if (empty($login_data['partname']) or empty($login_data['token'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            $res = LeadsController::get_discarded_leads($login_data['partname'], $page_offset, $page_limit, $conn);
            if (! $res) {
                header("HTTP/1.1 200 OK");
                echo "Get Nothing, maybe there is an errors.. try again";
            } else {
                header("HTTP/1.1 200 OK");
                echo $res;
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php

use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Providers\ExcelCreatorProvider;
use App\Providers\ExcelWriterProvider;

require_once('../bootstrap/app.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data);

    //main programs
    if (empty($login_data['partname'])
        or empty($login_data['token'])
        or empty($input_data['export_criteria'])
        or empty($input_data['export_reason'])
        or empty($input_data['export_label'])
    ) {
        $response = array(
            "id"              => NULL,
            "status"          => "error",
            "success"         => FALSE,
            "message"         => "Missing parameters",
            "export_reason"   => $input_data['export_reason'],
            "export_criteria" => $input_data['export_criteria'],
            "login"           => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            // Provide filepath.
            $filepath_main_title = $input_data['export_reason'] . '_leads_' . $input_data['export_label'] . '_';
            if ($input_data['export_criteria']["monthly"] === true) {
                $filepath_second_part = $input_data['export_criteria']["month_rank"] . '_' . $input_data['export_criteria']["year"] . ".xlsx";
            } else {
                $filepath_second_part = $input_data['export_criteria']["start_date"] . '_' . $input_data['export_criteria']["end_date"] . ".xlsx";
            }
            $filepath = '../storages/app/exports/' . $filepath_main_title . $filepath_second_part;

            // check if file already exist then delete it and re-download it.
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            // Proceed with the export.
            $export_data = LeadsController::downloadable_leads($login_data['partname'], $input_data['export_reason'], $input_data['export_criteria'], $conn);
            if ($export_data['rows']) {
                $ExcelCreatorProvider = new ExcelCreatorProvider($filepath, $export_data['headers']);
                $ExcelCreatorProvider->createFile();

                $ExcelWriterProvider = new ExcelWriterProvider($filepath);
                foreach ($export_data['rows'] as $row) {
                    $ExcelWriterProvider->appendRow(array_values($row));
                    $ExcelWriterProvider->save();
                }

                header("HTTP/1.1 202 Accepted");
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filepath_main_title . $filepath_second_part . '"');
                $ExcelWriterProvider->download();
            } else {
                header("HTTP/1.1 406 Not Acceptable");
                $response = array(
                    "status"  => "error",
                    "message" => "No such data to export.."
                );
                echo json_encode($response);
            }
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php
require_once '../bootstrap/app.php';

use App\Controllers\AssuranceAutoController;
use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;
use App\Providers\DeliveryDestinationProvider;
use App\Providers\PartnerProvider;
use App\Providers\TimeProvider;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /**
     * provide IP ADDRESS and USER_AGENT
     */
    $ip        = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $referer   = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "Aucun referer";

    /**
     * send data to webservice DWH.
     * If this is a test, do not send the data to webservice DWH.
     */
    $ch = curl_init();

    $gender = [
        "mr"  => "M",
        "mme" => "F",
    ];
    $api_data = [
        "origine"      => "BUDGETDEVIS",
        "datecollecte" => date('Y-m-d H:i:s'),
        "ip"           => $ip,
        "urlcollecte"  => $referer,
        "email"        => $input_data['email'],
        "birthdate"    => isset($input_data['birthdate']) && ! empty($input_data['birthdate']) ? date("Y-m-d", strtotime($input_data['birthdate'])) : null,
        "mobile"       => substr(preg_replace('/[^\d]/', '', $input_data['phone']), 0, 2) == "33" ? '0' . substr(preg_replace('/[^\d]/', '', $input_data['phone']), 2) : preg_replace('/[^\d]/', '', $input_data['phone']),
        "civility"     => $gender[$input_data['civility']],
        "lastname"     => $input_data['lastname'],
        "firstname"    => $input_data['firstname'],
        "adresse1"     => $input_data['address'] ?? "",
        "zipcode"      => $input_data['zipcode'] ?? "",
        "city"         => $input_data['city'] ?? "",
        "country"      => "FR",
    ];

    $query_string = http_build_query($api_data);
    $url          = "https://api.kontikimedia.com:5007/api/collecte?" . $query_string;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: NemUO2X7D21QDwPFa2TCYeZIbDXNwsAKi8eftLN1epQULvwJE4zMo7AXBgAO1jo8SKJgu0c88EGsq7FVrzBp9CAoFNiCD6zf3PN1l3mL0qbMbJvSZE4VntoF3cyeLKbW']);
    curl_exec($ch);
    curl_close($ch);

    /**
     * provide if the lead is deliverable direclty has value
     */
    $deliverable = $input_data['deliverable'] ?? null;

    /**
     * provide CITY location
     */

    if (isset($input_data['city']) && ! empty($input_data['city'])) {
        $city = $input_data['city'];
    } else if (isset($input_data['zipcode']) && ! empty($input_data['zipcode'])) {
        $city = CityProvider::check_city($input_data['zipcode']);
    } else {
        $city = null;
    }

    /**
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data, $ip, $city, $userAgent, $referer);

    //main programs
    if (
        empty($login_data['partname'])
        or empty($login_data['token'])
        or (empty($input_data['firstname']) && empty($input_data['lastname']) && empty($input_data['call_up_moment']))
        or (empty($input_data['phone']) && empty($input_data['call_up_moment']) && $input_data['call_up_moment'] !== null)
        or (empty($input_data['zipcode']) && empty($input_data['call_up_moment']))
        or (empty($input_data['email']) && empty($input_data['call_up_moment']))
    ) {
        $response = [
            "id"       => null,
            "status"   => "error",
            "success"  => false,
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "login"    => $login_data,
        ];

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == false) {
            $response = [
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "login"   => $login_data,
            ];

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            $birthdate = $input_data['birthdate'] ?? null;
            if (! empty($birthdate)) {
                $d = \DateTime::createFromFormat('Y-m-d', $birthdate);
                if ($d) {
                    $year = (int) $d->format('Y');

                    if ($year < 1000) {
                        if ($year < 10) {
                            // 3 zéros devant (ex: 0002) → 200X
                            $year = (int) ('200' . $year);
                        } else {
                            // 2 zéros devant (ex: 0095) → 19XX
                            $year = (int) ('19' . $year);
                        }

                        $birthdate = $year . '-' . $d->format('m') . '-' . $d->format('d');
                        $d         = \DateTime::createFromFormat('Y-m-d', $birthdate);
                    }

                    // vérifie age >= 18
                    if ((new \DateTime())->diff($d)->y < 18) {
                        $conn->close();
                        header("HTTP/1.1 400 Bad Request");
                        echo json_encode([
                            "status"  => "error",
                            "message" => "Invalid birthdate: age must be 18+",
                        ]);
                        exit;
                    }

                    // met à jour la birthdate corrigée
                    $input_data['birthdate'] = $birthdate;
                    $lead['birthdate']       = $birthdate;
                }
            }

            // save lead info to DB leads table
            $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
            // $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
            // append last insert ID to the lead payload because we may need it in direct delivery mode.
            $lead["lead_id"] = $last_insert_id;

            /**
             * save lead tags specifics data to the corresponding tags table
             * $partners variable is from bootstrap app imported in the top.
             */
            if (in_array($login_data['partname'], $partners["travaux"])) {
                /**
                 * create lead travaux DATA payload
                 * then save travaux data to table travaux of the database.
                 */

                $travaux_data = TravauxController::makeTravauxData($input_data);

                TravauxController::save_travaux(
                    $conn, $login_data, $lead, $travaux_data, $partners["travaux_kontiki_ID"][$login_data['partname']]
                );

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $travaux_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then save defisc data to the "defiscalisation" table of the DB.
                 */
                $defisc_data = DefiscController::makeDefiscData($input_data);
                DefiscController::save_defisc($conn, $login_data, $lead, $defisc_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $defisc_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead assurance DATA payload
                 * then save the payload to the "assurances" table of the DB.
                 */
                $assurances_data = AssuranceController::makeAssuranceData($input_data);
                AssuranceController::save_assurance($conn, $login_data, $lead, $assurances_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $assurances_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead security DATA payload
                 * then save the payload to the "securities" table of the DB.
                 */
                $security_data = SecurityController::makeSecurityData($input_data);
                SecurityController::save_security($conn, $login_data, $lead, $security_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $security_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formation DATA payload
                 * then save the payload to the "formations" table of the DB.
                 */
                $formation_data = FormationController::makeFormationData($input_data);
                FormationController::save_formation($conn, $login_data, $lead, $formation_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $formation_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead rachat de credits DATA payload
                 * then save the payload to the "rachat_de_credits" table of the DB.
                 */
                $rac_data = RachatCreditController::makeRacData($input_data);
                RachatCreditController::save_rac($conn, $login_data, $rac_data, $lead);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $rac_data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                /**
                 * create lead assurance_auto DATA payload
                 * then save the payload to the "assurance_auto" table of the DB.
                 */
                $assurance_auto_data = AssuranceAutoController::makeData($input_data);
                AssuranceAutoController::save($conn, $login_data, $lead, $assurance_auto_data);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */
                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                /**
                 * create lead assurance_sante DATA payload
                 * then save the payload to the "assurance_sante" table of the DB.
                 */

                $assurance = AssuranceSanteController::makeData($input_data);

                AssuranceSanteController::save($conn, $login_data, $lead, $assurance);

                // if delivery mode is SET, deliver lead directly
                if (! empty($deliverable)) {
                    /**
                     * Dispatch direct delivery to their clients
                     */

                    DeliveryDestinationProvider::dispacth(
                        $deliverable,
                        $lead,
                        $data,
                        TimeProvider::getTime(),
                        $login_data['partname'],
                        $clients,
                        $conn
                    );
                }
            }

            if ($last_insert_id) {

                $rawName = PartnerProvider::getName($conn, $login_data['partname']);

                if (! empty($rawName)) {
                    $thematique = str_replace(
                        ["BudgetDevis-", "-", "_"],
                        ["", " ", " "],
                        $rawName
                    );

                    // format propre (majuscule première lettre)
                    $thematique = ucfirst(trim($thematique));
                } else {
                    $thematique = "Non défini";
                }

                /**
                 * ✅ Payload email
                 */
                $emailPayload = [
                    "firstname"  => $input_data['firstname'] ?? "",
                    "lastname"   => $input_data['lastname'] ?? "",
                    "email"      => $input_data['email'] ?? "",
                    "phone"      => $input_data['phone'] ?? "",
                    "zipcode"    => $input_data['zipcode'] ?? "",
                    "thematique" => $thematique,
                    "source"     => $referer,
                ];

                /**
                 * ✅ Envoi email (non bloquant)
                 */
                if (! empty($emailPayload['email'])) {

                    // $chEmail = curl_init("https://budgetdevis.com/accueil/api/lead-confirmation");
                    $chEmail = curl_init("http://budgetdevis.local/api/lead-confirmation");

                    curl_setopt($chEmail, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chEmail, CURLOPT_POST, true);
                    curl_setopt($chEmail, CURLOPT_POSTFIELDS, json_encode($emailPayload));
                    curl_setopt($chEmail, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ]);

                    curl_setopt($chEmail, CURLOPT_TIMEOUT, 2);

                    $responseEmail = curl_exec($chEmail);

                    if (curl_errno($chEmail)) {
                        error_log("Email error: " . curl_error($chEmail));
                    }

                    curl_close($chEmail);
                }

                // close connexion
                $conn->close();

                // response
                $response = [
                    "id"      => $last_insert_id,
                    "status"  => "success",
                    "message" => "Lead registered Successfully..",
                ];

                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                // return message
                $response = [
                    "id"      => $last_insert_id,
                    "status"  => "error",
                    "message" => "Error saving lead data.. ",
                ];

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {

    $response = [
        "status"  => "error",
        "message" => "Something went wrong",
    ];

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Services\PingService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /** 
    * create login DATA payload
    */
    $login_data = LoginController::makeLoginData($input_data);

    /** 
    * create ping DATA payload
    */
    $ping_data = array(
        "zipcode" => $input_data['zipcode'] ?? null,
        "city" => $input_data['city'] ?? null
    );
    
    /** 
    * SPECIAL input_DATA => CLIENTS ID given by the Frontend.
    */
    $clientID = $input_data['clientID'] ?? null;

    //main programs
    if (empty($clientID) or empty($login_data['partname']) or empty($login_data['token']) or empty($ping_data['zipcode']) or empty($ping_data['city'])) {
        $response = array(
            "id" => NULL,
            "status" => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data" => $input_data
        );
            
        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status" => "error",
                "message" => "No maching 'fournisseurs'",
                "data" => $login_data
            );
            
            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            // ping response response
            $ping_properties = new PingService($ping_data, $clientID);
            $ping_status = $ping_properties->ping();

            if($ping_status){
                $response = array(
                    "ping" => true,
                    "status" => "success",
                    "message" => "pre-matching success.."
                );
                
                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {
                $response = array(
                    "ping" => false,
                    "status" => "error",
                    "message" => 'pre-matching error, departement not allowed at this time'
                );
                
                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";
            
    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status" => "error",
        "message" => "Something went wrong"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php 
require_once('../bootstrap/app.php');

use App\Providers\SftpUploaderProvider;

function upload($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path)
{
    try {
        $sftp_uploader = new SftpUploaderProvider($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path);
        $local_path = '../storages/app/energy/eni/';
        // $date = date('d-m-Y', strtotime('-1 day'));
        $date = date('d-m-Y');
        // $date = date('d-m-Y');
        $local_file = 'eni_leads_' . $date . ".xlsx";
        if (file_exists($local_path.$local_file)) {
            $sftp_uploader->uploadFile($local_path, $local_file);
            return "File uploaded successfully.";
        } else {
            return "No such file to upload today.";
        }
    } catch (\Throwable $th) {
        return "Error: " . $th;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $eni_sftp_host = $sftp["eni"]["host"];
    $eni_sftp_username = $sftp["eni"]["username"];
    $eni_sftp_password = $sftp["eni"]["password"];
    $eni_sftp_port = $sftp["eni"]["port"];
    $eni_sftp_upload_path = $sftp["eni"]["upload_path"];
    
    $eni_log = upload($eni_sftp_host, $eni_sftp_port, $eni_sftp_username, $eni_sftp_password, $eni_sftp_upload_path);

    // log schedule
    $logPath = '../logs/eni/eni_logs_' . date('d-m-Y') . '.txt';
    if (file_exists($logPath)) {
        file_put_contents($logPath, $eni_log. " " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    } else {
        file_put_contents($logPath, $eni_log. " " . date('Y-m-d H:i:s') . "\n");
    }

    echo "Operation complete...\n$eni_log";
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";
            
    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status" => "error",
        "message" => "Something went wrong"
    );
            
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php

use App\Controllers\AssuranceAutoController;
use App\Services\SendFinancesService;
require_once('../bootstrap/app.php');

use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\LoginController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\StatisticsController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;
use App\Providers\TimeProvider;
use App\Services\SendAssuranceAutoService;
use App\Services\SendAssuranceService;
use App\Services\SendTravauxService;
use App\Services\SendDefiscService;
use App\Services\SendFormationService;
use App\Services\SendSecurityService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /** 
     * provide IP ADDRESS
     */
    $ip = $input_data['classics']['ip'];
    $userAgent = $input_data['classics']['userAgent'] ?? null;
    $referer = $input_data['classics']['referer'] ?? null;

    /** 
     * provide CITY location
     */
    $city = (isset($input_data['classics']['city']) && ! empty($input_data['classics']['city']))
        ? $input_data['classics']['city']
        : CityProvider::check_city($input_data['classics']['zipcode']);

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data['login']);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data['classics'], $ip, $city, $userAgent, $referer);

    /** 
     * SPECIAL input_DATA => CLIENTS ID given by the Frontend.
     */
    $clientID = $input_data['clientID'] ?? null;

    //main programs
    if (empty($login_data['partname'])
        or empty($login_data['token'])
        or empty($clientID)
        or empty($lead['lead_id'])
        or (empty($lead['phone']) && empty($input_data['securities']['canal']))
        or (empty($lead['zipcode']) && empty($input_data['securities']['canal']))
        or (empty($lead['email']) && empty($input_data['securities']['canal']))
    ) {
        $response = array(
            "id"       => NULL,
            "status"   => "error",
            "success"  => FALSE,
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "login"    => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {

            /**
             * send all information to the client according to it's tags and mandatory
             * $partners variable is from bootstrap app imported in the top.
             */
            if (in_array($login_data['partname'], $partners["travaux"])) {
                /**
                 * create lead travaux DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $travaux_data    = TravauxController::makeTravauxData($input_data['travaux']);
                $lead_properties = new SendTravauxService($lead, $travaux_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
               
                $defisc_data     = DefiscController::makeDefiscData($input_data['defisc']);
                $lead_properties = new SendDefiscService($lead, $defisc_data, $clientID, TimeProvider::getTime());
                
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead insurances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                
                $insurance_data  = isset($input_data['insurances']) ? AssuranceController::makeAssuranceData($input_data['insurances']) : [];
                $lead_properties = new SendAssuranceService($lead, $insurance_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formations DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $formations_data = FormationController::makeFormationData($input_data['formations']);
                $lead_properties = new SendFormationService($lead, $formations_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead securities DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $security_data   = SecurityController::makeSecurityData($input_data['securities']);
                $lead_properties = new SendSecurityService($lead, $security_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $finances_data   = RachatCreditController::makeRacData($input_data['finances']);
                $lead_properties = new SendFinancesService($lead, $finances_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $assurance_auto_data   = AssuranceAutoController::makeData($input_data['cars']);
                $lead_properties = new SendAssuranceAutoService($lead, $assurance_auto_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                /**
                 * create lead finances DATA payload
                 * then send the payload with the lead classics info to the clients.
                 */
                $assurance_data   = AssuranceSanteController::makeData($input_data['insurances']);
                $lead_properties = new SendAssuranceService($lead, $assurance_data, $clientID, TimeProvider::getTime());
                $send_response   = $lead_properties->send();
            }

            /**
             * SAVE CLIENTS responses into the DB after sending the lead.
             */
            LeadsController::save_leads_has_clients($conn, TimeProvider::getTime(), $login_data['partname'], $lead['lead_id'], $send_response, $clients[$clientID]);
            StatisticsController::setStat($conn, date('Y-m'));

            /**
             * Returning RESPONSE STATUS to the Frontend.
             */
            if ($send_response['status'] != 'success') {

                // close the connexion
                $conn->close();

                $response = array(
                    "id"      => $lead['lead_id'],
                    "status"  => "error",
                    "message" => 'Error when sending lead.. ' . $send_response['description']
                );

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                $response = array(
                    "id"      => $lead['lead_id'],
                    "status"  => "success",
                    "message" => "Lead sent Successfully.."
                );

                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php

use App\Controllers\StatisticsController;

require_once('../bootstrap/app.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    return null;
}

$items = StatisticsController::getStat($conn, $_GET);

echo json_encode($items);
<?php
require_once('../bootstrap/app.php');

use App\Controllers\AssuranceAutoController;
use App\Controllers\AssuranceController;
use App\Controllers\AssuranceSanteController;
use App\Controllers\LoginController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;
use App\Providers\CityProvider;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }
    
    /** 
     * provide IP ADDRESS
     */
    $ip = $input_data['classics']['ip'];
    $userAgent = $input_data['classics']['userAgent'] ?? null;
    $referer = $input_data['classics']['referer'] ?? null;
    /** 
     * provide CITY location
     */
    $city = (isset($input_data['classics']['city']) && ! empty($input_data['classics']['city']))
        ? $input_data['classics']['city']
        : CityProvider::check_city($input_data['classics']['zipcode']);

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($input_data['login']);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data['classics'], $ip, $city, $userAgent, $referer);

    //main programs
    if (empty($login_data['partname']) or empty($login_data['token']) or empty($lead['lead_id'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => array(
                "login"   => $login_data['partname'],
                "token"   => $login_data['token'],
                "lead_id" => $lead['lead_id'],
            )
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            // set lead call status to not callable
            $success_update_lead = LeadsController::update_lead($conn, $lead);

            if (! $success_update_lead) {
                $response = array(
                    "status"  => "error",
                    "message" => "Error when updating lead.. "
                );

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            } else {

                /**
                 * update lead tags specifics data to the corresponding tags table
                 * $partners variable is from bootstrap app imported in the top.
                 */
                if (in_array($login_data['partname'], $partners["travaux"])) {
                    $travaux_data        = TravauxController::makeTravauxData($input_data['travaux']);
                    $success_update_tags = TravauxController::update_travaux($conn, $lead, $travaux_data);
                    /**
                     * leads tag travaux updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                    $defisc_data         = DefiscController::makeDefiscData($input_data['defisc']);
                    $success_update_tags = DefiscController::update_defisc($conn, $lead, $defisc_data);
                    /**
                     * leads tag defisc updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                    $assurance_data      = isset($input_data['insurances']) ? AssuranceController::makeAssuranceData($input_data['insurances']) : [];
                    $success_update_tags = AssuranceController::update_assurance($conn, $lead, $assurance_data);
                    /**
                     * leads tag insurances updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["formations"])) {
                    $formations_data     = FormationController::makeFormationData($input_data['formations']);
                    $success_update_tags = FormationController::update_formation($conn, $lead, $formations_data);
                    /**
                     * leads tag formations updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["securities"])) {
                    $security_data       = SecurityController::makeSecurityData($input_data['securities']);
                    $success_update_tags = SecurityController::update_security($conn, $lead, $security_data);
                    /**
                     * leads tag securities updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["finances"])) {
                    $rac_data            = RachatCreditController::makeRacData($input_data['finances']);
                    $success_update_tags = RachatCreditController::update_rac($conn, $lead, $rac_data);
                    /**
                     * leads tag finances updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["assurance_auto"])) {
                    $assurance_auto_data = AssuranceAutoController::makeData($input_data['cars']);
                    $success_update_tags = AssuranceAutoController::update($conn, $lead, $assurance_auto_data);
                    /**
                     * leads tag assurance_auto updated.
                     */
                } elseif (in_array($login_data['partname'], $partners["assurance"])) {
                    
                    $assurance_data = AssuranceSanteController::makeData($input_data['insurances']);
                    $success_update_tags = AssuranceSanteController::update($conn, $lead, $assurance_data);
                    /**
                     * leads tag assurance updated.
                     */
                }


                if (! $success_update_tags) {

                    // close database connexion
                    mysqli_close($conn);

                    // return response
                    $response = array(
                        "status"  => "error",
                        "message" => "Error when updating table travaux data.. "
                    );

                    header("HTTP/1.1 406 Not Acceptable");
                    echo json_encode($response);
                } else {

                    // close database connexion
                    mysqli_close($conn);

                    // return response
                    $response = array(
                        "status"  => "success",
                        "message" => "Discard lead Success.."
                    );

                    header("HTTP/1.1 202 Accepted");
                    echo json_encode($response);
                }
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php
require_once('../bootstrap/app.php');

use App\Controllers\LoginController;
use App\Controllers\LeadsController;



if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    /** 
     * create login DATA payload
     */
    $login_data = LoginController::makeLoginData($_GET);
    /**
     * get page limit
     */
    $page_limit = isset($_GET['limit']) && $_GET['limit'] !== 'undefined' ? $_GET['limit'] : 50;
    /**
     * get page offset
     */
    $page_offset = isset($_GET['offset']) && $_GET['offset'] !== 'undefined' ? $_GET['offset'] : 0;

    if (empty($login_data['partname']) or empty($login_data['token'])) {
        $response = array(
            "id"      => NULL,
            "status"  => "error",
            "success" => FALSE,
            "message" => "Missing parameters",
            "data"    => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {
        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == FALSE) {
            $response = array(
                "status"  => "error",
                "message" => "No maching 'fournisseurs'",
                "data"    => $login_data
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            $res = LeadsController::get_leads_has_clients($login_data['partname'], $page_offset, $page_limit, $conn);
            if (! $res) {
                header("HTTP/1.1 200 OK");
                echo "Get Nothing, maybe there is an errors.. try again";
            } else {
                header("HTTP/1.1 200 OK");
                echo $res;
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "status"  => "error",
        "message" => "Something went wrong"
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}
<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class AssuranceAutoController
{
    /**
     * Method that provide assurance data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeData($input_data) 
    {
        /**
        * create lead assurance auto DATA payload
        */
        $data = array(
            "registration" => $input_data['registration'] ?? '',
            "brand" => $input_data['brand'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "date_insured" => $input_data['date_insured'] ?? '',

        );

        return $data;
    }

    /**
     * Method for saving assurance data inner assurance table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return void
     */
    public static function save($conn, $login_data, $lead, $data)
    {
        try {
            $query = SavesRequests::save_assurance_auto_query($data, $lead, $login_data);
            // file_put_contents('save_assurance_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_assurance_error.txt', $th);
        }
    }

    /**
     * Method to update the assurance data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return bool
     */
    public static function update($conn, $lead, $data)
    {
        $encoded_registration = base64_encode($data['registration']);
        $encoded_brand = base64_encode($data['brand']);
        $encoded_date_insured = base64_encode($data['date_insured']);
        $encoded_custom_field_1 = base64_encode($data['custom_field_1']);
        $encoded_custom_field_2 = base64_encode($data['custom_field_2']);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurance_auto SET registration=?, brand=?, custom_field_1=?, custom_field_2=?, date_insured=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssi', $encoded_registration, $encoded_brand, $encoded_custom_field_1, $encoded_custom_field_2, $encoded_date_insured, $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_assurance_auto_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class AssuranceController
{
    /**
     * Method that provide assurance data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeAssuranceData($input_data) 
    {
        
        /**
        * create lead assurance DATA payload
        */
        $assurance_data = array(
            "bank" => $input_data['bank_assurance'] ?? '',
            "bien" => $input_data['property_assurance'] ?? '',
            "objectif" => $input_data['objectif_assurance'] ?? '',
            "fumeur" => $input_data['fumeur'] ?? '',
            "profession" => $input_data['profession'] ?? $input_data['situationPro'] ?? '',
            "amount" => $input_data['montant_pret'] ?? '',
            "rate" => $input_data['taux_pret'] ?? '',
            "duration" => $input_data['duree_pret'] ?? '',
            "quote_type" => $input_data['quote_type'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "custom_field_3" => $input_data['custom_field_3'] ?? '',
            "custom_field_4" => $input_data['custom_field_4'] ?? '',
            "custom_field_5" => $input_data['custom_field_5'] ?? '',
            "custom_field_6" => $input_data['custom_field_6'] ?? '',
            "custom_field_7" => $input_data['custom_field_7'] ?? '',
            "custom_field_8" => $input_data['custom_field_8'] ?? '',
        );

        return $assurance_data;
    }

    /**
     * Method for saving assurance data inner assurance table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return void
     */
    public static function save_assurance($conn, $login_data, $lead, $assurance_data)
    {
        try {
            $query = SavesRequests::save_assurance_query($assurance_data, $lead, $login_data);
            // file_put_contents('save_assurance_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_assurance_error.txt', $th);
        }
    }

    /**
     * Method to update the assurance data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return bool
     */
    public static function update_assurance($conn, $lead, $assurance_data)
    {
        $encoded_amount = isset($assurance_data['amount']) ? base64_encode($assurance_data['amount']) : '';
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurances SET montant_pret=?, taux_pret=?, duree_pret=?, quote_type=?, professionnal_situation=?, custom_field_1=?, custom_field_2=?, custom_field_3=?, custom_field_4=?, custom_field_5=?, custom_field_6=? , custom_field_7=?, custom_field_8=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssssssssssi', $encoded_amount, $assurance_data['rate'], $assurance_data['duration'], $assurance_data['quote_type'], $assurance_data["profession"], $assurance_data['custom_field_1'], $assurance_data['custom_field_2'], $assurance_data['custom_field_3'], $assurance_data['custom_field_4'], $assurance_data['custom_field_5'], $assurance_data['custom_field_6'], $assurance_data['custom_field_7'], $assurance_data['custom_field_8'], $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_assurance_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;
use App\Requests\SavesRequests;

class AssuranceSanteController
{
    /**
     * Method that provide assurance data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeData($input_data) 
    {
        /**
        * create lead assurance auto DATA payload
        */
        $data = array(
            "besoin" => $input_data['besoin'] ?? null,
            "regime_social" => $input_data['regime_social'] ?? null,
            "profession" => $input_data['profession'] ?? null,
            "profession_compl" => $input_data['profession_compl'] ?? null,
            "situation_famille" => $input_data['situation_famille'] ?? null,
            "nombre_enfant" => $input_data['nombre_enfant'] ?? 0,
            "assurer_conjoint" => $input_data['assurer_conjoint'] ?? null,
            "cid" => $input_data['cid'] ?? null,
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
            "custom_field_3" => $input_data['custom_field_3'] ?? '',
            "custom_field_4" => $input_data['custom_field_4'] ?? '',
            "custom_field_5" => $input_data['custom_field_5'] ?? '',
            "custom_field_6" => $input_data['custom_field_6'] ?? '',
            
        );

        return $data;
    }

    /**
     * Method for saving assurance data inner assurance table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return void
     */
    public static function save($conn, $login_data, $lead, $data)
    {
        try {
            $query = SavesRequests::save_assurance_sante_query($data, $lead, $login_data);
            // file_put_contents('save_assurance_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_assurance_sante_error.txt', $th);
        }
    }

    /**
     * Method to update the assurance data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $assurance_data
     * @return bool
     */
    public static function update($conn, $lead, $data)
    {
        // $encoded_besoin = base64_encode($data['besoin']);
        // $encoded_regime_social = base64_encode($data['regime_social']);
        // $encoded_profession = base64_encode($data['profession']);
        // $encoded_profession_compl = base64_encode($data['profession_compl']);
        // $encoded_situation_famille = base64_encode($data['situation_famille']);
        // $encoded_assurer_conjoint = base64_encode($data['assurer_conjoint']);
        // $encoded_cid = base64_encode($data['cid']);
        $encoded_besoin = base64_encode($data['besoin'] ?? '');
        $encoded_regime_social = base64_encode($data['regime_social'] ?? '');
        $encoded_profession = base64_encode($data['profession'] ?? '');
        $encoded_profession_compl = base64_encode($data['profession_compl'] ?? '');
        $encoded_situation_famille = base64_encode($data['situation_famille'] ?? '');
        $encoded_assurer_conjoint = base64_encode($data['assurer_conjoint'] ?? '');
        $encoded_cid = base64_encode($data['cid'] ?? '');
        $encode_custom_field_1 = base64_encode($data['custom_field_1'] ?? '');
        $encode_custom_field_2 = base64_encode($data['custom_field_2'] ?? '');
        $encode_custom_field_3 = base64_encode($data['custom_field_3'] ?? '');
        $encode_custom_field_4 = base64_encode($data['custom_field_4'] ?? '');
        $encode_custom_field_5 = base64_encode($data['custom_field_5'] ?? '');
        $encode_custom_field_6 = base64_encode($data['custom_field_6'] ?? '');
        
       
        try {
            $stmt = mysqli_prepare($conn, "UPDATE assurance SET besoin=?, regime_social=?, profession=?, profession_compl=?, situation_famille=?, nombre_enfant=?, assurer_conjoint=?, custom_field_1=?, custom_field_2=?, cid=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'ssssssssssssssi', $encoded_besoin, $encoded_regime_social, $encoded_profession, $encoded_profession_compl, $encoded_situation_famille, $data['nombre_enfant'], $encoded_assurer_conjoint, $encoded_cid, $encode_custom_field_1, $encode_custom_field_2, $encode_custom_field_3, $encode_custom_field_4, $encode_custom_field_5, $encode_custom_field_6, $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_assurance_sante_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;


class AuthController
{
    /**
     * LeadIT authentication controller.
     * @param mixed $email
     * @param mixed $mdp
     * @param mixed $conn
     * @return array|bool|null
     */
    public static function auth($email, $mdp, $conn)
    {
        try {
            $query = "select * from users_validation where email = ? and mdp = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $query);

            // Bind parameters to statement
            mysqli_stmt_bind_param($stmt, "ss", $email, $mdp);

            // Execute statement
            mysqli_stmt_execute($stmt);

            // Get result set
            $result = mysqli_stmt_get_result($stmt);

            // Fetch all rows as associative arrays
            $row = mysqli_fetch_assoc($result);
            
            // Close statement and connection
            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            return $row;
        } catch (\Throwable $th) {
            return null;
        }
    }
    

}
<?php

namespace App\Controllers;
use App\Requests\SavesRequests;


class DefiscController
{
    /**
     * Method that provide defiscalisation data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeDefiscData($input_data) {
        /**
        * create lead defiscalisation DATA payload
        */
        $defisc_data = array(
            "impot" => $input_data['impot'] ?? null, //prélèvement impot
            "impotAnnuel" => $input_data['impotAnnuel'] ?? null,
            "project_type" => $input_data['project_type'] ?? null, // (immobilier || placement)
            "children" => $input_data['children'] ?? null, // (number of children in charge)
            "revenuMensuel" => $input_data['revenuMensuel'] ?? null,
            "apportPerso" => $input_data['apportPerso'] ?? null, // (apport personnel)
            "epargneMensuel" => $input_data['epargneMensuel'] ?? null // (capacité d'épargne mensuel)
        );

        return $defisc_data;
    }

    /**
     * Method for saving defisc data inner defiscalisation table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $defisc_data
     * @return void
     */
    public static function save_defisc($conn, $login_data, $lead, $defisc_data)
    {
        try {
            $query = SavesRequests::save_defisc_query($defisc_data, $lead, $login_data);
            // file_put_contents('save_mutuelle_query.txt', $query);
            $conn->query($query);
            $conn->commit();
        } catch (\Throwable $th) {
            file_put_contents('save_defiscalisation_error.txt', $th);
        }
    }

    /**
     * Method to update the defiscalisation data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $defisc_data
     * @return bool
     */
    public static function update_defisc($conn, $lead, $defisc_data){
        try {
            $impotAnnuel = base64_encode($defisc_data['impot']);
            // $query = "update leads set call_status = 'not callable' where id = $id";
            $stmt = mysqli_prepare($conn, "UPDATE defiscalisation SET status_leads=?, matrimonialSituation=?, imposition=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssi', $lead['situation'], $lead['matrimoniale'], $impotAnnuel, $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_defisc_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class FormationController
{
    /**
     * Method that provide formations data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeFormationData($input_data)
    {
        /**
         * create lead formations DATA payload
         */
        if (
            (isset($input_data['salarieDuPriveOuTravailleurIndependant']) && ! empty($input_data['salarieDuPriveOuTravailleurIndependant']))
            && (isset($input_data['travailleDepuisCesDernieresAnnees']) && ! empty($input_data['travailleDepuisCesDernieresAnnees']))
            && (isset($input_data['retraite']) && ! empty($input_data['retraite']))) {
            $situationPro = $input_data['salarieDuPriveOuTravailleurIndependant']
                . "\n" . $input_data['travailleDepuisCesDernieresAnnees']
                . "\n" . $input_data['retraite'];
        } elseif (isset($input_data['situationPro']) && ! empty($input_data['situationPro'])) {
            $situationPro = $input_data['situationPro'];
        } else {
            $situationPro = "";
        }

        // payload for formation data.
        $formation_data = array(
            "situationPro" => $situationPro,
            "langue"       => $input_data['langue'] ?? null,
        );

        return $formation_data;
    }

    /**
     * Method for saving formations data inner formations table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $formation_data
     * @return void
     */
    public static function save_formation($conn, $login_data, $lead, $formation_data)
    {
        try {
            $query = SavesRequests::save_formation_query($formation_data, $lead, $login_data);
            // file_put_contents('save_formation_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_formation_error.txt', $th);
        }
    }

    /**
     * Method to update the formations data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $formation_data
     * @return bool
     */
    public static function update_formation($conn, $lead, $formation_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE formations SET situationPro=?, langue=?, custom_field_1=?, custom_field_2=?, custom_field_3=?, custom_field_4=?, custom_field_5=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssssssi', $formation_data['situationPro'], $formation_data['langue'], $formation_data['custom_field_1'], $formation_data['custom_field_2'], $formation_data['custom_field_3'], $formation_data['custom_field_4'], $formation_data['custom_field_5'], $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_formation_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;

use App\Providers\ConnexionProvider;
use App\Providers\PhoneNumberProvider;
use App\Providers\TimeProvider;
use App\Requests\CurrentRequests;
use App\Requests\DiscardedRequests;
use App\Requests\SavesRequests;
use App\Requests\ValidatedRequests;


class LeadsController
{
    /**
     * Method to Decode response to send to the clients.
     * @param mixed $array
     * @return array
     */
    public static function decodeResponse($array) : array
    {
        foreach ($array as $key => $value) {
            if($key === 'id'){
                continue;
            }
            if (is_array($value)) {
                // If the value is an array, call this function recursively.
                $array[$key] = LeadsController::decodeResponse($value);
            } else {
                // If the value is not an array, try to decode it.
                // $isCorrectBase64 = LeadsController::isBase64Encoded($value);
                if (!empty($value) && is_string($value)) {
                    $decoded = addslashes(base64_decode($value));
                } else {
                    $decoded = '';
                }
                // $decoded = addslashes(base64_decode($value));
                if (mb_check_encoding($decoded, 'UTF-8') && $decoded !== "") {
                    // If the value was successfully decoded, replace it with the decoded version.

                    // check if $value is a phone number
                    if ($key === 'phone') {
                        $array[$key] = PhoneNumberProvider::remove_plus_33(PhoneNumberProvider::remove_whitespace($decoded));
                    } else if (in_array($key, ['firstname', 'lastname', 'city'])) {
                        $array[$key] = ucfirst(strtolower($decoded));
                    } else {
                        $array[$key] = $decoded;
                    }
                }
                // If the value was not successfully decoded, leave it as it is.

                // check if $value is a date and transform it into a good date format.
                if (in_array($key, ['receive_date', 'validation_date', 'discard_date'])) {
                    $array[$key] = TimeProvider::frenchFormat($value);
                }
            }
        }
        return $array;
    }

    /**
     * Method to provid classics data right format.
     * @param mixed $input_data
     * @param mixed $ip
     * @param mixed $city
     * @return array
     */
    public static function makeClassicsData($input_data, $ip, $city, $userAgent, $referer)
    {
        if (isset($input_data['id_base'])) {
            $base_id = $input_data['id_base'];
        } else {
            $base_id = $input_data['affiliateID'];
        }

        /**
         * create lead classics DATA payload
         */
        $lead = array(
            "lead_id"          => $input_data['id'] ?? null,
            "civility"         => $input_data['civility'] ?? null,
            "birthdate"        => $input_data['birthdate'] ?? null,
            "firstname"        => $input_data['firstname'] ?? null,
            "lastname"         => $input_data['lastname'] ?? null,
            "email"            => $input_data['email'] ?? null,
            "phone"            => $input_data['phone'] ?? null,
            "zipcode"          => $input_data['zipcode'] ?? null,
            "city"             => $city ?? null,
            "address"          => $input_data['address'] ?? null,
            "geo"              => $input_data['geo'] ?? 'FR',
            "situation"        => $input_data['situation'] ?? null, // (Proprietaire | Locataire)
            "matrimoniale"     => $input_data['matrimoniale'] ?? null, // (Célibataire | marié etc...)
            "situationPro"     => $input_data['situationPro'] ?? null, // (situation professionnel ex: Salarié | en activité | retraité)
            "affiliateID"      => $base_id ?? 0,
            "ip"               => $ip,
            "userAgent"        => $userAgent,
            "referer"          => $referer,
            "description_call" => $input_data['description_call'] ?? null, // for comment if needed
            "call_up_moment"   => $input_data['call_up_moment'] ?? null, // (*mandatory for re-call module)
            "user_sender"      => $input_data['user_sender'] ?? null, // the user that treated the lead
            "user_action"      => $input_data['user_action'] ?? "commenting", // (depends on the user api call action : discarding | commenting | sending)
            "receive_date"     => $input_data['receive_date'] ?? null,
        );

        return $lead;
    }

    /**
     * Method to get lists of "not" validated leads.
     * @param mixed $partenaire
     * @param mixed $offset
     * @param mixed $limit
     * @param mixed $partners
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_leads($partenaire, $offset, $limit, $partners, $conn)
    {
        try {
            /** 
             * first get list of leads
             */
            $list_query = CurrentRequests::which_query($partenaire, $offset, $limit, $partners);
            $rows       = ConnexionProvider::fetch_all($conn, $list_query);


            /** 
             * next get leads count.
             */
            $count_query = CurrentRequests::which_count_query($partenaire, $partners);
            $leads_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays leads count.
             */
            $today_count_query = CurrentRequests::which_todays_count_query($partenaire, $partners);
            $today_leads_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($rows),
                "count"        => $leads_count['totals'],
                "todays_count" => $today_leads_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to get lists of validated leads.
     * @param mixed $partenaire
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_leads_has_clients($partenaire, $offset, $limit, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            $validated_query = ValidatedRequests::validated_query($partenaire, $offset, $limit);
            $validated_rows  = ConnexionProvider::fetch_all($conn, $validated_query);


            /** 
             * next get validated leads count.
             */
            $count_query     = ValidatedRequests::validated_count_query($partenaire);
            $validated_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays validated leads count.
             */
            $today_count_query     = ValidatedRequests::todays_validated_count_query($partenaire);
            $today_validated_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($validated_rows),
                "count"        => $validated_count['totals'],
                "todays_count" => $today_validated_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to download leads validated or discarded.
     * @param string $partenaire
     * @param string $export_reason
     * @param mixed $export_criteria
     * @param mixed $conn
     * @return array|null
     */
    public static function downloadable_leads($partenaire, $export_reason, $export_criteria, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            if ($export_reason === 'validated') {
                $downloadable_query = ValidatedRequests::validated_downloadable_query($partenaire, $export_criteria);
            } else {
                $downloadable_query = DiscardedRequests::discarded_downloadable_query($partenaire, $export_criteria);
            }
            $downloadable_rows = ConnexionProvider::fetch_all($conn, $downloadable_query);

            // close the connexion
            mysqli_close($conn);

            if (count($downloadable_rows) > 0) {
                // assemble the final results
                $final_result = array(
                    "headers" => array_keys($downloadable_rows[0]),
                    "rows"    => LeadsController::decodeResponse($downloadable_rows)
                );


                return $final_result;
            }
            return null;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method to get lists of discarded leads.
     * @param mixed $partenaire
     * @param mixed $conn
     * @return bool|string|null
     */
    public static function get_discarded_leads($partenaire, $offset, $limit, $conn)
    {
        try {
            /** 
             * first get list of validated leads
             */
            $discarded_query = DiscardedRequests::discarded_query($partenaire, $offset, $limit);
            $discarded_rows  = ConnexionProvider::fetch_all($conn, $discarded_query);


            /** 
             * next get validated leads count.
             */
            $count_query     = DiscardedRequests::discarded_count_query($partenaire);
            $discarded_count = ConnexionProvider::fetch_count($conn, $count_query);


            /** 
             * next get todays validated leads count.
             */
            $today_count_query     = DiscardedRequests::todays_discarded_count_query($partenaire);
            $today_discarded_count = ConnexionProvider::fetch_count($conn, $today_count_query);


            // close the connexion
            mysqli_close($conn);

            // assemble the final results
            $final_result = array(
                "list"         => LeadsController::decodeResponse($discarded_rows),
                "count"        => $discarded_count['totals'],
                "todays_count" => $today_discarded_count['todays_count']
            );

            // Encode result set as JSON object
            $json = json_encode($final_result);

            // return the results
            return $json;
        } catch (\Throwable $th) {
            // throw $th;
            return null;
        }
    }

    /**
     * Method for saving leads from LP to the DB.
     * @param mixed $lead
     * @param mixed $login_data
     * @param mixed $conn
     * @param mixed $date
     * @return mixed
     */
    public static function save_leads($lead, $login_data, $conn, $date)
    {
        try {
            $query = SavesRequests::save_leads_query($date, $lead, $login_data);
            // file_put_contents('save_leads_query.txt', $query);
            $conn->query($query);
            $last_id = $conn->insert_id;
            $conn->commit();
            // $conn->close();
            return $last_id;
        } catch (\Throwable $th) {
            file_put_contents('save_leads_error.txt', $th);
            //throw $th;
        }
    }

    /**
     * Method for saving clients response to the DB
     * after sending leads to it.
     * @param mixed $conn
     * @param mixed $date
     * @param mixed $partenaire
     * @param mixed $id_du_lead
     * @param mixed $send_result
     * @param mixed $client
     * @return void
     */
    public static function save_leads_has_clients($conn, $date, $partenaire, $id_du_lead, $send_result, $client)
    {
        try {
            $query = SavesRequests::save_leads_has_client_query($date, $send_result, $id_du_lead, $partenaire, $client);
            // file_put_contents('save_leads_has_clients_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('save_leads_has_clients_error.txt', $th);
        }
    }

    /**
     * Method for updating leads data from LEADIT.
     * @param mixed $conn
     * @param mixed $lead
     * @return bool
     */
    public static function update_lead($conn, $lead)
    {
        try {
            switch ($lead['user_action']) {
                case 'discarding':
                    $call_status = "CALLED";
                    $discard_date = TimeProvider::getTime();
                    break;
                case 'sending':
                    $call_status = "CALLED";
                    $discard_date = null;
                    break;

                default:
                    $call_status = "NOT CALLED";
                    $discard_date = null;
                    break;
            }

            // define the column names and values you want to update
            $column_names  = array(
                "discard_date",
                "civility",
                "firstname",
                "lastname",
                "email",
                "phone",
                "zipcode",
                "city",
                "address",
                "birthdate",
                "user_sender",
                "description_call",
                "call_status"
            );
            $column_values = array(
                $discard_date,
                $lead["civility"],
                base64_encode($lead["firstname"]),
                base64_encode($lead["lastname"]),
                base64_encode($lead["email"]),
                base64_encode($lead["phone"]),
                base64_encode($lead["zipcode"]),
                base64_encode($lead["city"]),
                base64_encode($lead["address"]),
                $lead["birthdate"],
                $lead["user_sender"],
                base64_encode($lead['description_call'] ?? ''),
                $call_status
            );

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE leads SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, array($lead["lead_id"]));
            $bind_params_ref = array();
            foreach ($bind_params as $key => $value) {
                $bind_params_ref[$key] = &$bind_params[$key];
            }
            array_unshift($bind_params_ref, $param_types);
            array_unshift($bind_params_ref, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $bind_params_ref);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_leads_error.txt', $th);
            return false;
        }
    }

    /**
     * Method for updating leads status 
     * when discarding or commenting from LEADIT.
     * @param mixed $posted_data
     * @param mixed $conn
     * @return bool
     */
    public static function comment_or_discard_lead($posted_data, $conn)
    {
        try {
            if ($posted_data['is_action_discard']) {
                $call_status = "CALLED";
            } else {
                $call_status = "NOT CALLED";
            }
            $desc_call = base64_encode($posted_data['description_call']);
            // $query = "update leads set call_status = 'not callable' where id = $id";
            $stmt = mysqli_prepare($conn, "UPDATE leads SET discard_date=?, call_status=?, description_call=?, user_sender=? WHERE id=?");

            mysqli_stmt_bind_param($stmt, 'ssssi', $posted_data['discard_date'], $call_status, $desc_call, $posted_data['user_sender'], $posted_data['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('save_leads_has_clients_error.txt', $th);
            return false;
        }
    }
}
<?php

namespace App\Controllers;


class LoginController
{
    /**
     * Method to makeLoginData
     * @param mixed $input_data
     * @return array
     */
    public static function makeLoginData($input_data)
    {
        /** 
        * create login DATA payload
        */
        $login_data = array(
            "partname" => $input_data['partner'] ?? null,
            "token"    => $input_data['token'] ?? null
        );
        return $login_data;
    }

    public static function login($lead, $conn)
    {
        try {
            $query = 'select * from fournisseurs where login=\'' . $lead['partname'] . '\' and mdp=\'' . $lead['token'] . '\'';
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            return ($row != null ? TRUE : FALSE);
        } catch (\Throwable $th) {
            return FALSE;
        }
    }
    

}
<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class RachatCreditController
{
    /**
     * Method that provide rac data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeRacData($input_data)
    {
        /**
         * create lead rac DATA payload
         */
        $rac_data = array(
            "fichage"           => $input_data['fichage'] ?? null,
            "contract"          => $input_data['contract'] ?? null,
            "nb_credit_conso"   => intval($input_data['nb_credit_conso']) ?? 0,
            "mensualites_conso" => json_encode($input_data['mensualites_conso']) ?? null,
            "restant_du_conso"  => json_encode($input_data['restant_du_conso']) ?? null,
            "type_credit_conso" => json_encode($input_data['type_credit_conso']) ?? null,
            "nb_credit_immo"    => intval($input_data['nb_credit_immo']) ?? 0,
            "mensualites_immo"  => json_encode($input_data['mensualites_immo']) ?? null,
            "restant_du_immo"   => json_encode($input_data['restant_du_immo']) ?? null,
            "revenu_mensuel"    => strval($input_data['revenu_mensuel']) ?? "0",
        );

        return $rac_data;
    }

    /**
     * Method for saving rac data inner rachat_de_credits table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $rac_data
     * @param mixed $lead
     * @return void
     */
    public static function save_rac($conn, $login_data, $rac_data, $lead)
    {
        try {
            $query = SavesRequests::save_rac_query($rac_data, $lead, $login_data);
            // file_put_contents('save_rac_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_rac_error.txt', $th);
        }
    }

    /**
     * Method to update the rac data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $rac_data
     * @return bool
     */
    public static function update_rac($conn, $lead, $rac_data)
    {
        try {

            // define the column names and values you want to update
            $column_names  = array(
                "status_logement",
                "fichage",
                "contract",
                "nb_credit_conso",
                "mensualites_conso",
                "restant_du_conso",
                "type_credit_conso",
                "nb_credit_immo",
                "mensualites_immo",
                "restant_du_immo",
                "revenu_mensuel"
            );
            $column_values = array(
                $lead['situation'],
                $rac_data['fichage'],
                $rac_data['contract'],
                $rac_data['nb_credit_conso'],
                base64_encode($rac_data['mensualites_conso']),
                base64_encode($rac_data['restant_du_conso']),
                base64_encode($rac_data['type_credit_conso']),
                $rac_data['nb_credit_immo'],
                base64_encode($rac_data['mensualites_immo']),
                base64_encode($rac_data['restant_du_immo']),
                base64_encode($rac_data['revenu_mensuel'])
            );

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE rachat_de_credits SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE leads_id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, array($lead["lead_id"]));
            $bind_params_ref = array();
            foreach ($bind_params as $key => $value) {
                $bind_params_ref[$key] = &$bind_params[$key];
            }
            array_unshift($bind_params_ref, $param_types);
            array_unshift($bind_params_ref, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $bind_params_ref);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_rac_error.txt', $th);
            return false;
        }
    }

}
<?php

namespace App\Controllers;

use App\Requests\SavesRequests;


class SecurityController
{
    /**
     * Method that provide security data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeSecurityData($input_data)
    {
        /**
         * create lead security DATA payload
         */
        $security_data = array(
            "canal" => $input_data['canal'] ?? '',
            "custom_field_1" => $input_data['custom_field_1'] ?? '',
            "custom_field_2" => $input_data['custom_field_2'] ?? '',
        );

        return $security_data;
    }

    /**
     * Method for saving security data inner security table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $security_data
     * @return void
     */
    public static function save_security($conn, $login_data, $lead, $security_data)
    {
        try {
            $query = SavesRequests::save_security_query($security_data, $lead, $login_data);
            // file_put_contents('save_security_query.txt', $query);
            $conn->query($query);
            $conn->commit();
            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_security_error.txt', $th);
        }
    }

    /**
     * Method to update the security data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $security_data
     * @return bool
     */
    public static function update_security($conn, $lead, $security_data)
    {
        try {
            $stmt = mysqli_prepare($conn, "UPDATE securities SET canal=? , custom_field_1=?, custom_field_2=? WHERE leads_id=?");

            mysqli_stmt_bind_param($stmt, 'sssi', $security_data['canal'], $security_data['custom_field_1'], $security_data['custom_field_2'], $lead['lead_id']);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_security_error.txt', $th);
            return false;
        }
    }
}
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
<?php
namespace App\Controllers;

use App\Requests\SavesRequests;

class TravauxController
{
    /**
     * Method that provide travaux data on the right format.
     * @param mixed $input_data
     * @return array
     */
    public static function makeTravauxData($input_data)
    {
        /**
         * create lead travaux DATA payload
         */
        if (isset($input_data['difficulty'])) {
            $travaux_desc = $input_data['difficulty'];
        } else if ((isset($input_data['description']))) {
            $travaux_desc = $input_data['description'];
        } else {
            $travaux_desc = null;
        }

        $travaux_data = [
            "type_logement"          => $input_data['type_logement'] ?? null,
            "type_chauffage"         => $input_data['type_chauffage'] ?? null,
            "description"            => $travaux_desc ?? null,                 // for some project details
            "date_start"             => $input_data['date_start'] ?? null,     // for project starting date.
            "energetic_cost"         => $input_data['energetic_cost'] ?? null, // not save in the DB, just used for PAC data.
            "partToInsulate"         => $input_data['partToInsulate'] ?? null, // for isolation
            "type_emetteur"          => $input_data['type_emetteur'] ?? null,
            "type_client"            => $input_data['type_client'] ?? null,
            "surface_logement"       => $input_data['surface_logement'] ?? null,
            "preferred_contact_time" => $input_data['preferred_contact_time'] ?? null,
            "custom_field_1"         => $input_data['custom_field_1'] ?? null,
            "custom_field_2"         => $input_data['custom_field_2'] ?? null,
            "custom_field_3"         => $input_data['custom_field_3'] ?? null,
            "custom_field_4"         => $input_data['custom_field_4'] ?? null,
            "custom_field_5"         => $input_data['custom_field_5'] ?? null,
        ];

        return $travaux_data;
    }

    /**
     * Method for saving travaux data inner travaux table on the DB.
     * @param mixed $conn
     * @param mixed $login_data
     * @param mixed $lead
     * @param mixed $travaux_data
     * @param mixed $id_travaux_kontiki
     * @return void
     */
    public static function save_travaux($conn, $login_data, $lead, $travaux_data, $id_travaux_kontiki)
    {
        try {
            $query = SavesRequests::save_travaux_query($login_data, $lead, $travaux_data, $id_travaux_kontiki);
            // file_put_contents('save_travaux_query.txt', $query);
            $conn->query($query);
            $conn->commit();

            // $conn->close();
        } catch (\Throwable $th) {
            file_put_contents('save_travaux_error.txt', $th);
        }
    }

    /**
     * Method to update the travaux data on the DB.
     * @param mixed $conn
     * @param mixed $lead
     * @param mixed $travaux_data
     * @return bool
     */
    public static function update_travaux($conn, $lead, $travaux_data)
    {
        try {

            // define the column names and values you want to update
            $column_names = [
                "type_chauffage",
                "type_logement",
                "partToInsulate",
                "situation_immo",
                "date_start",
                "description",
                "type_emetteur", //nouveau colonne
                "type_client",
                "surface_logement",
                "preferred_contact_time",
                "custom_field_1",
                "custom_field_2",
                "custom_field_3",
                "custom_field_4",
                "custom_field_5",
            ];
            $column_values = [
                $travaux_data['type_chauffage'],
                $travaux_data['type_logement'],
                $travaux_data['partToInsulate'],
                $lead['situation'],
                $travaux_data['date_start'],
                $travaux_data['description'],
                $travaux_data['type_emetteur'],
                $travaux_data['type_client'],
                $travaux_data['surface_logement'],
                $travaux_data['preferred_contact_time'],
                $travaux_data['custom_field_1'],
                $travaux_data['custom_field_2'],
                $travaux_data['custom_field_3'],
                $travaux_data['custom_field_4'],
                $travaux_data['custom_field_5'],
            ];

            // generate the parameter types string dynamically based on the number of columns
            $param_types = str_repeat('s', count($column_names));

            // append the 'i' type for the ID column
            $param_types .= 'i';

            // prepare the SQL query
            $sql = "UPDATE travaux SET ";
            foreach ($column_names as $column_name) {
                $sql .= "$column_name=?, ";
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE leads_id=?";

            $stmt = mysqli_prepare($conn, $sql);

            // bind the parameter values dynamically
            $bind_params     = array_merge($column_values, [$lead["lead_id"]]);
            $bind_params_ref = [];
            foreach ($bind_params as $key => $value) {
                $bind_params_ref[$key] = &$bind_params[$key];
            }
            array_unshift($bind_params_ref, $param_types);
            array_unshift($bind_params_ref, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $bind_params_ref);

            if (mysqli_stmt_execute($stmt)) {
                // update successful
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return true;
            } else {
                // update failed
                mysqli_stmt_close($stmt);
                // mysqli_close($conn);
                return false;
            }
        } catch (\Throwable $th) {
            // throw $th;
            file_put_contents('update_travaux_error.txt', $th);
            return false;
        }
    }
}

<?php

namespace App\Models;


class ApiModel
{
    # leads information
    private $classics_data;
    private $client_id;
    private $date_of_insert;

    # leads specifics information
    private $specifics_data;


    /**
     * Api Class Constructor
     * @param mixed $lead
     * @param mixed $tags_data
     * @param mixed $client_id
     * @param mixed $date_of_insert
     */
    public function __construct(
        array $lead = null, 
        array $tags_data = null,
        string $client_id = null, 
        string $date_of_insert = null
    ){
        $this->classics_data = $lead ?? [];
        $this->specifics_data = $tags_data ?? [];
        $this->client_id = $client_id ?? null;
        $this->date_of_insert = $date_of_insert ?? null;
    }


    // getters
    /**
     * getter of lead classics data
     * @return array|string
     */
    public function getClassics(){
        return $this->classics_data;
    } 

    /**
     * getter of lead Specifics data
     * @return array|string
     */
    public function getSpecifics(){
        return $this->specifics_data;
    } 

    /**
     * getter of the ClientID
     * @return string
     */
    public function getClientID(){
        return $this->client_id;
    }

    /**
     * getter of the Insert date of the lead
     * @return string
     */
    public function getDoi(){
        return $this->date_of_insert;
    }


    // setters 

    /**
     * Setter of lead Classics data
     * @param mixed $lead
     * @return void
     */
    public function setClassics($lead)
    {
        $this->classics_data = $lead;
    }

    /**
     * Setter of lead  data
     * @param mixed $tags_data
     * @return void
     */
    public function setSpecifics($tags_data)
    {
        $this->specifics_data = $tags_data;
    }

    /**
     * Setter of client ID
     * @param mixed $clientID
     * @return void
     */
    public function setClientID($clientID)
    {
        $this->client_id = $clientID;
    }

    /**
     * Setter of the lead insert date.
     * @param mixed $date
     * @return void
     */
    public function setDoi($date)
    {
        $this->date_of_insert = $date;
    }
}
<?php

namespace App\Models;


class LoginModel
{
    # login information
    private $partname;
    private $token;

    /**
     * Summary of __construct
     * @param mixed $partner
     * @param mixed $token
     */
    public function __construct(
        string $partner, 
        string $token,
    ){
        $this->partname = $partner;
        $this->token    = $token;
    }


    // getters

    /**
     * Summary of getPartname
     * @return string
     */
    public function getPartname(){
        return $this->partname;
    } 

    /**
     * Summary of getToken
     * @return string
     */
    public function getToken(){
        return $this->token;
    }


    // setters 

    /**
     * Summary of setPartname
     * @param mixed $partner
     * @return void
     */
    public function setPartname($partner)
    {
        $this->partname = $partner;
    }

    /**
     * Summary of setToken
     * @param mixed $token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
<?php

namespace App\Models;


class PingModel
{

    # ping information
    private $ping_data;
    private $client_id;


    /**
     * Defisc Api Class Constructor
     * @param mixed $ping_data
     * @param mixed $client_id
     */
    public function __construct(array $ping_data, string $client_id)
    {
        $this->ping_data = $ping_data ?? [];
        $this->client_id = $client_id ?? null;
    }


    // getters
    /**
     * getter of ping data data
     * @return array|string
     */
    public function getPingData(){
        return $this->ping_data;
    } 

    /**
     * getter of the ClientID
     * @return string
     */
    public function getClientID(){
        return $this->client_id;
    }


    // setters 

    /**
     * Setter of ping data data
     * @param mixed $ping_data
     * @return void
     */
    public function setPingData($ping_data)
    {
        $this->ping_data = $ping_data;
    }

    /**
     * Setter of client ID
     * @param mixed $clientID
     * @return void
     */
    public function setClientID($clientID)
    {
        $this->client_id = $clientID;
    }
}
<?php

namespace App\Providers;

class CityProvider
{
    /**
     * Summary of check_city
     * @param mixed $zipcode
     * @return mixed
     */
    public static function check_city($zipcode){

        $data = array(
            "zipcode" => $zipcode
        );
        $endpoint = "https://budgetdevis.com/support/api/cityzipcode";
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            // send data
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $output = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($output);
            if($response){
                return $response[0]->city;
            }else{
                return null;
            }
        } catch (\Throwable $th) {
            file_put_contents('check_city_error.txt', $th);
            return null;
        }
    }
}
<?php

namespace App\Providers;

class ConnexionProvider
{
    /**
     * Provider method to fetch multiple rows
     * @param mixed $conn
     * @param mixed $query
     * @return array
     */
    public static function fetch_all($conn, $query)
    {
        //prepare statement
        $stmt = mysqli_prepare($conn, $query);
        // Execute statement
        mysqli_stmt_execute($stmt);
        // Get result set
        $result = mysqli_stmt_get_result($stmt);
        // Fetch all rows as associative arrays
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Close statement
        mysqli_stmt_close($stmt);

        // return results
        return $rows;
    }
    
    public static function fetch_count($conn, $query)
    {
        // prepare the statement
        $stmt = mysqli_prepare($conn, $query);
        // Execute statement
        mysqli_stmt_execute($stmt);
        // Get result set
        $result = mysqli_stmt_get_result($stmt);
        // Fetch the row as associative arrays
        $count = mysqli_fetch_assoc($result);
        // Close statement
        mysqli_stmt_close($stmt);

        // return counts results
        return $count;
    }
}
<?php

namespace App\Providers;

class CurlProvider
{
    /**
     * Make a curl post_requests
     * @param mixed $url
     * @param array $headers
     * @param mixed $data
     * @return array
     */
    public static function post_requests($url, $headers, $data, $certificat = null)
    {
        try {
            $ch = curl_init();
            if ($certificat !== null) {
                curl_setopt($ch, CURLOPT_CAINFO, $certificat);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST           => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => $data
            ));
            // send data
            $output             = curl_exec($ch);
            if ($output === false) {
                echo 'Erreur cURL : ' . curl_error($ch);
            }
            $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [$output, $http_response_code];
        } catch (\Throwable $th) {
            //throw $th;
            return ['', $th];
        }
    }

    /**
     * Make a curl get_requests
     * @param mixed $url
     * @return array
     */
    public static function get_requests($url)
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_MAXREDIRS      => 1,
                CURLOPT_FOLLOWLOCATION => true
            ));
            // send data
            $output             = curl_exec($ch);
            $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [$output, $http_response_code];
        } catch (\Throwable $th) {
            //throw $th;
            return ['', $th];
        }
    }
}
<?php

namespace App\Providers;

use App\Controllers\LeadsController;
use App\Services\SendAssuranceService;
use App\Services\SendFormationService;
use App\Services\SendSecurityService;
use App\Services\SendTravauxService;

class DeliveryDestinationProvider
{
    public static function dispacth($deliverable, $classicsData, $specificsData, $insert_date, $partname, $clients, $conn)
    {
        //explode deliverable to get tag on [0] and clients list on [1]
        $delivery = explode('/', $deliverable);

        //explode clients list to array and do all operation foreach client.
        $delivery_clients = explode('-', $delivery[1]);
        $delivery_results = [];

        foreach ($delivery_clients as $client) {
            /**.
             * detect destination Api service class.
             */
            $clientID = $delivery[0] . '#' . $client;
            switch ($delivery[0]) {
                case 'assurance':
                    $lead_properties = new SendAssuranceService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'security':
                    $lead_properties = new SendSecurityService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'formation':
                    $lead_properties = new SendFormationService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
                case 'pannsol':
                    $lead_properties = new SendTravauxService($classicsData, $specificsData, $clientID, $insert_date);
                    break;

                default:
                    $lead_properties = new SendAssuranceService($classicsData, $specificsData, $clientID, $insert_date);
                    break;
            }
            $status = $lead_properties->send();
            //append the result to the results arrays
            $delivery_results[] = $status;
        }

        // write each response to the database after sending the data.
        foreach ($delivery_results as $send_response) {

            // SAVE CLIENTS responses into the DB after sending the lead.
            /*
             */
            LeadsController::save_leads_has_clients($conn, TimeProvider::getTime(), $partname, $classicsData["lead_id"], $send_response, $clients[$clientID]);
        }
    }
}
<?php


namespace App\Providers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelCreatorProvider
{
    private $filePath;
    private $headers;

    public function __construct($filePath, $headers)
    {
        $this->filePath = $filePath;
        $this->headers = $headers;
    }

    public function createFile()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        // Set the headers
        $headerColumn = 'A';
        foreach ($this->headers as $header) {
            $sheet->setCellValue($headerColumn . '1', $header);
            $headerColumn++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->filePath);
    }
}
?>

<?php


namespace App\Providers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelWriterProvider
{
    private $filePath;
    private $spreadsheet;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->loadSpreadsheet();
    }

    private function loadSpreadsheet()
    {
        $this->spreadsheet = IOFactory::load($this->filePath);
    }

    /**
     * Method to append a new row in an excel file.
     * @param array $data
     * @return void
     */
    public function appendRow($data)
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $lastRow = $sheet->getHighestRow() + 1;
        $sheet->fromArray($data, null, 'A' . $lastRow);
    }

    public function save()
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->filePath);
    }

    public function download()
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }
}
?>

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
<?php

namespace App\Providers;

class PhoneNumberProvider
{
    /**
     * Method to remove whitespace on phonenumber.
     * @param mixed $number
     * @return string
     */
    public static function remove_whitespace($number) : string
    {
        return str_replace(' ', '', $number);
    }

    /**
     * METHOD TO REMOVE +33 OR 33 on phonenumber.
     * @param mixed $number
     * @return string
     */
    public static function remove_plus_33($number) : string
    {
        return preg_replace('/^(?:\+?33|0)/', '0', $number);
    }
}
<?php

namespace App\Providers;

use phpseclib3\Net\SFTP;

class SftpUploaderProvider
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $remotePath;

    public function __construct($host, $port, $username, $password, $remotePath)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->remotePath = $remotePath;
    }

    public function uploadFile($localPath, $fileName)
    {
        $sftp = new SFTP($this->host, $this->port);
        
        if (!$sftp->login($this->username, $this->password)) {
            throw new \Exception("SFTP login failed.");
        }
        
        $remoteFile = $this->remotePath . '/' . $fileName;
        
        if ($sftp->file_exists($remoteFile)) {
            // File already exists, delete it before uploading
            $sftp->delete($remoteFile);
        }
        
        if (!$sftp->put($remoteFile, $localPath.$fileName, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \Exception("File upload failed.");
        }
    }
}

<?php

namespace App\Providers;

use DateTime;

class TimeProvider
{
    /**
     * Summary of getTime
     * @return string
     */
    public static function getTime()
    {
        $date    = new DateTime();
        $newDate = $date->format('Y-m-d H:i:s');
        return $newDate;
    }

    /**
     * Method to format date into french format.
     * @param mixed $datetime
     * @return mixed
     */
    public static function frenchFormat($datetime)
    {
        $french_date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if (is_bool($french_date)) {
            return $datetime;
        } else {
            return $french_date->format("d/m/Y H:i:s");
        }

    }
}
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
        return 'insert into assurances (bank, property_assurance, objectif_assurance, montant_pret, quote_type, taux_pret, duree_pret, professionnal_situation, custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5, custom_field_6, custom_field_7, custom_field_8,
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
                \'' . ($assurance_data['custom_field_8'] ?? '') . '\',
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
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Expose-Headers: Content-Disposition');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Paris');

/*
| -------------------------
| REQUIRE the connexion class.
| -------------------------
| Needed when interacting with the Budgetdevis databases.
*/
// require_once __DIR__.'/../../travaux/contact/admin/config/conn.php';

$username = "leadmarket";
    // $password = "0FU[7zBLos3R";
    // $hostname = "localhost";
    // $db_name = "lead_market_place";
    $username = "root";
    $password = "";
    $hostname = "localhost";
    $db_name = "lead_market_place";
 
 
    //connection to the database
    $conn = new mysqli($hostname, $username, $password, $db_name);
    /* check connection */
    if ($conn->connect_errno) {
        printf("Connect failed: %s\n", $conn->connect_error);
        exit();
    }  


/*
| -------------------------
| REQUIRE the autoload once.
| -------------------------
| Needed when importing external class using namespaces.
*/
require_once __DIR__.'/../vendor/autoload.php';



/*
| -------------------------
| REQUIRE constants.
| -------------------------
| Lists of static constants used inner some class.
*/
require_once('../constants/configs.php');
require_once('../constants/partners.php');
require_once('../constants/clientsList.php');

define('BENCHMARK_API_KEY', 'ws_bench_key_change_this_in_production');
<?php
// here you can find all availables
// CLIENTS NAME lists according to IT's ID fetched from the requests.

$clients = [
    /**
     * panneau solaire clients
     */
    "pannsol#1"    => "LEAD VALUE",        // (LEAD VALUE panneau solaire)
    "pannsol#2"    => "DATA OPP",          // (Data Opp panneau solaire)
    "pannsol#3"    => "SOFANMEDIA",        // (SOFANMEDIA panneau solaire)
    "pannsol#4"    => "AM BUSNESS",        // for CPF Bureautique (AM BUSNESS)
    "pannsol#5"    => "Goracash",          // (Goracash panneau solaire)
    "pannsol#6"    => "Unitead",           // (Unitead panneau solaire)
    "pannsol#7"    => "Adkomo",            // (Akdomo panneau solaire)
    "pannsol#8"    => "Oceads",            // (Oceads panneau solaire)
    "pannsol#9"    => "Mediamoov",         // (Mediamoov panneau solaire)
    "pannsol#10"   => "Yacuza",            // (Yacuza panneau solaire)
    "pannsol#11"   => "PERSEE MEDIA",      // (PERSEE MEDIA Sheet panneau solaire)
    "pannsol#12"   => "Batiweb",           // (Batiweb panneau solaire)
    "pannsol#13"   => "PERSEE MEDIA 2",    // (PERSEE MEDIA WS panneau solaire)
    "pannsol#14"   => "Lead Creative",     // (Lead Creative panneau solaire)
    "pannsol#15"   => "CONFLUENT DIGITAL", // (CONFLUENT DIGITAL panneau solaire)
    "pannsol#16"   => "Ted Jordan Srl",    // (Ted Jordan Srl panneau solaire)
    "pannsol#17"   => "Flexylead",         // (Flexylead panneau solaire)
    "pannsol#18"   => "Aston Group",       // (ASTON GROUP panneau solaire pv)
    "pannsol#19"   => "Leads FR",          // (Leads FR panneau solaire)
    "pannsol#20"   => "viteundevis",
    "pannsol#21"   => "LEAD VALUE",        // (LEAD VALUE panneau solaire)
    "pannsol#22"   => "PERFUSION DIGITAL",        // (PERFUSION DIGITAL panneau solaire)
    "pannsol#23"   => "CPRY_DIGITAL", //    "pannsol CPRY_DIGITAL"
    "pannsol#24"    => "prosperaleads",     // (prosperaleads panneau solaire)
    "pannsol#25"   => "devis_plus", //devis plus panneau solaire
    /**
     * defiscalisation/Pinel clients
     */
    "defisc#1"     => "MyOptin",
    "defisc#2"     => "Lead Creative",           // (Lead Creative Defisc)
    "defisc#3"     => "VMB INVESTISSEMENTS SAS", // (VMB Defisc)
    "defisc#4"     => "Edilead",                 // (Edilead Defisc)
    "defisc#5"     => "SOFANMEDIA",              // (Sofanmedia 1er client Defisc)
    "defisc#6"     => "SOFANMEDIA 2",            // (Sofanmedia 2e client Defisc)
    "defisc#7"     => "CPRY_DIGITAL",            //AZUR
    /**
     * ENI clients
     */
    "energy#1"     => "Lead Creative",
    "energy#2"     => "EURO CRM",

    /**
     * poele a granules clients
     */
    "pag#1"        => "DATA OPP", // (Data Opp poele a granules)
    /**
     * isolation
     */
    "iso#1"        => "DATA OPP",          // (Data Opp isolation)
    "iso#2"        => "MOKHTAR",           // (MOKHTAR isolation)
    "iso#3"        => "Yacuza",            // (Yacuza isolation)
    "iso#4"        => "SH CONSEIL",        // (ex SOCIETE MOONER isolation)
    "iso#5"        => "CONFLUENT DIGITAL", // (CONFLUENT DIGITAL isolation)
    "iso#6"        => "Aston Group",       // (ASTON GROUP isolation)
    "iso#7"        => "Leads FR",          // (Leads FR isolation)
    "iso#8"        => "viteundevis",
    "iso#9"        => "PERFUSION DIGITAL",
    "iso#10"       => "CONFLUENT DIGITAL", //confluent digital isolation nouveaux 
    "iso#11"       => "CPRY_DIGITAL",
    "iso#12"       => "Flexylead", //ite flexyleads
    "iso#13"       => "prosperaleads", //prosperaleads isolation
    "iso#14"       => "devis_plus", // devis plus isolation ite
    /**
     * assurance
     */
    "assurance#1"  => "Lead Creative",   // (Lead creative assurance pret)
    "assurance#2"  => "SOFANMEDIA",      // (Sofanmedia assurance vie)
    "assurance#3"  => "EURO CRM",        // (EURO CRM/Axa assurance auto)
    "assurance#4"  => "Filiassur",       // (Filiassur/IKI assurance emprunteur)
    "assurance#5"  => "Filiassur",       // (Filiassur/IKI mutuelle senior)
    "assurance#6"  => "PERSEE MEDIA",    // (PERSEE MEDIA mutuelle senior)
    "assurance#7"  => "Oceads",          // (Oceads mutuelle senior)
    "assurance#8"  => "Mediamoov",       // (Mediamoov bilan auditif)
    "assurance#9"  => "Cardata",         // (Cardata bilan auditif)
    "assurance#10" => "Mediamoov 217",   // (Mediamoov 217 bilan auditif)
    "assurance#11" => "Mutac",           // (Mutac Assurance Obsèques)
    "assurance#12" => "LMP Sante",       // (LMP Santé)
    "assurance#13" => "Leads FR",        // (Leads FR mutuelle Santé Sénior)
    "assurance#14" => "Aston Group",     // (Aston Group mutuelle Santé Sénior)
    "assurance#15" => "Aston Group",     // (Aston Group ASSURANCE AUTO)
    "assurance#16" => "Test Client",     // (Test Client ASSURANCE ANIMAUX)
    "assurance#17" => "EURO CRM",        // (Ws-conciergerie assurance emprunteur)
    "assurance#18" => "EURO CRM",        // (euro crm mutuelle senior)
    "assurance#19" => "Mediamoov",       //Media Moov
    "assurance#20" => "auxillaire_veto", // for CPF Langue (SOFANMEDIA)
    "assurance#21" => "webrivage",       // (webrivage mutuelle senior)
    "assurance#22" => "CAP",             // for CPF Langue (SOFANMEDIA)
    "assurance#23" => "Mediamoov",       //Media Moov assurance emprunteur
    "assurance#24" => "Flexylead",       //Mutuelle senior de Flexylead
    "assurance#25" => "LEAD VALUE",
    "assurance#27" => "CONFLUENT DIGITAL", //Mutuelle senior de "CONFLUENT DIGITAL"
    "assurance#28" => "DATA OPP",            // Data Opp assurance emprunteur
    "assurance#30" => "CONFLUENT DIGITAL", //animaux de "CONFLUENT DIGITAL"
    /**
     * security
     */
    "security#1"   => "Sector Alarm",
    "security#2"   => "Aston Group", // (Aston Group Alarm IDF)
    "security#3"   => "Aston Group", // (Aston Group Alarm NATIO)
    "security#4"   => "compleo",

    /**
     * formations
     */
    "formation#1"  => "AM BUSNESS", // for CPF Bureautique (AM BUSNESS)
    "formation#2"  => "AUXILLAIRE", // for CPF Langue (SOFANMEDIA)
    "formation#3"  => "AUXILLAIRE", // for CPF Langue (SOFANMEDIA)
    /**
     * pompe a chaleur
     */
    "pac#1"        => "LEAD VALUE",              // (LEAD VALUE pompe a chaleur)
    "pac#2"        => "VMB INVESTISSEMENTS SAS", // (VMB INVESTISSEMENTS SAS pompe a chaleur)
    "pac#3"        => "Goracash",                // (GORACASH pompe a chaleur)
    "pac#4"        => "DATA OPP",                // (Data Opp pompe a chaleur)
    "pac#5"        => "Edilead",                 // (Edilead pompe a chaleur)
    "pac#6"        => "Adkomo",                  // (Adkomo pompe a chaleur)
    "pac#7"        => "SOFANMEDIA",              // (SOFANMEDIA pompe a chaleur)
    "pac#8"        => "PROXISERVE",              // (PROXISERVE pompe a chaleur)
    "pac#9"        => "Unitead",                 // (Unitead pompe a chaleur)
    "pac#10"       => "Oceads",                  // (Oceads pompe a chaleur)
    "pac#11"       => "Mailomedia",              // (Mailomedia pompe a chaleur)
    "pac#12"       => "Yacuza",                  // (Yacuza pompe a chaleur)
    "pac#13"       => "Batiweb",                 // (Batiweb pompe a chaleur)
    "pac#14"       => "Ted Jordan Srl",          // (Ted Jordan Srl pompe a chaleur)
    "pac#15"       => "Flexylead",               // (Flexylead pompe a chaleur)
    "pac#16"       => "Leads FR",                // (Leads FR pompe a chaleur)
    "pac#17"       => "lovvis-ads",              // (lovvis-ads proxiserve pompe a chaleur-teletech)
    "pac#18"       => "lovvis-ads",              // (lovvis-ads proxiserve pompe a chaleur)
    "pac#19"       => "Aston Group",             // (Aston Group pompe a chaleur)
    "pac#20"       => "viteundevis",
    "pac#21"       => "LEAD VALUE",              // (LEAD VALUE pompe a chaleur)
    "pac#22"       => "CONFLUENT DIGITAL",     //(CONFLUENT DIGITAL pompe a chaleur)
    "pac#23"       => "PERFUSION DIGITAL",
    "pac#24"       => "CPRY_DIGITAL",   //PAC 
    "pac#25"       => "Mediamoov",
    "pac#26"       => "prosperaleads", //prosperaleads pompe a chaleur
    "pac#27"       => "devis_plus" , //devis plus pompe a chaleur

    /**
     * rachat de credits
     */
    "rac#1"        => "SOFANMEDIA", // (SOFANMEDIA rachat de credits)
    /**
     * Douche senior
     */
    "douche#1"     => "Lead Creative", // (Lead Creative Douche senior data)
    "douche#2"     => "Goracash",      // (Goracash Douche senior data)
    "douche#3"     => "viteundevis",
    "douche#4"     => "CONFLUENT DIGITAL",
    "douche#5"     => "devis_plus",

    /**
     * Fenêtre
     */
    "fenetre#1"    => "Aston Group", // (Aston Group fenêtre)
    "fenetre#2"    => "Goracash",    // (Goracash fenêtre)
    "fenetre#3"    => "viteundevis",

    "clim#1"        => "CONFLUENT DIGITAL",

];
<?php
// here you can find all availables
// necessary CONFIG.

$sftp = array(
    // sftp for eni/lead creative
    "eni" => array(
        "host" => "sftp2.kontikimedia.com",
        "username" => "leadcreative",
        "password" => "96v0eFibkFB*",
        "port" => 22,
        "upload_path" => "ftp_up/",
        "download_path" => "ftp_down/",
    )
);
<?php
// here you can find all available partners list

$partners = [
    "assurance"          => ["kontiki-70", "kontiki-34"],
    "insurances"         => ["kontiki-47", "kontiki-52", "kontiki-65", "kontiki-66", "kontiki-73", "kontiki-76", "kontiki-77", "kontiki-78", "kontiki-79"],
    "assurance_auto"     => ["kontiki-57"],
    "defisc"             => ["kontiki-14", "kontiki-16"],
    "formations"         => ["kontiki-49", "kontiki-51", "kontiki-80", "kontiki-81"],
    "securities"         => ["kontiki-48"],
    "finances"           => ["kontiki-10"],
    "travaux"            => [
        "kontiki-20",
        "kontiki-18",
        "kontiki-42",
        "kontiki-43",
        "kontiki-44",
        "kontiki-50",
        "kontiki-54",
        "kontiki-75",
        "kontiki-19",
        "kontiki-82",
        "kontiki-83",
    ],
    "travaux_kontiki_ID" => [
        "kontiki-20" => "13",
        "kontiki-18" => "19",
        "kontiki-42" => "24",
        "kontiki-43" => "25",
        "kontiki-44" => "26",
        "kontiki-50" => "23",
        "kontiki-54" => "27",
        "kontiki-75" => "19",
        "kontiki-19" => "22",
        "kontiki-82"=> "32",
        "kontiki-83" => "31",
        
    ],
    /*
    "is_email_verify"     => [
        "kontiki-57"
    ],
    "is_phone_verify"     => [
        "kontiki-57"
    ],
    */
];
<?php
require_once '../bootstrap/app.php';


use App\Controllers\AssuranceController;
use App\Controllers\DefiscController;
use App\Controllers\FormationController;
use App\Controllers\LeadsController;
use App\Controllers\LoginController;
use App\Controllers\RachatCreditController;
use App\Controllers\SecurityController;
use App\Controllers\TravauxController;

use App\Providers\CityProvider;
use App\Providers\DeliveryDestinationProvider;
use App\Providers\TimeProvider;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // check $_POST
    if (empty($_POST)) {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_POST;
    }

    /**
     * provide IP ADDRESS
     */


    $ip = $_SERVER['REMOTE_ADDR'];

    /**
     * provide if the lead is deliverable direclty has value
     */


    $deliverable = $input_data['lead_type'] ?? null;

    /**
     * provide CITY location
     */

    if (isset($input_data['city']) && ! empty($input_data['city'])) {
        $city = $input_data['city'];
    } else if (isset($input_data['zipcode']) && ! empty($input_data['zipcode'])) {
        $city = CityProvider::check_city($input_data['zipcode']);
    } else {
        $city = null;
    }


    /**
     * create login DATA payload
     */


    $login_data = LoginController::makeLoginData($input_data);

    /**
     * create lead classics DATA payload
     */
    $lead = LeadsController::makeClassicsData($input_data, $ip, $city);



    //main programs
    if (
        empty($login_data['partname'])
        or empty($login_data['token'])
        or (empty($input_data['firstname']) && empty($input_data['lastname']))
        or (empty($input_data['phone']))
        or (empty($input_data['zipcode']))
        or (empty($input_data['email']))
        or (empty($input_data['id_base']))
        or (empty($deliverable))
    ) {
        $response = array(
            "id"       => null,
            "status"   => "error",
            "message"  => "Missing parameters",
            "leadData" => $lead,
            "leadType" => $deliverable,
            "authData" => $login_data
        );

        header("HTTP/1.1 400 Bad Requests");
        echo json_encode($response);
    } else {

        //launch mode
        $loggedIn = LoginController::login($login_data, $conn);
        if ($loggedIn == false) {
            $response = array(
                "id"       => null,
                "status"   => "error",
                "message"  => "Invalid Token or partner",
                "authData" => $login_data,
            );

            header("HTTP/1.1 400 Bad Requests");
            echo json_encode($response);
        } else {
            // save lead info to DB leads table
            $last_insert_id = LeadsController::save_leads($lead, $login_data, $conn, TimeProvider::getTime());
            // append last insert ID to the lead payload because we may need it in direct delivery mode.
            $lead["lead_id"] = $last_insert_id;

            /**
             * save lead tags specifics data to the corresponding tags table
             * $partners variable is from bootstrap app imported in the top.
             */
            if (in_array($login_data['partname'], $partners["travaux"])) {
                /**
                 * create lead travaux DATA payload
                 * then save travaux data to table travaux of the database.
                 */
                $travaux_data = TravauxController::makeTravauxData($input_data);
                TravauxController::save_travaux(
                    $conn, $login_data, $lead, $travaux_data, $partners["travaux_kontiki_ID"][$login_data['partname']]
                );
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $travaux_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["defisc"])) {
                /**
                 * create lead defiscalisation DATA payload
                 * then save defisc data to the "defiscalisation" table of the DB.
                 */
                $defisc_data = DefiscController::makeDefiscData($input_data);
                DefiscController::save_defisc($conn, $login_data, $lead, $defisc_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $defisc_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["insurances"])) {
                /**
                 * create lead assurance DATA payload
                 * then save the payload to the "assurances" table of the DB.
                 */
                $assurances_data = AssuranceController::makeAssuranceData($input_data);
                AssuranceController::save_assurance($conn, $login_data, $lead, $assurances_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $assurances_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["securities"])) {
                /**
                 * create lead security DATA payload
                 * then save the payload to the "securities" table of the DB.
                 */
                $security_data = SecurityController::makeSecurityData($input_data);
                SecurityController::save_security($conn, $login_data, $lead, $security_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $security_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["formations"])) {
                /**
                 * create lead formation DATA payload
                 * then save the payload to the "formations" table of the DB.
                 */
                $formation_data = FormationController::makeFormationData($input_data);
                FormationController::save_formation($conn, $login_data, $lead, $formation_data);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $formation_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            } elseif (in_array($login_data['partname'], $partners["finances"])) {
                /**
                 * create lead rachat de credits DATA payload
                 * then save the payload to the "rachat_de_credits" table of the DB.
                 */
                $rac_data = RachatCreditController::makeRacData($input_data);
                RachatCreditController::save_rac($conn, $login_data, $rac_data, $lead);
                /**
                 * Dispatch direct delivery to their clients
                 */
                DeliveryDestinationProvider::dispacth(
                    $deliverable,
                    $lead,
                    $rac_data,
                    TimeProvider::getTime(),
                    $login_data['partname'],
                    $clients,
                    $conn
                );
            }

            if ($last_insert_id) {

                // close the connexion
                $conn->close();

                // return message
                $response = array(
                    "id"      => $last_insert_id,
                    "status"  => "success",
                    "message" => "Lead received successfully.",
                );

                header("HTTP/1.1 202 Accepted");
                echo json_encode($response);
            } else {

                // close the connexion
                $conn->close();

                // return message
                $response = array(
                    "id"      => null,
                    "status"  => "error",
                    "message" => "Error on saving lead.",
                );

                header("HTTP/1.1 406 Not Acceptable");
                echo json_encode($response);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $response = "new option connexion...";

    header("HTTP/1.1 200 Ok");
    echo json_encode($response);
} else {
    $response = array(
        "id"      => null,
        "status"  => "error",
        "message" => "Something went wrong, invalid request method",
    );

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode($response);
}