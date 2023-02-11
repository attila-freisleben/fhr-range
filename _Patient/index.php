<?php


$id = isset($_REQUEST['id']) ? cdv($_REQUEST["id"]) : genID('TAJ', '', $nat);

$jsonarr = json_decode(file_get_contents_https($baseurl . "/$resource/$id"), true);
if (!isset($jsonarr['id'])) {

    $gndr = isset($_REQUEST['gender']) ? $_REQUEST['gender'] : $gender[round(rand(1, 2), 0)];
    $nc = rand(1, 10) < 8 ? 1 : round(rand(1, 2), 0);

    $lang = $_REQUEST['lang'];
    $prefix = $_REQUEST['prefix'];


    if (!isset($_REQUEST['nat']))
        $_REQUEST['nat'] = 'en';

    if (!isset($_REQUEST['lang']))
        $_REQUEST['lang'] = $nat;


    $opts = $_REQUEST;


    $address[] = genAddress($opts);

    $active = rand(1, 100) > 5;
    $birthDate = genBirthdate(array('gender' => $gndr, 'nat' => $nat));
    if (rand(1, 100) < 5) {
        $deceased = genDate($birthDate);
        $active = false;
    }


    if (in_array($nat, $cyrs)) {

        unset($name);
        if ($gndr == 'female' && rand(0, 10) < 6) {
            $name[] = genName(array('use' => "temp", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'lang' => $lang, 'birthDate' => $birthDate));
            $name[] = genName(array('use' => "temp", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'name' => $name[0], 'lang' => $lang, 'birthDate' => $birthDate));
        } else
            $name[] = genName(array('use' => "temp", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'lang' => $lang, 'birthDate' => $birthDate));


        foreach ($name as $nam) {
            unset($givn);
            unset($pref);
            $fam = to_cyr($nam['family']);
            $txt = to_cyr($nam['text']);
            foreach ($nam['given'] as $gvn) {
                $givn[] = to_cyr($gvn);
            }
            foreach ($nam['prefix'] as $prf) {
                $pref[] = to_cyr($prf);
            }

            if (is_array($pref))
                $ns[] = array('use' => 'official', 'text' => $txt, 'family' => $fam, 'given' => $givn, 'prefix' => $pref);
            else
                $ns[] = array('use' => 'official', 'text' => $txt, 'family' => $fam, 'given' => $givn);
        }
        $nn = $name;
        unset($name);
        foreach ($ns as $n)
            $name[] = $n;
        foreach ($nn as $n)
            $name[] = $n;

        $aa = $address;

        foreach ($aa as $addr) {
            $cty = to_cyr($addr['city']);
            $state = to_cyr($addr['state']);
            $txt = to_cyr($addr['text']);
            $cntry = $addr['country'];
            unset($ln);
            foreach ($addr['line'] as $lin)
                $ln[] = to_cyr($lin);
            $as[] = array('city' => $cty, 'state' => $state, 'text' => $txt, 'country' => $cntry, 'line' => $ln);
        }
        unset($address);
        foreach ($as as $a)
            $address[] = $a;
        foreach ($aa as $a)
            $address[] = $a;


    } else {
        unset($name);
        if ($gndr == 'female' && rand(0, 10) < 6) {
            $name[] = genName(array('use' => "maiden", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'lang' => $lang, 'birthDate' => $birthDate));
            $name[] = genName(array('use' => "official", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'name' => $name[0], 'lang' => $lang, 'birthDate' => $birthDate));
        } else
            $name[] = genName(array('use' => "official", 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $nat, 'lang' => $lang, 'birthDate' => $birthDate));
    }


    $patient['id'] = $id;
    $patient['identifier'][0]['use'] = 'official';
    $patient['identifier'][0]['value'] = $id;
    $patient['identifier'][0]['system'] = $fhir_system;
    $patient['name'] = $name;
    $patient['birthDate'] = $birthDate;
    $patient['gender'] = $gndr;
    $patient['active'] = $active;
    if (isset($deceased))
        $patient['deceased'] = $deceased;
    $patient['address'] = $address;
    unset($telecom);
    if (floor(rand(0, 10)) > 8)
        $telecom[] = genEmail(array('name' => $name, 'birthDate' => $birthDate, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => $nat));

    $telecom[] = genEmail(array('name' => $name, 'birthDate' => $birthDate, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => 'com'));

    $homePhone = genPhone(array('name' => $name, 'birthDate' => $birthDate, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => $nat, 'use' => 'home'));

    if ($homePhone['use'] == 'home')
        $telecom[] = $homePhone;
    if (floor(rand(0, 10)) > 1)
        $telecom[] = genPhone(array('name' => $name, 'birthDate' => $birthDate, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => $nat, 'use' => 'mobile'));
    if (floor(rand(0, 10)) > 9)
        $telecom[] = genPhone(array('name' => $name, 'birthDate' => $birthDate, 'state' => $addr[0]['state'], 'district' => $addr[0]['district'], 'city' => $addr[0]['city'], 'nat' => $nat, 'use' => 'work'));

    $prank = 1;
    foreach ($telecom as $key => $tc) {
        if ($tc['system'] == 'phone')
            $telecom[$key]['rank'] = $prank++;
    }
    $erank = 1;
    foreach ($telecom as $key => $tc) {
        if ($tc['system'] == 'email')
            $telecom[$key]['rank'] = $erank++;
    }

    $patient['telecom'] = $telecom;
    $result['body'] = $patient;

} else {
    $result['body'] = $jsonarr;
}

$endpoint_post = "/Patients/addPatient";
$endpoint_get = "/Patients/getPatient?taj=$id";

?>