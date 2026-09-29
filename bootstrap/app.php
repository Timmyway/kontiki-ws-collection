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
| REQUIRE the autoload once.
| -------------------------
| Needed when importing external class using namespaces.
*/
require_once __DIR__.'/../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();


/*
| -------------------------
| REQUIRE the connexion class.
| -------------------------
| Needed when interacting with the Budgetdevis databases.
*/
require_once 'conn.php';


/*
| -------------------------
| REQUIRE constants.
| -------------------------
| Lists of static constants used inner some class.
*/
require_once('../constants/configs.php');
require_once('../constants/partners.php');
require_once('../constants/clientsList.php');