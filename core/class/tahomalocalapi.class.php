<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class tahomalocalapi extends eqLogic {
  /* Gestion du démon */
  public static function deamon_info() {
    $return = array();
    $return['log'] = __CLASS__;
    $return['state'] = 'nok';
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/tahomalocalapid.pid';
    if (file_exists($pid_file)) {
        if (@posix_getsid(trim(file_get_contents($pid_file)))) {
            $return['state'] = 'ok';
        } else {
            shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
        }
    }
    $return['launchable'] = 'ok';
    $user = config::byKey('user', __CLASS__); // exemple si votre démon à besoin de la config user,
    $pswd = config::byKey('password', __CLASS__); // password,
    // $clientId = config::byKey('clientId', __CLASS__); // et clientId
    $portDaemon=config::byKey('daemonPort', __CLASS__);
    if ($user == '') {
        $return['launchable'] = 'nok';
        $return['launchable_message'] = __('Le nom d\'utilisateur n\'est pas configuré', __FILE__);
    } elseif ($pswd == '') {
        $return['launchable'] = 'nok';
        $return['launchable_message'] = __('Le mot de passe n\'est pas configuré', __FILE__);
    // } elseif ($clientId == '') {
    //     $return['launchable'] = 'nok';
    //     $return['launchable_message'] = __('La clé d\'application n\'est pas configurée', __FILE__);
    }
    return $return;
}

/* Start daemon */
public static function deamon_start() {
  self::deamon_stop();
  $deamon_info = self::deamon_info();
  if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
  }

  $path = realpath(dirname(__FILE__) . '/../../resources/tahomalocalapid'); 
  $cmd = 'python3 ' . $path . '/tahomalocalapid.py'; // nom du démon
  $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
  $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '55009'); // port du daemon
  $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/tahomalocalapi/core/php/jeeTahomalocalapi.php'; // chemin de la callback url 
  $cmd .= ' --user "' . trim(str_replace('"', '\"', config::byKey('user', __CLASS__))) . '"'; // user compte somfy
  $cmd .= ' --pswd "' . trim(str_replace('"', '\"', config::byKey('password', __CLASS__))) . '"'; // et password compte Somfy
  $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__); // l'apikey pour authentifier les échanges suivants
  $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/tahomalocalapid.pid'; // et on précise le chemin vers le pid file (ne pas modifier)
  $cmd .= ' --pincode "' . trim(str_replace('"', '\"', config::byKey('pincode', __CLASS__))) . '"'; // Pin code box Somfy
  $cmd .= ' --boxLocalIp "' . trim(str_replace('"', '\"', config::byKey('boxLocalIp', __CLASS__))) . '"'; // IP box somfy
  
  log::add(__CLASS__, 'info', 'Lancement démon');
  $result = exec($cmd . ' >> ' . log::getPathToLog('tahomalocalapi_daemon') . ' 2>&1 &'); 
  $i = 0;
  while ($i < 20) {
      $deamon_info = self::deamon_info();
      log::add(__CLASS__, 'info', 'Daemon_info -> '. json_encode($deamon_info));
      if ($deamon_info['state'] == 'ok') {
          break;
      }
      sleep(1);
      $i++;
  }
  if ($i >= 30) {
      log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
      return false;
  }
  message::removeAll(__CLASS__, 'unableStartDeamon');
  return true;
}

/* Stop daemon */
public static function deamon_stop() {
  $pid_file = jeedom::getTmpFolder(__CLASS__) . '/tahomalocalapid.pid'; // ne pas modifier
  if (file_exists($pid_file)) {
      $pid = intval(trim(file_get_contents($pid_file)));
      system::kill($pid);
  }
  system::kill('tahomalocalapid.py'); // nom du démon à modifier
  sleep(1);
}

/* Send data to daemon */
public static function sendToDaemon($params) {
  $deamon_info = self::deamon_info();
  if ($deamon_info['state'] != 'ok') {
      throw new Exception("Le démon n'est pas démarré");
  }
  $params['apikey'] = jeedom::getApiKey(__CLASS__);
  $payLoad = json_encode($params);
  $socket = socket_create(AF_INET, SOCK_STREAM, 0);
  socket_connect($socket, '127.0.0.1', config::byKey('socketport', __CLASS__, '55009')); //port par défaut de votre plugin à modifier
  socket_write($socket, $payLoad, strlen($payLoad));
  socket_close($socket);
}

  /*     * *************************Attributs****************************** */

  public static function create_or_update_devices($devices) {
    log::add(__CLASS__, 'debug', 'create_or_update_devices');
    
    $eqLogics=eqLogic::byType(__CLASS__);
    foreach ($devices as $device) {
        log::add(__CLASS__, 'debug', '  - device : ' . $device['deviceURL']);
         $found = false;

         foreach ($eqLogics as $eqLogic) {
             if ($device['deviceURL'] == $eqLogic->getConfiguration('deviceURL')) {
                log::add(__CLASS__, 'debug', '      -> device already exist');
                 $eqLogic_found = $eqLogic;
                 $found = true;
                 break;
             }
         }

         if (!$found) {
            log::add(__CLASS__, 'debug', '      -> device not exist -> auto create it');
             $eqLogic = new eqLogic();
             $eqLogic->setEqType_name(__CLASS__);
             $eqLogic->setIsEnable(1);
             $eqLogic->setIsVisible(1);
             $eqLogic->setName($device['label']);
             $eqLogic->setConfiguration('type', $device['controllableName']);
             $eqLogic->setConfiguration('deviceURL', $device['deviceURL']);
             $eqLogic->save();

             $eqLogic = self::byId($eqLogic->getId());

             /***********************************/
             //Actions
             
             if ($device['uiClass'] == "HitachiHeatingSystem") {
                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Automatic');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setAutoManu');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'auto');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Manuel');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setAutoManu');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'manu');
                 $tahomaLocalPiCmd->save();

             } else if ($device['uiClass'] == "HeatingSystem") {
                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('On');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setOnOff');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'on');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Off');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setHeatingLevel');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'off');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Auto');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setActiveMode');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'auto');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Eco');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setHeatingLevel');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'eco');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('Confort');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setHeatingLevel');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'comfort');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('other');
                 $tahomaLocalPiCmd->setName('HG');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setHeatingLevel');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', 'frostprotection');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('slider');
                 $tahomaLocalPiCmd->setName('Confort temperature');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setComfortTemperature');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', '#slider#');
                 $tahomaLocalPiCmd->setConfiguration('minValue', '15');
                 $tahomaLocalPiCmd->setConfiguration('maxValue', '30');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('slider');
                 $tahomaLocalPiCmd->setName('Eco temperature');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setEcoTemperature');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', '#slider#');
                 $tahomaLocalPiCmd->setConfiguration('minValue', '10');
                 $tahomaLocalPiCmd->setConfiguration('maxValue', '25');
                 $tahomaLocalPiCmd->save();

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();
                 $tahomaLocalPiCmd->setType('action');
                 $tahomaLocalPiCmd->setSubType('slider');
                 $tahomaLocalPiCmd->setName('HG temperature');
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                 $tahomaLocalPiCmd->setConfiguration('commandName', 'setSecuredPositionTemperature');
                 $tahomaLocalPiCmd->setConfiguration('nparams', 1);
                 $tahomaLocalPiCmd->setConfiguration('parameters', '#slider#');
                 $tahomaLocalPiCmd->setConfiguration('minValue', '5');
                 $tahomaLocalPiCmd->setConfiguration('maxValue', '10');
                 $tahomaLocalPiCmd->save();

             } else {
                 foreach ($device['definition']['commands'] as $command) {

                     $tahomaLocalPiCmd = new tahomalocalapiCmd();

                     if ($device['controllableName'] == "io:RollerShutterGenericIOComponent") {
                         // Store
                     }

                     if ($device['controllableName'] == "rts:OnOffRTSComponent") {
                         // Prise On-Off
                     }

                     if ($device['controllableName'] == "io:LightIOSystemSensor") {
                         // Module de luminosité
                     }

                     if ($device['controllableName'] == "rts:LightRTSComponent") {
                         // Lampe
                     }

                     if ($device['controllableName'] == "ovp:HLinkMainController") {
                         // Hitachi Link
                     }

                     $useCmd = true;

                     if ($command['commandName'] == "setClosure") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setIsVisible(0);
                         $tahomaLocalPiCmd->setSubType('slider');
                         $tahomaLocalPiCmd->setConfiguration('request', 'closure');
                         $tahomaLocalPiCmd->setConfiguration('parameters', '#slider#');
                         $tahomaLocalPiCmd->setConfiguration('minValue', '0');
                         $tahomaLocalPiCmd->setConfiguration('maxValue', '100');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'FLAP_SLIDER');
                     } else if ($command['commandName'] == "setOrientation") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setIsVisible(0);
                         $tahomaLocalPiCmd->setSubType('slider');
                         $tahomaLocalPiCmd->setConfiguration('request', 'orientation');
                         $tahomaLocalPiCmd->setConfiguration('parameters', '#slider#');
                         $tahomaLocalPiCmd->setConfiguration('minValue', '0');
                         $tahomaLocalPiCmd->setConfiguration('maxValue', '100');
                     } else if ($command['commandName'] == "open") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-up"></i>');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'FLAP_UP');
                     } else if ($command['commandName'] == "close") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-down"></i>');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'FLAP_DOWN');
                     } else if ($command['commandName'] == "lock") {
                         // serrure connectée : commande action ouvrir
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-lock"></i>');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'LOCK_CLOSE');
                     } else if ($command['commandName'] == "unlock") {
                         // serrure connectée : commande action fermer
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-unlock"></i>');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'LOCK_OPEN');
                     } else if ($command['commandName'] == "setLockedUnlocked") {
                         // serrure connectée : commande action ouvrir ou fermer
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('select');
                         $tahomaLocalPiCmd->setIsVisible(0);
                         $tahomaLocalPiCmd->setConfiguration('parameters', '#select#');
                         $tahomaLocalPiCmd->setConfiguration('listValue', 'unlocked|Ouvrir;locked|Fermer');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-unlock-alt"></i>');
                     } else if ($command['commandName'] == "my") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-star-o"></i>');
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'FLAP_STOP');
                     } else if ($command['commandName'] == "stop") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-stop"></i>');
                     } else if ($command['commandName'] == "on") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-toggle-on"></i>');
                     } else if ($command['commandName'] == "alarmPartial1") {
                         //zone alarme 1
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-toggle-on"></i>');
                     } else if ($command['commandName'] == "alarmPartial2") {
                         //zone alarme 2
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-toggle-on"></i>');
                     } else if ($command['commandName'] == "off") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-toggle-off"></i>');
                     } else if ($command['commandName'] == "down") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-down"></i>');
                         $tahomaLocalPiCmd->setIsVisible(0);
                     } else if ($command['commandName'] == "up") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-up"></i>');
                         $tahomaLocalPiCmd->setIsVisible(0);
                     } else if ($command['commandName'] == "rollOut") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-down"></i>');
                         $tahomaLocalPiCmd->setIsVisible(0);
                     } else if ($command['commandName'] == "rollUp") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-arrow-up"></i>');
                         $tahomaLocalPiCmd->setIsVisible(0);
                     } else if ($command['commandName'] == "test") {
                         $tahomaLocalPiCmd->setType('action');
                         $tahomaLocalPiCmd->setSubType('other');
                         $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-exchange"></i>');
                     } else {
                         $useCmd = false;
                     }

                     if ($useCmd) {
                         $tahomaLocalPiCmd->setName($command['commandName']);
                         //   $tahomaLocalPiCmd->setLogicalId('on');
                         $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                         $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
                         $tahomaLocalPiCmd->setConfiguration('commandName', $command['commandName']);
                         $tahomaLocalPiCmd->setConfiguration('nparams', $command['nparams']);

                         $tahomaLocalPiCmd->save();
                     }
                 }
             }
             // Cancel operation
             $tahomaLocalPiCmd = new tahomalocalapiCmd();
             $tahomaLocalPiCmd->setType('action');
             $tahomaLocalPiCmd->setSubType('other');
             $tahomaLocalPiCmd->setDisplay('icon', '<i class="fa fa-ban"></i>');
             $tahomaLocalPiCmd->setName("cancel");
             $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
             $tahomaLocalPiCmd->setConfiguration('deviceURL', $device['deviceURL']);
             $tahomaLocalPiCmd->setConfiguration('commandName', "cancelExecutions");
             $tahomaLocalPiCmd->setConfiguration('nparams', 1);
             $tahomaLocalPiCmd->save();			 
             /***********************************/
			 
			 
             //Infos
             //foreach ($device['definition']['states'] as $state) {
             foreach ($device['states'] as $state) {

                 $tahomaLocalPiCmd = new tahomalocalapiCmd();

                 $tahomaLocalPiCmd->setName($state['name']);
                 $tahomaLocalPiCmd->setEqLogic_id($eqLogic->getId());
                 $tahomaLocalPiCmd->setLogicalId($state['name']);
                 $tahomaLocalPiCmd->setConfiguration('type', $state['name']);
                 $tahomaLocalPiCmd->setType('info');
                 switch ($state->type) {
                     case 1:
                         $tahomaLocalPiCmd->setSubType('numeric');
                         break;
                     case 2:
                         $tahomaLocalPiCmd->setSubType('numeric');
                         break;
                     case 3:
                         $tahomaLocalPiCmd->setSubType('string');
                         break;
                     case 6:
                         $tahomaLocalPiCmd->setSubType('binary');
                         break;
                     default:
                         $tahomaLocalPiCmd->setSubType('string');
                 }
                 $tahomaLocalPiCmd->setIsVisible(0);

                 foreach ($device['attributes'] as $attribute) {
                     switch ($attribute['name']) {
                         case 'core:MeasuredValueType':
                             switch ($attribute['value']) {
                                 case 'core:TemperatureInCelcius':
                                     $tahomaLocalPiCmd->setUnite('°C');
                                     break;
                                 case 'core:VolumeInCubicMeter':
                                     $tahomaLocalPiCmd->setUnite('m3');
                                     break;
                                 case 'core:ElectricalEnergyInWh':
                                     $tahomaLocalPiCmd->setUnite('Wh');
                                     break;
                             }
                             break;
                         case 'core:MaxSensedValue':
                             $tahomaLocalPiCmd->setConfiguration('maxValue', $attribute['value']);
                             break;
                         case 'core:MinSensedValue':
                             $tahomaLocalPiCmd->setConfiguration('minValue', $attribute['value']);
                             break;

                     }
                 }
                 $tahomaLocalPiCmd->save();

                 $linkedCmdName = '';
                 switch ($state['name']) {
                     //if ($state['name'] == "core:ClosureState") {
                     case 'core:ClosureState':
                         $linkedCmdName = 'setClosure';
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'FLAP_STATE');
                         $tahomaLocalPiCmd->save();
                         break;
                     case 'core:SlateOrientationState':
                         $linkedCmdName = 'setOrientation';
                         break;
                     case 'core:ComfortRoomTemperatureState':
                         $linkedCmdName = 'setComfortTemperature';
                         break;
                     case 'core:EcoRoomTemperatureState':
                         $linkedCmdName = 'setEcoTemperature';
                         break;
                     case 'core:SecuredPositionTemperatureState':
                         $linkedCmdName = 'setSecuredPositionTemperature';
                         break;
                     case 'core:LockedUnlockedState':
                         // Serrure connectée état lié
                         $linkedCmdName = 'setLockedUnlocked';
                         $tahomaLocalPiCmd->setDisplay('generic_type', 'LOCK_STATE');
                         $tahomaLocalPiCmd->save();
                         break;
                 }
                 if ($linkedCmdName !== '') {
                     foreach ($eqLogic->getCmd() as $action) {
                         if ($action->getConfiguration('commandName') == $linkedCmdName) {
                             $action->setValue($tahomaLocalPiCmd->getId());
                             $action->save();
                         }
                     }
                 }
             }
         } else {
             $eqLogic = $eqLogic_found;

// Update !

         }

         foreach ($eqLogic->getCmd() as $command) {

             // Mise a jour des generic_type

             if ($command->getType() == 'action') {
                 if ($command->getName() == 'open') {
                     $command->setDisplay('generic_type', 'FLAP_UP');
                     $command->save();
                 }
                 if ($command->getName() == 'close') {
                     $command->setDisplay('generic_type', 'FLAP_DOWN');
                     $command->save();
                 }
                 if ($command->getName() == 'my') {
                     $command->setDisplay('generic_type', 'FLAP_STOP');
                     $command->save();
                 }
                 // Serrure connectée
                 if ($command->getName() == 'lock') {
                     $command->setDisplay('generic_type', 'LOCK_CLOSE');
                     $command->save();
                 }
                 if ($command->getName() == 'unlock') {
                     $command->setDisplay('generic_type', 'LOCK_OPEN');
                     $command->save();
                 }
             }

             //Recupération des valeur et mise a jour des commandes info par event

             if ($command->getType() == 'info') {
                 foreach ($device['states'] as $state) {
                     if ($state['name'] == $command->getConfiguration('type')) {
                         $command->setCollectDate('');

                         $value = $state['value'];
                         if ($state['name'] == "core:ClosureState") {
                             $value = 100 - $value;
                         }

                         $command->event($value);
                     }
                 }
             }
         }
     }

  }

  public static function updateItems($item){
    log::add(__CLASS__, 'debug', 'updateItems -> '. json_encode($item));

	$found = false;
    $eqLogic_found;
    $eqLogics=eqLogic::byType(__CLASS__);

    foreach ($eqLogics as $eqLogic) {
        if ($item['deviceURL'] == $eqLogic->getConfiguration('deviceURL')) {
            $eqLogic_found = $eqLogic;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        log::add(__CLASS__, 'error', ' - évènement sur équipement :' .$item['deviceURL'].' non géré par le plugin ... relancer le daemon pour forcer sa création');
    } else {
        foreach ($item['deviceStates'] as $state) {
            log::add(__CLASS__, 'debug','   - maj equipement ' . $item['deviceURL'] . ' | commande : ' . $state['name'] . '| valeur : '.$state['value']);
            $cmd=$eqLogic_found->getCmd('info',$state['name'],true, false);
          
            if (is_object($cmd)){            
                if ($state['name'] == $cmd->getConfiguration('type')) {
                    $cmd->setCollectDate('');

                    $value = $state['value'];
                    if ($state['name'] == "core:ClosureState") {
                        $value = 100 - $value;
                    }
                    log::add(__CLASS__, 'debug','       -> valeur MAJ : ' . $value);
                    $cmd->event($value);
                }
            }
        }    
    }
  }

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class tahomalocalapiCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
    //$deviceUrl=$this->getConfiguration('deviceURL');
    //$commandName=$this->getConfiguration('commandName');
    //$parameters=$this->getConfiguration('parameters');
    //log::add(__CLASS__, 'debug','   - Execution demandée ' . $deviceUrl . ' | commande : ' . $commandName . '| parametres : '.$parameters);
  }

  /*     * **********************Getteur Setteur*************************** */
}
