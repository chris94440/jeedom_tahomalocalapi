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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement avant la mise à jour du plugin
function tahomalocalapi_pre_update() {
    log::add('tahomalocalapi', 'debug','!!!! pre update !!!!');
    log::add('tahomalocalapi', 'debug','    - purge img folder');
    $dir = __DIR__.'/../data/img/';
    array_map('unlink', glob("{$dir}*.png"));

    //suppression commandes action inutile
    $eqLogics = eqLogic::byType('tahomalocalapi');
    foreach($eqLogics as $eq) {
        log::add('tahomalocalapi', 'debug','    - purge unused eqlogic ?  ' . $eq->getName());
        if (is_object($eq) && ($eq->getName() == 'setPosition' || $eq->getName() == 'setPositionAndLinearSpeed')) {
            log::add('tahomalocalapi', 'debug','        -> purged');
            $eq->remove();
            $eq->save();
        }
    }
}
