<?php

$encounter['id'] = $_REQUEST['encounter'];
$subject['id'] = $_REQUEST['subject'];
$recorder['id'] = $_REQUEST['recorder'];
$servicetype = $_REQUEST['servicetype'];
$cnt = $_REQUEST['cnt'];
$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : 2019;


$clinicalStatuses[] = "active";
// $clinicalStatuses[] = "recurrence";
// $clinicalStatuses[] = "relapse";
$clinicalStatuses[] = "inactive";
// $clinicalStatuses[] = "remission";
// $clinicalStatuses[] = "resolved";

$class = 'AMB';

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genID('');

// $jsonarr = json_decode(file_get_contents_https($baseurl."/$resource/$id"),true);

if (!isset($jsonarr['id'])) {
    $clinicalStatus['coding'][0] = array("system" => "http://hl7.org/fhir/ValueSet/condition-clinical", "code" => $clinicalStatuses[round(rand(0, count($clinicalStatuses) - 1), 0)]);

    if (isset($encounter['id']))
        $encounter = array("reference" => "Encounter/" . $encounter['id'], 'type' => 'Encounter');
    else
        $encounter = getReference(array("type" => "Encounter"));


    if (isset($subject['id'])) {
        $pat = getResource('Patient/' . $subject['id']);
        $subject = array("reference" => "Patient/" . $subject['id'], 'type' => 'Patient', 'display' => $pat['name'][0]['text']);
    }
//  else
//    $subject = getReference($enc['subject']['reference']);

    if (isset($recorder['id'])) {
        $pra = getResource('Practitioner/' . $recorder['id']);
        $recorder = array("reference" => "Practitioner/" . $recorder['id'], 'type' => 'Practitioner', 'display' => $pra['name'][0]['text']);
    } else {
        $enc = getResource($encounter['reference']);
        $recorder = $enc['participant'][0]['individual'];
        if (isset($enc['period.start']))
            $year = substr($enc['period']['start'], 0, 4);
    }


    $age = round(($year - substr($pat['birthDate'], 0, 4)) / 10, 0);


    $opts['nat'] = $nat;
    $opts['gender'] = $pat['gender'] == 'male' ? 1 : 2;
    $opts['profession'] = $servicetype;
    $opts['agegrp'] = $age;
    $opts['cnt'] = $cnt;
    if ($opts['nat'] == 'hu')
        $opts['system'] = 'ICD';
    else
        $opts['system'] = 'SNOMED_CT';

    $dcnt = 0;
    $codes = genCondition($opts);

    $condition['id'] = $id;
    $condition['identifier'][0]['use'] = 'official';
    $condition['identifier'][0]['value'] = $id;
    $condition['identifier'][0]['system'] = $fhir_system;
    // $condition['clinicalStatus'] = $clinicalStatus;
    $condition['encounter'] = $encounter;

//  $condition['context']      = $encounter;
    $condition['recorder'] = $recorder;
    $condition['code'] = $code;
    $condition['subject'] = $subject;

//  foreach($codes as $code){
    $condition1 = $condition;
    $condition1['code'] = $codes;
    $conditions[] = $condition1;
//   }

    $result['body'] = $conditions[0];

} else {
    $result['body'] = $jsonarr;
}

?>