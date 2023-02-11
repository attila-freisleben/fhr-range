<?php
$opts = $_REQUEST;
$id = $opts['id'];

$code = isset($opts['code']) ? $opts['code'] : 'doctor';

if (isset($id)) {
    $jsonarr = json_decode(file_get_contents_https($baseurl . "/$resource/$id"), true);
    $orgid = $opts['orgid'];
    $locid = $opts['locid'];
    $hsid = $opts['hsid'];
    $docid = $opts['docid'];

    if (!isset($jsonarr['id'])) {
        $practitionerrole['id'] = $id;
        $practitionerrole['identifier'][0]['use'] = 'official';
        $practitionerrole['identifier'][0]['value'] = $id;
        $practitionerrole['identifier'][0]['system'] = $fhir_system;
        $practitionerrole['active'] = true;
        $practitionerrole['code'][0]['coding'][0] = array('system' => 'http://terminology.hl7.org/CodeSystem/v2-0286', 'code' => $code);
    } else
        $practitionerrole = $jsonarr;

    if (isset($docid)) {
        $practitioner['reference'] = 'Practitioner/' . $docid;
        $practitioner['type'] = 'Practitioner';
        $tmp = json_decode(file_get_contents_https($baseurl . "/Practitioner/$docid"), true);
        $practitioner['display'] = $tmp['name'][0]['text'];
        $practitionerrole['practitioner'] = $practitioner;
    }

    if (isset($orgid)) {
        $orgid = str_replace('Organization/', '', $orgid);
        $organization['reference'] = 'Organization/' . $orgid;
        $organization['type'] = 'Organization';
        $tmp = json_decode(file_get_contents_https($baseurl . "/Organization/$orgid"), true);
        $organization['display'] = $tmp['name'];
        $practitionerrole['organization'] = $organization;
    }

    if (isset($locid)) {
        $locid = str_replace('Location/', '', $locid);
        $location['reference'] = 'Location/' . $locid;
        $location['type'] = 'Location';
        $tmp = json_decode(file_get_contents_https($baseurl . "/Location/$locid"), true);
        $location['display'] = $tmp['name'];
        $practitionerrole['location'][] = $location;
    }

    if (isset($hsid)) {
        $hsid = str_replace('HealthcareService/', '', $hsid);
        $healthcareService['reference'] = 'HealthcareService/' . $hsid;
        $healthcareService['type'] = 'HealthcareService';
        $tmp = json_decode(file_get_contents_https($baseurl . "/HealthcareService/$hsid"), true);
        $healthcareService['display'] = $tmp['name'];
        $practitionerrole['healthcareService'][] = $healthcareService;
    }

    $result['body'] = $practitionerrole;

} else {
    $result['body'] = 'ID needed';
}


?>