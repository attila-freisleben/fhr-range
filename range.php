<?php

error_reporting(E_ERROR);
$allowedIPs = array();
if (in_array('HTTP_X_FORWARDED_FOR', array_keys($_SERVER)))
    if (!in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $allowedIPs)) {
        $result['status'] = 401;
        header("Content-Type: application/fhir+json; charset=utf-8", true, $result['status']);
        echo json_encode(array('Error' => '401 Unauthorized'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }


foreach ($_REQUEST as $key => $val)
    $_REQUEST[$key] = str_replace("%20", " ", $val);


if ($argv[1] != "") {
    $_REQUEST['resource'] = $argv[1];
    $_REQUEST['teo'] = 'aha';
}

if ($argv[2] != "")
    $_REQUEST['nat'] = $argv[2];

$resource = $_REQUEST['resource'];
$rstart = microtime(true);
set_time_limit(180);

if (isset($_REQUEST['baseurl']))
    $baseurl = $_REQUEST['baseurl'];

set_include_path("./inc");
include_once("include.php");
include_once("common.php");

$db = new db_connect($dbparams);


$nat = $_REQUEST['nat'] == "" ? 'EN' : strtoupper($_REQUEST['nat']);

$nat = $nat == "US" ? "en" : $nat;


if ($_REQUEST['id'] == "")
    unset($_REQUEST['id']);


require("_$resource/index.php");


$rend = microtime(true);
if (!isset($result['body']['resourceType']))
    $result['body']['resourceType'] = str_replace('/', '', $resource);
$result['body']['meta']['lastUpdated'] = getZdate();
$result['body']['text'] = $gentextdiv;


$result['status'] = '200';
// output result
header("Content-Type: application/fhir+json; charset=utf-8", true, $result['status']);


echo json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


?>