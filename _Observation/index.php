<?php

$encounter['id'] = $_REQUEST['encounter'];

$statuses[] = "registered";
$statuses[] = "preliminary";
$statuses[] = "final";
$statuses[] = "amended";

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : genID('');

$jsonarr = json_decode(file_get_contents_https($baseurl . "/$resource/$id"), true);
if (!isset($jsonarr['id'])) {
    $status = $statuses[round(rand(0, count($statuses) - 1), 0)];

    if (isset($encounter['id']))
        $encounter = array("reference" => "Encounter/" . $encounter['id']);
    else
        $encounter = getReference(array("type" => "Encounter"));

    $enc = getResource($encounter['reference']);
    $org = getResource($enc['serviceProvider']['reference']);
    $pat = getResource($enc['subject']['reference']);
    $performer = $enc['participant'][0]['individual'];

    $age = substr($enc['period']['start'], 1, 4) - substr($pat['birthDate'], 1, 4);
    $cond = getResource($enc['diagnosis'][0]['condition']['reference']);

    $opts['nat'] = 'hu';
    $opts['gender'] = $pat['gender'] == 'male' ? 1 : 2;
    $opts['profession'] = $encounter['serviceType'][1]['code']; //substr($org['name'],0,2);
    $opts['count'] = $_REQUEST['count'] == "" ? round(rand(1, 5), 0) : $_REQUEST['count'];
    $opts['date'] = $enc['period']['start'];

    $opts['birthDate'] = $pat['birthDate'];
    $opts['ICD'] = $cond['code']['coding'][1]['code'];

    $observation['id'] = $id;

    $observation['resourceType'] = 'Observation';
    $observation['identifier'][0]['use'] = 'official';
    $observation['identifier'][0]['value'] = $id;
    $observation['identifier'][0]['system'] = $fhir_system;
    $observation['status'] = $status;
    $observation['encounter'] = $encounter;
    $observation['performer'][] = $performer;
    $observation['subject'] = $enc['subject'];
    $observation['effectiveDateTime'] = $enc['period']['start'];

    $profession = $enc['serviceType']['coding'][1]['code'];

    if ($profession == '0100') {
        $panel = 'ACTIVITY';
    }
    if ($profession == '1900')
        $panel = 'PULMON';
    if ($profession == '0600')
        $panel = 'OTO';
    if ($profession == '0103')
        $panel = 'CAPD';

    if (rand(0, 10) < 4)
        $panel = 'GENERAL';
    if (rand(0, 10) < 4)
        $panel = 'BLOODPRESSURE';

    $panel = 'OTO';


    $code = array();
    $opts['panel'] = $panel;
    $code = genObservations($opts);

    $i = 0;

    $observation['code'] = $code['code'];

    if (isset($code['component']))
        $observation['component'] = $code['component'];

    if (isset($observation['component']))
        foreach ($observation['component'] as $cc) {
            if (isset($cc['code']['coding'][0]['code'])) {
                $lncarr = json_decode(file_get_contents_https($loincurl . "/" . $cc['code']['coding'][0]['code']), true);
                $observation['component'][$i++]['code']['coding'][0]['display'] = $lncarr['coding']['display'];
            }
        }

    if (isset($observation['code']['coding'][0]['code'])) {
        $lncarr = json_decode(file_get_contents_https($loincurl . "/" . $observation['code']['coding'][0]['code']), true);
        $observation['code']['coding'][0]['display'] = $lncarr['coding']['display'];
    }


    if (rand(0, 10) <= 10) {
        $observation['div'] = $div;
        $observations[] = $observation;

        unset($observation['code']);
        unset($observation['component']);

        unset($observation['valueString']);
        unset($observation['valueQuantity']);
        unset($observation['valueString']);


        $id = $observation['id'];
        $id++;

        $labopts['gender'] = $opts['gender'];
        $labopts['date'] = $opts['date'];
        $labopts['birthDate'] = $opts['birthDate'];
        $labopts['ICD'] = $opts['ICD'];
        $labor = genLabor($labopts);
        foreach ($labor as $code) {
            $observation['id'] = $id++;

            unset($observation['valueString']);
            unset($observation['valueQuantity']);
            unset($observation['valueString']);

            $observation['code'] = $code['code'];
            if (isset($code['valueQuantity']))
                $observation['valueQuantity'] = $code['valueQuantity'];
            if (isset($code['valueDateTime']))
                $observation['valueDateTime'] = $code['valueDateTime'];
            if (isset($code['valueString']))
                $observation['valueString'] = $code['valueString'];


            if (isset($code['component']))
                $observation['component'] = $code['component'];

            if (isset($observation['component']))
                foreach ($observation['component'] as $cc) {
                    if (isset($cc['code']['coding'][0]['code'])) {
                        $lncarr = json_decode(file_get_contents_https($loincurl . "/" . $cc['code']['coding'][0]['code']), true);
                        $observation['component'][$i++]['code']['coding'][0]['display'] = $lncarr['coding']['display'];
                    }
                }

            if (isset($observation['code']['coding'][0]['code'])) {
                $lncarr = json_decode(file_get_contents_https($loincurl . "/" . $observation['code']['coding'][0]['code']), true);
                $observation['code']['coding'][0]['display'] = $lncarr['coding']['display'];
            }
            $observation['div'] = $div;
            $observations[] = array('resource' => $observation);
        }

        $bundle['resourceType'] = 'Bundle';
        $bundle['type'] = 'searchset';
        $bundle['id'] = 'obs' + date();
        $bundle['entry'] = $observations;

        $result['body'] = $bundle;
    } else
        $result['body'] = $observation;
} else {
    $result['body'] = $jsonarr;
}

?>