<?php

use App\Controllers\StatisticsController;

require_once('../bootstrap/app.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    return null;
}

$items = StatisticsController::getStat($conn, $_GET);

echo json_encode($items);