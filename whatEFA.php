<?php

/*** EFA Endpoint ***/
static $efa = 'http://bsvg.efa.de/bsvagstd/XML_DM_REQUEST?';

/*** Verify Method ***/
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    
    halt(405, 'error', 'Request method '.$_SERVER['REQUEST_METHOD'].' is not supported');
}

/*** Verify Parameters ***/
if (!isset($_GET['city'])) {
    halt(400, 'error', 'Parameter missing: [city]');
}

if (!isset($_GET['stop'])) {
    halt(400, 'error', 'Parameter missing: [stop]');
}

if (isset($_GET['limit']) && !isInteger($_GET['limit'])) {
    halt(400, 'error', 'Parameter invalid: [limit] (must be integer)');
}

$city = $_GET['city'];
$stop = $_GET['stop'];
$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;

/*** Find Stop ***/
$findStop_params = array(
    'sessionID' => 0,
    'locationServerActive' => 1,
    'outputFormat' => 'json',
    'type_dm' => 'stop',
    'name_dm' => "$city $stop",
    'limit' => $limit
);

$findStop_url = $efa . http_build_query($findStop_params);

if (!$findStop_response = file_get_contents($findStop_url)) {
    halt(503, 'error', 'Problem communicating with EFA server');
}

$findStop_data = json_decode($findStop_response);

if (isset($findStop_data->dm->message)) {
    halt(404, 'error', 'No matching stop found');
}

/*** Get Departures for Stop ***/
$getDepartures_params = array(
    'sessionID' => $findStop_data->parameters[1]->value,
    'requestID' => $findStop_data->parameters[0]->value,
    'outputFormat' => 'json',
    'dmLineSelectionAll' => '1'
);

$getDepartures_url = $efa . http_build_query($getDepartures_params);

if (!$getDepartures_response = file_get_contents($getDepartures_url)) {
    halt(503, 'error', 'Problem communicating with EFA server');
}

$getDepartures_data = json_decode($getDepartures_response);

if (isset($getDepartures_data->dm->message) || is_null($getDepartures_data->departureList)) {
    halt(404, 'error', 'No departures found');
}

/*** Force departureList to be an array ***/
if (!is_array($getDepartures_data->departureList)) {
    $getDepartures_data->departureList = array($getDepartures_data->departureList->departure);
}

/*** Build Response Data ***/
$result = array(
    'stopName' => $getDepartures_data->departureList[0]->nameWO,
    'stopLongName' => $getDepartures_data->departureList[0]->stopName,
    'platforms' => array()
);
foreach ($getDepartures_data->departureList as $d) {
    if (!isset($result['platforms'][$d->platform])) {
        $result['platforms'][$d->platform] = array(
            'name' => $d->platformName ?: $d->platform,
            'transitLines' => array()
        );
    }
    
    $transitLines = &$result['platforms'][$d->platform]['transitLines'];
    
    if (!isset($transitLines[$d->servingLine->number])) {
        $transitLines[$d->servingLine->number] = array(
            'directionTo' => $d->servingLine->direction,
            'directionFrom' => $d->servingLine->directionFrom,
            'type' => $d->servingLine->name,
            'departures' => array()
        );
    }
    
    $departures = &$transitLines[$d->servingLine->number]['departures'];

    $year = $d->dateTime->year;
    $month = $d->dateTime->month;
    $day = $d->dateTime->day;
    $hour = $d->dateTime->hour;
    $minute = $d->dateTime->minute;
    
    $departures[] = strtotime("$year-$month-$day $hour:$minute");
}

success($result);

/************************************/

function success($data) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(array(
        'status' => 200,
        'state' => 'success',
        'data' => $data
    ));
    
    exit;
}

function halt($status, $state, $message) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode(array(
        'status' => $status,
        'state' => $state,
        'message' => $message
    ));
    
    exit;
}

function isInteger($value) {
    return ctype_digit(strval($value));
}