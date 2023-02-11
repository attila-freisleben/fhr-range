<?php

$nat = $_REQUEST['nat'];
$city = $_REQUEST['address_city'];
$name = $_REQUEST['name'];
$domain = $_REQUEST['domain'];
$eaddr = $_REQUEST['eaddr'];
$parent = $_REQUEST['parent'];
$org = $_REQUEST['org'];
$lat = $_REQUEST['lat'];
$lon = $_REQUEST['lon'];

$nat = strtolower($nat);

$addr = genAddress(array('nat' => $nat, 'city' => $city));

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genLocID(array('address' => $addr));

$jsonarr = json_decode(file_get_contents_https($baseurl . "$resource/$id"), true);;

if (!isset($jsonarr['id'])) {
    $type = !isset($parent) ? 'parent' : 'child';


    $location['id'] = $id;
    $location['identifier'][0]['use'] = 'official';
    $location['identifier'][0]['value'] = $id;
    $location['identifier'][0]['system'] = $fhir_system;
    if ($type != 'parent')
        $location['partOf'] = array('reference' => "Location/$parent");

    $location['name'] = $name;
    $location['address'] = $addr;

    $telecom[] = genPhone(array('name' => $name, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => $nat, 'use' => 'work'));
    $telecom[] = genEmail(array('name' => $name, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => 'com', 'use' => 'work', 'domain' => $domain, 'eaddr' => $eaddr));

    $location['telecom'] = $telecom;
    $location['status'] = "active";

    $location['position']['latitude'] = $lat;
    $location['position']['longitude'] = $lon;

    $organization = json_decode(file_get_contents_https($baseurl . "/Organization/$org"), true);
    $location['managingOrganization'] = array('reference' => "Organization/" . $org, 'type' => 'Organization', 'display' => $organization['name']);

    $result['body'] = $location;
} else {
    $result['body'] = $jsonarr;
}


?>