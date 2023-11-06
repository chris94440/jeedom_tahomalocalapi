# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import traceback
import re
import signal
from optparse import OptionParser
from os.path import join
import json
import argparse
import requests
from urllib.parse import quote

try:
	from jeedom.jeedom import *
except ImportError:
	print("Error: importing module jeedom.jeedom")
	sys.exit(1)

def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode('utf-8'))
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket: %s", message)
			return
		try:
			if message['action'] == 'execCmd':				
				logging.info('== action execute command ==')
				execCmd(message)
			elif message['action'] == 'synchronize':
				logging.info('== action synchronize ==')
				getDevicesList()
			elif message['action'] == 'cancelExecution':
				logging.info('== action cancelExecution ==')
				deleteExecution(message['execId'])
			elif message['action'] == 'eqlogicDetail':
				logging.info('== action eqlogicDetail ==')
				getDevice(message['eqId'])
			else:
				logging.info('== other action not manage yes : ' + message['action']  + ' ==')
		except Exception as e:
			logging.error('Send command to demon error: %s' ,e)

def listen():
	logging.debug('Listen socket jeedom')
	jeedom_socket.open()

	httpLog()

	global _tahomaTokenList
	tokenList = json.loads(_tahomaTokenList)	
	for item in tokenList:
		if not 'token' in item:
			item['token']=manageAuthentication(item)	

		getDevicesList(item)
		item['listenerId']=registerListener(item)
		logging.info('	- item detail : %s', item)
		#jeedom_com.send_change_immediate({'saveTahomaSession' : {'pinCode' : item['pinCode'], 'tokenValue' : token}})		

	try:
		while 1:
			time.sleep(0.5)
			read_socket()
			fetchListeners()

	except KeyboardInterrupt:
		shutdown()

def manageAuthentication(item):
	jsessionid = loginTahoma(item['user'],item['passdword'])

	if jsessionid:
		availableToken(jsessionid,item['pinCode'])
		tokenTahoma = tahoma_token(jsessionid)
		if tokenTahoma:
			validateToken(jsessionid,item['pinCode'],tokenTahoma)
			return tokenTahoma

def httpLog():
	logging.getLogger("requests").setLevel(logging.ERROR)
	logging.getLogger("urllib3").setLevel(logging.ERROR)
	requests.packages.urllib3.disable_warnings()
	

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting...", int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file %s", _pidfile)
	try:
		if _listenerId:
			unregisterListeners()
	except:
		pass
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	# try:
	# 	jeedom_serial.close()
	# except:
	# 	pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

def loginTahoma(user,pwd):
	logging.debug(' * logging tahoma')

	try:
		url = _overkizUrl +'/login'

		payload ={
			'userId' : user,
			'userPassword' : pwd
		}

		headers = {
			'Content-Type': 'application/x-www-form-urlencoded'
		}

		response = requests.request("POST", url, headers=headers, data=payload)

		if response.status_code and (response.status_code == 200):
			if response.cookies.get("JSESSIONID"):
				#global _jsessionid
				return response.cookies.get("JSESSIONID")			
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			shutdown()
			
	except requests.exceptions.HTTPError as err:
		logging.error("Error when logging to tahoma -> %s",err)

def tahoma_token(jsessionid,pincode):
	logging.debug(' * retrieve tahoma_token')
	try:

		url = _overkizUrl +'/config/' + pincode + '/local/tokens/generate'

		headers = {
			'Content-Type' : 'application/json',
			'Cookie' : 'JSESSIONID=' + jsessionid
		}

		response = requests.request("GET", url, headers=headers)

		if response.status_code and (response.status_code == 200):
			if response.json().get('token'):
				#global _tokenTahoma
				return response.json().get('token')
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			shutdown()
	except requests.exceptions.HTTPError as err:
		logging.error("Error when retrieving tahoma token -> %s",err)
		shutdown()

def getDevicesList(item):	
	logging.debug(' * Retrieve devices list')
	try:

		url = 'http://' + item['ip'] +'/enduser-mobile-web/1/enduserAPI/setup/devices'
		
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + item['token']
		}

		response = requests.request("GET", url, verify=False, headers=headers)

		if response.status_code and (response.status_code == 200):
			jeedom_com.send_change_immediate({'devicesList' : response.json()})
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)	
			shutdown()	

	except requests.exceptions.HTTPError as err:
		logging.error("rror when retrieving tahoma devices list -> %s",err)
		shutdown()

def getGateways(ipBox,tokenTahoma):	
	logging.debug(' * Retrieve gateways list')
	try:

		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/setup/gateways'
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}

		response = requests.request("GET", url, verify=False, headers=headers)

		if response.status_code and (response.status_code == 200):
			logging.debug("Gateways list : %s", response.json())
			#jeedom_com.send_change_immediate({'gatewaysList' : response.json()})
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)	
			shutdown()	

	except requests.exceptions.HTTPError as err:
		logging.error("rror when retrieving tahoma devices list -> %s",err)
		shutdown()

def validateToken(jsessionid,pincode,tokenTahoma):
	logging.debug(' * validate tahoma token')
	try:
	
		url = _overkizUrl + '/config/' + pincode + '/local/tokens'
		
		headers = {
			'Content-Type' : 'application/json',
			'Cookie' : 'JSESSIONID=' + jsessionid
		}

		payload=json.dumps({
				"label": "JeedomTahomaLocalApi_token",				
				"token": tokenTahoma ,
				"scope": "devmode"
		})		

		response = requests.request("POST", url, headers=headers, data=payload)

		if not response.status_code and not (response.status_code == 200):
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			shutdown()
		#else:
		#	jeedom_com.send_change_immediate({'saveTahomaSession' : {'pinCode' : _pincode, 'tokenValue' : _tokenTahoma}})
		

	except requests.exceptions.HTTPError as err:
		logging.error("Error when validate tahoma token -> %s",err)
		shutdown()

def availableToken(jsessionid,pincode):
	logging.debug(' * Get available tahoma token')
	try:
	
		url = _overkizUrl + '/config/' + pincode + '/local/tokens/devmode'
		
		headers = {
			'Content-Type' : 'application/json',
			'Cookie' : 'JSESSIONID=' + jsessionid
		}

		response = requests.request("GET", url, headers=headers)

		if response.status_code and (response.status_code == 200):
			logging.debug("Token list : %s", response.json())
			json_data = response.json()
			for item in json_data:
				logging.info(" token  : %s", item)
				if (item['label'] == 'JeedomTahomaLocalApi_token' and item['scope'] == 'devmode'):
					deleteToken(jsessionid,pincode,item['uuid'])
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			shutdown()	

	except requests.exceptions.HTTPError as err:
		logging.error("Error when retrieving available tahoma token -> %s",err)
		shutdown()

def deleteToken(jsessionid,pincode,uuid):
	logging.debug(' * Delete tahoma token : ' + uuid)
	try:
	
		url = _overkizUrl + '/config/' + pincode + '/local/tokens/' + uuid		
		
		headers = {
			'Content-Type' : 'application/json',
			'Cookie' : 'JSESSIONID=' + jsessionid
		}

		'''
		response = requests.request("DELETE", url, headers=headers)

		if response.status_code and (response.status_code == 200):
			logging.debug("Token deleted : %s", uuid)
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			#shutdown()	
		'''
	except requests.exceptions.HTTPError as err:
		logging.error("Error when deleting tahoma token -> %s",err)
		shutdown()

def downloadTahomaCertificate():
	logging.debug(' * Download Tahoma certificate')
	try:

		url = 'https://ca.overkiz.com/overkiz-root-ca-2048.crt'
		
		response = requests.request("GET", url)

		logging.debug("Http code : %s", response.status_code)

		open('/var/www/html/plugins/tahomalocalapi/resources/tahomalocalapid/overkiz-root-ca-2048.crt', "wb").write(response.content)

	except requests.exceptions.HTTPError as err:
		logging.error("Error when downloading tahoma certificate -> %s",err)

def registerListener(item):
	logging.debug(' * Register listener')
	try:

		url = 'http://' + item['ip'] +'/enduser-mobile-web/1/enduserAPI/events/register'
		
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + item['token']
		}

		
		response = requests.request("POST", url, verify=False, headers=headers)

		if response.status_code and (response.status_code == 200):
			if response.json().get('id'):
				global _listenerId
				return response.json().get('id')
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)	
			shutdown()	

	except requests.exceptions.HTTPError as err:
		logging.error("Error when register listener tahoma -> %s",err)
		shutdown()

def fetchListeners():
	tokenList = json.loads(_tahomaTokenList)	
	for item in tokenList:	
		if ('token' in item) and ('ip' in item):
			logging.info("fetch listener for box ip " + item['ip'] + ' and listener id ' + item['listenerId'])
			fetchListener(item['ip'],item['token'],item['listenerId'])


def fetchListener(ipBox,tokenTahoma,listenerId):
	try:

		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/events/' + listenerId + '/fetch'		
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}
		
		response = requests.request("POST", url, verify=False, headers=headers)		

		if response.status_code and (response.status_code == 200):
			if response.json():
				logging.debug("Response : %s", response.json())
				json_data = response.json()
				for item in json_data:
					#logging.debug(item['name'] + ' -> ' + item['deviceURL'])
					jeedom_com.send_change_immediate({'eventItem' : item})
					if 'actions' in item:						
						for action in item['actions']:
							if 'command' in action:
								if action['command'] != "advancedRefresh":
									if (action['deviceURL'] != ''):
										logging.debug("			-> execute execForceRefresh for device " + action['deviceURL'])
										execForceRefresh(action['deviceURL'])
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response header : %s", response.headers)		
			shutdown()

	except requests.exceptions.HTTPError as err:
		logging.error("Error when fetch listener tahoma -> %s",err)
		shutdown()

def getDeviceStates(ipBox,tokenTahoma,deviceUrl):
	
	logging.debug(' * getDeviceStates | '  + deviceUrl)
	try:

		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/setup/devices/'+ quote(deviceUrl) +'/states'
		logging.debug(' 	* url :  '  + url)
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}
		
		response = requests.request("POST", url, verify=False, headers=headers)

		logging.debug("Http code : %s", response.status_code)

		if response.status_code and (response.status_code == 200):
			if response.json():
				logging.debug("Response : %s", response.json())
				

		#logging.debug("Response header : %s", response.headers)		
		#return response.json().get('id')

	except requests.exceptions.HTTPError as err:
		logging.error("Error when retrieving tahoma device states -> %s",err)
		shutdown()

def unregisterListeners():
	tokenList = json.loads(_tahomaTokenList)	
	for item in tokenList:		
		if ('token' in item) and ('ip' in item):
			logging.info("unregister listener for box ip -> %s",item['ip'])
			unregisterListener(item['ip'],item['token'])

def unregisterListener(ipBox,tokenTahoma):
	#logging.debug(' * Tahoma fetchListener | '  + listenerId)
	try:

		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/events/' + _listenerId + '/unregister'	
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}
		
		response = requests.request("POST", url, verify=False, headers=headers)		
	except requests.exceptions.HTTPError as err:
		logging.error("Error when unregister listener to tahoma -> %s",err)

def execCmd(ipBox,tokenTahoma,params):	
	logging.debug(' * Execute command')
	try:

		if params['commandName'] == "stop":
			deleteExecutionForADevice(params['deviceUrl'])

		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/exec/apply'

		if params['parameters'] != "":
			payload=json.dumps({
					"label": params['commandName'],								
					"actions": [
					{
					"commands": [
						{
						"name": params['name'],
						"parameters": [
							params['parameters']
						]
						}
					],
					"deviceURL": params['deviceUrl']
					}
				]
			})
		else:
			payload=json.dumps({
					"label": params['commandName'],								
					"actions": [
					{
					"commands": [
						{
						"name": params['name']
						}
					],
					"deviceURL": params['deviceUrl']
					}
				]
			})
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}

		logging.debug("	- payload :  %s", payload)
		response = requests.request("POST", url, verify=False, headers=headers, data=payload)

		if response.status_code and (response.status_code == 200):
			logging.debug("ExecCmd http : %s", response.status_code)
			if response.json().get('execId'):
				logging.debug("Execution id : %s", response.json().get('execId'))
				response=json.dumps({"deviceId": params['deviceId'],	"execId": response.json().get('execId')})
				jeedom_com.send_change_immediate({'execIdEvent' : response})
				#execForceRefresh(params['deviceUrl'])
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
	except requests.exceptions.HTTPError as err:
		logging.error("Error when executing cmd to tahoma -> %s",err)
		shutdown()

def execForceRefresh(ipBox,deviceUrl,tokenTahoma):	
	logging.debug(' * Execute force refresh')
	try:
		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/exec/apply'

		payload=json.dumps({"label":"advancedRefresh","actions": [{"commands": [{"name": "advancedRefresh", "parameters": ["p1"]}],"deviceURL": deviceUrl}]})
		
		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}

		logging.debug("	- payload :  %s", payload)
		response = requests.request("POST", url, verify=False, headers=headers, data=payload)

		if response.status_code and (response.status_code == 200):
			logging.debug("ExecCmd http : %s", response.status_code)
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
	except requests.exceptions.HTTPError as err:
		logging.error("Error when executing cmd to tahoma -> %s",err)
		shutdown()

def deleteExecutionForADevice(ipBox,deviceUrl,tokenTahoma):
	logging.debug(' * Delete execution for a device: ' + deviceUrl)
	try:
		url = 'http://' + ipBox +'/enduser-mobile-web/1/enduserAPI/exec/current'

		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + tokenTahoma
		}

		response = requests.request("GET", url, verify=False, headers=headers)

		if response.status_code and (response.status_code == 200):
			json_data = response.json()
			for item in json_data:
				for act in item['actionGroup']['actions']:
					if (deviceUrl == act['deviceURL']):
						deleteExecution(item['id'])
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			#shutdown()
	except requests.exceptions.HTTPError as err:
		logging.error("Error when deleting execution for a deviceto tahoma -> %s",err)
		shutdown()

def deleteExecution(executionId):
	logging.debug(' * Delete execution : ' + executionId)
	try:
		url = 'http://' + _ipBox +'/enduser-mobile-web/1/enduserAPI/exec/current/setup/' + executionId

		headers = {
			'Content-Type' : 'application/json',
			'Authorization' : 'Bearer ' + _tokenTahoma
		}

		response = requests.request("DELETE", url, verify=False, headers=headers)

		if response.status_code and (response.status_code == 200):
			logging.debug('Delete execution ok')
		else:
			logging.error("Http code : %s", response.status_code)
			logging.error("Response : %s", response.json())
			logging.error("Response header : %s", response.headers)
			#shutdown()
	except requests.exceptions.HTTPError as err:
		logging.error("Error when deleting execution cmd to tahoma -> %s",err)
		shutdown()
# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55009
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/tahomalocalapid.pid'
_apikey = ''
_callback = ''
_cycle = 0.3
_user = ''
_pwd = ''
_jsessionid=''
_pincode=''
_tokenTahoma=''
_ipBox='https://192.168.1.28:8443'
_overkizUrl='https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI'
_listenerId=''
_tahomaTokenList=''

parser = argparse.ArgumentParser(
    description='Desmond Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Daemon port", type=str)
parser.add_argument("--user", help="User for local api Tahoma", type=str)
parser.add_argument("--pswd", help="Password for local api Tahoma", type=str)
parser.add_argument("--pincode", help="Tahoma pin code", type=str)
parser.add_argument("--boxLocalIp", help="Tahoma IP", type=str)
parser.add_argument("--tahomatokenapi", help="Tahoma token list", type=str)

args = parser.parse_args()

if args.device:
	_device = args.device
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.cycle:
    _cycle = float(args.cycle)
if args.socketport:
	_socket_port = args.socketport
if args.user:
	_user = args.user
if args.pswd:
	_pwd=args.pswd
if args.pincode:
	_pincode=args.pincode
if args.boxLocalIp:
	_ipBox='https://' + args.boxLocalIp + ':8443'
if args.tahomatokenapi:
	_tahomaTokenList=args.tahomatokenapi

_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('*-------------------------------------------------------------------------*')
logging.info('Start demond')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
#logging.info('Apikey: %s', _apikey)
logging.info('Device: %s', _device)
logging.info('User: %s', _user)
#logging.info('Pwd: %s', _pwd)
#logging.info('Pin ocde: %s', _pincode)
#logging.info('Box IP: %s', _ipBox)
#logging.info('Tahoma token list : %s', _tahomaTokenList)

tokenList = json.loads(_tahomaTokenList)
logging.info('Tahoma conf')
for item in tokenList:
	logging.info('	- box ip : %s', item['ip'])
	logging.info('	- box code pin : %s', item['pinCode'])
	logging.info('	- token tahoma : %s', item['token'])		


logging.info('*-------------------------------------------------------------------------*')

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_com = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle) # création de l'objet jeedom_com
	if not jeedom_com.test(): #premier test pour vérifier que l'url de callback est correcte
		logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
		shutdown()

	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception as e:
	logging.error('Fatal error: %s', e)
	logging.info(traceback.format_exc())
	shutdown()
