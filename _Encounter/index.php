<?php

if (isset($_REQUEST['patient']))
    $patient["id"] = $_REQUEST['patient'];

if (isset($_REQUEST['organization']))
    $organization["id"] = $_REQUEST['organization'];

if (isset($_REQUEST['practitioner']))
    $practitioner["id"] = $_REQUEST['practitioner'];

if (isset($_REQUEST['location']))
    $locid = $_REQUEST['location'];

if (isset($_REQUEST['servicetype']))
    $serviceType = getServiceType(array('szakma' => $_REQUEST['servicetype']));

if (isset($_REQUEST['status']))
    $status = $_REQUEST['status'];

if (isset($_REQUEST['start']))
    $start = $_REQUEST['start'];
if (isset($_REQUEST['end']))
    $end = $_REQUEST['end'];

if (isset($_REQUEST['duration']))
    $dura = $_REQUEST['duration'];

$statuses1[] = "planned";
$statuses1[] = "arrived";
$statuses1[] = "triaged";
$statuses1[] = "in-progress";

// $statuses[] = "onleave";

$statuses2[] = "finished";
$statuses2[] = "cancelled";
$statuses2[] = "entered-in-error";
$statuses2[] = "unknown";

$class['code'] = 'AMB';
$class['system'] = 'http://terminology.hl7.org/CodeSystem/v3-ActCode';
$class['display'] = 'Ambulatory';

// $jsonarr = json_decode(file_get_contents_https($baseurl."/$resource/$id"),true);

if (!isset($jsonarr['id'])) {
    $gndr = $gender[round(rand(1, 2), 0)];

    $active = rand(1, 100) > 5;

    if (isset($patient['id'])) {
        $pat = getResource('Patient/' . $patient['id']);
        $subject = array("reference" => "Patient/" . $patient['id'], 'type' => 'Patient', 'display' => $pat['name'][0]['text']);
    } else
        $subject = getReference(array("type" => "Patient"));

    $nat = $pat['address'][0]['country'];

    $opts = array('nat' => $nat);
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genID('', '', "ENC.$nat.");

    $maxcnt = 10;
    $cnt = 0;
// do{

    if (isset($organization)) {
        $serviceProvider = array("reference" => "Organization/" . $organization["id"], 'type' => 'Organization');
        $org = getResource('Organization/' . $organization['id']);
        $serviceProvider = array("reference" => "Organization/" . $organization['id'], 'type' => 'Organization', 'display' => $org['name']);
    } else {
        $serviceProvider = getReference(array("type" => "Organization"));
    }

    $org = getResource($serviceProvider['reference']);
    $serviceProvider['display'] = $org['name'];
    $serviceProvider['type'] = 'Organization';

    unset($location);
    if (isset($locid))
        $location['location'] = array("reference" => "Location/" . $locid, 'type' => 'Location');
    else {
        $location['location'] = getReference(array("type" => "Location"));
    }

    $loc = getResource($location['location']['reference']);
    $location['location']['display'] = $loc['name'];
    $location['status'] = 'completed';

// }while(!isset($org['partOf']) && $cnt++<$maxcnt);

    if (!isset($start))
        $start = '2010-01-01';
    $st = explode("-", $start);
    $sy = $st[0];
    $sm = $st[1];
    $sd = $st[2];

    $period['start'] = addTime($start, 0);

    if (!isset($end))
        $end = '2010-01-01';

    $et = explode("-", $end);
    $ey = $et[0];
    $em = $et[1];
    $ed = $et[2];

//  $dura = rand($dmin,$dmax); 
    $period['end'] = addTime($period['start'], $dura);
    $duration['value'] = floor($dura / 60);
    $duration['system'] = 'http://unitsofmeasure.org';
    $duration['code'] = 'min';
    // $duration['display'] = 'min';


    $now = getTDate();
    if (!isset($status)) {
        if (substr($now, 0, 10) == substr($period['start'], 0, 10))
            $status = $statuses1[round(rand(0, count($statuses1) - 1), 0)];
        else
            if (round(rand(1, 10), 0) == 1)
                $status = $statuses2[round(rand(0, count($statuses2) - 1), 0)];
            else
                $status = "finished";
    }


    $encounter['id'] = $id;
    $encounter['identifier'][0]['use'] = 'official';
    $encounter['identifier'][0]['value'] = $id;
    $encounter['identifier'][0]['system'] = $fhir_system;
    $encounter['status'] = $status;
    $encounter['class'] = $class;
    $encounter['subject'] = $subject;
    $encounter['length'] = $duration;
    $encounter['serviceProvider'] = $serviceProvider;
    $encounter['period'] = $period;
    $encounter['serviceType'] = $serviceType;
    $encounter['location'][] = $location;
    $result['body'] = $encounter;
} else {
    $result['body'] = $jsonarr;
}

?>