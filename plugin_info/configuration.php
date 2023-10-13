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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Nom utilisateur Tahoma}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Nom utilisateur Tahoma}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="user"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mot de passe}}
        <sup><i class="fas fa-question-circle tooltips" title="{{ }}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="password"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Port du daemon}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Port du daemon}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="daemonPort"/>
      </div>
      <!-- <div class="col-md-4">
        <select class="configKey form-control" data-l1key="daemonPort">
          <option value=""></option>
          <option value="value1">value1</option>
          <option value="value2">value2</option>
        </select>
      </div>
    </div> -->
  </fieldset>
</form>
