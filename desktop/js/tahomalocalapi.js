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

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmdi").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

$("#table_cmda").sortable({
	axis: "y",
	cursor: "move",
	items: ".cmd",
	placeholder: "ui-state-highlight",
	tolerance: "intersect",
	forcePlaceholderSize: true
  })

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  	//buildContactList();
  
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
   		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<legend><i class="fas fa-info"></i> Commandes Infos</legend>';
		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" ></span>';
		tr += '</td>';
		
		tr += '<td>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" >';
		tr += '</td>';
	   
		tr += '<td>';
		//tr += '<span class="cmdAttr" data-l1key="type"></span>';
		//tr += '   /   ';
		tr += '<span class="cmdAttr" data-l1key="subType"></span>';
		tr += '</td>';
  
  		tr += '<td>';
        if (typeof jeeFrontEnd !== 'undefined' && jeeFrontEnd.jeedomVersion !== 'undefined') {
            tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
            
    	}
		tr += '</td>';
	   
		tr += '<td>';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		if (init(_cmd.subType) == 'numeric' || init(_cmd.subType) == 'binary') {
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		}
	  
		tr += '</td>';
		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fas fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Evaluer}}</a>';
		}
    
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
  
  	if (init(_cmd.type) == 'info') {
    	$('#table_cmdi tbody').append(tr);
    	$('#table_cmdi tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
  	if (init(_cmd.type) == 'action') {
		if ((_cmd.name).includes("Rafraichir")) {
			$('#table_cmdr tbody').append(tr);
			$('#table_cmdr tbody tr:last').setValues(_cmd, '.cmdAttr');
		} else {
			$('#table_cmda tbody').append(tr);
			$('#table_cmda tbody tr:last').setValues(_cmd, '.cmdAttr');
		}

    }
	//$('#table_cmd tbody').append(tr);
    //$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
	//getImage(getUrlVars('id'));
	//getEqDetail(getUrlVars('id'));
	
}


$(".eqLogicDisplayCard").on('click', function(event) {
	var eqlogicId=$(this).attr('data-eqlogic_id');
	getImage(eqlogicId);
	getEqDetail(eqlogicId);	
});

$('.eqLogicAction[data-action=syncDevices]').on('click', function () {
	$('#div_alert').showAlert({message: '{{Synchronisation en cours}}', level: 'warning'});
  	syncSomfyDevices();
});

function getEqDetail(eqId) {
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // méthode de transmission des données au fichier php
		url: "plugins/tahomalocalapi/core/ajax/tahomalocalapi.ajax.php", // url du fichier php
		data: {
			action: "getEqlogicDetails",
			id : eqId
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) { // si l'appel a bien fonctionné
			getEquipmentDetails(data['result']);
	}
  });
  }

  function getEquipmentDetails(jsonDetail) {
	$('#div_equipment_details_info').empty();
	$('#div_equipment_details_action').empty();

	var detailInfo ="<span><u>Type information : </u></span>";
	(jsonDetail['cmdsInfo']).forEach((item,index) => {
		detailInfo += "</br>";
		detailInfo += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- "+item['name']+"</span>";
	});

	var detailAction ="<span><u>Type action : </u></span>";
	(jsonDetail['cmdsAction']).forEach((item,index) => {
		detailAction += "</br>";
		detailAction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- "+item['name']+"</span>";
	});

	$('#div_equipment_details_info').html('<div">'+detailInfo+'</div>');
	$('#div_equipment_details_action').html('<div">'+detailAction+'</div>');
}

function getImage(eqId) {
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // méthode de transmission des données au fichier php
		url: "plugins/tahomalocalapi/core/ajax/tahomalocalapi.ajax.php", // url du fichier php
		data: {
			action: "getEqlogicImage",
			id : eqId
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) { // si l'appel a bien fonctionné
		$('#img_eqTahomalocalapi').attr("src", data['result']);
	}
  });
  }
  


function syncSomfyDevices() {
  $.ajax({// fonction permettant de faire de l'ajax
      type: "POST", // méthode de transmission des données au fichier php
      url: "plugins/tahomalocalapi/core/ajax/tahomalocalapi.ajax.php", // url du fichier php
      data: {
          action: "syncSomfyDevices",
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
      if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
      }
      $('#div_alert').showAlert({message: '{{Récupération terminée avec succès}}', level: 'success'});
      window.location.reload();
  }
});
}

$('.eqLogicAction[data-action=infosCommunity]').off('click').on('click', function () {
	infosCommunity($('.txtInfoPlugin').html())  
});

async function infosCommunity(txtInfoPlugin) {

	var data = {
		action: 'getDevicesDetails'
	  }
	  var infoPlugin = await asyncAjaxGenericFunction(data);
	
	  getSimpleModal({
		title: "Forum",
		width: 0.5 * $(window).width(),
		fields: [{
		  type: "string",
		  value: txtInfoPlugin
		},
		{
		  type: "string",
		  id: "infoPluginModal",
		  value: infoPlugin.result
		}],
		buttons: {
		  "Fermer": function () {
			$('#simpleModalAlert').hide();
			$(this).dialog("close");
		  },
		  "Copier": function () {
			copyDivToClipboard('#infoPluginModal', true)
		  }
		}
	  }, function (result) { });

	
  }

  function copyDivToClipboard(myInput, addBacktick = false) {
	var initialText = $(myInput).html();
	if (addBacktick) {
	  $(myInput).html('```<br/>' + initialText.replaceAll('<b>', '').replaceAll('</b>', '') + '```');
	}
	var range = document.createRange();
	range.selectNode($(myInput).get(0));
	window.getSelection().removeAllRanges(); // clear current selection
	window.getSelection().addRange(range); // to select text
	document.execCommand("copy");
	window.getSelection().removeAllRanges();// to deselect
	$('#div_simpleModalAlert').showAlert({
	  message: 'Infos copiées',
	  level: 'success'
	});
	if (addBacktick) {
	  $(myInput).html(initialText);
	}
  }

  function getSimpleModal(_options, _callback) {
	if (!isset(_options)) {
		return;
	}
	$("#simpleModal").dialog('destroy').remove();
	if ($("#simpleModal").length == 0) {
		$('body').append('<div id="simpleModal"></div>');
		$("#simpleModal").dialog({
			title: _options.title,
			closeText: '',
			autoOpen: false,
			modal: true,
			width: 350
		});
		jQuery.ajaxSetup({
			async: false
		});
		$('#simpleModal').load('index.php?v=d&plugin=tahomalocalapi&modal=modal.tahomalocalapi');
		jQuery.ajaxSetup({
			async: true
		});
	}
	setSimpleModalData(_options.fields);
	$("#simpleModal").dialog({
		title: _options.title, buttons: {
			"Annuler": function () {
				$(this).dialog("close");
			}
		}
	});
	$('#simpleModal').dialog('open');
	$('#simpleModal').keydown(function (e) {
		if (e.which == 13) {
			$('#saveSimple').click();
			return false;
		}
	})
};

function setSimpleModalData(options) {
  items = [];
  options.forEach(option => {
    if (option.type == "string") {
      var id = (option.id !== undefined) ? `id="${option.id}"` : '';
      items.push(`<li ${id}>${option.value}</li>`);
    }
  });

  /*
  $("#modalOptions").append(items.join(""));
  //refreshSwipe("swipeUp");
  //refreshSwipe("swipeDown");
  //refreshSwipe("action");
  */
}

async function asyncAjaxGenericFunction(data) {
    $.fn.hideAlert();

    const result = await $.post({
        url: "plugins/tahomalocalapi/core/ajax/tahomalocalapi.ajax.php",
        data: data,
        cache: false,
        dataType: 'json',
        async: false,
    });

    if (result.state != 'ok') {
        $.fn.showAlert({
            message: result.result,
            level: 'danger'
        });
    }

	console.log("asyncAjaxGenericFunction result : " + result);
    return result;
}
