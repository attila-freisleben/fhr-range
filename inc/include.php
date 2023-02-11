<?php
error_reporting(E_ERROR);
set_time_limit(0);

include_once("db_functions.inc.php");
include_once("settings.php");

$deflimit = 20;       //default limit on returned rows
$searchPrecision = 0.05;
$approxPrecision = 0.1;

$gentextdiv['div'] = "<div xmlns=\"http://www.w3.org/1999/xhtml\">Generated $resource resource for testing purposes. Generated by $baseurl</div>";
$gentextdiv['status'] = "generated";


$keywords[] = "resource";
$keywords[] = "method";
$keywords[] = "id";
$keywords[] = "_debug";
$keywords[] = "_summary";
$keywords[] = "_count";
$keywords[] = "_sort";
$keywords[] = "_include";
$keywords[] = "_revinclude";
$keywords[] = "_random";
$keywords[] = "_groupby";
$keywords[] = "_top";
$keywords[] = "_bottom";
$keywords[] = "_c";
$keywords[] = "_crf";
$keywords[] = "_offset";
$keywords[] = "_lang";
$keywords[] = "_has";
$keywords[] = "_min";
$keywords[] = "_max";

$cond_prefix['eq'] = "=";
$cond_prefix['ne'] = "<>";
$cond_prefix['gt'] = ">";
$cond_prefix['lt'] = "<";
$cond_prefix['ge'] = ">=";
$cond_prefix['le'] = "<=";
$cond_prefix['sa'] = ">=";
$cond_prefix['eb'] = "<=";
$cond_prefix['ap'] = "approx.";
$cond_prefix['bt'] = "between";
$app_low = 0.95;
$app_high = 1.05;

$cmds_dir = "./cmds";

/****************************************************************/
function getkey($t_resource)
{
    /****************************************************************/
    global $basedb, $db;
    $key = $db->db_exec("insert into $t_resource._json_sequence (id) values (null)");

    return $key;
}

/****************************************************************/
function getTdate()
{
    /****************************************************************/
    return gmdate('Y-m-d') . "T" . gmdate('H:i:sP');
}

/****************************************************************/
function validateResource($data, $resourceType, $profile)
    /****************************************************************/
{
    global $cs_url;

    if ($profile != '')
        $profile = "?profile=$profile";

    $url = $cs_url . $resourceType . '/$validate' . $profile;

    if (is_array($st))
        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    else
        $payload = utf8_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1800000);
    curl_setopt($ch, CURLOPT_MAXCONNECTS, 50);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/fhir+json',
            'Content-Length: ' . strlen($payload))
    );

    $result = curl_exec($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);

    $resarr = json_decode($result, true);

    $ret['valid'] = $resarr['issue'][0]['severity'] != 'error' ? 0 : 1;
    $ret['text'] = $resarr;

    return $ret;
}

/****************************************************************/
function curl_send_fhir($url, $method, $data)
    /****************************************************************/
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 150000);
    curl_setopt($ch, CURLOPT_MAXCONNECTS, 50);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/fhir+json',
            'Content-Length: ' . strlen($data))
    );

    $result = curl_exec($ch);

    curl_close($ch);
    return $result;
}


/****************************************************************/
function debug($qstr, $str)
    /****************************************************************/
{
    global $debug;
    if (!is_array($str))
        $str = array($str);
    if ($debug)
        echo "\r\n******" . $qstr . "******\r\n" . print_r($str, true) . "\r\n********\r\n";
}


/****************************************************************/
function getResourceDef($resource)
    /****************************************************************/
{
    global $hl7fhirBase;
    $json = file_get_contents($hl7fhirBase . $resource . ".profile.json");
    $jsarr = json_decode($json, true);
    if (json_last_error != JSON_ERROR_NONE) {
        $json = file_get_contents($cs_url . "/StructureDefinition/" . $resource);
        $jsarr = json_decode($json, true);
        $jsarr = $jsarr['entry']['resource'];
    }
    return $jsarr;
}

/****************************************************************/
function logger($log)
{
    /****************************************************************/
    return "";
    $whoami = basename($_SERVER['SCRIPT_NAME'], '.php');
    $logfile = $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/logs/$whoami.log";
    $log = $whoami . "\t" . date('Y.m.d H:i:s') . "\t" . $_SERVER["HTTP_X_FORWARDED_FOR"] . "\t" . $log . "\n";
    file_put_contents($logfile, $log, FILE_APPEND);
}


/****************************************************************/
function waitForResource($resource)
{
    /****************************************************************/
    global $db, $basedb;
    $maxtime = 10;
    $start = time();
    do {
        $db->db_exec("select * from $basedb._resources where resource='$resource'");
        $rs = $db->db_fetch();
        $timeout = time() - $start > $maxtime;
        if ($rs['LOCKED'] == 1)
            usleep(500000);
    } while (($rs['RESOURCE'] == '$resource' && $rs['LOCKED'] == 1) || $timeout);

    return !$timeout;
}

/****************************************************************/
function getZdate()
{
    /****************************************************************/
    return gmdate('Y-m-d') . "T" . gmdate('H:i:s') . ".000Z";
}


/****************************************************************/
function file_get_contents_https($url, $to = 1500)
{
    /****************************************************************/
    $ch = curl_init($url);
//    $to = isset($opts['timeout']) ? $opts['timeout'] : 1800;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //false
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //false
    curl_setopt($ch, CURLOPT_HEADER, false); //false
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); //1 
    curl_setopt($ch, CURLOPT_TIMEOUT, $to); //timeout in seconds
    // curl_setopt($ch,CURLOPT_TIMEOUT_MS,1800000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/fhir+json'));
    try {
        $result = curl_exec($ch);
    } catch (exception $e) {
        echo "\r\n" . $url . "::" . $e->getMessage() . "\r\n";
        $result = file_get_contents_https($url, $to);
    }
    curl_close($ch);
    return $result;
}

/****************************************************************/
function curl_send($url, $payload, $method = "")
{
    /****************************************************************/

    if (is_array($payload))
        $payload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    if ($method == 'DELETE')
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    else
        if ($method == 'PUT')
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        else
            curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1500000);

    curl_setopt($ch, CURLOPT_MAXCONNECTS, 50);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));

    try {
        $result = curl_exec($ch);
    } catch (exception $e) {
        echo "\r\n" . $url . "::" . $e->getMessage() . "\r\n";
        $result = curl_send($url, $payload, $method);
    }

    curl_close($ch);

    return $result;
}


/****************************************************************/
function curl_send_ggl($url, $method, $data, $key)
    /****************************************************************/
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 150000);
    curl_setopt($ch, CURLOPT_MAXCONNECTS, 50);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        , 'X-Goog-Api-Key: ' . $key)
    );

    $result = curl_exec($ch);

    curl_close($ch);
    return $result;
}


/*************************************************/
function gtranslate($text, $lang = 'en-US')
{
    /************************************************/
    global $db, $rangeDB;


    $gs = "https://translation.googleapis.com/language/translate/v2";
    $key = "";

    $text = strtolower($text);

    $text = str_replace('performer', 'participant', $text);

    $text = str_replace("'", "´", $text);


    $hash = md5($text . $lang);
    $db->db_exec("select transtext from $rangeDB.translator where hash='$hash' and lang='$lang'");
    $rs = $db->db_fetch();
    if ($rs['TRANSTEXT'] == "") {
        $gtext = explode("<br>", $text);
        $body['q'] = $gtext;
        $body['target'] = substr($lang, 0, 2);

        $json = curl_send_ggl($gs, 'POST', json_encode($body, JSON_UNESCAPED_SLASHES || JSON_UNESCAPED_UNICIDE || JSON_PRETTY_PRINT), $key);
        $arr = json_decode($json, true);
        $ret = "";

        foreach ($arr['data']['translations'] as $translated)
            $ret .= $translated['translatedText'] . " ";

        if ($ret == "")
            $ret = $text;

        $ret = str_replace('&quot;', "˝", $ret);

        $ret = trim($ret);
        $hash = md5($text . $lang);
        $db->db_exec("insert into $rangeDB.translator values ('$text','$hash','$lang','$ret')");
    } else
        $ret = $rs['TRANSTEXT'];
    return $ret;
}

/***********************************************************/
function convertArray($arr, $narr = array(), $nkey = '')
{
    /***********************************************************/
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $narr = array_merge($narr, convertArray($value, $narr, $nkey . $key . '.'));
        } else {
            $narr[$nkey . $key] = $value;
        }
    }
    return $narr;
}


/***********************************************************/
function iter($arr, $dkey = "", $trarr, $lang)
{
    /***********************************************************/
    foreach ($arr as $key => $value) {
        if ($dkey == "")
            $trkey = $key;
        else
            $trkey = $dkey . "." . $key;

        $trkey = preg_replace('/[0-9]+/', '0', $trkey);

        if (is_array($value)) {
            $ret[$key] = iter($value, $trkey, $trarr, $lang);
        } else {
            if (in_array($trkey, $trarr))
                $ret[$key] = gtranslate($value, $lang);
            else
                $ret[$key] = $value;
        }
    }

    return $ret;
}

/***********************************************/
function translateResource($resource, $lang)
{
    /***********************************************/

    if (strtolower($lang) == 'ua')
        $lang = 'uk';
    if (strtolower($lang) == 'at')
        $lang = 'de';
    if (strtolower($lang) == 'gb')
        $lang = 'en';

    $trsummaryDetail =
        '{
        "value": "value"
 }';

    $grmPatientFlow =
        '{
        "datasets": [{ "label" : "label" }],
        "daily" : { "labels" : [],
                    "data" :[{ "label": "label" }] 
                   }
 }';


    $trHealthcareService = '{
        "name":"name", 
        "type": [{"coding": [{"display": "display"}] }],
        "category" : [{ "text" : "text", "coding": [{"display": "display"}] }],
        "serviceProvisionCode" : [{ "coding": [{"display": "display"}] }] 
 }';

    $trCondition =
        '{
        "code": {"coding": [{"display": "display"}] }
 }';

    $trObservation =
        '{
        "code": {"coding": [{"display": "display"}] },
        "component": [{ "code": {"coding": [{"display": "display"}] } }]

 }';

    $trEncounter = '{
        "class" : {"display" : "display" },
        "diagnosis": [{"use" :  [{"display": "display"}] , "condition": {"display":"display"} }],
        "participant": [ {"type" : [ {"coding": [{"display": "display"}] }] } ],
        "serviceType" : { "coding": [{"display": "display"}] } 
 }';

    $translate_resource['HealthcareService'] = $trHealthcareService;
    $translate_resource['Condition'] = $trCondition;
    $translate_resource['Encounter'] = $trEncounter;
    $translate_resource['Observation'] = $trObservation;

    $translate_resource['summaryDetail'] = $trsummaryDetail;
    $translate_resource['grmPatientFlow'] = $grmPatientFlow;

    $translateFields = $translate_resource[$resource['resourceType']];

    $trarr = convertArray(json_decode($translateFields, true));
    $trarr = array_keys($trarr);

    return iter($resource, "", $trarr, $lang);
}

function to_cyr($textlat)
{
    $cyr = [
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
        'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
        'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', ''
    ];
    $lat = [
        'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
        'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya',
        'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
        'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya', '’'
    ];
    $textlat = str_replace($lat, $cyr, $textlat);
    return $textlat;
}

function to_lat($textcyr)
{
    $cyr = [
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
        'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
        'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', ''
    ];
    $lat = [
        'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
        'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya',
        'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
        'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya', '’'
    ];
    $textcyr = str_replace($cyr, $lat, $textcyr);
    return $textcyr;
}


function tc($text, $lang)
{
    global $cyrs;
    if (in_array($lang, $cyrs))
        $text = to_cyr($text);
    return $text;
}

function tl($text, $lang)
{
    global $cyrs;
    if (in_array($lang, $cyrs))
        $text = to_lat($text);
    return $text;
}


$cyrs = array('UA', 'RU', 'BG', 'SRB');


?>