<?php

try {
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

    if (!jeedom::apiAccess(init('apikey'), 'tahomalocalapi')) { //remplacez template par l'id de votre plugin
        echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
        die();
    }
    if (init('test') != '') {
        echo 'OK';
        die();
    }
    $result = json_decode(file_get_contents("php://input"), true);
    if (!is_array($result)) {
        die();
    }
    
    if (isset($result['eventItem'])) {        
        log::add('tahomalocalapi', 'debug', 'Message receive for evenItem -> ' . json_encode($result['eventItem']));
        tahomalocalapi::updateItems($result['eventItem']);
    } elseif (isset($result['devicesList'])) {
        log::add('tahomalocalapi', 'debug', 'Message receive for devicesList, nb object : ' . sizeof($result['devicesList']));
        log::add('tahomalocalapi', 'debug', '   - content  : ' . json_encode($result['devicesList']));
        tahomalocalapi::create_or_update_devices($result['devicesList']);
    } elseif (isset($result['execIdEvent'])) {
        $jsonMef=str_replace(array('\\','"{','}"'), array('','{','}'),json_encode($result['execIdEvent']));
        log::add('tahomalocalapi', 'debug', 'Message receive for execIdEvent : ' . $jsonMef);
        tahomalocalapi::storeExecId($jsonMef);
    }
    
} catch (Exception $e) {
    log::add('tahomalocalapi', 'error', displayException($e)); //remplacez template par l'id de votre plugin
}