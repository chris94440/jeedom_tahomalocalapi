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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

    if (init('action') == 'syncSomfyDevices') {
      tahomalocalapi::synchronize();
      ajax::success();
    } else if (init('action') == 'getEqlogicImage') {
      $eqLogic = tahomalocalapi::byId(init('id'));
      ajax::success($eqLogic->getImage());
    } else if ( init('action') == 'getEqlogicDetails') {
      $eqLogic = tahomalocalapi::byId(init('id'));
      ajax::success($eqLogic->getEqlogicDetails());
    } else if (init('action') == 'getDevicesDetails') {
      ajax::success(tahomalocalapi::getDevicesDetails());
    } else if (init('action') == 'resetTokenTahoma'){
      ajax::success(tahomalocalapi::resetTokenTahoma());
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
