<?php


$nat = $_vars['address.country'][0];
if ($nat == "")
    $nat = $_vars['address_country'][0];
if ($nat == "")
    $nat = $_REQUEST['nat'];
$lang = $_REQUEST['lang'];

$q = "select szakma,name,snomed,display,code,sum(cnt)/10000 as pat_per_thousand from fhiringRange.forg_knds_jb a join fhiringRange.szakmak b on (a.szakma=b.code and type='O') group by 1,2,3,4,5 order by 5";
$db->db_exec($q);


$res = array();

for (; $rs = $db->db_fetch();) {
    if ($rs['PAT_PER_THOUSAND'] < 10)
        continue;
    unset($hs);

    $id = $rs['CODE'];
    $hs['id'] = $id;
    $hs['identifier'][0]['use'] = 'official';
    $hs['identifier'][0]['value'] = $id;
    $hs['identifier'][0]['system'] = $fhir_system;
    $hs['active'] = true;
    $hs['category'][0]['coding'][0]['code'] = '7';
    $hs['category'][0]['coding'][0]['display'] = 'Community Health Care';
    $hs['category'][0]['coding'][0]['system'] = 'http://terminology.hl7.org/CodeSystem/service-category';
    $hs['category'][0]['text'] = 'Community Health Care';

    $hs['type'][0]['coding'][0]['system'] = 'http://snomed.info/sct';
    $hs['type'][0]['coding'][0]['code'] = $rs['SNOMED'];
    $hs['type'][0]['coding'][0]['display'] = $rs['DISPLAY'];

    $hs['type'][1]['coding'][0]['system'] = $fhir_system;
    $hs['type'][1]['coding'][0]['code'] = $rs['CODE'];
    $hs['type'][1]['coding'][0]['display'] = $rs['DISPLAY'];


    $hs['name'] = $rs['DISPLAY'];
    $hs['serviceProvisionCode'][0]['coding'][0]['system'] = "http://terminology.hl7.org/CodeSystem/service-provision-conditions";
    $hs['serviceProvisionCode'][0]['coding'][0]['code'] = "free";
    $hs['serviceProvisionCode'][0]['coding'][0]['display'] = "Free";

    $forg = min(5, ceil($rs['PAT_PER_THOUSAND'] / 70));
    $dows = array('mon', 'tue', 'wed', 'thu', 'fri');

    $dow = $dows;
    $dow = array_values($dow);
    $hs['availableTime'][0]['daysOfWeek'] = $dow;
    $hs['availableTime'][0]['availableStartTime'] = '08:30:00';
    if ($rs['PAT_PER_THOUSAND'] < 50)
        $hs['availableTime'][0]['availableEndTime'] = '12:30:00';
    else
        $hs['availableTime'][0]['availableEndTime'] = '16:30:00';

    $hs['patient_per_thousand_people'] = $rs['PAT_PER_THOUSAND'];
    $hs['resourceType'] = 'HealthcareService';
    $res[]['resource'] = $hs;
}

$result['body']['resourceType'] = 'Bundle';
$result['body']['entry'] = $res;

?>

