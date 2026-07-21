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
