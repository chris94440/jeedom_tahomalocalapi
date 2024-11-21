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
    } elseif (isset($result['tahomaSession'])) {
        if (array_key_exists('tahomaSession',$result) && array_key_exists('pinCode',$result['tahomaSession']) && array_key_exists('token',$result['tahomaSession']) && array_key_exists('uuid',$result['tahomaSession'])) {
            $pincode=$result['tahomaSession']['pinCode'];
            $tokenValue=$result['tahomaSession']['token'];
            $uuid=$result['tahomaSession']['uuid'];

            config::save('tahomalocalapi_session', array('pinCode' => $pincode, 'token' => $tokenValue, 'uuid' => $uuid),'tahomalocalapi');
        } else {
            log::add('tahomalocalapi', 'error', 'Error in content of received message for tahomaSession : ' . json_encode($result));
        }
    } elseif (isset($result['gatewaysList'])) {
        if (array_key_exists('gatewaysList',$result)) {
            $jsonMef=str_replace(array('\\','"{','}"'), array('','{','}'),json_encode($result['gatewaysList']));
            log::add('tahomalocalapi', 'debug', 'Gateways list : ' . $jsonMef);
            config::save('tahomalocalapi_gatewaysList',  $result['gatewaysList'],'tahomalocalapi');
            tahomalocalapi::checkGateways($result['gatewaysList']);
        }
    } elseif (isset($result['healthCheck'])) {
        config::save('healthCheck', time(),'tahomalocalapi');
    }
    
} catch (Exception $e) {
    log::add('tahomalocalapi', 'error', displayException($e)); //remplacez template par l'id de votre plugin
}