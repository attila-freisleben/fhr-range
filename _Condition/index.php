<?php

$encounter['id'] = $_REQUEST['encounter'];
$subject['id'] = $_REQUEST['subject'];
$recorder['id'] = $_REQUEST['recorder'];
$servicetype = $_REQUEST['servicetype'];
$cnt = isset($_REQUEST['cnt']) ? $_REQUEST['cnt'] : 1;
$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : 2019;


$clinicalStatuses[] = "active";
// $clinicalStatuses[] = "recurrence";
// $clinicalStatuses[] = "relapse";
$clinicalStatuses[] = "inactive";
// $clinicalStatuses[] = "remission";
// $clinicalStatuses[] = "resolved";

$class = 'AMB';

$mpt = rand(5, 8);

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genID('');


if (!isset($jsonarr['id'])) {
    $clinicalStatus['coding'][0] = array("system" => "http://hl7.org/fhir/ValueSet/condition-clinical", "code" => $clinicalStatuses[round(rand(0, count($clinicalStatuses) - 1), 0)]);

    if (isset($encounter['id']))
        $encounter = array("reference" => "Encounter/" . $encounter['id'], 'type' => 'Encounter');
    else
        $encounter = getReference(array("type" => "Encounter"));


    $enc = getResource($encounter['reference']);
    if (!isset($serviceType))
        $serviceType = $enc['serviceType']['coding'][1]['code'];

    if (!isset($subject['id'])) {
        $subject = getResource($enc['subject']['reference']);
    }

    if (isset($subject['id'])) {
        $pat = getResource('Patient/' . $subject['id']);
        $patid = $subject['id'];
        $subject = array("reference" => "Patient/" . $subject['id'], 'type' => 'Patient', 'display' => $pat['name'][0]['text']);
    } else
        $subject = getReference($enc['subject']['reference']);

    if (isset($recorder['id'])) {
        $pra = getResource('Practitioner/' . $recorder['id']);
        $recorder = array("reference" => "Practitioner/" . $recorder['id'], 'type' => 'Practitioner', 'display' => $pra['name'][0]['text']);
    } else {
        $recorder = $enc['participant'][0]['individual'];
        if (isset($enc['period.start']))
            $year = substr($enc['period']['start'], 0, 4);
    }
    $age = round(($year - substr($pat['birthDate'], 0, 4)) / 10, 0);

    $pt = 0;
    $pcs = array();
    $patconds = json_decode(file_get_contents_https($baseurl . "/Encounter?subject.reference=Patient/" . $patid . "&serviceType.coding[1].code=" . $serviceType . "&_summary=count&_groupby=diagnosis[0].condition.display"), true);
    if (isset($patconds['entry'])) {
        $patconds = $patconds['entry'];

        $ptc['patconds'] = $patconds;
        $ptc['uri'] = $baseurl . "/Encounter?subject.reference=Patient/" . $patid . "&serviceType.coding[1].code=" . $serviceType . "&_summary=count&_groupby=diagnosis[0].condition.display";

        $pt = 0;
        foreach ($patconds as $pc) {
            if ($pc['value'] != 'null' && isset($pc['value'])) {
                $pt++;
                $pcs[] = $pc['value'];
            }
            $ptc['Pt'] = $pt;
        }

        $ptc['subject'] = $subject;
        $ptc['pcs'] = $pcs;
    }

    if ($pt >= $mpt) {
        $dname = $pcs[rand(0, count($pcs) - 1)];
        $ptc['dname'] = $dname;

        $patcond = json_decode(file_get_contents_https($baseurl . "/Condition?subject.reference=Patient/" . $patid . "&code.coding[0].display=" . urlencode($dname)), true);
        $ptc['code']['YYY'] = $patcond;
        if (isset($patcond['entry']))
            $patcond = $patcond['entry'][0]['resource'];
        $ptc['code']['uri'] = $baseurl . "/Condition?subject.reference=Patient/" . $patid . "&code.coding[0].display=$dname";
        $ptc['code']['XXX'] = $patcond;
        $codes = $patcond['code'];
    } else {
        $opts['nat'] = $nat;
        $opts['gender'] = $pat['gender'] == 'male' ? 1 : 2;
        $opts['profession'] = $serviceType;
        $opts['agegrp'] = $age;
        $opts['cnt'] = $cnt;
        $opts['dname'] = $dname;
        if ($opts['nat'] == 'hu')
            $opts['system'] = 'ICD';
        else
            $opts['system'] = 'SNOMED_CT';

        $dcnt = 0;
        $codes = genCondition($opts);
    }

    $condition['id'] = $id;
    $condition['identifier'][0]['use'] = 'official';
    $condition['identifier'][0]['value'] = $id;
    $condition['identifier'][0]['system'] = $fhir_system;
    // $condition['clinicalStatus']        = $clinicalStatus;
    $condition['encounter'] = $encounter;

//  $condition['context']      = $encounter;
    $condition['recorder'] = $recorder;
    $condition['code'] = $code;
    $condition['subject'] = $subject;

//$condition['patcond'] = $ptc;

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