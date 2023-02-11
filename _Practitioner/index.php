<?php

$id = isset($_REQUEST['id']) ? $_REQUEST["id"] : genID('TAJ');

$jsonarr = json_decode(file_get_contents_https($baseurl . "/$resource/$id"), true);
if (!isset($jsonarr['id'])) {


    $gndr = isset($_REQUEST['gender']) ? $_REQUEST['gender'] : $gender[round(rand(1, 2), 0)];
    $nc = rand(1, 10) < 8 ? 1 : round(rand(1, 2), 0);
    $lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : $nat;

    $prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : "";

    if (!isset($_REQUEST['nat']))
        $_REQUEST['nat'] = 'en';

    $opts = $_REQUEST;

    $address[] = genAddress($opts);


    if (in_array($nat, $cyrs)) {

        unset($name);
        $name[] = genName(array('use' => "temp", 'gender' => $gndr, 'prefix' => $prefix, 'givenNames' => $nc, 'nat' => $nat, 'lang' => $lang, 'birthDate' => $birthDate));

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


        $active = rand(1, 100) > 5;
        $birthDate = genBirthdate(array('gender' => $gndr, 'nat' => $nat, 'min' => 25, 'max' => 65));
        if (rand(1, 100) < 5) {
            $deceased = genDate($birthDate);
            $active = false;
        }


        $patient['id'] = $id;
        $patient['identifier'][0]['use'] = 'official';
        $patient['identifier'][0]['value'] = $id;
        $patient['identifier'][0]['system'] = $fhir_system;
        $patient['name'] = $name;
        $patient['birthDate'] = $birthDate;
        $patient['gender'] = $gndr;
        $patient['active'] = $active;

        $patient['address'] = $address;
        unset($telecom);
        if (floor(rand(0, 10)) > 8)
            $telecom[] = genEmail(array('name' => array($name[1]), 'birthDate' => $birthDate, 'state' => $addr[1]['state'], 'district' => $addr[1]['district'], 'city' => $addr[1]['city'], 'nat' => $nat));

        $telecom[] = genEmail(array('name' => array($name[1]), 'birthDate' => $birthDate, 'state' => $addr[1]['state'], 'district' => $addr[1]['district'], 'city' => $addr[1]['city'], 'nat' => 'com'));

        $homePhone = genPhone(array('name' => array($name[1]), 'birthDate' => $birthDate, 'state' => $addr[1]['state'], 'district' => $addr[1]['district'], 'city' => $addr[1]['city'], 'nat' => $nat, 'use' => 'home'));

        if ($homePhone['use'] == 'home')
            $telecom[] = $homePhone;
        if (floor(rand(0, 10)) > 1)
            $telecom[] = genPhone(array('name' => array($name[1]), 'birthDate' => $birthDate, 'state' => $addr[1]['state'], 'district' => $addr[1]['district'], 'city' => $addr[1]['city'], 'nat' => $nat, 'use' => 'mobile'));
        if (floor(rand(0, 10)) > 9)
            $telecom[] = genPhone(array('name' => array($name[1]), 'birthDate' => $birthDate, 'state' => $addr[1]['state'], 'district' => $addr[1]['district'], 'city' => $addr[1]['city'], 'nat' => $nat, 'use' => 'work'));

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

    } else {
        unset($name);
        $name[] = genName(array('use' => "official", 'prefix' => $prefix, 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $lang));
        if ($gndr == 'female')
            $name[] = genName(array('use' => "maiden", 'prefix' => $prefix, 'gender' => $gndr, 'givenNames' => $nc, 'nat' => $lang, 'name' => $name[0]));


        $active = rand(1, 100) > 5;
        $birthDate = genBirthdate(array('gender' => $gndr, 'nat' => $nat, 'min' => 25, 'max' => 65));
        if (rand(1, 100) < 5) {
            $deceased = genDate($birthDate);
            $active = false;
        }


        $patient['id'] = $id;
        $patient['identifier'][0]['use'] = 'official';
        $patient['identifier'][0]['value'] = $id;
        $patient['identifier'][0]['system'] = $fhir_system;
        $patient['name'] = $name;
        $patient['birthDate'] = $birthDate;
        $patient['gender'] = $gndr;
        $patient['active'] = $active;

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

    }


    $result['body'] = $patient;


} else {
    $result['body'] = $jsonarr;
}


?>