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

try:
	from jeedom.jeedom import *
except ImportError:
	print("Error: importing module jeedom.jeedom")
	sys.exit(1)

def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket: %s", message)
			return
		try:
			print ('read')
		except Exception as e:
			logging.error('Send command to demon error: %s' ,e)

def listen():
	jeedom_socket.open()
	loginTahoma()
	try:
		while 1:
			time.sleep(0.5)
			read_socket()
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting...", int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file %s", _pidfile)
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

def loginTahoma():
	logging.debug(" * logging tahoma")

	try:
		url = "https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/login"

		payload ={
			'userId' : _user,
			'userPassword' : _pwd
		}

		headers = {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Cookie': 'JSESSIONID=E3~1BD465E1E82DDBA6EBB62CD29B096EFD'
		}

		response = requests.request("POST", url, headers=headers, data=payload)

		logging.debug("Http code : %s", response.status_code)
		logging.debug("Response : %s", response.json())
		logging.debug("Response header : %s", response.headers)
		logging.debug("Response header[Set-Cookie] : %s", response.headers[Set-Cookie])
		

		# r = requests.post(url, data = {'key':'value'}, auth=('user', 'passwd'))
		# r.text      # response as a string
		# r.content   # response as a byte string
		# #     gzip and deflate transfer-encodings automatically decoded 
		# r.json()    # return python object from json! this is what you probably want!

	except requests.exceptions.HTTPError as err:
		logging.debug("Error when connection to tahoma -> %s",err)
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
	_socketport = args.socketport
if args.user:
	_user = args.user
if args.pswd:
	_pwd=args.pswd

_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond ChD')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
logging.info('Apikey: %s', _apikey)
logging.info('Device: %s', _device)
logging.info('User: %s', _user)
logging.info('Pwd: %s', _pwd)

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
