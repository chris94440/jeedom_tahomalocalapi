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
  	<legend><i class="fas fa-list-alt"></i> {{Configuration connexion compte Somfy}}</legend>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Token compte Somfy}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Si alimenté ne pas alimenter login/mdp }}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="tokenTahoma"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Nom utilisateur compte Somfy}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Nom utilisateur Somfy | A alimenter si token tahoma non alimenté}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="user"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mot de passe compte Somfy}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Mot de passe compte Somfy | A alimenter si token tahoma non alimenté }}"></i></sup>
      </label>
      <div class="col-md-4">
        <input type="password" class="configKey form-control" data-l1key="password"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Code pin box Somfy}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Code pin box Somfy accessible depuis le compte client }}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="pincode"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Adresse IP local de la box Somfy}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Adresse IP locale de la box Somfy}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="boxLocalIp"/>
      </div>
    </div> 
          
   	<legend><i class="fas fa-list-alt"></i> {{Configuration daemon}}</legend>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Port du daemon}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Port du daemon}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="socketport" placeholder="55009 par défaut"/>
      </div>
    </div>
      <!-- <div class="col-md-4">
        <select class="configKey form-control" data-l1key="daemonPort">
          <option value=""></option>
          <option value="value1">value1</option>
          <option value="value2">value2</option>
        </select>
      </div>
    </div> -->
    <legend><i class="fas fa-clock"></i> {{Auto refresh des équipements (cron)}}</legend>
    <div class="form-group">
        <label class="col-md-4 control-label">{{Délai cron}}
        	<sup><i class="fas fa-question-circle tooltips" title="{{Auto refresh des équipements}}"></i></sup>
      	</label>
    	<div class="col-md-4">
       		<select class="configKey form-control" data-l1key="autorefresh" >
              <option value="NoRefresh">{{Aucun refresh}}</option>
              <option value="* * * * *">{{Toutes les minutes}}</option>
              <option value="*/2 * * * *">{{Toutes les 2 minutes}}</option>
              <option value="*/3 * * * *">{{Toutes les 3 minutes}}</option>
              <option value="*/4 * * * *">{{Toutes les 4 minutes}}</option>
              <option value="*/5 * * * *">{{Toutes les 5 minutes}}</option>
              <option value="*/10 * * * *">{{Toutes les 10 minutes}}</option>
              <option value="*/15 * * * *">{{Toutes les 15 minutes}}</option>
              <option value="*/30 * * * *">{{Toutes les 30 minutes}}</option>
              <option value="*/45 * * * *">{{Toutes les 45 minutes}}</option>
              <option value="0 * * * *">{{Toutes les heures}}</option>
              <option value="0 */4 * * *">{{Toutes les 4 heures}}</option>
              <option value="0 0 * * *">{{Une fois par jour}}</option>
      		</select>
      	</div>
    </div>
  </fieldset>
</form>
