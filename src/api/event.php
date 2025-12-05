<?php
require __DIR__ . '/../../config/bootstrap.php';

$eventId = $_GET['id'];
$url = "https://app.ticketmaster.com/discovery/v2/events/$eventId.json?apikey=" . $TICKETMASTER_API_KEY;

$response = file_get_contents($url);
header("Content-Type: application/json");
echo $response;
