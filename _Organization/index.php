<?php


include_once('include.php');

$nat = $_REQUEST['address_country'];
$city = $_REQUEST['address_city'];
$postalCode = $_REQUEST['address_postalcode'];
$name = $_REQUEST['name'];
$parent = $_REQUEST['parent'];
$lang = $_REQUEST['_lang'];


if (!isset($lang))
    $lang = $nat;

$addr = genAddress(array('nat' => $nat, 'city' => tl($city, $lang)));

$ad['city'] = tc($addr['city'], $lang);
$ad['text'] = tc($addr['text'], $lang);
$ad['state'] = tc($addr['state'], $lang);
$ad['line'][0] = tc($addr['line'][0], $lang);
$ad['country'] = $addr['country'];

$address[] = $ad;

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genOrgID(array('address' => $addr));

$jsonarr = json_decode(file_get_contents_https($baseurl . "$resource/$id"), true);;

if (!isset($jsonarr['id'])) {
    $type = !isset($parent) ? 'parent' : 'child';
// $name = genOrg(array('type'=>$type,'address'=>$addr,'nat'=>$nat,'parent'=>$parent));

    $active = rand(1, 100) > 0; //true for now

    $organization['id'] = $id;
    $organization['identifier'][0]['use'] = 'official';
    $organization['identifier'][0]['value'] = $id;
    $organization['identifier'][0]['system'] = $fhir_system;
    if ($type != 'parent')
        $organization['partOf'] = array('reference' => "Organization/$parent");

    $organization['name'] = $name;
    $organization['active'] = $active;
    $organization['address'] = $address;
    $eaddr = str_replace(array($city, " - ", " ", "’"), array("", "", ".", ""), tl($name, $lang));
    $dom = str_replace(array($city, " - ", " ", "’"), array("", "", ".", ""), tl($addr['city'], $lang));

    $organization['telecom'][] = genPhone(array('name' => $name, 'use' => 'work', 'nat' => $nat, 'city' => $addr['city']));
    $organization['telecom'][] = genEmail(array('eaddr' => $eaddr, 'use' => 'work', 'nat' => $nat, 'city' => $addr['city'], 'domain' => $dom . ".$nat"));

    $result['body'] = $organization;
} else {
    $result['body'] = $jsonarr;
}


?>